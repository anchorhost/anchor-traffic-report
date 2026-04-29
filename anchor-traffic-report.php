<?php
/**
 * Plugin Name: Anchor Traffic Report
 * Description: Editorial-style traffic recap reports populated by AI agents. Stores arbitrary scanned data in normalized tables and renders standalone HTML.
 * Version:     0.1.0
 * Author:      Anchor Hosting
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ATR_VERSION', '0.1.0' );
define( 'ATR_PATH', plugin_dir_path( __FILE__ ) );
define( 'ATR_URL',  plugin_dir_url( __FILE__ ) );
define( 'ATR_DB_VERSION', 3 );

require_once ATR_PATH . 'includes/helpers.php';
require_once ATR_PATH . 'includes/class-atr-schema.php';
require_once ATR_PATH . 'includes/class-atr-reports.php';
require_once ATR_PATH . 'includes/class-atr-traffic.php';
require_once ATR_PATH . 'includes/class-atr-dimensions.php';
require_once ATR_PATH . 'includes/class-atr-platforms.php';
require_once ATR_PATH . 'includes/class-atr-comments.php';
require_once ATR_PATH . 'includes/class-atr-press.php';
require_once ATR_PATH . 'includes/class-atr-timeline.php';
require_once ATR_PATH . 'includes/class-atr-snapshots.php';
require_once ATR_PATH . 'includes/class-atr-renderer.php';
require_once ATR_PATH . 'includes/class-atr-rest.php';

register_activation_hook( __FILE__, [ 'ATR_Schema', 'install' ] );

add_action( 'plugins_loaded', function () {
    ATR_Schema::maybe_upgrade();
} );

add_action( 'rest_api_init', [ 'ATR_REST', 'register_routes' ] );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    require_once ATR_PATH . 'includes/class-atr-cli.php';
    WP_CLI::add_command( 'atr', 'ATR_CLI' );
}
