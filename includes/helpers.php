<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function atr_table( string $name ): string {
    global $wpdb;
    return $wpdb->prefix . 'atr_' . $name;
}

/** Decode JSON column safely; returns array. */
function atr_json_decode( $value ): array {
    if ( is_array( $value ) ) return $value;
    if ( ! $value ) return [];
    $d = json_decode( $value, true );
    return is_array( $d ) ? $d : [];
}

/** UTC datetime → EST (UTC-5) "h:i A" */
function atr_utc_to_est_time( string $utc_datetime ): string {
    $ts = strtotime( $utc_datetime . ' UTC' );
    if ( $ts === false ) return $utc_datetime;
    return date( 'g:i A', $ts - 5 * HOUR_IN_SECONDS );
}

/** UTC datetime → EST date "M j" */
function atr_utc_to_est_date( string $utc_datetime ): string {
    $ts = strtotime( $utc_datetime . ' UTC' );
    if ( $ts === false ) return $utc_datetime;
    return date( 'M j', $ts - 5 * HOUR_IN_SECONDS );
}

/** Convert "YYYY-MM-DD HH:MM:SS" UTC → EST. Returns ['date' => 'M j', 'time' => 'h:i A'] */
function atr_utc_to_est_parts( string $utc_datetime ): array {
    $ts = strtotime( $utc_datetime . ' UTC' );
    if ( $ts === false ) return [ 'date' => $utc_datetime, 'time' => '' ];
    $est = $ts - 5 * HOUR_IN_SECONDS;
    return [ 'date' => date( 'M j', $est ), 'time' => date( 'g:i A', $est ) ];
}

/** Format duration seconds → "M:SS" */
function atr_format_duration( $seconds ): string {
    $s = (int) round( (float) $seconds );
    $m = intdiv( $s, 60 );
    $r = $s % 60;
    return sprintf( '%d:%02d', $m, $r );
}

/** Compact integer → "12.3K" / "1.2M" / "456" */
function atr_format_compact( $n ): string {
    $n = (float) $n;
    if ( $n >= 1_000_000 ) return rtrim( rtrim( number_format( $n / 1_000_000, 1, '.', '' ), '0' ), '.' ) . 'M';
    if ( $n >= 10_000 )    return rtrim( rtrim( number_format( $n / 1_000, 1, '.', '' ), '0' ), '.' ) . 'K';
    if ( $n >= 1_000 )     return number_format( $n, 0 );
    return (string) (int) $n;
}

/** "9 AM" / "12 PM" / "12 AM" — short form for chart x-axis */
function atr_short_hour( int $h, int $m = 0, bool $compact = true ): string {
    $h = $h % 24;
    $period = $h < 12 ? 'a' : 'p';
    $disp = $h % 12;
    if ( $disp === 0 ) $disp = 12;
    if ( $compact ) {
        // Show "12a" or "12p" only on transitions; bare digit otherwise.
        if ( $disp === 12 ) return '12' . $period;
        return (string) $disp;
    }
    return $disp . ' ' . strtoupper( $period ) . 'M';
}

/** Slug a string. */
function atr_slug( string $s ): string {
    return sanitize_title( $s );
}

/** Comment quote metadata line: "<source label> · M j, Y · h:i A EST" */
function atr_format_quote_meta( array $c ): string {
    if ( ! empty( $c['source_label'] ) && ! empty( $c['posted_at'] ) ) {
        $est = strtotime( $c['posted_at'] . ' UTC' ) - 5 * HOUR_IN_SECONDS;
        return $c['source_label'] . ' · ' . date( 'M j, Y · g:i A', $est ) . ' EST';
    }
    return $c['source_label'] ?? '';
}
