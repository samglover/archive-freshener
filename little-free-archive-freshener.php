<?php

/*
Plugin Name: Little Free Archive Freshener
Plugin URI: https://samglover.net/projects/little-free-archive-freshener/
Description: Little Free Archive Freshener is a helpful dashboard widget that prompts you to update your archives, one post or page at a time.
Author: Sam Glover
Version: 1.0.2
Author URI: https://samglover.net
*/

namespace ARCHIVE_FRESHENER;

if (!defined('ABSPATH')) exit;

/**
 * Constants
 */
define('ARCHIVE_FRESHENER_PLUGIN_VERSION', '1.0.2');
define('ARCHIVE_FRESHENER_DIR_PATH', plugin_dir_path(__FILE__));
define('ARCHIVE_FRESHENER_DIR_URL', plugin_dir_url(__FILE__));


/**
 * Plugin Files
 */
if (is_admin()) {
  require_once(ARCHIVE_FRESHENER_DIR_PATH . 'widget.php');
  require_once(ARCHIVE_FRESHENER_DIR_PATH . 'options.php');
}

add_action('admin_enqueue_scripts', __NAMESPACE__ . '\register_admin_js');
function register_admin_js() {
	wp_enqueue_script('wpau-admin-js', ARCHIVE_FRESHENER_DIR_URL . 'admin.js', ['jquery'], '', true);
}

register_activation_hook(__FILE__, __NAMESPACE__ . '\activate');
function activate() {
  $default_options = [
		'lfaf_expiration_date'      => 90,
    'lfaf_included_post_types'  => ['post', 'page'],
  ];

  foreach ($default_options as $key => $val) {
    if (!get_option($key)) update_option($key, $val);
  }
}
