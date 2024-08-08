<?php
/**
 * AJAX class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Registers AJAX actions for saving statuses, fetching usernames,
 * searching Taxonomy Terms etc.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 3.0.0
 */
class WP_To_Social_Pro_Ajax {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.4.7
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   3.0.0
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Actions.
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_usernames_search_facebook', array( $this, 'usernames_search_facebook' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_username_save_twitter', array( $this, 'username_save_twitter' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_save_statuses', array( $this, 'save_statuses' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_save_statuses_post', array( $this, 'save_statuses_post' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_get_status_row', array( $this, 'get_status_row' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_character_count', array( $this, 'character_count' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_get_log', array( $this, 'get_log' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_clear_log', array( $this, 'clear_log' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_search_terms', array( $this, 'search_terms' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_search_authors', array( $this, 'search_authors' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_search_roles', array( $this, 'search_roles' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_bulk_publish', array( $this, 'bulk_publish' ) );
		add_action( 'wp_ajax_' . $this->base->plugin->filter_name . '_repost_test', array( $this, 'repost_test' ) );

	}

	/**
	 * Searches for matching usernames on Facebook for the given search term,
	 * typically for Facebook autocomplete mentions.
	 *
	 * @since   4.5.6
	 */
	public function usernames_search_facebook() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-usernames-search-facebook', 'nonce' );

		// Sanitize inputs.
		$search = sanitize_text_field( $_REQUEST['search'] );

		// Run search.
		$results = $this->base->get_class( 'facebook_api' )->usernames_search( $search );

		// Bail if an error occured.
		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
		}

		// Add '@' before each username, and cast to a key/value array.
		$usernames = array();
		foreach ( $results->data as $index => $result ) {
			$usernames[ $index ] = array(
				'key'   => '@' . $result->name,
				'value' => '@' . $result->name . '[' . $result->id . ']',
			);
		}

		// Return usernames.
		wp_send_json_success( $usernames );

	}

	/**
	 * Saves the given Twitter username and user ID to the API.
	 *
	 * @since   ?
	 */
	public function username_save_twitter() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-username-save-twitter', 'nonce' );

		// Sanitize inputs.
		$user_id  = sanitize_text_field( $_REQUEST['user_id'] );
		$username = sanitize_text_field( $_REQUEST['username'] );

		// Save.
		$results = $this->base->get_class( 'twitter_api' )->username_save( $user_id, $username );

		wp_send_json_success( $results );

	}

	/**
	 * Saves statuses for the given Post Type in the Plugin's Settings section.
	 *
	 * @since   4.0.8
	 */
	public function save_statuses() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-save-statuses', 'nonce' );

		// Parse request.
		$post_type = sanitize_text_field( $_REQUEST['post_type'] );
		$statuses  = json_decode( wp_unslash( $_REQUEST['statuses'] ), true );

		// Get some other information.
		$post_type_object  = get_post_type_object( $post_type );
		$documentation_url = $this->base->plugin->documentation_url . '/status-settings';

