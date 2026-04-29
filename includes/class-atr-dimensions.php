<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Dimensions {

    public static function table(): string { return atr_table( 'dimensions' ); }

    /** Replace all rows of a given dim_kind for a report. Each row: {key, label?, note?, visits, pageviews, position?, meta?} */
    public static function replace( int $report_id, string $dim_kind, array $rows ): int {
        global $wpdb;
        $wpdb->delete( self::table(), [ 'report_id' => $report_id, 'dim_kind' => $dim_kind ] );
        $count = 0;
        $position = 0;
        foreach ( $rows as $r ) {
            if ( empty( $r['key'] ) ) continue;
            $position++;
            $wpdb->insert( self::table(), [
                'report_id' => $report_id,
                'dim_kind'  => $dim_kind,
                'd_key'     => substr( (string) $r['key'], 0, 190 ),
                'label'     => isset( $r['label'] ) ? substr( (string) $r['label'], 0, 190 ) : null,
                'note'      => isset( $r['note'] ) ? substr( (string) $r['note'], 0, 255 ) : null,
                'visits'    => (int) ( $r['visits'] ?? 0 ),
                'pageviews' => (int) ( $r['pageviews'] ?? 0 ),
                'position'  => (int) ( $r['position'] ?? $position ),
                'meta'      => isset( $r['meta'] ) ? wp_json_encode( $r['meta'] ) : null,
            ] );
            $count++;
        }
        return $count;
    }

    public static function for_report( int $report_id, string $dim_kind, int $limit = 0 ): array {
        global $wpdb;
        $sql = "SELECT * FROM " . self::table() . " WHERE report_id = %d AND dim_kind = %s ORDER BY position ASC, visits DESC";
        if ( $limit > 0 ) $sql .= $wpdb->prepare( " LIMIT %d", $limit );
        return $wpdb->get_results(
            $wpdb->prepare( $sql, $report_id, $dim_kind ),
            ARRAY_A
        ) ?: [];
    }

    public static function clear( int $report_id, ?string $dim_kind = null ): int {
        global $wpdb;
        $where = [ 'report_id' => $report_id ];
        if ( $dim_kind ) $where['dim_kind'] = $dim_kind;
        return (int) $wpdb->delete( self::table(), $where );
    }
}
