<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://woot.ro
 * @since             1.0.0
 * @package           Woot
 *
 * @wordpress-plugin
 * Plugin Name:       Woot.ro
 * Plugin URI:        https://woot.ro
 * Description:       Integrates all popular couriers in Romania, providing a one-stop solution for all your delivery needs
 * Version:           2.0.5
 * Author:            Woot.ro
 * Author URI:        https://woot.ro
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woot
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WOOT_VERSION', '2.0.5');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woot-activator.php
 */
function activate_woot()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-woot-activator.php';
	Woot_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woot-deactivator.php
 */
function deactivate_woot()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-woot-deactivator.php';
	Woot_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_woot');
register_deactivation_hook(__FILE__, 'deactivate_woot');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-woot.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woot()
{
	// Check if WooCommerce is installed and active
	if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
		add_action('admin_notices', 'show_required_plugin_notice');
		return;
	}

	$plugin = new Woot();
	$plugin->run();
}

function show_required_plugin_notice()
{
	$notice = <<<EOT
<div class="notice notice-error">
        <p>Woot.ro requires WooCommerce to be installed and active. You cannot use the plugin's features until WooCommerce is set up.</p>
    </div>
EOT;

	echo $notice;
}

run_woot();
