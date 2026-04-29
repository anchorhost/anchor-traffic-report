<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * REST API for AI-agent population. All write endpoints require manage_options.
 *
 * GET    /atr/v1/reports
 * GET    /atr/v1/reports/<slug>
 * POST   /atr/v1/reports
 * PATCH  /atr/v1/reports/<slug>
 * DELETE /atr/v1/reports/<slug>
 *
 * PUT    /atr/v1/reports/<slug>/traffic                 — array body
 * PUT    /atr/v1/reports/<slug>/dimensions/<kind>       — array body
 * POST   /atr/v1/reports/<slug>/platforms               — single or array
 * DELETE /atr/v1/reports/<slug>/platforms               — clear
 * POST   /atr/v1/reports/<slug>/comments                — single or array
 * DELETE /atr/v1/reports/<slug>/comments                — clear
 * POST   /atr/v1/reports/<slug>/press                   — single or array
 * POST   /atr/v1/reports/<slug>/timeline                — single or array
 *
 * GET    /atr/v1/reports/<slug>/render                  — returns HTML
 * POST   /atr/v1/reports/<slug>/render                  — body: {out: "/path"} writes file
 */
class ATR_REST {

    const NS = 'atr/v1';

    public static function register_routes(): void {
        $auth_write = [ __CLASS__, 'permission_write' ];
        $auth_read  = '__return_true';

        register_rest_route( self::NS, '/reports', [
            [ 'methods' => 'GET',  'callback' => [ __CLASS__, 'list_reports' ], 'permission_callback' => $auth_read ],
            [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'create_report' ], 'permission_callback' => $auth_write ],
        ] );

        register_rest_route( self::NS, '/reports/(?P<slug>[a-z0-9\-]+)', [
            [ 'methods' => 'GET',    'callback' => [ __CLASS__, 'get_report' ], 'permission_callback' => $auth_read ],
            [ 'methods' => 'PATCH',  'callback' => [ __CLASS__, 'update_report' ], 'permission_callback' => $auth_write ],
            [ 'methods' => 'DELETE', 'callback' => [ __CLASS__, 'delete_report' ], 'permission_callback' => $auth_write ],
        ] );

        register_rest_route( self::NS, '/reports/(?P<slug>[a-z0-9\-]+)/traffic', [
            [ 'methods' => 'PUT', 'callback' => [ __CLASS__, 'put_traffic' ], 'permission_callback' => $auth_write ],
        ] );

        register_rest_route( self::NS, '/reports/(?P<slug>[a-z0-9\-]+)/dimensions/(?P<kind>[a-z_]+)', [
            [ 'methods' => 'PUT',    'callback' => [ __CLASS__, 'put_dim' ], 'permission_callback' => $auth_write ],
            [ 'methods' => 'DELETE', 'callback' => [ __CLASS__, 'clear_dim' ], 'permission_callback' => $auth_write ],
        ] );

        foreach ( [ 'platforms' => 'ATR_Platforms', 'comments' => 'ATR_Comments', 'press' => 'ATR_Press', 'timeline' => 'ATR_Timeline' ] as $route => $cls ) {
            register_rest_route( self::NS, "/reports/(?P<slug>[a-z0-9\-]+)/{$route}", [
                [ 'methods' => 'POST',   'callback' => function ( $req ) use ( $cls ) { return self::add_to( $cls, $req ); }, 'permission_callback' => $auth_write ],
                [ 'methods' => 'DELETE', 'callback' => function ( $req ) use ( $cls ) { return self::clear_of( $cls, $req ); }, 'permission_callback' => $auth_write ],
            ] );
        }

