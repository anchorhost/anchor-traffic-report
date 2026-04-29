<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Platforms {

    public static function table(): string { return atr_table( 'platforms' ); }

    public static function add( int $report_id, array $row ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'report_id'     => $report_id,
            'kind'          => substr( (string) ( $row['kind'] ?? 'other' ), 0, 40 ),
            'label'         => substr( (string) ( $row['label'] ?? '' ), 0, 190 ),
            'badge'         => isset( $row['badge'] ) ? substr( (string) $row['badge'], 0, 8 ) : null,
            'accent'        => isset( $row['accent'] ) ? substr( (string) $row['accent'], 0, 20 ) : null,
            'url'           => $row['url'] ?? null,
            'posted_at'     => $row['posted_at'] ?? null,
            'posted_label'  => isset( $row['posted_label'] ) ? substr( (string) $row['posted_label'], 0, 120 ) : null,
            'headline_html' => wp_kses_post( wp_unslash( (string) ( $row['headline_html'] ?? '' ) ) ),
            'stats'         => isset( $row['stats'] ) ? ( is_string( $row['stats'] ) ? $row['stats'] : wp_json_encode( $row['stats'] ) ) : null,
            'meta_html'     => wp_kses_post( wp_unslash( (string) ( $row['meta_html'] ?? '' ) ) ),
            'position'      => (int) ( $row['position'] ?? 0 ),
            'size'          => substr( (string) ( $row['size'] ?? 'standard' ), 0, 20 ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function update( int $id, array $row ): bool {
        global $wpdb;
        $update = [];
        $allowed = [ 'kind', 'label', 'badge', 'accent', 'url', 'posted_at', 'posted_label', 'headline_html', 'meta_html', 'position', 'size' ];
        foreach ( $allowed as $f ) {
            if ( array_key_exists( $f, $row ) ) {
                $update[ $f ] = in_array( $f, [ 'headline_html', 'meta_html' ], true )
                    ? wp_kses_post( wp_unslash( (string) $row[ $f ] ) )
                    : $row[ $f ];
            }
        }
        if ( array_key_exists( 'stats', $row ) ) {
            $update['stats'] = is_string( $row['stats'] ) ? $row['stats'] : wp_json_encode( $row['stats'] );
        }
        if ( ! $update ) return false;
        return false !== $wpdb->update( self::table(), $update, [ 'id' => $id ] );
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
