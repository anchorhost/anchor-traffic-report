<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Anchor Traffic Report CLI.
 *
 * All subcommands accept JSON via --input=<path|->. Use - to read from STDIN.
 *
 * Examples:
 *   wp atr report create --slug=godaddy-viral --title="GoDaddy Viral Recap" --post-url=https://...
 *   wp atr report update godaddy-viral --input=- < report.json
 *   wp atr traffic replace godaddy-viral --input=traffic.json
 *   wp atr dim replace godaddy-viral --kind=referrer --input=referrers.json
 *   wp atr platform add godaddy-viral --input=hn.json
 *   wp atr comment add godaddy-viral --input=comment.json
 *   wp atr render godaddy-viral --out=/path/to/output.html
 */
class ATR_CLI {

    /**
     * Report CRUD: create | update | show | list | delete | refresh.
     *
     * Pass JSON via --input=<file|->; flags also flow into the row.
     *
     * @when after_wp_load
     */
    public function report( $args, $assoc ) {
        $sub = $args[0] ?? null;
        $slug = $args[1] ?? ( $assoc['slug'] ?? null );

        switch ( $sub ) {
            case 'create':
                $data = self::merge_with_json( $assoc );
                if ( isset( $data['post-published'] ) ) { $data['post_published_at'] = $data['post-published']; unset( $data['post-published'] ); }
                if ( isset( $data['post-url'] ) )       { $data['post_url'] = $data['post-url']; unset( $data['post-url'] ); }
                $row = ATR_Reports::create( $data );
                WP_CLI::success( "Created report id={$row['id']} slug={$row['slug']}" );
                break;

            case 'update':
                if ( ! $slug ) WP_CLI::error( 'Slug required' );
                $data = self::merge_with_json( $assoc );
                if ( isset( $data['post-published'] ) ) { $data['post_published_at'] = $data['post-published']; unset( $data['post-published'] ); }
                if ( isset( $data['post-url'] ) )       { $data['post_url'] = $data['post-url']; unset( $data['post-url'] ); }
                $row = ATR_Reports::update( $slug, $data );
                if ( ! $row ) WP_CLI::error( "Report not found: {$slug}" );
                WP_CLI::success( "Updated report {$slug}" );
                break;

            case 'show':
                if ( ! $slug ) WP_CLI::error( 'Slug required' );
                $row = ATR_Reports::get_by_slug( $slug );
                if ( ! $row ) WP_CLI::error( "Report not found: {$slug}" );
                WP_CLI::log( wp_json_encode( $row, JSON_PRETTY_PRINT ) );
                break;

            case 'list':
                $rows = ATR_Reports::list();
                WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'slug', 'title', 'status', 'refreshed_at', 'updated_at' ] );
                break;

            case 'delete':
                if ( ! $slug ) WP_CLI::error( 'Slug required' );
                ATR_Reports::delete( $slug );
                WP_CLI::success( "Deleted {$slug} (and all related rows)" );
                break;

            case 'refresh':
                if ( ! $slug ) WP_CLI::error( 'Slug required' );
                ATR_Reports::set_refreshed( $slug );
                WP_CLI::success( "Marked refreshed: {$slug}" );
                break;

