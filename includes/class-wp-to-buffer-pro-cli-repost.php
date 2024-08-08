<?php
/**
 * Repost CLI class.
 *
 * @package WP_To_Buffer_Pro
 * @author WP Zinc
 */

/**
 * Defines the Repost WP-CLI Command
 *
 * @package WP_To_Buffer_Pro
 * @author  WP Zinc
 * @version 3.7.8
 */
class WP_To_Buffer_Pro_CLI_Repost {

	/**
	 * Reposts Posts, Pages and Custom Post Types to the API
	 * based on the status settings at Plugin and Post level.
	 *
	 * @since   3.7.8
	 *
	 * @param   array $args           Array of positional arguments (not used).
	 * @param   array $assoc_args     Array of associative arguments.
	 */
	public function __invoke( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		// Get Plugin Instance.
		$plugin = WP_To_Buffer_Pro::get_instance();

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Started' );

		// Get Arguments.
		$post_types = ( isset( $assoc_args['post_types'] ) ? explode( ',', $assoc_args['post_types'] ) : false );
		$test_mode  = ( isset( $assoc_args['test_mode'] ) ? true : false );

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Post Types: ' . print_r( $post_types, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Test Mode: ' . $test_mode );

		// Run Repost.
		$plugin->get_class( 'repost' )->run( $post_types, $test_mode );

		$plugin->get_class( 'log' )->add_to_debug_log( $plugin->plugin->displayName . ': WP-CLI: Repost: Stopped' );

	}

}
