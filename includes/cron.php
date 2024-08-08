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

	// Call CRON Repost function.
	$wp_to_buffer_pro->get_class( 'cron' )->repost();

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