		// Save and return.
		$result = $this->base->get_class( 'settings' )->update_settings( $post_type, $statuses );

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Return success, with flag denoting if the Post Type is configured to send statuses.
		wp_send_json_success(
			array(
				'post_type_enabled' => $this->base->get_class( 'settings' )->is_post_type_enabled( $post_type ),
			)
		);

	}

	/**
	 * Saves statuses for the given Post
	 *
	 * @since   4.4.1
	 */
	public function save_statuses_post() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-save-statuses-post', 'nonce' );

		// Parse request to build Post compliant settings array.
		$post_id  = sanitize_text_field( $_REQUEST['post_id'] );
		$settings = array(
			'featured_image'    => $_REQUEST['featured_image'],
			'additional_images' => $_REQUEST['additional_images'],
			'override'          => $_REQUEST['override'],
			'statuses'          => $_REQUEST['statuses'],
		);

		// Save and return.
		$result = $this->base->get_class( 'post' )->save_settings( $post_id, $settings );

		// Bail if an error occured.
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Return success, with flag denoting if the Post Type is configured to send statuses.
		wp_send_json_success(
			array(
				'post_type_enabled' => true,
			)
		);

	}

	/**
	 * Returns HTML markup that can be injected inside a <tr> to show the status' information
	 *
	 * @since   4.4.0
	 */
	public function get_status_row() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-get-status-row', 'nonce' );

		// Parse request.
		$status    = json_decode( wp_unslash( $_REQUEST['status'] ), true );
		$post_type = sanitize_text_field( $_REQUEST['post_type'] );
		$action    = sanitize_text_field( $_REQUEST['post_action'] );

		// Return array of row data (message, image, schedule).
		wp_send_json_success( $this->base->get_class( 'settings' )->get_status_row( $status, $post_type, $action ) );

	}

	/**
	 * Renders the given status and Post to calculate the character count on a status
	 * when using the "Post using Manual Settings" option.
	 *
	 * @since   3.1.6
	 */
	public function character_count() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-character-count', 'nonce' );

		// Get post and status.
		$post   = get_post( absint( $_POST['post_id'] ) );
		$status = sanitize_text_field( $_POST['status'] );

		// Parse status.
		$parsed_status = $this->base->get_class( 'publish' )->parse_text( $post, $status );

		// Return parsed status and character count.
		wp_send_json_success(
			array(
				'status'          => $status,
				'parsed_status'   => $parsed_status,
				'character_count' => strlen( $parsed_status ),
			)
		);

	}

	/**
	 * Fetches the plugin log for the given Post ID, in HTML format
	 * compatible for insertion into the Log Table.
	 *
	 * @since   3.0.0
	 */
	public function get_log() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-get-log', 'nonce' );

		// Get Post ID.
		$post_id = absint( $_REQUEST['post'] );

		// Return log table output.
		wp_send_json_success( $this->base->get_class( 'log' )->build_log_table_output( $this->base->get_class( 'log' )->get( $post_id ) ) );

	}

	/**
	 * Clears the plugin log for the given Post ID
	 *
	 * @since   3.0.0
	 */
	public function clear_log() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-clear-log', 'nonce' );

		// Get Post ID.
		$post_id = absint( $_REQUEST['post'] );

		// Clear log.
		$this->base->get_class( 'log' )->delete_by_post_id( $post_id );

		wp_send_json_success();

	}

	/**
	 * Searches for Taxonomy Terms for the given Taxonomy and freeform text
	 *
	 * @since   3.0.0
	 */
	public function search_terms() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-search-terms', 'nonce' );

		// Get vars.
		$taxonomy = sanitize_text_field( $_REQUEST['taxonomy'] );
		$search   = sanitize_text_field( $_REQUEST['q'] );

		// Get results.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'orderby'    => 'name',
				'order'      => 'ASC',
				'hide_empty' => 0,
				'number'     => 0,
				'fields'     => 'id=>name',
				'search'     => $search,
			)
		);

		// If an error occured, bail.
		if ( is_wp_error( $terms ) ) {
			return wp_send_json_error( $terms->get_error_message() );
		}

		// Build array.
		$terms_array = array();
		foreach ( $terms as $term_id => $name ) {
			$terms_array[] = array(
				'id'   => $term_id,
				'text' => $name,
			);
		}

		// Done.
		wp_send_json_success( $terms_array );

	}

	/**
	 * Searches for Authors for the given freeform text
	 *
	 * @since   3.9.5
	 */
	public function search_authors() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-search-authors', 'nonce' );

		if ( ! isset( $_REQUEST['q'] ) ) {
			return wp_send_json_error( __( 'No search term was provided.', 'wp-to-social-pro' ) );
		}

		// Get vars.
		$query = sanitize_text_field( $_REQUEST['q'] );

		// Get results.
		$users = new WP_User_Query(
			array(
				'search' => '*' . $query . '*',
			)
		);

		// If an error occured, bail.
		if ( is_wp_error( $users ) ) {
			return wp_send_json_error( $users->get_error_message() );
		}

		// Build array.
		$users_array = array();
		$results     = $users->get_results();
		if ( ! empty( $results ) ) {
			foreach ( $results as $user ) {
				$users_array[] = array(
					'id'   => $user->ID,
					'text' => $user->user_login,
				);
			}
		}

		// Done.
		wp_send_json_success( $users_array );

	}

	/**
	 * Searches for Roles for the given freeform text
	 *
	 * @since   4.5.9
	 */
	public function search_roles() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-search-roles', 'nonce' );

		if ( ! isset( $_REQUEST['q'] ) ) {
			return wp_send_json_error( __( 'No search term was provided.', 'wp-to-social-pro' ) );
		}

		// Get vars.
		$query = sanitize_text_field( $_REQUEST['q'] );

		// Get results.
		$results = array();
		foreach ( wp_roles()->roles as $role => $permissions ) {
			if ( stripos( $role, $query ) !== false ) {
				$results[] = array(
					'id'   => $role,
					'text' => $role,
				);
			}
		}

		// Done.
		wp_send_json_success( $results );

	}

	/**
	 * Sends a publish request for the next Post ID in the index sequence.
	 * Used for bulk publishing
	 *
	 * @since   3.0.5
	 */
	public function bulk_publish() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-bulk-publish', 'nonce' );

		// Check required POST variables have been set.
		if ( ! isset( $_POST['current_index'] ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . __( 'Error', 'wp-to-social-pro' ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: current_index parameter missing from request.', 'wp-to-social-pro' ) . '</td></tr>'
			);
		}
		if ( ! isset( $_POST['id'] ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . __( 'Error', 'wp-to-social-pro' ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: id parameter missing from request.', 'wp-to-social-pro' ) . '</td></tr>'
			);
		}
		if ( ! isset( $_POST['number_requests'] ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . __( 'Error', 'wp-to-social-pro' ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: number_requests parameter missing from request.', 'wp-to-social-pro' ) . '</td></tr>'
			);
		}

		// Get required POST variables.
		$current_index   = absint( $_POST['current_index'] );
		$post_id         = absint( $_POST['id'] );
		$number_requests = absint( $_POST['number_requests'] );

		// Get Test Mode Flag.
		$test_mode = $this->base->get_class( 'settings' )->get_option( 'test_mode', false );

		// Publish statuses using the 'bulk_publish' action.
		$results = $this->base->get_class( 'publish' )->publish( $post_id, 'bulk_publish', $test_mode );

		// If no results were returned, bail with an error.
		if ( ! isset( $results ) ) {
			wp_send_json_error(
				'
                <tr><th colspan="8">' . ( $current_index + 1 ) . '/' . $number_requests . ': ' . get_the_title( $post_id ) . '</th></tr>
                <tr><td colspan="8">' . __( 'Error: No response was received.', 'wp-to-social-pro' ) . '</td></tr>'
			);
			die();
		}

		// If the overall result is a WP error, bail with an error.
		if ( is_wp_error( $results ) ) {
			// Build log.
			$log = array(
				'action'         => 'bulk_publish',
				'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'result'         => 'warning',
				'result_message' => $results->get_error_message(),
			);

			// If logging is enabled, log the warning.
			if ( $this->base->get_class( 'settings' )->get_option( 'log', '[enabled]' ) ) {
				$this->base->get_class( 'log' )->add( $post_id, $log );
			}

			// Return log.
			wp_send_json_error(
				'
                <tr><th colspan="8">' . ( $current_index + 1 ) . '/' . $number_requests . ': ' . get_the_title( $post_id ) . '</th></tr>' .
				$this->base->get_class( 'log' )->build_log_table_output(
					array(
						$log,
					)
				)
			);
			die();
		}

		// Build table HTML log.
		wp_send_json_success(
			'<tr><th colspan="8">' . ( $current_index + 1 ) . '/' . $number_requests . ': ' . get_the_title( $post_id ) . '</th></tr>' .
			$this->base->get_class( 'log' )->build_log_table_output( $results )
		);
		die();

	}

	/**
	 * Tests the Repost functionality as if it were triggered by WordPress' Cron now
	 *
	 * @since   4.1.8
	 */
	public function repost_test() {

		// Run a security check first.
		check_ajax_referer( $this->base->plugin->name . '-repost-test', 'nonce' );

		$this->base->get_class( 'cron' )->repost( true );
		wp_send_json_success( $this->base->get_class( 'log' )->get_debug_log() );
		die();

	}

}
