<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Comments {

    public static function table(): string { return atr_table( 'comments' ); }

    public static function add( int $report_id, array $row ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'report_id'   => $report_id,
            'source_kind' => substr( (string) ( $row['source_kind'] ?? 'other' ), 0, 40 ),
            'author'      => substr( (string) ( $row['author'] ?? '' ), 0, 120 ),
            'handle_html' => isset( $row['handle_html'] ) ? wp_kses_post( wp_unslash( (string) $row['handle_html'] ) ) : null,
            'role_label'  => isset( $row['role_label'] ) ? substr( (string) $row['role_label'], 0, 40 ) : null,
            'score'       => isset( $row['score'] ) ? (int) $row['score'] : null,
            'body_html'   => wp_kses_post( wp_unslash( (string) ( $row['body_html'] ?? '' ) ) ),
            'url'         => $row['url'] ?? null,
            'source_label'=> isset( $row['source_label'] ) ? substr( (string) $row['source_label'], 0, 190 ) : null,
            'posted_at'   => $row['posted_at'] ?? null,
            'featured'    => ! empty( $row['featured'] ) ? 1 : 0,
            'avatar'      => isset( $row['avatar'] ) ? substr( (string) $row['avatar'], 0, 8 ) : null,
            'avatar_style'=> isset( $row['avatar_style'] ) ? substr( (string) $row['avatar_style'], 0, 40 ) : null,
            'position'    => (int) ( $row['position'] ?? 0 ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function for_report( int $report_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() . " WHERE report_id = %d ORDER BY position ASC, id ASC",
                $report_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function clear( int $report_id ): int {
        global $wpdb;
        return (int) $wpdb->delete( self::table(), [ 'report_id' => $report_id ] );
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( self::table(), [ 'id' => $id ] );
    }
}
