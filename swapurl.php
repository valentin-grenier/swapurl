<?php

/**
 * Plugin Name: SwapURL
 * Description: Replaces URLs in post content based on an uploaded JSON file.
 * Version: 1.0.0
 * Author: Valentin Grenier • Studio Val
 * Author URI: https://studio-val.fr
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: swapurl
 */

# Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

# Define Plugin Constants
define('SWAPURL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SWAPURL_PLUGIN_URL', plugin_dir_url(__FILE__));

define('SWAPURL_JSON_DIR', WP_CONTENT_DIR . '/json-files');

# Include Core Plugin Classes
require_once SWAPURL_PLUGIN_DIR . 'classes/admin/class-admin.php';
require_once SWAPURL_PLUGIN_DIR . 'classes/core/class-plugin.php';
require_once SWAPURL_PLUGIN_DIR . 'classes/core/class-processor.php';
require_once SWAPURL_PLUGIN_DIR . 'classes/core/class-logger.php';

# Initialize the Plugin
function swapurl_initialize_plugin()
{
    new SWAPURL_Plugin();

    # Load plugin classes
    new SWAPURL_Admin();
    new SWAPURL_Logger();
}
add_action('plugins_loaded', 'swapurl_initialize_plugin');
