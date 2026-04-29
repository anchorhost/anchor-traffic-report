<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Renderer {

    /** Render a report by slug to an HTML string. */
    public static function render( string $slug ): string {
        $report = ATR_Reports::get_by_slug( $slug );
        if ( ! $report ) throw new RuntimeException( "Report not found: {$slug}" );

        $rid = (int) $report['id'];

        // Load snapshots (oldest → newest).
        $snap_meta = ATR_Snapshots::for_report( $rid );
        $frames = [];
        foreach ( $snap_meta as $meta ) {
            $full = ATR_Snapshots::get( (int) $meta['id'] );
            if ( ! $full ) continue;
            $payload = ATR_Snapshots::payload( $full );
            $frames[] = [
                'id'          => 'snap-' . $meta['id'],
                'label'       => $meta['label'] ?: ( 'Snapshot ' . $meta['id'] ),
                'captured_at' => $meta['captured_at'],
                'ctx'         => self::build_context_from_payload( $payload, $report ),
                'is_live'     => false,
            ];
        }
        // No snapshots? Render the live state once (no scrubber).
        if ( ! $frames ) {
            $frames[] = [
                'id'          => 'live',
                'label'       => 'Live',
                'captured_at' => $report['refreshed_at'] ?: current_time( 'mysql', true ),
                'ctx'         => self::build_context_from_live( $rid, $report ),
                'is_live'     => true,
            ];
        }

        // Compute scrubber positions (left percent), with a minimum visual gap so dots never stack.
        $n = count( $frames );
        if ( $n > 1 ) {
            $first = strtotime( $frames[0]['captured_at'] . ' UTC' );
            $last  = strtotime( $frames[ $n - 1 ]['captured_at'] . ' UTC' );
            $span  = max( 1, $last - $first );
            foreach ( $frames as $i => $f ) {
                $ts = strtotime( $f['captured_at'] . ' UTC' );
                $frames[ $i ]['pct'] = ( $ts - $first ) / $span * 100.0;
            }
            $min_gap = 100.0 / max( $n + 1, 6 ); // ensure dots never overlap
            for ( $i = 1; $i < $n; $i++ ) {
                if ( $frames[ $i ]['pct'] - $frames[ $i - 1 ]['pct'] < $min_gap ) {
                    $frames[ $i ]['pct'] = min( 100.0, $frames[ $i - 1 ]['pct'] + $min_gap );
                }
            }
        } else {
            $frames[0]['pct'] = 100.0;
        }

        ob_start();
        $atr_report = $report;
        $atr_frames = $frames;
        include ATR_PATH . 'templates/report.php';
        return (string) ob_get_clean();
    }

    /** Build a context dict from current live DB state. */
    public static function build_context_from_live( int $rid, array $report ): array {
        $platforms = ATR_Platforms::for_report( $rid );
        foreach ( $platforms as &$p ) $p['_stats'] = atr_json_decode( $p['stats'] );
        unset( $p );

        $ctx = [
            'report'     => $report,
            'totals'     => atr_json_decode( $report['totals'] ),
            'hero_stats' => atr_json_decode( $report['hero_stats'] ),
            'config'     => atr_json_decode( $report['config'] ),
            'ledes'      => atr_json_decode( $report['section_ledes'] ),
            'timeline'   => ATR_Timeline::for_report( $rid ),
            'platforms'  => $platforms,
            'comments'   => ATR_Comments::for_report( $rid ),
            'press'      => ATR_Press::for_report( $rid ),
            'traffic'    => ATR_Traffic::for_report( $rid ),
            'referrers'  => ATR_Dimensions::for_report( $rid, 'referrer' ),
            'countries'  => ATR_Dimensions::for_report( $rid, 'country' ),
            'browsers'   => ATR_Dimensions::for_report( $rid, 'browser' ),
            'devices'    => ATR_Dimensions::for_report( $rid, 'device' ),
        ];
        $ctx['chart'] = self::build_chart( $ctx['traffic'], $ctx['timeline'] );
        return $ctx;
    }

    /** Build a context dict from a snapshot's frozen payload. $base_report supplies stable fields. */
    public static function build_context_from_payload( array $payload, array $base_report ): array {
        $r = $payload['report'] ?? [];
        $report = $base_report;
        // Override snapshotted fields if present.
        foreach ( [ 'hero_subtitle_html', 'context_callout_html', 'kicker', 'headline_html', 'refreshed_at', 'totals', 'hero_stats', 'section_ledes', 'config' ] as $f ) {
            if ( array_key_exists( $f, $r ) ) {
                $val = $r[ $f ];
                $report[ $f ] = is_array( $val ) ? wp_json_encode( $val ) : $val;
            }
        }

        $platforms = $payload['platforms'] ?? [];
        foreach ( $platforms as &$p ) $p['_stats'] = atr_json_decode( $p['stats'] ?? '' );
        unset( $p );

        $ctx = [
            'report'     => $report,
            'totals'     => $r['totals']        ?? [],
            'hero_stats' => $r['hero_stats']    ?? [],
            'config'     => $r['config']        ?? [],
            'ledes'      => $r['section_ledes'] ?? [],
            'timeline'   => $payload['timeline']  ?? [],
            'platforms'  => $platforms,
            'comments'   => $payload['comments']  ?? [],
            'press'      => $payload['press']     ?? [],
            'traffic'    => $payload['traffic']   ?? [],
            'referrers'  => $payload['referrers'] ?? [],
            'countries'  => $payload['countries'] ?? [],
            'browsers'   => $payload['browsers']  ?? [],
            'devices'    => $payload['devices']   ?? [],
        ];
        $ctx['chart'] = self::build_chart( $ctx['traffic'], $ctx['timeline'] );
        return $ctx;
    }

    /** Compute SVG bar coords + axis labels. Detects hourly vs daily granularity from row deltas. */
    public static function build_chart( array $traffic, array $timeline ): array {
        if ( ! $traffic ) {
            return [ 'bars' => [], 'labels' => [], 'days' => [], 'markers' => [], 'peak' => 0, 'floor' => 0, 'count' => 0, 'range_label' => '', 'granularity' => 'hour' ];
        }

        $peak = 0;
        $floor = PHP_INT_MAX;
        foreach ( $traffic as $r ) {
            $peak = max( $peak, (int) $r['visits'] );
            $floor = min( $floor, (int) $r['visits'] );
        }
        if ( $peak <= 0 ) $peak = 1;
        if ( $floor === PHP_INT_MAX ) $floor = 0;

        $count = count( $traffic );

        // Detect granularity from delta between first two rows.
        $granularity = 'hour';
        if ( $count >= 2 ) {
            $delta = strtotime( $traffic[1]['hour_utc'] . ' UTC' ) - strtotime( $traffic[0]['hour_utc'] . ' UTC' );
            if ( $delta >= 12 * HOUR_IN_SECONDS ) $granularity = 'day';
        }
        $is_daily = $granularity === 'day';
        $unit_secs = $is_daily ? DAY_IN_SECONDS : HOUR_IN_SECONDS;

        $vb_w        = 1040;
        $chart_top   = 20;
        $chart_bot   = 180;
        $left_pad    = 6;
        $right_buf   = 14;
        $usable_w    = $vb_w - $left_pad - $right_buf;
        $slot        = $count > 0 ? $usable_w / $count : 0;
        $bar_w       = max( 4, min( 48, (int) floor( $slot * 0.78 ) ) );
        $scale       = ( $chart_bot - $chart_top ) / $peak;

        $peak_idx = 0;
        foreach ( $traffic as $i => $r ) if ( (int) $r['visits'] === $peak ) { $peak_idx = $i; break; }
        if ( $is_daily ) {
            $orange_from = max( 0, $peak_idx - 1 );
            $orange_to   = min( $count - 1, $peak_idx + 1 );
        } else {
            $orange_from = max( 0, $peak_idx - 1 );
            $orange_to   = min( $count - 1, $peak_idx + 4 );
        }

        $bars   = [];
        $labels = [];
        $days   = []; // sub-row labels (used in hourly mode for date boundaries)
        $current_day = null;
        $day_start = 0;
        $days_last_label = '';
        $last_month_seen = null;

        foreach ( $traffic as $i => $r ) {
            $hour_utc = $r['hour_utc'];
            $est_ts   = strtotime( $hour_utc . ' UTC' ) - 5 * HOUR_IN_SECONDS;

            if ( $is_daily ) {
                $month = date( 'Y-m', $est_ts );
                $day_num = date( 'j', $est_ts );
                $h_disp = $i === 0 || $month !== $last_month_seen
                    ? date( 'M j', $est_ts )
                    : (string) $day_num;
                $last_month_seen = $month;
            } else {
                $h = (int) date( 'G', $est_ts );
                $h_disp = atr_short_hour( $h );
                if ( $i === 0 ) $h_disp = $h_disp . ( $h < 12 ? 'a' : 'p' );
            }

            $height = (int) round( (int) $r['visits'] * $scale );
            if ( $height < 1 ) $height = 1;
            $y = $chart_bot - $height;
            $x = $left_pad + (int) round( $i * $slot );

            $color = ( $i >= $orange_from && $i <= $orange_to ) ? 'bg1' : 'bg2';
            $opacity = ! empty( $r['partial'] ) ? '0.55' : '1';

            $bars[] = [ 'x' => $x, 'y' => $y, 'w' => $bar_w, 'h' => $height, 'color' => $color, 'opacity' => $opacity, 'visits' => (int) $r['visits'], 'hour_utc' => $hour_utc ];
            $labels[] = $h_disp;

            if ( ! $is_daily ) {
                $est_date = date( 'Y-m-d', $est_ts );
                $est_day_label = date( 'M j', $est_ts );
                if ( $current_day !== $est_date ) {
                    if ( $current_day !== null ) {
                        $days[] = [ 'label' => $days_last_label, 'span' => $i - $day_start ];
                    }
                    $current_day = $est_date;
                    $days_last_label = $est_day_label;
                    $day_start = $i;
                }
            }
        }
        if ( ! $is_daily ) {
            $days[] = [ 'label' => $days_last_label, 'span' => $count - $day_start ];
        } else {
            // Single month label spanning the chart.
            $first_ts_lbl = strtotime( $traffic[0]['hour_utc'] . ' UTC' ) - 5 * HOUR_IN_SECONDS;
            $last_ts_lbl  = strtotime( $traffic[ $count - 1 ]['hour_utc'] . ' UTC' ) - 5 * HOUR_IN_SECONDS;
            if ( date( 'Y-m', $first_ts_lbl ) === date( 'Y-m', $last_ts_lbl ) ) {
                $days[] = [ 'label' => date( 'F Y', $first_ts_lbl ), 'span' => $count ];
            } else {
                $days[] = [ 'label' => date( 'M Y', $first_ts_lbl ) . ' → ' . date( 'M Y', $last_ts_lbl ), 'span' => $count ];
            }
        }

        $markers = [];
        foreach ( $timeline as $tl ) {
            if ( empty( $tl['event_at'] ) || empty( $tl['chart_marker'] ) ) continue;
            $tl_ts = strtotime( $tl['event_at'] . ' UTC' );
            foreach ( $traffic as $i => $r ) {
                $bar_start_ts = strtotime( $r['hour_utc'] . ' UTC' );
                $bar_end_ts   = $bar_start_ts + $unit_secs;
                if ( $tl_ts >= $bar_start_ts && $tl_ts < $bar_end_ts ) {
                    $offset_pct = ( $tl_ts - $bar_start_ts ) / $unit_secs;
                    $x = $left_pad + (int) round( ( $i + $offset_pct ) * $slot );
                    $est_ts = $tl_ts - 5 * HOUR_IN_SECONDS;
                    $time_str = $is_daily ? date( 'M j', $est_ts ) : date( 'g:i A', $est_ts );
                    $label = $tl['chart_label'] ?: ( $tl['label'] . ' · ' . $time_str );
                    $markers[] = [
                        'x'     => $x,
                        'label' => $label,
                        'side'  => $i < $count / 2 ? 'left' : 'right',
                    ];
                    break;
                }
            }
        }

        $first_ts = strtotime( $traffic[0]['hour_utc'] . ' UTC' ) - 5 * HOUR_IN_SECONDS;
        $last_ts  = strtotime( $traffic[ $count - 1 ]['hour_utc'] . ' UTC' ) - 5 * HOUR_IN_SECONDS;
        $range_label = $is_daily
            ? date( 'F j', $first_ts ) . ' → ' . date( 'F j', $last_ts ) . ' EST'
            : date( 'F j, g:i A', $first_ts ) . ' → ' . date( 'F j, g:i A', $last_ts ) . ' EST';

        return [
            'bars'        => $bars,
            'labels'      => $labels,
            'days'        => $days,
            'markers'     => $markers,
            'peak'        => $peak,
            'floor'       => $floor,
            'count'       => $count,
            'range_label' => $range_label,
            'granularity' => $granularity,
        ];
    }
}
