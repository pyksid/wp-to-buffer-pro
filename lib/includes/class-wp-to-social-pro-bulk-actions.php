<?php
/**
 * Bulk Actions class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Registers and handles bulk actions on WP_List_Tables,
 * primarily for Bulk Publishing selected Post(s).
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 3.0.0
 */
class WP_To_Social_Pro_Bulk_Actions {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.3.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   3.3.8
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Actions.
		add_action( 'admin_init', array( $this, 'register_bulk_action_filters' ) );

	}

	/**
	 * Registers Bulk Action Filters
	 *
	 * @since   3.3.8
	 */
	public function register_bulk_action_filters() {

		// Get public Post Types.
		$post_types = $this->base->get_class( 'common' )->get_post_types();

		// Bail if no Post Types.
		if ( empty( $post_types ) ) {
			return;
		}

		// For each Post Type, add filters for Bulk Actions.
		foreach ( $post_types as $post_type ) {
			add_filter( 'bulk_actions-edit-' . $post_type->name, array( $this, 'register_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-' . $post_type->name, array( $this, 'handle_bulk_actions' ), 10, 3 );
		}

	}

	/**
	 * Adds Bulk Action options to Post Type WP_List_Tables
	 *
	 * @since   3.3.8
	 *
	 * @param   array $actions    Registered Bulk Actions.
	 * @return  array               Registered Bulk Actions
	 */
	public function register_bulk_actions( $actions ) {

		// If no bulk actions exist, cast as an array now.
		// This may be due to e.g. User capability Plugins removing all actions for a given
		// WordPress User Role.
		if ( ! is_array( $actions ) ) {
			$actions = array();
		}

		// Define Actions.
		$bulk_actions = array(
			$this->base->plugin->name => sprintf(
				/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
				__( 'Send to %s', 'wp-to-social-pro' ),
				$this->base->plugin->account
			),
		);

		/**
		 * Defines Bulk Actions to be added to the select dropdown on WP_List_Tables.
		 *
		 * @since   3.3.8
		 *
		 * @param   array   $bulk_actions   Plugin Specific Bulk Actions.
		 * @param   string  $actions        Existing Registered Bulk Actions (excluding Plugin Specific Bulk Actions).
		 */
		$bulk_actions = apply_filters( $this->base->plugin->filter_name . '_bulk_actions_register_bulk_actions', $bulk_actions, $actions );

		// Merge with default Bulk Actions.
		$actions = array_merge( $bulk_actions, $actions );

		// Return.
		return $actions;

	}

	/**
	 * Handles Bulk Actions when one is selected to run
	 *
	 * @since   3.3.8
	 *
	 * @param   string $redirect_to    Redirect URL.
	 * @param   string $action         Bulk Action to Run.
	 * @param   array  $post_ids       Post IDs to apply Action on.
	 * @return  string                  Redirect URL
	 */
	public function handle_bulk_actions( $redirect_to, $action, $post_ids ) {

		// Bail if the action isn't specified.
		if ( empty( $action ) ) {
			return $redirect_to;
		}

		// Bail if no Post IDs.
		if ( empty( $post_ids ) ) {
			return $redirect_to;
		}

		switch ( $action ) {
			case $this->base->plugin->name:
				// Get the Post Type from the screen.
				$screen = get_current_screen();

				// Redirect to Bulk Publishing, with the chosen Post IDs preselected
				// and the required nonce verification.
				$args        = array(
					'page'                               => $this->base->plugin->name . '-bulk-publish',
					'post_ids'                           => implode( ',', $post_ids ),
					'type'                               => $screen->post_type,
					$this->base->plugin->name . '_nonce' => wp_create_nonce( $this->base->plugin->name ),
				);
				$redirect_to = admin_url( add_query_arg( $args, 'admin.php' ) );
				break;

			default:
				// Allow developers to run their Bulk Action now.
				do_action( $this->base->plugin->name . '_bulk_actions_handle_bulk_actions', $action, $post_ids );
				do_action( $this->base->plugin->name . '_bulk_actions_handle_bulk_actions_' . $action, $post_ids );
				break;
		}

		// Return redirect.
		return $redirect_to;

	}

}
