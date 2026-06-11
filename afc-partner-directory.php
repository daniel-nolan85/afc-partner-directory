<?php
/**
 * Plugin Name: AFC Partner Directory
 * Plugin URI:  https://nolancode.com
 * Description: Manages and displays a directory of Partner Organizations for AFC Scholarship Fund.
 * Version:     1.0.0
 * Author:      Daniel Nolan
 * Author URI:  https://nolancode.com
 * License:     GPL-2.0+
 * Text Domain: afc-partner-directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AFC_PLUGIN_VERSION', '1.0.0' );
define( 'AFC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once AFC_PLUGIN_DIR . 'includes/class-cpt.php';
require_once AFC_PLUGIN_DIR . 'includes/class-meta-fields.php';
require_once AFC_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once AFC_PLUGIN_DIR . 'includes/class-block.php';

function afc_init() {
    new AFC_CPT();
    new AFC_Meta_Fields();
    new AFC_REST_API();
    new AFC_Block();
}
add_action( 'plugins_loaded', 'afc_init' );
