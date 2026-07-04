<?php
/*
Plugin Name: آیکونیست
Plugin URI: https://technoall.ir
Description: افزونه سبک و حرفه‌ای برای تبدیل خودکار آیکون های SVG برای استفاده و ویرایش در المنتور
Version: 1.0.0
Author: Technoall
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: iconist
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ICONIST_VERSION',  '1.0.0' );
define( 'ICONIST_DIR_URL',  plugin_dir_url( __FILE__ ) );
define( 'ICONIST_DIR_PATH', plugin_dir_path( __FILE__ ) );

function iconist_load_textdomain() {
    load_plugin_textdomain( 'iconist', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'iconist_load_textdomain' );

require_once ICONIST_DIR_PATH . 'includes/class-iconist-sanitizer.php';
require_once ICONIST_DIR_PATH . 'includes/class-iconist-converter.php';
require_once ICONIST_DIR_PATH . 'admin/class-iconist-admin.php';

new Iconist_Converter();
new Iconist_Admin();
