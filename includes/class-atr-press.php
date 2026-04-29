<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Press {

    public static function table(): string { return atr_table( 'press_pickups' ); }

    public static function add( int $report_id, array $row ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'report_id'    => $report_id,
            'outlet'       => substr( (string) ( $row['outlet'] ?? '' ), 0, 120 ),
            'author'       => isset( $row['author'] ) ? substr( (string) $row['author'], 0, 120 ) : null,
            'url'          => $row['url'] ?? '',
            'published_at' => $row['published_at'] ?? null,
            'notes_html'   => isset( $row['notes_html'] ) ? wp_kses_post( wp_unslash( (string) $row['notes_html'] ) ) : null,
            'position'     => (int) ( $row['position'] ?? 0 ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function for_report( int $report_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . " WHERE report_id = %d ORDER BY position ASC, published_at ASC",
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
