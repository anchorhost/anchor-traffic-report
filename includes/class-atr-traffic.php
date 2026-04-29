<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Traffic {

    public static function table(): string { return atr_table( 'traffic_hourly' ); }

    /** Replace all hourly rows for a report with the supplied list. */
    public static function replace( int $report_id, array $rows ): int {
        global $wpdb;
        $wpdb->delete( self::table(), [ 'report_id' => $report_id ] );
        $count = 0;
        foreach ( $rows as $r ) {
            $hour = $r['hour_utc'] ?? $r['hour'] ?? null;
            if ( ! $hour ) continue;
            $wpdb->insert( self::table(), [
                'report_id' => $report_id,
                'hour_utc'  => gmdate( 'Y-m-d H:00:00', strtotime( $hour . ( strpos( $hour, 'UTC' ) === false ? ' UTC' : '' ) ) ),
                'visits'    => (int) ( $r['visits'] ?? 0 ),
                'pageviews' => (int) ( $r['pageviews'] ?? 0 ),
                'partial'   => ! empty( $r['partial'] ) ? 1 : 0,
            ] );
            $count++;
        }
        return $count;
    }

    public static function for_report( int $report_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT hour_utc, visits, pageviews, partial FROM " . self::table()
                . " WHERE report_id = %d ORDER BY hour_utc ASC",
                $report_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function clear( int $report_id ): int {
        global $wpdb;
        return (int) $wpdb->delete( self::table(), [ 'report_id' => $report_id ] );
    }
}