        register_rest_route( self::NS, '/reports/(?P<slug>[a-z0-9\-]+)/render', [
            [ 'methods' => 'GET',  'callback' => [ __CLASS__, 'render_get' ], 'permission_callback' => $auth_read ],
            [ 'methods' => 'POST', 'callback' => [ __CLASS__, 'render_post' ], 'permission_callback' => $auth_write ],
        ] );
    }

    public static function permission_write(): bool {
        return current_user_can( 'manage_options' );
    }

    /* Reports ------------------------------------------------------------- */

    public static function list_reports() {
        return rest_ensure_response( ATR_Reports::list() );
    }

    public static function get_report( WP_REST_Request $req ) {
        $row = ATR_Reports::get_by_slug( $req['slug'] );
        if ( ! $row ) return new WP_Error( 'atr_not_found', 'Not found', [ 'status' => 404 ] );
        $rid = (int) $row['id'];
        return rest_ensure_response( [
            'report'    => $row,
            'traffic'   => ATR_Traffic::for_report( $rid ),
            'platforms' => ATR_Platforms::for_report( $rid ),
            'comments'  => ATR_Comments::for_report( $rid ),
            'press'     => ATR_Press::for_report( $rid ),
            'timeline'  => ATR_Timeline::for_report( $rid ),
            'referrers' => ATR_Dimensions::for_report( $rid, 'referrer' ),
            'countries' => ATR_Dimensions::for_report( $rid, 'country' ),
            'browsers'  => ATR_Dimensions::for_report( $rid, 'browser' ),
            'devices'   => ATR_Dimensions::for_report( $rid, 'device' ),
        ] );
    }

    public static function create_report( WP_REST_Request $req ) {
        try {
            $row = ATR_Reports::create( (array) $req->get_json_params() );
        } catch ( Throwable $e ) {
            return new WP_Error( 'atr_invalid', $e->getMessage(), [ 'status' => 400 ] );
        }
        return rest_ensure_response( $row );
    }

    public static function update_report( WP_REST_Request $req ) {
        $row = ATR_Reports::update( $req['slug'], (array) $req->get_json_params() );
        if ( ! $row ) return new WP_Error( 'atr_not_found', 'Not found', [ 'status' => 404 ] );
        return rest_ensure_response( $row );
    }

    public static function delete_report( WP_REST_Request $req ) {
        return rest_ensure_response( [ 'deleted' => ATR_Reports::delete( $req['slug'] ) ] );
    }

    /* Traffic ------------------------------------------------------------- */

    public static function put_traffic( WP_REST_Request $req ) {
        $report = ATR_Reports::get_by_slug( $req['slug'] );
        if ( ! $report ) return new WP_Error( 'atr_not_found', 'Not found', [ 'status' => 404 ] );
        $body = $req->get_json_params();
        if ( ! is_array( $body ) ) return new WP_Error( 'atr_invalid', 'Body must be array', [ 'status' => 400 ] );
        $n = ATR_Traffic::replace( (int) $report['id'], $body );
        return rest_ensure_response( [ 'replaced' => $n ] );
    }

    /* Dimensions ---------------------------------------------------------- */

    public static function put_dim( WP_REST_Request $req ) {
        $report = ATR_Reports::get_by_slug( $req['slug'] );
        if ( ! $report ) return new WP_Error( 'atr_not_found', 'Not found', [ 'status' => 404 ] );
        $body = $req->get_json_params();
        if ( ! is_array( $body ) ) return new WP_Error( 'atr_invalid', 'Body must be array', [ 'status' => 400 ] );
        $n = ATR_Dimensions::replace( (int) $report['id'], $req['kind'], $body );
        return rest_ensure_response( [ 'replaced' => $n ] );
    }

    public static function clear_dim( WP_REST_Request $req ) {
        $report = ATR_Reports::get_by_slug( $req['slug'] );
        if ( ! $report ) return new WP_Error( 'atr_not_found', 'Not found', [ 'status' => 404 ] );
        $n = ATR_Dimensions::clear( (int) $report['id'], $req['kind'] );
        return rest_ensure_response( [ 'cleared' => $n ] );
    }

    /* Platforms / Comments / Press / Timeline (generic) ------------------- */

    private static function add_to( string $cls, WP_REST_Request $req ) {
        $report = ATR_Reports::get_by_slug( $req['slug'] );
        if ( ! $report ) return new WP_Error( 'atr_not_found', 'Not found', [ 'status' => 404 ] );
        $body = $req->get_json_params();
        $items = isset( $body[0] ) ? $body : [ $body ];
        $ids = [];
        foreach ( $items as $row ) $ids[] = call_user_func( [ $cls, 'add' ], (int) $report['id'], $row );
        return rest_ensure_response( [ 'added' => count( $ids ), 'ids' => $ids ] );
    }

    private static function clear_of( string $cls, WP_REST_Request $req ) {
        $report = ATR_Reports::get_by_slug( $req['slug'] );
        if ( ! $report ) return new WP_Error( 'atr_not_found', 'Not found', [ 'status' => 404 ] );
        $n = call_user_func( [ $cls, 'clear' ], (int) $report['id'] );
        return rest_ensure_response( [ 'cleared' => $n ] );
    }

    /* Render -------------------------------------------------------------- */

    public static function render_get( WP_REST_Request $req ) {
        try {
            $html = ATR_Renderer::render( $req['slug'] );
        } catch ( Throwable $e ) {
            return new WP_Error( 'atr_render', $e->getMessage(), [ 'status' => 404 ] );
        }
        $resp = new WP_REST_Response( $html );
        $resp->header( 'Content-Type', 'text/html; charset=utf-8' );
        return $resp;
    }

    public static function render_post( WP_REST_Request $req ) {
        $params = (array) $req->get_json_params();
        $out = $params['out'] ?? null;
        if ( ! $out ) return new WP_Error( 'atr_invalid', 'out path required', [ 'status' => 400 ] );
        try {
            ATR_Renderer::write( $req['slug'], $out );
        } catch ( Throwable $e ) {
            return new WP_Error( 'atr_render', $e->getMessage(), [ 'status' => 500 ] );
        }
        return rest_ensure_response( [ 'written' => $out ] );
    }
}