            default:
                WP_CLI::error( "Unknown subcommand: {$sub}. Use create|update|show|list|delete|refresh" );
        }
    }

    /**
     * Replace hourly traffic data for a report.
     *
     * Each JSON item: {hour_utc, visits, pageviews, partial?}
     *
     * @when after_wp_load
     */
    public function traffic( $args, $assoc ) {
        $slug = $args[0] ?? null;
        if ( ! $slug ) WP_CLI::error( 'Slug required' );
        $report = ATR_Reports::get_by_slug( $slug );
        if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );

        if ( ! empty( $assoc['clear'] ) ) {
            $n = ATR_Traffic::clear( (int) $report['id'] );
            WP_CLI::success( "Cleared {$n} traffic rows" );
            return;
        }

        $rows = self::read_json( $assoc['input'] ?? null );
        if ( ! is_array( $rows ) ) WP_CLI::error( 'JSON must be an array' );
        $n = ATR_Traffic::replace( (int) $report['id'], $rows );
        WP_CLI::success( "Replaced traffic with {$n} rows" );
    }

    /**
     * Replace dimension rows for a report. --kind=referrer|country|browser|device.
     *
     * @when after_wp_load
     */
    public function dim( $args, $assoc ) {
        $slug = $args[0] ?? null;
        $kind = $assoc['kind'] ?? null;
        if ( ! $slug || ! $kind ) WP_CLI::error( 'slug and --kind required' );
        $report = ATR_Reports::get_by_slug( $slug );
        if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );

        if ( ! empty( $assoc['clear'] ) ) {
            $n = ATR_Dimensions::clear( (int) $report['id'], $kind );
            WP_CLI::success( "Cleared {$n} {$kind} rows" );
            return;
        }
        $rows = self::read_json( $assoc['input'] ?? null );
        if ( ! is_array( $rows ) ) WP_CLI::error( 'JSON must be an array' );
        $n = ATR_Dimensions::replace( (int) $report['id'], $kind, $rows );
        WP_CLI::success( "Replaced {$kind} with {$n} rows" );
    }

    /**
     * Manage platform cards: add | clear | list.
     *
     * @when after_wp_load
     */
    public function platform( $args, $assoc ) {
        $sub = $args[0] ?? null;
        $slug = $args[1] ?? null;
        if ( ! $slug ) WP_CLI::error( 'Slug required' );
        $report = ATR_Reports::get_by_slug( $slug );
        if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );

        switch ( $sub ) {
            case 'add':
                $payload = self::read_json( $assoc['input'] ?? null );
                if ( ! $payload ) WP_CLI::error( 'JSON payload required' );
                $items = isset( $payload[0] ) ? $payload : [ $payload ];
                $count = 0;
                foreach ( $items as $row ) { ATR_Platforms::add( (int) $report['id'], $row ); $count++; }
                WP_CLI::success( "Added {$count} platform card(s)" );
                break;
            case 'clear':
                $n = ATR_Platforms::clear( (int) $report['id'] );
                WP_CLI::success( "Cleared {$n} platforms" );
                break;
            case 'list':
                WP_CLI\Utils\format_items( 'table', ATR_Platforms::for_report( (int) $report['id'] ), [ 'id', 'kind', 'label', 'position', 'url' ] );
                break;
            default:
                WP_CLI::error( "Unknown subcommand: {$sub}. Use add|clear|list" );
        }
    }

    /**
     * Manage comments: add | clear | list.
     *
     * @when after_wp_load
     */
    public function comment( $args, $assoc ) {
        $sub = $args[0] ?? null;
        $slug = $args[1] ?? null;
        if ( ! $slug ) WP_CLI::error( 'Slug required' );
        $report = ATR_Reports::get_by_slug( $slug );
        if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );

        switch ( $sub ) {
            case 'add':
                $payload = self::read_json( $assoc['input'] ?? null );
                if ( ! $payload ) WP_CLI::error( 'JSON payload required' );
                $items = isset( $payload[0] ) ? $payload : [ $payload ];
                foreach ( $items as $row ) ATR_Comments::add( (int) $report['id'], $row );
                WP_CLI::success( 'Added ' . count( $items ) . ' comment(s)' );
                break;
            case 'clear':
                $n = ATR_Comments::clear( (int) $report['id'] );
                WP_CLI::success( "Cleared {$n} comments" );
                break;
            case 'list':
                WP_CLI\Utils\format_items( 'table', ATR_Comments::for_report( (int) $report['id'] ), [ 'id', 'author', 'source_kind', 'featured', 'position' ] );
                break;
            default:
                WP_CLI::error( "Unknown subcommand: {$sub}" );
        }
    }

    /**
     * Manage press pickups: add | clear | list.
     *
     * @when after_wp_load
     */
    public function press( $args, $assoc ) {
        $sub = $args[0] ?? null;
        $slug = $args[1] ?? null;
        if ( ! $slug ) WP_CLI::error( 'Slug required' );
        $report = ATR_Reports::get_by_slug( $slug );
        if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );

        switch ( $sub ) {
            case 'add':
                $payload = self::read_json( $assoc['input'] ?? null );
                $items = isset( $payload[0] ) ? $payload : [ $payload ];
                foreach ( $items as $row ) ATR_Press::add( (int) $report['id'], $row );
                WP_CLI::success( 'Added ' . count( $items ) . ' press item(s)' );
                break;
            case 'clear':
                ATR_Press::clear( (int) $report['id'] );
                WP_CLI::success( 'Cleared press' );
                break;
            case 'list':
                WP_CLI\Utils\format_items( 'table', ATR_Press::for_report( (int) $report['id'] ), [ 'id', 'outlet', 'author', 'published_at' ] );
                break;
            default:
                WP_CLI::error( "Unknown subcommand: {$sub}" );
        }
    }

    /**
     * Manage timeline events: add | clear | list.
     *
     * @when after_wp_load
     */
    public function timeline( $args, $assoc ) {
        $sub = $args[0] ?? null;
        $slug = $args[1] ?? null;
        if ( ! $slug ) WP_CLI::error( 'Slug required' );
        $report = ATR_Reports::get_by_slug( $slug );
        if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );

        switch ( $sub ) {
            case 'add':
                $payload = self::read_json( $assoc['input'] ?? null );
                $items = isset( $payload[0] ) ? $payload : [ $payload ];
                foreach ( $items as $row ) ATR_Timeline::add( (int) $report['id'], $row );
                WP_CLI::success( 'Added ' . count( $items ) . ' timeline event(s)' );
                break;
            case 'clear':
                ATR_Timeline::clear( (int) $report['id'] );
                WP_CLI::success( 'Cleared timeline' );
                break;
            case 'list':
                WP_CLI\Utils\format_items( 'table', ATR_Timeline::for_report( (int) $report['id'] ), [ 'id', 'event_at', 'label', 'marker', 'position' ] );
                break;
            default:
                WP_CLI::error( "Unknown subcommand: {$sub}" );
        }
    }

    /**
     * Manage point-in-time snapshots: save | list | show | delete | clear.
     *
     * Usage:
     *   wp atr snapshot save <slug> --captured-at="2026-04-26 21:00:00" --label="Day 1"
     *   wp atr snapshot list <slug>
     *   wp atr snapshot show <id>
     *   wp atr snapshot delete <id>
     *   wp atr snapshot clear <slug>
     *
     * @when after_wp_load
     */
    public function snapshot( $args, $assoc ) {
        $sub = $args[0] ?? null;

        switch ( $sub ) {
            case 'save':
                $slug = $args[1] ?? null;
                if ( ! $slug ) WP_CLI::error( 'Slug required' );
                $report = ATR_Reports::get_by_slug( $slug );
                if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );
                $captured_at = $assoc['captured-at'] ?? current_time( 'mysql', true );
                $label       = $assoc['label']        ?? '';
                $note        = $assoc['note']         ?? '';
                $id = ATR_Snapshots::save( (int) $report['id'], $captured_at, $label, $note );
                WP_CLI::success( "Saved snapshot #{$id} for {$slug} at {$captured_at}" );
                break;

            case 'list':
                $slug = $args[1] ?? null;
                if ( ! $slug ) WP_CLI::error( 'Slug required' );
                $report = ATR_Reports::get_by_slug( $slug );
                if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );
                $rows = ATR_Snapshots::for_report( (int) $report['id'] );
                WP_CLI\Utils\format_items( 'table', $rows, [ 'id', 'captured_at', 'label', 'note', 'created_at' ] );
                break;

            case 'show':
                $id = (int) ( $args[1] ?? 0 );
                if ( ! $id ) WP_CLI::error( 'Snapshot id required' );
                $row = ATR_Snapshots::get( $id );
                if ( ! $row ) WP_CLI::error( "Snapshot #{$id} not found" );
                WP_CLI::log( wp_json_encode( $row, JSON_PRETTY_PRINT ) );
                break;

            case 'delete':
                $id = (int) ( $args[1] ?? 0 );
                if ( ! $id ) WP_CLI::error( 'Snapshot id required' );
                ATR_Snapshots::delete( $id );
                WP_CLI::success( "Deleted snapshot #{$id}" );
                break;

            case 'clear':
                $slug = $args[1] ?? null;
                if ( ! $slug ) WP_CLI::error( 'Slug required' );
                $report = ATR_Reports::get_by_slug( $slug );
                if ( ! $report ) WP_CLI::error( "Report not found: {$slug}" );
                $n = ATR_Snapshots::clear( (int) $report['id'] );
                WP_CLI::success( "Cleared {$n} snapshot(s)" );
                break;

            default:
                WP_CLI::error( "Unknown subcommand: {$sub}. Use save|list|show|delete|clear" );
        }
    }

    /**
     * Render a report to HTML.
     *
     * Defaults to writing <abspath>/reports/<slug>-report.html.
     *   --out=<path>  override destination
     *   --stdout      print to stdout instead of writing
     *
     * @when after_wp_load
     */
    public function render( $args, $assoc ) {
        $slug = $args[0] ?? null;
        if ( ! $slug ) WP_CLI::error( 'Slug required' );

        try {
            $html = ATR_Renderer::render( $slug );
        } catch ( Throwable $e ) {
            WP_CLI::error( $e->getMessage() );
        }

        if ( ! empty( $assoc['stdout'] ) ) {
            WP_CLI::log( $html );
            return;
        }

        $out = $assoc['out'] ?? ( ABSPATH . 'reports/' . sanitize_file_name( $slug ) . '-report.html' );
        $dir = dirname( $out );
        if ( ! is_dir( $dir ) ) wp_mkdir_p( $dir );
        $bytes = file_put_contents( $out, $html );
        if ( $bytes === false ) WP_CLI::error( "Failed to write {$out}" );
        WP_CLI::success( "Wrote {$bytes} bytes to {$out}" );
    }

    /** Read JSON from path or STDIN. */
    private static function read_json( $source ) {
        if ( ! $source ) return null;
        $raw = $source === '-' ? file_get_contents( 'php://stdin' ) : file_get_contents( $source );
        if ( $raw === false ) WP_CLI::error( "Could not read: {$source}" );
        $data = json_decode( $raw, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) WP_CLI::error( 'Invalid JSON: ' . json_last_error_msg() );
        return $data;
    }

    private static function merge_with_json( array $assoc ): array {
        $data = $assoc;
        unset( $data['input'] );
        if ( ! empty( $assoc['input'] ) ) {
            $extra = self::read_json( $assoc['input'] );
            if ( is_array( $extra ) ) $data = array_merge( $extra, $data );
        }
        return $data;
    }
}
