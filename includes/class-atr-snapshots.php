<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Snapshots {

    public static function table(): string { return atr_table( 'snapshots' ); }

    /**
     * Capture the current live state of a report into a snapshot row.
     *
     * @param int    $report_id
     * @param string $captured_at  Datetime in UTC (YYYY-MM-DD HH:MM:SS)
     * @param string $label        Human-readable label (e.g. "Day 1", "After HN front page")
     * @param string $note         Optional longer note
     * @return int Snapshot ID
     */
    public static function save( int $report_id, string $captured_at, string $label = '', string $note = '' ): int {
        global $wpdb;
        $report = ATR_Reports::get( $report_id );
        if ( ! $report ) throw new RuntimeException( "Report {$report_id} not found" );

        $payload = self::build_payload( $report_id, $report );

        $wpdb->insert( self::table(), [
            'report_id'   => $report_id,
            'captured_at' => $captured_at,
            'label'       => substr( $label, 0, 190 ),
            'note'        => substr( $note, 0, 255 ),
            'payload'     => wp_json_encode( $payload ),
        ] );
        return (int) $wpdb->insert_id;
    }

    /** Sorted oldest → newest. */
    public static function for_report( int $report_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, report_id, captured_at, label, note, position, created_at
                   FROM " . self::table() . "
                  WHERE report_id = %d
                  ORDER BY captured_at ASC",
                $report_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function get( int $snapshot_id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", $snapshot_id ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public static function delete( int $snapshot_id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( self::table(), [ 'id' => $snapshot_id ] );
    }

    public static function clear( int $report_id ): int {
        global $wpdb;
        return (int) $wpdb->delete( self::table(), [ 'report_id' => $report_id ] );
    }

    /** Decode a snapshot's payload to an array. */
    public static function payload( array $snapshot ): array {
        return atr_json_decode( $snapshot['payload'] ?? '' );
    }

    /** Snapshot a report's current state into a portable array. */
    public static function build_payload( int $report_id, array $report ): array {
        return [
            'report'    => [
                'totals'                => atr_json_decode( $report['totals'] ),
                'hero_stats'            => atr_json_decode( $report['hero_stats'] ),
                'hero_subtitle_html'    => $report['hero_subtitle_html'],
                'context_callout_html'  => $report['context_callout_html'],
                'section_ledes'         => atr_json_decode( $report['section_ledes'] ),
                'config'                => atr_json_decode( $report['config'] ),
                'kicker'                => $report['kicker'],
                'headline_html'         => $report['headline_html'],
                'refreshed_at'          => $report['refreshed_at'],
            ],
            'traffic'   => ATR_Traffic::for_report( $report_id ),
            'platforms' => ATR_Platforms::for_report( $report_id ),
            'comments'  => ATR_Comments::for_report( $report_id ),
            'press'     => ATR_Press::for_report( $report_id ),
            'timeline'  => ATR_Timeline::for_report( $report_id ),
            'referrers' => ATR_Dimensions::for_report( $report_id, 'referrer' ),
            'countries' => ATR_Dimensions::for_report( $report_id, 'country' ),
            'browsers'  => ATR_Dimensions::for_report( $report_id, 'browser' ),
            'devices'   => ATR_Dimensions::for_report( $report_id, 'device' ),
        ];
    }
}
