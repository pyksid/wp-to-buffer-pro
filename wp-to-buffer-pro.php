<?php
/**
 * WP to Buffer Pro WordPress Plugin.
 *
 * @package WP_To_Buffer_Pro
 * @author WP Zinc
 *
 * @wordpress-plugin
 * Plugin Name: WP to Buffer Pro
 * Plugin URI: https://www.wpzinc.com/plugins/wordpress-to-buffer-pro
 * Version: 6.3.2
 * Author: WP Zinc
 * Author URI: https://www.wpzinc.com
 * Description: Send WordPress Pages, Posts or Custom Post Types to your Buffer (buffer.com) account for scheduled publishing to social networks.
 * Text Domain: wpzinc-social
 * License:     Commercial
 * License URI: https://www.wpzinc.com/terms/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Bail if Plugin is alread loaded.
if ( class_exists( 'WP_To_Buffer_Pro' ) ) {
	return;
}

// Define Plugin version and build date.
define( 'WP_TO_BUFFER_PRO_PLUGIN_VERSION', '6.3.2' );
define( 'WP_TO_BUFFER_PRO_PLUGIN_BUILD_DATE', '2026-10-24 10:00:00' );

// Define Plugin paths.
define( 'WP_TO_BUFFER_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_TO_BUFFER_PRO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Define the autoloader for this Plugin
 *
 * @since   3.4.7
 *
 * @param   string $class_name     The class to load.
 */
function wp_to_buffer_pro_autoloader( $class_name ) {

	// Bail if the class isn't in this Plugin's WPZinc namespace.
	if ( strpos( $class_name, 'WPZinc\\' ) !== 0 ) {
		return;
	}

	// Break the fully qualified class name into its parts.
	$class_parts = explode( '\\', $class_name );
	$short_name  = end( $class_parts );

	// Define the file name from the class' short name.
	$file_name = 'class-' . str_replace( '_', '-', strtolower( $short_name ) ) . '.php';

	// Map the sub-namespace (the second segment) to the directories to search.
	// WPZinc\Social\Pro\* : Pro library + this Plugin's service/main classes.
	// WPZinc\Shared\*     : shared dashboard/admin utilities.
	$namespace_paths = array(
		'Social' => array(
			WP_TO_BUFFER_PRO_PLUGIN_PATH . 'lib/social/includes',
			WP_TO_BUFFER_PRO_PLUGIN_PATH . 'lib/social/includes/integrations',
			WP_TO_BUFFER_PRO_PLUGIN_PATH . 'includes',
		),
		'Shared' => array(
			WP_TO_BUFFER_PRO_PLUGIN_PATH . 'lib/shared',
		),
	);

	$sub_namespace = isset( $class_parts[1] ) ? $class_parts[1] : '';
	if ( ! isset( $namespace_paths[ $sub_namespace ] ) ) {
		return;
	}

	// Iterate through the include paths to find the file.
	foreach ( $namespace_paths[ $sub_namespace ] as $path ) {
		if ( file_exists( $path . '/' . $file_name ) ) {
			require_once $path . '/' . $file_name;
			return;
		}
	}

}
spl_autoload_register( 'wp_to_buffer_pro_autoloader' );

// Load Activation, Cron and Deactivation functions.
require_once WP_TO_BUFFER_PRO_PLUGIN_PATH . 'includes/activation.php';
require_once WP_TO_BUFFER_PRO_PLUGIN_PATH . 'includes/cron.php';
require_once WP_TO_BUFFER_PRO_PLUGIN_PATH . 'includes/functions.php';
require_once WP_TO_BUFFER_PRO_PLUGIN_PATH . 'includes/deactivation.php';
register_activation_hook( __FILE__, 'wp_to_buffer_pro_activate' );
if ( version_compare( get_bloginfo( 'version' ), '5.1', '>=' ) ) {
	add_action( 'wp_insert_site', 'wp_to_buffer_pro_activate_new_site' );
} else {
	add_action( 'wpmu_new_blog', 'wp_to_buffer_pro_activate_new_site' );
}
add_action( 'activate_blog', 'wp_to_buffer_pro_activate_new_site' );
register_deactivation_hook( __FILE__, 'wp_to_buffer_pro_deactivate' );

/**
 * Main function to return Plugin instance.
 *
 * @since   3.8.1
 */
function WP_To_Buffer_Pro() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName

	return WP_To_Buffer_Pro::get_instance();

}

// Finally, initialize the Plugin.
require_once WP_TO_BUFFER_PRO_PLUGIN_PATH . 'includes/class-wp-to-buffer-pro.php';
$wp_to_buffer_pro = WP_To_Buffer_Pro();
