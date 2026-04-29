<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Timeline {

    public static function table(): string { return atr_table( 'timeline' ); }

    public static function add( int $report_id, array $row ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'report_id'        => $report_id,
            'event_at'         => $row['event_at'] ?? current_time( 'mysql', true ),
            'label'            => substr( (string) ( $row['label'] ?? '' ), 0, 190 ),
            'description_html' => isset( $row['description_html'] ) ? wp_kses_post( wp_unslash( (string) $row['description_html'] ) ) : null,
            'marker'           => substr( (string) ( $row['marker'] ?? 'info' ), 0, 20 ),
            'chart_marker'     => ! empty( $row['chart_marker'] ) ? 1 : 0,
            'chart_label'      => isset( $row['chart_label'] ) ? substr( (string) $row['chart_label'], 0, 190 ) : null,
            'position'         => (int) ( $row['position'] ?? 0 ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function for_report( int $report_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . " WHERE report_id = %d ORDER BY position ASC, event_at ASC",
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
