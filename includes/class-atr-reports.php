<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Reports {

    public static function table(): string { return atr_table( 'reports' ); }

    public static function get_by_slug( string $slug ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE slug = %s", $slug ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public static function get( int $id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM " . self::table() . " WHERE id = %d", $id ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public static function list(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, slug, title, status, refreshed_at, updated_at FROM " . self::table() . " ORDER BY updated_at DESC",
            ARRAY_A
        ) ?: [];
    }

    /** Insert a new report or return existing by slug. Returns the row. */
    public static function create( array $data ): array {
        global $wpdb;
        if ( empty( $data['slug'] ) || empty( $data['title'] ) ) {
            throw new InvalidArgumentException( 'slug and title are required' );
        }
        $existing = self::get_by_slug( $data['slug'] );
        if ( $existing ) return $existing;

        $row = self::sanitize( $data );
        $row['created_at'] = current_time( 'mysql', true );
        $row['updated_at'] = current_time( 'mysql', true );
        $wpdb->insert( self::table(), $row );
        return self::get_by_slug( $data['slug'] );
    }

    /** Partial update by slug. JSON fields are merged when value is array. */
    public static function update( string $slug, array $data ): ?array {
        global $wpdb;
        $existing = self::get_by_slug( $slug );
        if ( ! $existing ) return null;

        $row = self::sanitize( $data, $existing );
        if ( ! $row ) return $existing;
        $row['updated_at'] = current_time( 'mysql', true );

        $wpdb->update( self::table(), $row, [ 'id' => (int) $existing['id'] ] );
        return self::get_by_slug( $slug );
    }

    public static function set_refreshed( string $slug ): void {
        global $wpdb;
        $wpdb->update(
            self::table(),
            [ 'refreshed_at' => current_time( 'mysql', true ), 'updated_at' => current_time( 'mysql', true ) ],
            [ 'slug' => $slug ]
        );
    }

    public static function delete( string $slug ): bool {
        global $wpdb;
        $row = self::get_by_slug( $slug );
        if ( ! $row ) return false;
        $rid = (int) $row['id'];
        $wpdb->delete( self::table(), [ 'id' => $rid ] );
        foreach ( [ 'traffic_hourly', 'dimensions', 'platforms', 'comments', 'press_pickups', 'timeline' ] as $t ) {
            $wpdb->delete( atr_table( $t ), [ 'report_id' => $rid ] );
        }
        return true;
    }

    private static function sanitize( array $data, array $existing = [] ): array {
        $row = [];
        $string_fields = [ 'slug', 'title', 'post_url', 'status', 'kicker' ];
        foreach ( $string_fields as $f ) {
            if ( array_key_exists( $f, $data ) ) {
                $row[ $f ] = is_string( $data[ $f ] ) ? wp_unslash( $data[ $f ] ) : $data[ $f ];
            }
        }
        $html_fields = [ 'headline_html', 'hero_subtitle_html', 'context_callout_html' ];
        foreach ( $html_fields as $f ) {
            if ( array_key_exists( $f, $data ) ) {
                $row[ $f ] = wp_kses_post( wp_unslash( (string) $data[ $f ] ) );
            }
        }
        if ( array_key_exists( 'post_published_at', $data ) ) {
            $row['post_published_at'] = $data['post_published_at'] ?: null;
        }
        if ( array_key_exists( 'refreshed_at', $data ) ) {
            $row['refreshed_at'] = $data['refreshed_at'] ?: null;
        }
        $json_fields = [ 'hero_stats', 'totals', 'section_ledes', 'config' ];
        foreach ( $json_fields as $f ) {
            if ( array_key_exists( $f, $data ) ) {
                $row[ $f ] = is_string( $data[ $f ] ) ? $data[ $f ] : wp_json_encode( $data[ $f ] );
            }
        }
        return $row;
    }
}
