<?php
/**
 * Defines functions that are called by WordPress' Cron.
 *
 * @package WP_To_Buffer_Pro
 * @author WP Zinc
 */

/**
 * Define the WP Cron function to send status updates via the API
 *
 * @since   3.0.0
 *
 * @param   int    $post_id    Post ID.
 * @param   string $action     Action.
 */
function wp_to_buffer_pro_publish_cron( $post_id, $action ) {

	// Initialise Plugin.
	$wp_to_buffer_pro = WP_To_Buffer_Pro::get_instance();
	$wp_to_buffer_pro->initialize();

	// Call CRON Publish function.
	$wp_to_buffer_pro->get_class( 'cron' )->publish( $post_id, $action );

	// Shutdown.
	unset( $wp_to_buffer_pro );

}
add_action( 'wp_to_buffer_pro_publish_cron', 'wp_to_buffer_pro_publish_cron', 10, 2 );

/**
 * Define the WP Cron function to repost status updates via the API
 *
 * @since   3.7.2
 */
function wp_to_buffer_pro_repost_cron() {

	// Initialise Plugin.
	$wp_to_buffer_pro = WP_To_Buffer_Pro::get_instance();
	$wp_to_buffer_pro->initialize();

	// Get Test Mode Flag.
	$test_mode = (bool) $wp_to_buffer_pro->get_class( 'settings' )->get_option( 'test_mode', false );

	// Call CRON Repost function.
	$wp_to_buffer_pro->get_class( 'cron' )->repost( $test_mode );

	// Shutdown.
	unset( $wp_to_buffer_pro );

}
add_action( 'wp_to_buffer_pro_repost_cron', 'wp_to_buffer_pro_repost_cron' );

/**
 * Define the WP Cron function to perform the log cleanup
 *
 * @since   3.9.8
 */
function wp_to_buffer_pro_log_cleanup_cron() {

	// Initialise Plugin.
	$wp_to_buffer_pro = WP_To_Buffer_Pro::get_instance();
	$wp_to_buffer_pro->initialize();

	// Call CRON Log Cleanup function.
	$wp_to_buffer_pro->get_class( 'cron' )->log_cleanup();

	// Shutdown.
	unset( $wp_to_buffer_pro );

}
add_action( 'wp_to_buffer_pro_log_cleanup_cron', 'wp_to_buffer_pro_log_cleanup_cron' );

/**
 * Define the WP Cron function to perform the Media Library cleanup
 * of Text to Image generations
 *
 * @since   4.2.0
 */
function wp_to_buffer_pro_media_cleanup_cron() {

	// Initialise Plugin.
	$wp_to_buffer_pro = WP_To_Buffer_Pro::get_instance();
	$wp_to_buffer_pro->initialize();

	// Call Media Cleanup function.
	$wp_to_buffer_pro->get_class( 'media_library' )->cleanup();

	// Shutdown.
	unset( $wp_to_buffer_pro );

}
add_action( 'wp_to_buffer_pro_media_cleanup_cron', 'wp_to_buffer_pro_media_cleanup_cron' );

/**
 * Define the WP Cron function to migrate per-Post Status Settings to the new format.
 *
 * Runs as a self-rescheduling one-off event during plugin upgrade, processing
 * a batch of Posts per tick until the queue is empty.
 *
 * @since   6.0.0
 */
function wp_to_buffer_pro_migrate_status_settings_cron() {

	// Initialise Plugin.
	$wp_to_buffer_pro = WP_To_Buffer_Pro::get_instance();
	$wp_to_buffer_pro->initialize();

	// Call CRON status settings migration function.
	$wp_to_buffer_pro->get_class( 'cron' )->migrate_status_settings();

	// Shutdown.
	unset( $wp_to_buffer_pro );

}
add_action( 'wp_to_buffer_pro_migrate_status_settings_cron', 'wp_to_buffer_pro_migrate_status_settings_cron' );
