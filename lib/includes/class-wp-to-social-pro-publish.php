<?php
/**
 * Publish class
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Handles publishing status(es) to the scheduling service
 * based on the Post and Plugin settings, when a Post's
 * status is transitioned.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 3.0.0
 */
class WP_To_Social_Pro_Publish {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.2.4
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds all supported Tags and their Post data replacements.
	 *
	 * @since   3.7.8
	 *
	 * @var     array
	 */
	private $all_possible_searches_replacements = false;

	/**
	 * Holds searches and replacements for status messages.
	 *
	 * @since   3.7.8
	 *
	 * @var     array
	 */
	private $searches_replacements = false;

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
		add_action( 'wp_loaded', array( $this, 'register_publish_hooks' ), 1 );
		add_action( $this->base->plugin->name, array( $this, 'publish' ), 1, 2 );

	}

	/**
	 * Registers publish hooks against all public Post Types,
	 *
	 * @since   3.0.0
	 */
	public function register_publish_hooks() {

		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );

	}

	/**
	 * Fired when a Post's status transitions.  Called by WordPress when wp_insert_post() is called,
	 * and wp_insert_post() is called by WordPress and the REST API whenever creating or updating a Post.
	 *
	 * @since   3.1.6
	 *
	 * @param   string  $new_status     New Status.
	 * @param   string  $old_status     Old Status.
	 * @param   WP_Post $post           Post.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {

		// Bail if the Post Type isn't public.
		// This prevents the rest of this routine running on e.g. ACF Free, when saving Fields (which results in Field loss).
		$post_types = array_keys( $this->base->get_class( 'common' )->get_post_types() );
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		// New Post Screen loading.
		// Draft saved.
		if ( $new_status === 'auto-draft' || $new_status === 'draft' || $new_status === 'inherit' || $new_status === 'trash' ) {
			return;
		}

		// Remove actions registered by this Plugin.
		// This ensures that when Page Builders call publish or update events via AJAX, we don't run this multiple times.
		remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );
		remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10 );
		remove_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );
		remove_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10 );

		/**
		 * = REST API =
		 * If this is a REST API Request, we can't use the wp_insert_post action, because the metadata
		 * is *not* included in the call to wp_insert_post().  Instead, we must use a late REST API action
		 * that gives the REST API time to save metadata.
		 * Note that the meta being supplied in the REST API Request must be registered with WordPress using
		 * register_meta()
		 *
		 * = Gutenberg =
		 * If Gutenberg is being used on the given Post Type, two requests are sent:
		 * - a REST API request, comprising of Post Data and Metadata registered in Gutenberg,
		 * - a standard request, comprising of Post Metadata registered outside of Gutenberg (i.e. add_meta_box() data)
		 * The second request will be seen by transition_post_status() as an update.
		 * Therefore, we set a meta flag on the first Gutenberg REST API request to defer publishing the status until
		 * the second, standard request - at which point, all Post metadata will be available to the Plugin.
		 *
		 * = Classic Editor =
		 * Metadata is included in the call to wp_insert_post(), meaning that it's saved to the Post before we use it.
		 */

		$this->base->get_class( 'log' )->add_to_debug_log( 'Post ID: #' . $post->ID );

		// If transitioning from future to publish, this is a scheduled Post being published by WordPress Cron.
		// We don't need to know whether it's a Gutenberg, Classic Editor or REST API request.
		if ( $old_status === 'future' && $new_status === 'publish' ) {
			$this->base->get_class( 'log' )->add_to_debug_log( 'Scheduled Post being published by WordPress' );

			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// Flag to determine if the current Post is a Gutenberg Post or Rest API Request.
		$is_gutenberg_request = $this->is_gutenberg_request();
		$is_rest_api_request  = $this->is_rest_api_request();
		$this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg Post: ' . ( $is_gutenberg_request ? 'Yes' : 'No' ) );
		$this->base->get_class( 'log' )->add_to_debug_log( 'REST API Request: ' . ( $is_rest_api_request ? 'Yes' : 'No' ) );

		// If a previous request flagged that an 'update' request should be treated as a publish request (i.e.
		// we're using Gutenberg and request to post.php was made after the REST API), do this now.
		$needs_publishing = get_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_publishing', true );
		if ( $needs_publishing ) {
			// If "Use WP Cron" is enabled, we've already scheduled an event to perform
			// the publish action in Gutenberg's first request. Just delete the flag.
			if ( $this->base->get_class( 'settings' )->get_option( 'cron', false ) ) {
				$this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Use WP Cron enabled, so event already scheduled.' );
				return delete_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_publishing' );
			}

			$this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Needs Publishing' );

			// Run Publish Status Action now.
			delete_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_publishing' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// If a previous request flagged that an update request be deferred (i.e.
		// we're using Gutenberg and request to post.php was made after the REST API), do this now.
		$needs_updating = get_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_updating', true );
		if ( $needs_updating ) {
			// If "Use WP Cron" is enabled, we've already scheduled an event to perform
			// the publish action in Gutenberg's first request. Just delete the flag.
			if ( $this->base->get_class( 'settings' )->get_option( 'cron', false ) ) {
				$this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Use WP Cron enabled, so event already scheduled.' );
				return delete_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_updating' );
			}

			$this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Needs Updating' );

			// Run Publish Status Action now.
			delete_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_updating' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// Publish.
		if ( $new_status === 'publish' && $new_status !== $old_status ) {
			/**
			 * Gutenberg Editor REST API Request
			 * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
			 * as an 'update'.  Define a meta key that we'll check on the separate request later.
			 */
			if ( $is_gutenberg_request ) {
				$this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Defer Publish' );

				update_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_publishing', 1 );

				// If "Use WP Cron" is enabled, schedule the publish() cron event now and exit.
				// Hooking schedule_publish() to wp_insert_post results in wp_schedule_single_event
				// stating it scheduled the event, however the event never gets scheduled when using
				// Gutenberg.  This is likely due to the second Gutenberg request not having the required
				// permissions to actually schedule an event in the WordPress Cron.
				if ( $this->base->get_class( 'settings' )->get_option( 'cron', false ) ) {
					// Don't need to include $test_mode, as WP_To_Social_Pro_Cron::publish()
					// checks for Test Mode when the event runs.
					return $this->schedule_publish( $post->ID, 'publish' );
				}

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * REST API
			 */
			if ( $is_rest_api_request ) {
				$this->base->get_class( 'log' )->add_to_debug_log( 'REST API: Publish' );
				add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_publish' ), 10, 1 );

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * Classic Editor
			 */
			$this->base->get_class( 'log' )->add_to_debug_log( 'Classic Editor: Publish' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_publish' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

		// Update.
		if ( $new_status === 'publish' && $old_status === 'publish' ) {
			/**
			 * Gutenberg Editor REST API Request
			 * - Non-Gutenberg metaboxes are POSTed via a second, separate request to post.php, which appears
			 * as an 'update'.  Define a meta key that we'll check on the separate request later.
			 */
			if ( $is_gutenberg_request ) {
				$this->base->get_class( 'log' )->add_to_debug_log( 'Gutenberg: Defer Update' );

				update_post_meta( $post->ID, $this->base->plugin->filter_name . '_needs_updating', 1 );

				// If "Use WP Cron" is enabled, schedule the publish() cron event now and exit.
				// Hooking schedule_publish() to wp_insert_post results in wp_schedule_single_event
				// stating it scheduled the event, however the event never gets scheduled when using
				// Gutenberg.  This is likely due to the second Gutenberg request not having the required
				// permissions to actually schedule an event in the WordPress Cron.
				if ( $this->base->get_class( 'settings' )->get_option( 'cron', false ) ) {
					// Don't need to include $test_mode, as WP_To_Social_Pro_Cron::publish()
					// checks for Test Mode when the event runs.
					return $this->schedule_publish( $post->ID, 'update' );
				}

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * REST API
			 */
			if ( $is_rest_api_request ) {
				$this->base->get_class( 'log' )->add_to_debug_log( 'REST API: Update' );
				add_action( 'rest_after_insert_' . $post->post_type, array( $this, 'rest_api_post_update' ), 10, 1 );

				// Don't need to do anything else, so exit.
				return;
			}

			/**
			 * Classic Editor
			 */
			$this->base->get_class( 'log' )->add_to_debug_log( 'Classic Editor: Update' );
			add_action( 'wp_insert_post', array( $this, 'wp_insert_post_update' ), 999 );

			// Don't need to do anything else, so exit.
			return;
		}

	}

	/**
	 * Helper function to determine if the request is a Gutenberg REST API request.
	 *
	 * @since   @TODO
	 *
	 * @return  bool    Is Gutenberg REST API Request
	 */
	private function is_gutenberg_request() {

		if ( ! defined( 'REST_REQUEST' ) ) {
			return false;
		}

		if ( ! REST_REQUEST ) {
			return false;
		}

		// Gutenberg requests are REST API requests, but include a _locale key.
		// 'True' REST API requests do not include this key.
		if ( ! array_key_exists( '_locale', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		return true;

	}

	/**
	 * Helper function to determine if the request is a REST API request.
	 *
	 * @since   3.9.1
	 *
	 * @return  bool    Is REST API Request
	 */
	private function is_rest_api_request() {

		if ( ! defined( 'REST_REQUEST' ) ) {
			return false;
		}

		if ( ! REST_REQUEST ) {
			return false;
		}

		// Gutenberg requests are REST API requests, but include a _locale key.
		// 'True' REST API requests do not include this key.
		if ( array_key_exists( '_locale', $_REQUEST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return false;
		}

		return true;

	}

	/**
	 * Helper function to determine if the Post contains Gutenberg Content.
	 *
	 * @since   3.9.1
	 *
	 * @param   WP_Post $post   Post.
	 * @return  bool                Post Content contains Gutenberg Block Markup
	 */
	private function is_gutenberg_post_content( $post ) {

		if ( strpos( $post->post_content, '<!-- wp:' ) !== false ) {
			return true;
		}

		return false;

	}

	/**
	 * Called when a Post has been Published via the REST API.
	 *
	 * @since   3.6.8
	 *
	 * @param   WP_Post $post           Post.
	 */
	public function rest_api_post_publish( $post ) {

		$this->wp_insert_post_publish( $post->ID );

	}

	/**
	 * Called when a Post has been Published via the REST API
	 *
	 * @since   3.6.8
	 *
	 * @param   WP_Post $post           Post.
	 */
	public function rest_api_post_update( $post ) {

		$this->wp_insert_post_update( $post->ID );

	}

	/**
	 * Called when a Post has been Published
	 *
	 * @since   3.6.2
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function wp_insert_post_publish( $post_id ) {

		// Get Test Mode Flag and Use WP Cron Flag.
		$test_mode   = $this->base->get_class( 'settings' )->get_option( 'test_mode', false );
		$use_wp_cron = $this->base->get_class( 'settings' )->get_option( 'cron', false );

		// If "Use WP Cron" is enabled, schedule the publish() event and exit.
		if ( $use_wp_cron ) {
			// Don't need to include $test_mode, as WP_To_Social_Pro_Cron::publish()
			// checks for Test Mode when the event runs.
			return $this->schedule_publish( $post_id, 'publish' );
		}

		// Call main function to publish status(es) to social media.
		$results = $this->publish( $post_id, 'publish', $test_mode );

		// If no result, bail.
		if ( ! isset( $results ) ) {
			return;
		}

		// If no errors, return.
		if ( ! is_wp_error( $results ) ) {
			return;
		}

		// If logging is disabled, return.
		$log_enabled = $this->base->get_class( 'log' )->is_enabled();
		if ( ! $log_enabled ) {
			return;
		}

		// The result is a single warning caught before any statuses were sent to the API.
		// Add the warning to the log so that the user can see why no statuses were sent to API.
		$this->base->get_class( 'log' )->add(
			$post_id,
			array(
				'action'         => 'publish',
				'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'result'         => 'warning',
				'result_message' => $results->get_error_message(),
			)
		);

	}

	/**
	 * Called when a Post has been Updated
	 *
	 * @since   3.6.2
	 *
	 * @param   int $post_id    Post ID.
	 */
	public function wp_insert_post_update( $post_id ) {

		// If a status was last sent within 5 seconds, don't send it again.
		// Prevents Page Builders that trigger wp_update_post() multiple times on Publish or Update from
		// causing statuses to send multiple times.
		$last_sent = get_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_last_sent', true );
		if ( ! empty( $last_sent ) ) {
			$difference = ( current_time( 'timestamp' ) - $last_sent ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
			if ( $difference < 5 ) {
				return;
			}
		}

		// Get Test Mode Flag and Use WP Cron Flag.
		$test_mode   = $this->base->get_class( 'settings' )->get_option( 'test_mode', false );
		$use_wp_cron = $this->base->get_class( 'settings' )->get_option( 'cron', false );

		// If "Use WP Cron" is enabled, schedule the publish() event and exit.
		if ( $use_wp_cron ) {
			// Don't need to include $test_mode, as WP_To_Social_Pro_Cron::publish()
			// checks for Test Mode when the event runs.
			return $this->schedule_publish( $post_id, 'update' );
		}

		// Call main function to publish status(es) to social media.
		$results = $this->publish( $post_id, 'update', $test_mode );

		// If no result, bail.
		if ( ! isset( $results ) ) {
			return;
		}

		// If no errors, return.
		if ( ! is_wp_error( $results ) ) {
			return;
		}

		// If logging is disabled, return.
		$log_enabled = $this->base->get_class( 'log' )->is_enabled();
		if ( ! $log_enabled ) {
			return;
		}

		// The result is a single error caught before any statuses were sent to the API.
		// Add the error to the log so that the user can see why no statuses were sent to API.
		$this->base->get_class( 'log' )->add(
			$post_id,
			array(
				'action'         => 'update',
				'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'result'         => 'warning',
				'result_message' => $results->get_error_message(),
			)
		);

	}

	/**
	 * Called when any Page, Post or CPT is published or updated and Use WP Cron
	 * is enabled, which makes wp_insert_post_publish() and wp_insert_post_update()
	 * call this function.
	 *
	 * See WP_To_Social_Pro_Cron::publish(), which is fired when the event runs,
	 * and checks the Plugin's Test Mode to determine whether to send or log the status(es).
	 *
	 * @since   4.3.3
	 *
	 * @param   int    $post_id                Post ID.
	 * @param   string $action                 Action (publish|update).
	 * @return  mixed                               WP_Error | API Results array
	 */
	public function schedule_publish( $post_id, $action ) {

		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': schedule_publish(): Post ID: #' . $post_id );
		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': schedule_publish(): Action: ' . $action );

		// Get settings, validating the Post and Action.
		$settings = $this->validate( $post_id, $action );

		// If an error occured, bail.
		if ( is_wp_error( $settings ) ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': schedule_publish(): Settings Error: ' . $settings->get_error_message() );
			return $settings;
		}

		// If settings are false, we're not sending this Post, so there's no need to schedule an event.
		if ( ! $settings ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': schedule_publish(): Settings are blank, no event needs to be scheduled' );
			return false;
		}

		// Schedule registered action in 30 seconds time.
		$event = wp_schedule_single_event(
			time() + 30,
			$this->base->plugin->filter_name . '_publish_cron',
			array(
				$post_id,
				$action,
			)
		);

		// Bail if an error occured scheduling.
		if ( is_wp_error( $event ) ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': schedule_publish(): Event Error: ' . $event->get_error_message() );
			return $event;
		}

		// Add single log entry.
		$logs = array(
			array(
				'action'         => $action,
				'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				'profile_id'     => false,
				'profile_name'   => false,
				'result'         => 'pending',
				'result_message' => sprintf(
					/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
					__( 'Status added to WordPress Cron for sending to %1$s.  Check the Post\'s Log in a few minutes for confirmation that the status has been added to %2$s', 'wp-to-social-pro' ),
					$this->base->plugin->account,
					$this->base->plugin->account
				),
				'status_text'    => false,
			),
		);

		// Save log, if enabled.
		$log_enabled = $this->base->get_class( 'log' )->is_enabled();
		if ( $log_enabled ) {
			foreach ( $logs as $log ) {
				$this->base->get_class( 'log' )->add( $post_id, $log );
			}
		}

		// Return log results that are scheduled to be sent to the API via CRON.
		return $logs;

	}

	/**
	 * Main function. Called when any Page, Post or CPT is published, updated, reposted
	 * or bulk published.
	 *
	 * @since   3.0.0
	 *
	 * @param   int    $post_id                Post ID.
	 * @param   string $action                 Action (publish|update|repost|bulk_publish).
	 * @param   bool   $test_mode              Test Mode (won't send to API).
	 * @return  mixed                               WP_Error | API Results array
	 */
	public function publish( $post_id, $action, $test_mode = false ) {

		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Post ID: #' . $post_id );
		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Action: ' . $action );
		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Test Mode: ' . ( $test_mode ? 'Yes' : 'No' ) );

		// Get settings, validating the Post and Action.
		$settings = $this->validate( $post_id, $action );

		// If an error occured, bail.
		if ( is_wp_error( $settings ) ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Settings Error: ' . $settings->get_error_message() );
			return $settings;
		}

		// If settings are false, we're not sending this Post, so there's no need to schedule an event.
		if ( ! $settings ) {
			return false;
		}

		// Get post.
		$post = get_post( $post_id );

		// Clear any cached data that we have stored in this class.
		$this->clear_search_replacements();

		// Check a valid access token exists.
		$access_token  = $this->base->get_class( 'settings' )->get_access_token();
		$refresh_token = $this->base->get_class( 'settings' )->get_refresh_token();
		$expires       = $this->base->get_class( 'settings' )->get_token_expires();
		if ( ! $access_token ) {
			return new WP_Error(
				'no_access_token',
				sprintf(
					/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Plugin Name */
					__( 'The Plugin has not been authorized with %1$s! Go to %2$s > Settings to setup the plugin.', 'wp-to-social-pro' ),
					$this->base->plugin->account,
					$this->base->plugin->displayName
				)
			);
		}

		// Setup API.
		$this->base->get_class( 'api' )->set_tokens( $access_token, $refresh_token, $expires );

		// Get Profiles.
		$profiles = $this->base->get_class( 'api' )->profiles( false, $this->base->get_class( 'common' )->get_transient_expiration_time() );

		// Bail if the Profiles could not be fetched.
		if ( is_wp_error( $profiles ) ) {
			$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Profiles Error: ' . $profiles->get_error_message() );
			return $profiles;
		}

		// Array for storing statuses we'll send to the API.
		$statuses = array();

		// Run profiles and settings through role restriction, based on the Post's Author.
		$author = get_user_by( 'id', $post->post_author );
		if ( $author !== false ) {
			$profiles = $this->base->get_class( 'common' )->maybe_remove_profiles_by_role( $profiles, $author->roles[0] );
			$settings = $this->base->get_class( 'common' )->maybe_remove_profiles_by_role( $settings, $author->roles[0] );
		}

		// Iterate through each social media profile.
		foreach ( $settings as $profile_id => $profile_settings ) {

			// Skip some setting keys that aren't related to profiles.
			if ( in_array( $profile_id, array( 'featured_image', 'additional_images', 'override' ), true ) ) {
				continue;
			}

			// Skip if the Profile ID does not exist in the $profiles array, it's been removed from the API.
			if ( $profile_id !== 'default' && ! isset( $profiles[ $profile_id ] ) ) {
				continue;
			}

			// If the Profile's ID belongs to a Google Social Media Profile, skip it, as this is no longer supported
			// as Google+ closed down.
			// Allow this to go through for SocialPilot which supports GMB.
			if ( $profile_id !== 'default' && $profiles[ $profile_id ]['service'] === 'google' && $this->base->plugin->name !== 'wp-to-socialpilot-pro' ) {
				continue;
			}

			// Get detailed settings from Post or Plugin.
			switch ( $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[override]', 0 ) ) {
				case '1':
					// Use Post Settings.
					$profile_enabled  = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][enabled]', 0 );
					$profile_override = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][override]', 0 );

					// Use Override Settings.
					if ( $profile_override ) {
						$action_enabled  = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][' . $action . '][enabled]', 0 );
						$status_settings = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[' . $profile_id . '][' . $action . '][status]', array() );
					} else {
						$action_enabled  = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[default][' . $action . '][enabled]', 0 );
						$status_settings = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[default][' . $action . '][status]', array() );
					}
					break;

				case '0':
					// Use Plugin Settings.
					$profile_enabled  = $this->base->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][enabled]', 0 );
					$profile_override = $this->base->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][override]', 0 );

					// Use Override Settings.
					if ( $profile_override ) {
						$action_enabled  = $this->base->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][' . $action . '][enabled]', 0 );
						$status_settings = $this->base->get_class( 'settings' )->get_setting( $post->post_type, '[' . $profile_id . '][' . $action . '][status]', array() );
					} else {
						$action_enabled  = $this->base->get_class( 'settings' )->get_setting( $post->post_type, '[default][' . $action . '][enabled]', 0 );
						$status_settings = $this->base->get_class( 'settings' )->get_setting( $post->post_type, '[default][' . $action . '][status]', array() );
					}
					break;
			}

			// Check if this profile is enabled.
			if ( ! $profile_enabled ) {
				continue;
			}

			// Check if this profile's action is enabled.
			if ( ! $action_enabled ) {
				continue;
			}

			// Determine which social media service this profile ID belongs to.
			foreach ( $profiles as $profile ) {
				if ( $profile['id'] == $profile_id ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$service = $profile['service'];
					break;
				}
			}

			// Iterate through each Status.
			foreach ( $status_settings as $index => $status ) {
				// If this Status has Post Title, Excerpt or Content conditions enabled, check they are met.
				$conditions_met = $this->check_post_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Date conditions enabled, check they are met.
				$conditions_met = $this->check_date_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Taxonomy conditions enabled, check they are met.
				$conditions_met = $this->check_taxonomy_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Custom Field Conditions, check these Custom Field Conditions are met.
				$conditions_met = $this->check_custom_field_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Author conditions enabled, check they are met.
				$conditions_met = $this->check_author_condition( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Author Role conditions enabled, check they are met.
				$conditions_met = $this->check_author_role_condition( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// If this Status has Author Custom Field conditions enabled, check these Author Custom Field Conditions are met.
				$conditions_met = $this->check_author_custom_field_conditions( $status, $post );
				if ( ! $conditions_met ) {
					continue;
				}

				// Built in conditions are met.
				$conditions_met = true;

				/**
				 * Process condition settings for Integrations / Third Party Plugins
				 *
				 * @since   5.1.2
				 *
				 * @param   array       $status         Status
				 * @param   WP_Post     $post           WordPress Post
				 * @param   string      $profile_id     Social Media Profile ID.
				 * @param   string      $service        Social Media Service.
				 * @param   string      $action         Action (publish|update|repost|bulk_publish).
				 */
				$conditions_met = apply_filters( $this->base->plugin->filter_name . '_publish_status_conditions_met', $conditions_met, $status, $post, $profile_id, $service, $action );
				if ( ! $conditions_met ) {
					continue;
				}

				// If here, the status either has no conditions, or the conditions found are met.
				// Add the status to our array for it to be sent to the API.
				$status = $this->build_args( $post, $profile_id, $service, $status, $action );

				// If the status built is a WP_Error, something went wrong with e.g. the image.
				// Include the error object and the profile ID, so the error is logged.
				if ( is_wp_error( $status ) ) {
					$status = array(
						'profile_ids' => array( $profile_id ),
						'error'       => $status,
					);
				}

				// Add status to array of statuses.
				$statuses[] = $status;
			}
		}

		$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Statuses: ' . print_r( $statuses, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

		// Check if any statuses exist.
		// If not, exit.
		if ( count( $statuses ) === 0 ) {
			// Fetch Post Type object and Settings URL.
			$post_type_object = get_post_type_object( $post->post_type );
			$plugin_url       = admin_url( 'admin.php?page=' . $this->base->plugin->name . '-settings&tab=post&type=' . $post->post_type );
			$post_url         = admin_url( 'post.php?post=' . $post_id . '&action=edit' );

			// Return an error, depending on why no statuses were found.
			if ( isset( $conditions_met ) && ! $conditions_met ) {
				$error = new WP_Error(
					$this->base->plugin->filter_name . '_no_statuses_conditions',
					sprintf(
						/* translators: %1$s: Post Type Name, Singular, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %3$s: Action (Publish, Update, Repost, Bulk Publish), %4$s, %5$s, %6$s: Post Type Name, Singular, %7$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %8$s: Plugin URL, %9$s: Plugin Name, %10$s: Post Type Name, Singular, %11$s: Action (Publish, Update, Repost, Bulk Publish) */
						__( 'Status(es) exist for sending this %1$s to %2$s when you %3$s a %4$s, but no status was sent because the %5$s did not meet the status conditions. If you want this %6$s to be sent to %7$s, navigate to <a href="%8$s" target="_blank">%9$s > Settings > %10$s Tab > %11$s Action Tab</a>, ensuring that no Conditions are set on the defined statuses.', 'wp-to-social-pro' ),
						$post_type_object->labels->singular_name,
						$this->base->plugin->account,
						ucwords( str_replace( '_', ' ', $action ) ),
						$post_type_object->labels->singular_name,
						$post_type_object->labels->singular_name,
						$post_type_object->labels->singular_name,
						$this->base->plugin->account,
						$plugin_url,
						$this->base->plugin->displayName,
						$post_type_object->labels->name,
						ucwords( str_replace( '_', ' ', $action ) )
					)
				);

				$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Statuses Error: ' . $error->get_error_message() );

				return $error;
			} else {
				if ( $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[override]', 0 ) ) {
					// Post's Manual Settings don't permit sending to API.
					$error = new WP_Error(
						$this->base->plugin->filter_name . '_no_statuses_enabled',
						sprintf(
							/* translators: %1$s: Post Type Name, Singular, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %3$s: Action (Publish, Update, Repost, Bulk Publish), %4$s, %5$s, %6$s: Post Type Name, Singular, %7$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %8$s: Plugin URL, %9$s: Plugin Name, %10$s: Post Type Name, Singular, %11$s: Action (Publish, Update, Repost, Bulk Publish) */
							__( 'No %1$s Settings are defined for sending this %2$s to %3$s when you %4$s. To send statuses to %5$s on %6$s, <a href="%7$s" target="_blank">Edit the Post</a>, navigate to %8$s > Defaults > %9$s Action Tab, tick "Enabled" and also enable at least one social media profile.', 'wp-to-social-pro' ),
							$post_type_object->labels->singular_name,
							$post_type_object->labels->singular_name,
							$this->base->plugin->account,
							ucwords( str_replace( '_', ' ', $action ) ),
							$this->base->plugin->account,
							ucwords( str_replace( '_', ' ', $action ) ),
							$post_url,
							$this->base->plugin->displayName,
							ucwords( str_replace( '_', ' ', $action ) )
						)
					);
				} else {
					$error = new WP_Error(
						$this->base->plugin->filter_name . '_no_statuses_enabled',
						sprintf(
							/* translators: %1$s: Post Type Name, Singular, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %3$s: Action (Publish, Update, Repost, Bulk Publish), %4$s, %5$s, %6$s: Post Type Name, Singular, %7$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %8$s: Plugin URL, %9$s: Plugin Name, %10$s: Post Type Name, Singular, %11$s: Action (Publish, Update, Repost, Bulk Publish) */
							__( 'No Plugin Settings are defined for sending %1$s to %2$s when you %3$s a %4$s. To send statuses to %5$s on %6$s, navigate to <a href="%7$s" target="_blank">%8$s > Settings > %9$s Tab > %10$s Action Tab</a>, tick "Enabled", and also enable at least one social media profile.', 'wp-to-social-pro' ),
							$post_type_object->labels->name,
							$this->base->plugin->account,
							ucwords( str_replace( '_', ' ', $action ) ),
							$post_type_object->labels->singular_name,
							$this->base->plugin->account,
							ucwords( str_replace( '_', ' ', $action ) ),
							$plugin_url,
							$this->base->plugin->displayName,
							$post_type_object->labels->name,
							ucwords( str_replace( '_', ' ', $action ) )
						)
					);
				}

				$this->base->get_class( 'log' )->add_to_debug_log( $this->base->plugin->displayName . ': publish(): Statuses Error: ' . $error->get_error_message() );

				return $error;
			}
		}

		/**
		 * Determine the statuses to send, just before they're sent. Statuses can be added, edited
		 * and/or deleted as necessary here.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $statuses   Statuses to be sent to social media.
		 * @param   int     $post_id    Post ID.
		 * @param   string  $action     Action (publish, update, repost).
		 */
		$statuses = apply_filters( $this->base->plugin->filter_name . '_publish_statuses', $statuses, $post_id, $action );

		// Debugging.
		$this->base->get_class( 'log' )->add_to_debug_log( 'Statuses: ' . print_r( $statuses, true ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

		// Send status messages to the API.
		$results = $this->send( $statuses, $post_id, $action, $profiles, $test_mode );

		// If no results, we're finished.
		if ( empty( $results ) || count( $results ) === 0 ) {
			return false;
		}

		return $results;

	}

	/**
	 * Performs pre-publish and pre-schedule publish validation checks, including
	 * - if the action is supported
	 * - if the Post exists
	 * - if the Post Type's supported
	 * - whether the Post override disables sending statuses
	 *
	 * @since   4.3.3
	 *
	 * @param   int    $post_id                Post ID.
	 * @param   string $action                 Action (publish|update).
	 * @return  mixed                               WP_Error | API Results array
	 */
	private function validate( $post_id, $action ) {

		// Bail if the action isn't supported.
		$supported_actions = array_keys( $this->base->get_class( 'common' )->get_post_actions() );
		if ( ! in_array( $action, $supported_actions, true ) ) {
			return new WP_Error(
				'wp_to_social_pro_publish_invalid_action',
				sprintf(
					/* translators: Action */
					__( 'The %s action is not supported.', 'wp-to-social-pro' ),
					$action
				)
			);
		}

		// Get Post.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error(
				'no_post',
				sprintf(
					/* translators: Post ID */
					__( 'No WordPress Post could be found for Post ID %s', 'wp-to-social-pro' ),
					$post_id
				)
			);
		}

		// Bail if the Post Type isn't supported.
		// This prevents non-public Post Types sending status(es) where Post Level Default = Post using Manual Settings
		// and this non-public Post Type has been created by copying metadata from a public Post Type that specifies.
		// Post-specific status settings.
		$supported_post_types = array_keys( $this->base->get_class( 'common' )->get_post_types() );
		if ( ! in_array( get_post_type( $post ), $supported_post_types, true ) ) {
			return false;
		}

		/**
		 * If a draft or new Post is published, this function is always called before WP_To_Social_Pro::save_post()
		 * We can't control this, therefore we need to save the Post's plugin settings first, before checking them -
		 * otherwise we would be looking at an old copy of the Post's settings (if any exist).
		 */
		if ( in_array( $action, array( 'publish', 'update' ), true ) ) {
			$this->base->get_class( 'post' )->save_post( $post_id );
		}

		// Get Settings from either this Post or the Plugin's Settings, depending
		// on the Post's override setting.
		switch ( $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[override]', 0 ) ) {
			case '1':
				// Use Post Settings.
				return $this->base->get_class( 'post' )->get_settings( $post->ID );

			case '0':
				// Use Plugin Settings.
				return $this->base->get_class( 'settings' )->get_settings( get_post_type( $post ) );

			case '-1':
				// Do not Post.
				return false;

		}

		// Shouldn't ever reach here, but if we do, something went wrong.
		return false;

	}

	/**
	 * Checks whether the Post Title meets the status' Post Conditions, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   4.0.8
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_post_conditions( $status, $post ) {

		// Define Post Keys to test.
		$post_keys = array(
			'post_title',
			'post_excerpt',
			'post_content',
		);

		foreach ( $post_keys as $post_key ) {
			// Skip if no key or comparison is defined.
			if ( ! isset( $status[ $post_key ] ) ) {
				continue;
			}
			if ( ! is_array( $status[ $post_key ] ) ) {
				continue;
			}
			if ( ! isset( $status[ $post_key ]['compare'] ) ) {
				continue;
			}
			if ( empty( $status[ $post_key ]['compare'] ) ) {
				continue;
			}

			// Get the Post Key's value.
			switch ( $post_key ) {
				case 'post_title':
					$post_value = $this->get_title( $post );
					break;

				case 'post_excerpt':
					$post_value = $this->get_excerpt( $post, false );
					break;

				case 'post_content':
					$post_value = $this->get_content( $post );
					break;
			}

			// Test condition.
			$condition_passed = $this->condition_passed( $status[ $post_key ]['compare'], 'post', $post->ID, $post_value, $status[ $post_key ]['value'], false );
			if ( ! $condition_passed ) {
				return false;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Date Conditions, if any,
	 * to determine if the status can be sent to the API
	 *
	 * @since   4.0.7
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_date_conditions( $status, $post ) {

		// Conditions met if no start or end date specified.
		if ( ! isset( $status['start_date'] ) ) {
			return true;
		}
		if ( ! isset( $status['end_date'] ) ) {
			return true;
		}

		// Conditions met if start or end date are blank.
		if ( in_array( '', $status['start_date'], true ) ) {
			return true;
		}
		if ( in_array( '', $status['end_date'], true ) ) {
			return true;
		}

		// Fetch the Post Date, changing the year to this year.
		$post_date = new DateTime( $post->post_date );
		$post_date = strtotime( gmdate( 'Y' ) . '-' . $post_date->format( 'm-d' ) );

		// Define the start and end dates.
		$start_date = strtotime( gmdate( 'Y' ) . '-' . $status['start_date']['month'] . '-' . $status['start_date']['day'] );
		$end_date   = strtotime( gmdate( 'Y' ) . '-' . $status['end_date']['month'] . '-' . $status['end_date']['day'] );

		// Check if the Post's Date falls within the start and end date.
		if ( $post_date >= $start_date && $post_date <= $end_date ) {
			return true;
		}

		// Conditions not met.
		return false;

	}

	/**
	 * Checks whether the Post meets the status' Taxonomy Conditions, if any,
	 * to determine if the status can be sent to the API
	 *
	 * @since   4.0.7
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_taxonomy_conditions( $status, $post ) {

		// Conditions met if no Taxonomy Conditions are specified.
		if ( ! isset( $status['conditions'] ) ) {
			return true;
		}
		if ( empty( $status['conditions'] ) ) {
			return true;
		}
		if ( ! is_array( $status['conditions'] ) ) {
			return true;
		}
		if ( ! count( array_filter( $status['conditions'] ) ) ) {
			return true;
		}

		foreach ( $status['conditions'] as $taxonomy => $method ) {
			// Skip if no method is defined; this means no condition is set.
			if ( empty( $method ) ) {
				continue;
			}

			// Skip if no terms defined; we can't test a condition with no terms.
			if ( ! isset( $status['terms'][ $taxonomy ] ) ) {
				continue;
			}
			if ( empty( $status['terms'][ $taxonomy ] ) ) {
				continue;
			}
			if ( ! is_array( $status['terms'][ $taxonomy ] ) ) {
				continue;
			}
			if ( ! count( array_filter( $status['terms'][ $taxonomy ] ) ) ) {
				continue;
			}

			// Fetch Post Term IDs.
			$post_term_ids = wp_get_post_terms(
				$post->ID,
				$taxonomy,
				array(
					'fields' => 'ids',
				)
			);

			// Skip if an error occured (e.g. a Taxonomy Condition was set for a Taxonomy
			// that no longer exists).
			if ( is_wp_error( $post_term_ids ) ) {
				continue;
			}

			// Fetch Condition Term IDs.
			$condition_term_ids = $status['terms'][ $taxonomy ];

			// Depending on the condition method, determine whether the status
			// should be sent to the API.
			switch ( $method ) {
				/**
				 * Post must include ANY one of the condition terms
				 */
				case 'include_any':
					foreach ( $condition_term_ids as $condition_term_id ) {
						// If the Condition Term ID is in this Post, condition met.
						if ( in_array( (int) $condition_term_id, $post_term_ids, true ) ) {
							break 2;
						}
					}

					// If here, condition not met.
					return false;

				/**
				 * Post must include ALL of the condition terms
				 */
				case 'include_all':
					foreach ( $condition_term_ids as $condition_term_id ) {
						// If the Condition Term ID is not in this Post, condition not met.
						if ( ! in_array( (int) $condition_term_id, $post_term_ids, true ) ) {
							return false;
						}
					}
					break;

				/**
				 * Post must not have ANY one of the condition terms
				 */
				case 'exclude_any':
					foreach ( $condition_term_ids as $condition_term_id ) {
						// If the Condition Term ID is in this Post, condition not met.
						if ( in_array( (int) $condition_term_id, $post_term_ids, true ) ) {
							return false;
						}
					}
					break;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Custom Field Conditions, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   4.0.7
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_custom_field_conditions( $status, $post ) {

		// Conditions met if no Custom Field Conditions are specified.
		if ( ! isset( $status['custom_fields'] ) ) {
			return true;
		}

		// Conditions met is Custom Field Conditions are not an array.
		if ( ! is_array( $status['custom_fields'] ) ) {
			return true;
		}

		// Conditions met if no Custom Field Conditions in the array.
		if ( ! count( $status['custom_fields'] ) ) {
			return true;
		}

		foreach ( $status['custom_fields'] as $custom_field ) {
			// Skip if no key or comparison is defined.
			if ( empty( $custom_field['key'] ) ) {
				continue;
			}
			if ( empty( $custom_field['compare'] ) ) {
				continue;
			}

			// Get the Post's meta value.
			$post_meta_value = get_post_meta( $post->ID, $custom_field['key'], true );

			// Test condition.
			$condition_passed = $this->condition_passed( $custom_field['compare'], 'post', $post->ID, $post_meta_value, $custom_field['value'], $custom_field['key'] );
			if ( ! $condition_passed ) {
				return false;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Author Condition, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   4.0.7
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_author_condition( $status, $post ) {

		// Conditions met if no Authors are specified.
		if ( ! isset( $status['authors'] ) ) {
			return true;
		}
		if ( ! is_array( $status['authors'] ) ) {
			return true;
		}

		// Remove empty Authors.
		$status['authors'] = array_filter( $status['authors'] );

		// Conditions met if no Authors are specified after filtering the array.
		if ( ! count( $status['authors'] ) ) {
			return true;
		}

		// Test condition.
		switch ( $status['authors_compare'] ) {
			/**
			 * Not Equals
			 */
			case '!=':
				// Condition fails if the Post Author is in the array of Authors.
				if ( in_array( $post->post_author, $status['authors'], true ) ) {
					return false;
				}
				break;

			/**
			 * Equals
			 */
			case '=':
			default:
				// Condition fails if the Post Author is not in the array of Authors.
				if ( ! in_array( $post->post_author, $status['authors'], true ) ) {
					return false;
				}
				break;
		}

		// If here, condition passes.
		return true;

	}

	/**
	 * Checks whether the Post meets the status' Author Role Condition, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   4.0.7
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_author_role_condition( $status, $post ) {

		// Conditions met if no Roles are specified.
		if ( ! isset( $status['authors_roles'] ) ) {
			return true;
		}
		if ( ! is_array( $status['authors_roles'] ) ) {
			return true;
		}

		// Remove empty Roles.
		$status['authors_roles'] = array_filter( $status['authors_roles'] );

		// Conditions met if no Roles are specified after filtering the array.
		if ( ! count( $status['authors_roles'] ) ) {
			return true;
		}

		// Get Author's Role(s).
		$author_metadata = get_userdata( $post->post_author );

		// Test condition.
		switch ( $status['authors_roles_compare'] ) {
			/**
			 * Not Equals
			 */
			case '!=':
				// Condition fails if any one of the Post Author's Role(s) exists in the array of Author Roles.
				foreach ( $author_metadata->roles as $role ) {
					if ( in_array( $role, $status['authors_roles'], true ) ) {
						return false;
					}
				}

				// If here, none of the Post Author's Role(s) exist in the array of Author Roles, so the condition passes.
				return true;

			/**
			 * Equals
			 */
			case '=':
			default:
				// Condition passes if any one of Post Author's Role(s) exists in the array of Author Roles.
				foreach ( $author_metadata->roles as $role ) {
					if ( in_array( $role, $status['authors_roles'], true ) ) {
						return true;
					}
				}

				// If here, none of the Post Author's Role(s) exist in the array of Author Roles, so the condition fails.
				return false;
		}

	}

	/**
	 * Checks whether the Post meets the status' Author's Custom Field Conditions, if any,
	 * to determine if the status can be sent to the API.
	 *
	 * @since   4.5.9
	 *
	 * @param   array   $status     Status.
	 * @param   WP_Post $post       Post.
	 * @return  bool                    Status can be sent (conditions met or conditions do not exist)
	 */
	private function check_author_custom_field_conditions( $status, $post ) {

		// Conditions met if no Author Custom Field Conditions are specified.
		if ( ! isset( $status['authors_custom_fields'] ) ) {
			return true;
		}

		// Conditions met if Author Custom Field Conditions are not an array.
		if ( ! is_array( $status['authors_custom_fields'] ) ) {
			return true;
		}

		// Conditions met if no Author Custom Field Conditions in the array.
		if ( ! count( $status['authors_custom_fields'] ) ) {
			return true;
		}

		foreach ( $status['authors_custom_fields'] as $custom_field ) {
			// Skip if no key or comparison is defined.
			if ( empty( $custom_field['key'] ) ) {
				continue;
			}
			if ( empty( $custom_field['compare'] ) ) {
				continue;
			}

			// Get the Post Author's meta value.
			$user_meta_value = get_user_meta( $post->post_author, $custom_field['key'], true );

			// Test condition.
			$condition_passed = $this->condition_passed( $custom_field['compare'], 'user', $post->post_author, $user_meta_value, $custom_field['value'], $custom_field['key'] );

			if ( ! $condition_passed ) {
				return false;
			}
		}

		// If here, conditions met.
		return true;

	}

	/**
	 * Determines if the conditional query passes or fails.
	 *
	 * @since   4.0.8
	 *
	 * @param   string $comparison         Comparison Method.
	 * @param   string $type               Type (post|user).
	 * @param   int    $id                 Post or Author ID.
	 * @param   string $value              Post Value.
	 * @param   string $condition_value    Condition Value.
	 * @param   mixed  $condition_key      Condition Key (false | string).
	 * @return  bool                        Condition Passed
	 */
	private function condition_passed( $comparison, $type, $id, $value, $condition_value, $condition_key = false ) {

		switch ( $comparison ) {
			case '=':
				if ( $value == $condition_value ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					return true;
				}

				return false;

			case '!=':
				if ( $value != $condition_value ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
					return true;
				}

				return false;

			case '>':
				if ( $value > $condition_value ) {
					return true;
				}

				return false;

			case '>=':
				if ( $value >= $condition_value ) {
					return true;
				}

				return false;

			case '<':
				if ( $value < $condition_value ) {
					return true;
				}

				return false;

			case '<=':
				if ( $value <= $condition_value ) {
					return true;
				}

				return false;

			case 'IN':
				return in_array( $value, explode( ',', $condition_value ) ); // phpcs:ignore WordPress.PHP.StrictInArray

			case 'NOT IN':
				return ! in_array( $value, explode( ',', $condition_value ) );  // phpcs:ignore WordPress.PHP.StrictInArray

			case 'LIKE':
				if ( stripos( $value, $condition_value ) !== false ) {
					return true;
				}

				return false;

			case 'NOT LIKE':
				if ( stripos( $value, $condition_value ) === false ) {
					return true;
				}

				return false;

			case 'EMPTY':
				if ( empty( $value ) ) {
					return true;
				}

				return false;

			case 'NOT EMPTY':
				if ( ! empty( $value ) ) {
					return true;
				}

				return false;

			case 'EXISTS':
				if ( metadata_exists( $type, $id, $condition_key ) ) {
					return true;
				}

				return false;

			case 'NOT EXISTS':
				if ( ! metadata_exists( $type, $id, $condition_key ) ) {
					return true;
				}

				return false;

			default:
				/**
				 * Determine if a statuses meta conditionals have been met, where the conditional
				 * is not a plugin standard option.
				 *
				 * @since   3.7.2
				 *
				 * @param   bool    $condition_passed   Condition Passed.
				 * @param   string  $comparison         Comparison Method.
				 * @param   string  $type               Type (post|user).
				 * @param   int     $id                 Post or Author ID.
				 * @param   string  $post_value         Post Value.
				 * @param   string  $condition_value    Condition Value.
				 * @param   mixed   $condition_key      Condition Key (false | string).
				 */
				return apply_filters( $this->base->plugin->filter_name . '_publish_condition_passed', true, $comparison, $type, $id, $value, $condition_value, $condition_key = false );
		} // switch.

	}

	/**
	 * Helper method to build arguments and create a status via the API
	 *
	 * @since   3.0.0
	 *
	 * @param   obj    $post                       Post.
	 * @param   string $profile_id                 Profile ID.
	 * @param   string $service                    Service.
	 * @param   array  $status                     Status Settings.
	 * @param   string $action                     Action (publish|update|repost|bulk_publish).
	 * @return  bool                                Success
	 */
	private function build_args( $post, $profile_id, $service, $status, $action ) {

		// Build each API argument.
		// Profile ID.
		$args = array(
			'profile_ids' => array( $profile_id ),
		);

		// Text.
		$args['text'] = $this->parse_text( $post, $status['message'] );

		// Shorten URLs.
		if ( $this->base->supports( 'url_shortening' ) ) {
			$disable_url_shortening = $this->base->get_class( 'settings' )->get_option( 'disable_url_shortening', false );
			$args['shorten']        = ( $disable_url_shortening ? 'false' : 'true' );
		}

		// Drafts.
		if ( $this->base->supports( 'drafts' ) ) {
			$is_draft         = $this->base->get_class( 'settings' )->get_option( 'is_draft', false );
			$args['is_draft'] = ( $is_draft ? 'true' : 'false' );
		}

		// Google Business Profiles.
		if ( $this->base->supports( 'googlebusiness' ) && $service === 'googlebusiness' ) {
			$args['channel_data'] = array(
				'googlebusiness' => $this->parse_google_business( $post, $status ),
			);

			// Remove channel data if the configuration is false.
			if ( ! $args['channel_data']['googlebusiness'] ) {
				unset( $args['channel_data'] );
			}
		}

		// Instagram Update Type.
		if ( $this->base->supports( 'instagram_update_type' ) && $service === 'instagram' ) {
			if ( array_key_exists( 'update_type', $status ) && $status['update_type'] === 'story' ) {
				$args['update_type'] = $status['update_type'];
			}
		}

		// Schedule.
		switch ( $status['schedule'] ) {

			case 'queue_bottom':
				// This is the default for the API, so nothing more to do here.
				break;

			case 'queue_top':
				$args['top'] = true;
				break;

			case 'now':
				$args['now'] = true;
				break;

			/**
			 * Custom Time
			 */
			case 'custom':
				// Check days, hours, minutes are set.
				if ( empty( $status['days'] ) ) {
					$status['days'] = 0;
				}
				if ( empty( $status['hours'] ) ) {
					$status['hours'] = 0;
				}
				if ( empty( $status['minutes'] ) ) {
					$status['minutes'] = 0;
				}

				// Define the Post Date, depending on the action.
				switch ( $action ) {
					case 'publish':
						$post_date = $post->post_date_gmt;
						break;

					case 'update':
						$post_date = $post->post_modified_gmt;
						break;

					case 'repost':
					case 'bulk_publish':
						$post_date = date( 'Y-m-d H:i:s' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						break;
				}

				// Add days, hours and minutes.
				$timestamp = strtotime( '+' . $status['days'] . ' days ' . $status['hours'] . ' hours ' . $status['minutes'] . ' minutes', strtotime( $post_date ) );

				// No need to adjust for UTC here, as the date we're using is already UTC/GMT.
				$args['scheduled_at'] = date( 'Y-m-d H:i:s', $timestamp ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				break;

			/**
			 * Custom Time (Relative Format)
			 */
			case 'custom_relative':
				// Define the Post Date, depending on the action.
				switch ( $action ) {
					case 'publish':
						$post_date = $post->post_date_gmt;
						break;

					case 'update':
						$post_date = $post->post_modified_gmt;
						break;

					case 'repost':
					case 'bulk_publish':
						$post_date = date( 'Y-m-d H:i:s' ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						break;
				}

				// Define scheduled date and time based on the Relative Format.
				switch ( $status['schedule_relative_day'] ) {
					case 'today':
					case 'tomorrow':
						$timestamp = strtotime( $status['schedule_relative_day'] . ' ' . $status['schedule_relative_time'] );
						break;

					default:
						$timestamp = strtotime( 'next ' . $status['schedule_relative_day'] . ' ' . $status['schedule_relative_time'] );
						break;
				}

				// No need to adjust for UTC here, as the date we're using is already UTC/GMT.
				$args['scheduled_at'] = date( 'Y-m-d H:i:s', $timestamp ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				break;

			case 'custom_field':
				// Check days, hours, minutes are set.
				if ( empty( $status['days'] ) ) {
					$status['days'] = 0;
				}
				if ( empty( $status['hours'] ) ) {
					$status['hours'] = 0;
				}
				if ( empty( $status['minutes'] ) ) {
					$status['minutes'] = 0;
				}

				// Fetch the Post's Meta Value based on the given Custom Field Key.
				$post_date = get_post_meta( $post->ID, $status['schedule_custom_field_name'], true );

				// If the post date is numeric, it's most likely a timestamp
				// Convert it to a date and time.
				if ( is_numeric( $post_date ) ) {
					$post_date = date( 'Y-m-d H:i:s', $post_date ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				}

				// Get adjusted date and time.
				$date_time = $this->base->get_class( 'date' )->adjust_date_time(
					$post_date,
					$status['schedule_custom_field_relation'],
					$status['days'],
					$status['hours'],
					$status['minutes']
				);

				// Return UTC date and time.
				$args['scheduled_at'] = $this->base->get_class( 'date' )->get_utc_date_time( $date_time );
				break;

			/**
			 * Specific Date and Time
			 */
			case 'specific':
				/**
				 * The datetime that we send via the API must be in UTC, so that the social media service can then apply
				 * its timezone offset as defined by the user account's settings.
				 *
				 * For example, 2018-09-01 13:00:00 in a UTC+1 timezone will be sent as 2018-09-01 12:00:00, and scheduled as
				 * 2018-09-01 13:00:00, because the social media services' timezone will add an hour back to the scheduled
				 * datetime.
				 */
				$args['scheduled_at'] = $this->base->get_class( 'date' )->get_utc_date_time( date( 'Y-m-d H:i:s', strtotime( $status['schedule_specific'] ) ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				break;

			default:
				$scheduled_at = false;

				/**
				 * Allows integrations to define when the status should be scheduled for publication
				 *
				 * @since   4.6.9
				 *
				 * @param   string  $scheduled_at   Schedule Status (yyyy-mm-dd hh:mm:ss format).
				 * @param   array   $status         Status.
				 * @param   WP_Post $post           WordPress Post.
				 */
				$scheduled_at = apply_filters( $this->base->plugin->filter_name . '_publish_builds_args_schedule_' . $status['schedule'], $scheduled_at, $status, $post );

				// Ignore if no scheduled_at defined.
				if ( ! $scheduled_at ) {
					break;
				}

				$args['scheduled_at'] = $scheduled_at;
				break;

		}

		// Change the Image setting if it's an invalid value for the service.
		// This happens when e.g. Defaults are set, but per-service settings aren't.
		switch ( $service ) {
			/**
			 * Twitter
			 * - Force Use Feat. Image, not Linked to Post if Use Feat. Image, Linked to Post chosen
			 */
			case 'twitter':
				if ( $status['image'] == 1 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$status['image'] = 2;
				}

				// Set Use Text to Image, Linked to Post = Use Text to Image, not Linked to Post.
				if ( $status['image'] == 3 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$status['image'] = 4;
				}
				break;

			/**
			 * Pinterest, Instagram, Google Business, Mastodon
			 * - Force No Image, OpenGraph and Use Feat. Image, Linked to Post = Use Feat. Image, not Linked to Post.
			 * - Force Use Text to Image, Linked to Post = Use Text to Image, not Linked to Post.
			 */
			case 'pinterest':
			case 'instagram':
			case 'googlebusiness':
				// Set No Image, OpenGraph and Use Feat. Image, Linked to Post = Use Feat. Image, not Linked to Post.
				if ( $status['image'] == -1 || $status['image'] == 0 || $status['image'] == 1 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$status['image'] = 2;
				}

				// Set Use Text to Image, Linked to Post = Use Text to Image, not Linked to Post.
				if ( $status['image'] == 3 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$status['image'] = 4;
				}
				break;

			/**
			 * Pinterest, Instagram, Google Business, Mastodon
			 * - Force No Image and Use Feat. Image, Linked to Post = Use Feat. Image, not Linked to Post.
			 * - Force Use Text to Image, Linked to Post = Use Text to Image, not Linked to Post.
			 */
			case 'mastodon':
				// Set Use Feat. Image, Linked to Post = Use Feat. Image, not Linked to Post.
				if ( $status['image'] == 1 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$status['image'] = 2;
				}

				// Set Use Text to Image, Linked to Post = Use Text to Image, not Linked to Post.
				if ( $status['image'] == 3 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$status['image'] = 4;
				}

				// Set Use OpenGraph Settings = No Image.
				// This prevents Buffer from parsing and removing the URL from the status, which results in
				// Mastodon not displaying the link preview.
				if ( $status['image'] == 0 ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
					$status['image'] = -1;
				}
				break;

		}

		// If the status is set to No Image, don't attempt to fetch an image.
		if ( $status['image'] == -1 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
			$args['attachment'] = 'false';
		} else {
			// Determine if a format exists e.g. story|post for Instagram.
			$format = ( isset( $status['update_type'] ) ? $status['update_type'] : false );

			// Get Image.
			if ( $status['image'] == 3 || $status['image'] == 4 ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				// Text to Image.
				$text_to_image = $this->parse_text( $post, $status['text_to_image'] );

				/**
				 * Defines the text to use for the text to image.
				 *
				 * @since   4.2.0
				 *
				 * @param   string      $text_to_image              Text.
				 * @param   WP_Post     $post                       WordPress Post.
				 * @param   string      $profile_id                 Social Media Profile ID.
				 * @param   string      $service                    Social Media Service.
				 * @param   array       $status                     Parsed Status Message Settings.
				 * @param   string      $action                     Action (publish|update|repost|bulk_publish).
				 * @param   bool|string $format                     Status format (for example, 'story' or 'post' for Instagram).
				 */
				$text_to_image = apply_filters( $this->base->plugin->filter_name . '_publish_text_to_image', $text_to_image, $post, $profile_id, $service, $status, $action, $format );

				// Generate Image from Text.
				$image = $this->get_text_to_image( $text_to_image, $service, $profile_id, $post->ID, $format );
			} else {
				// Featured, Additional Image or Content Image.
				$image = $this->get_post_image( $post, $service, $format );
			}

			// Image will be an ID, false (if no image) or WP_Error (if an image exists but something went wrong).

			// If the image is a WP_Error object, log it and return.
			if ( is_wp_error( $image ) ) {
				$this->base->get_class( 'log' )->add_to_debug_log( 'Image Error: ' . $image->get_error_message() );
				return $image;
			}

			// If we have a Featured Image, add it to the Status is required.
			if ( ! is_wp_error( $image ) && $image !== false ) {
				switch ( $status['image'] ) {
					/**
					 * Use OpenGraph Settings
					 */
					case 0:
					case '':
						// Add Post link to media, so API service knows where to fetch OpenGraph data from.
						$args['media'] = array(
							'link' => $this->get_permalink( $post ),
						);
						break;

					/**
					 * Use Feat. Image, Linked to Post
					 * Use Text to Image, Linked to Post
					 * - Facebook, LinkedIn
					 */
					case 1:
					case 3:
						$args['media'] = array(
							'link'        => $this->get_permalink( $post ),
							'description' => $this->get_excerpt( $post, false ),
							'title'       => $this->get_title( $post ),
							'picture'     => $image['image'],
							'alt_text'    => $image['alt_text'],

							// Dashboard Thumbnail.
							// Not supplied, as may results in cURL timeouts.
						);
						break;

					/**
					 * Use Feat. Image, not Linked to Post
					 * Use Text to Image, not Linked to Post
					 * - Facebook, LinkedIn, Twitter, Instagram, Pinterest
					 */
					case 2:
					case 4:
						$args['media'] = array(
							'description' => $this->get_excerpt( $post, false ),
							'title'       => $this->get_title( $post ),
							'picture'     => $image['image'],
							'alt_text'    => $image['alt_text'],

							// Dashboard Thumbnail.
							// Supplied, as required when specifying media with no link.
							// Using the smallest possible image to avoid cURL timeouts.
							'thumbnail'   => $image['thumbnail'],

							// Hootsuite for Amazon S3 upload for quality tests.
							'id'          => $image['id'],
						);
						break;

				}
			}

			/**
			 * Additional Images
			 * - If supported, assigned to the Post and the Featured Image setting isn't OpenGraph,
			 * add additional images to the arguments
			 */
			if ( $status['image'] == 2 ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
				$additional_images_supported = $this->base->supports( 'additional_images' );
				$additional_images           = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, '[additional_images]', false );

				if ( $additional_images_supported && $additional_images !== false && ! empty( $additional_images ) ) {
					$args['extra_media'] = array();
					foreach ( $additional_images as $additional_image_id ) {
						// Get additional image.
						$additional_image = $this->base->get_class( 'image' )->get_image_sources( $additional_image_id, 'additional_image', $service, ( isset( $status['update_type'] ) ? $status['update_type'] : false ) );

						// Skip if not found.
						if ( is_wp_error( $additional_image ) ) {
							// Log error.
							$this->base->get_class( 'log' )->add_to_debug_log( 'Additional Image #' . $additional_image_id . ' Error: ' . $additional_image->get_error_message() );
							continue;
						}

						// Add additional image to extra_media parameter.
						$args['extra_media'][] = array(
							'thumbnail' => $additional_image['thumbnail'],
							'photo'     => $additional_image['image'],
							'alt_text'  => $additional_image['alt_text'],
						);
					}

					// If the extra_media parameter is empty, remove it.
					if ( empty( $args['extra_media'] ) ) {
						unset( $args['extra_media'] );
					}
				}
			}
		}

		// Pinterest.
		if ( $service === 'pinterest' && isset( $status['sub_profile'] ) ) {
			$args['subprofile_ids'] = array(
				$status['sub_profile'],
			);
			$args['source_url']     = $this->get_permalink( $post );
		}

		// Instagram.
		if ( $service === 'instagram' ) {
			// Add 'link' parameter so this status has a link when viewed through shopgr.id.
			$args['link'] = $this->get_permalink( $post );
		}

		// Replace Profile Tags.
		// Link shortening may be disabled by this function, regardless of the Plugin's setting - otherwise our indicies will be incorrect when a
		// Facebook Page mention exists and long link is changed to e.g.http://buff.ly/2k2vfeo by Buffer.
		$args = $this->process_profile_mentions( $args, $post, $profile_id, $service, $status, $action );

		/**
		 * Determine the standardised arguments array to send via the API for a status message's settings.
		 *
		 * @since   3.0.0
		 *
		 * @param   array       $args                       API standardised arguments.
		 * @param   WP_Post     $post                       WordPress Post.
		 * @param   string      $profile_id                 Social Media Profile ID.
		 * @param   string      $service                    Social Media Service.
		 * @param   array       $status                     Parsed Status Message Settings.
		 * @param   string      $action                     Action (publish|update|repost|bulk_publish).
		 */
		$args = apply_filters( $this->base->plugin->filter_name . '_publish_build_args', $args, $post, $profile_id, $service, $status, $action );

		// Return args.
		return $args;

	}

	/**
	 * Attempts to fetch the given Post's Image, in the following order:
	 * - Plugin's First (Featured) Image
	 * - Post's Featured Image
	 * - Post's first image in content, if service = pinterest or instagram
	 *
	 * @since   3.9.8
	 *
	 * @param   WP_Post     $post       Post ID.
	 * @param   string      $service    Social Media Service.
	 * @param   bool|string $format     Status format (for example, 'story' or 'post' for Instagram).
	 * @return  mixed                       false | array
	 */
	private function get_post_image( $post, $service, $format = false ) {

		// Plugin's First (Featured) Image.
		$image_id = $this->base->get_class( 'post' )->get_setting_by_post_id( $post->ID, 'featured_image' );
		if ( $image_id > 0 ) {
			return $this->base->get_class( 'image' )->get_image_sources( $image_id, 'plugin', $service, $format );
		}

		// Featured Image.
		$image_id = get_post_thumbnail_id( $post->ID );
		if ( $image_id > 0 ) {
			return $this->base->get_class( 'image' )->get_image_sources( $image_id, 'featured_image', $service, $format );
		}

		// Content's First Image.
		$images = preg_match_all( '/<img.+?src=[\'"]([^\'"]+)[\'"].*?>/i', apply_filters( 'the_content', $post->post_content ), $matches );
		if ( $images ) {
			// @TODO We can't handle image resizing, as get_image_sources() requires an image_id.
			// Is there a way to get the image ID by URL from the Media Library, so we can then process it through resizing?
			return array(
				'image'     => strtok( $matches[1][0], '?' ),
				'thumbnail' => strtok( $matches[1][0], '?' ),
				'alt_text'  => '',
				'source'    => 'post_content',
			);
		}

		// If here, no image was found in the Post.
		return false;

	}

	/**
	 * Generates an image from the given text, Social Media Service and Plugin Settings
	 *
	 * @since   4.2.0
	 *
	 * @param   string      $text       Text.
	 * @param   string      $service    Social Media Service.
	 * @param   string      $profile_id Social Media Profile ID.
	 * @param   int         $post_id    Post ID.
	 * @param   bool|string $format     Status format (for example, 'story' or 'post' for Instagram).
	 * @return  mixed                       false | array
	 */
	private function get_text_to_image( $text, $service, $profile_id, $post_id, $format = false ) {

		// Get Text to Image Settings.
		$settings = $this->base->get_class( 'settings' )->get_option( 'text_to_image' );

		// Setup Text to Image.
		$text_to_image = new WP_To_Social_Pro_Text_To_Image();

		// If a Background Image is specified for the given Profile ID in the settings, use it.
		if ( isset( $settings['background_image'] ) && isset( $settings['background_image'][ $profile_id ] ) && ! empty( $settings['background_image'][ $profile_id ] ) ) {
			// Load Image.
			$dimensions = $text_to_image->load( $settings['background_image'][ $profile_id ] );
		} else {
			// Get required dimensions for this Social Media Service.
			$dimensions = $this->base->get_class( 'image' )->get_social_media_image_size( $service, $format );

			// Create Image using Background Color.
			$text_to_image->create(
				$dimensions[0],
				$dimensions[1],
				( isset( $settings['background_color'] ) && ! empty( $settings['background_color'] ) ? $settings['background_color'] : '#e7e7e7' )
			);
		}

		// Bail if an error occured.
		if ( is_wp_error( $dimensions ) ) {
			return $dimensions;
		}

		// Get Font.
		$font = $this->base->plugin->folder . 'lib/assets/fonts/OpenSans-Regular.ttf';
		if ( isset( $settings['font'] ) ) {
			if ( ! $settings['font'] ) {
				// Custom Font.
				$font = get_attached_file( $settings['font_custom'] );
			} else {
				// Plugin Font.
				$font = $this->base->plugin->folder . 'lib/assets/fonts/' . $settings['font'] . '.ttf';
			}
		}

		// Add Text.
		$text_to_image->add_text(
			$text,
			$font,
			( isset( $settings['text_size'] ) ? $settings['text_size'] : 90 ),
			( isset( $settings['text_color'] ) ? $settings['text_color'] : '#000000' ),
			( isset( $settings['text_background_color'] ) ? $settings['text_background_color'] : false ),
			$dimensions[0],
			$dimensions[1],
			50
		);

		// Save to temporary file on disk.
		$image = $text_to_image->save_tmp();

		// Upload to Media Library.
		$image_id = $this->base->get_class( 'media_library' )->upload_local_image( $image, $post_id, false, $text, $text, $text );

		// Bail if we couldn't upload to the Media Library.
		if ( is_wp_error( $image_id ) ) {
			return $image_id;
		}

		// Return Text to Image.
		return $this->base->get_class( 'image' )->get_image_sources( $image_id, 'text_to_image', $service, $format );

	}

	/**
	 * Populates the status message by replacing tags with Post/Author data
	 *
	 * @since   3.0.0
	 *
	 * @param   WP_Post $post               Post.
	 * @param   string  $message            Status Message to parse.
	 * @return  string                          Parsed Status Message
	 */
	public function parse_text( $post, $message ) {

		// Perform spintax.
		$spintax = $this->base->get_class( 'spintax' )->process( $message );
		if ( ! is_wp_error( $spintax ) ) {
			$message = $spintax;
		}

		// Get Author.
		$author = get_user_by( 'id', $post->post_author );

		// If we haven't yet populated the searches and replacements for this Post, do so now.
		if ( ! $this->all_possible_searches_replacements ) {
			$this->all_possible_searches_replacements = $this->register_all_possible_searches_replacements( $post, $author );
		}

		// If no searches and replacements are defined, we can't parse anything.
		if ( ! $this->all_possible_searches_replacements || count( $this->all_possible_searches_replacements ) === 0 ) {
			return $message;
		}

		// Extract all of the tags in the message.
		preg_match_all( '|{(.+?)}|', $message, $matches );

		// If no tags exist in the message, there's nothing to parse.
		if ( ! is_array( $matches ) ) {
			return $message;
		}
		if ( count( $matches[0] ) === 0 ) {
			return $message;
		}

		// Define return text.
		$text = $message;

		// Iterate through matches, adding them to the search / replacement array.
		foreach ( $matches[1] as $index => $tag ) {
			// Clean up some vars.
			unset( $tag_params, $transformation, $replacement );

			// Define some default attributes for this tag.
			$tag_params = $this->get_default_tag_params( $matches[0][ $index ], $tag );

			// If we already have a replacement for this exact tag (i.e. from a previous status message),
			// we don't need to define the replacement again.
			if ( isset( $this->searches_replacements[ $tag_params['tag_with_braces'] ] ) ) {
				continue;
			}

			// Backward compatibility for word, sentence and character limit tags
			// Store them in the tag parameter's transformations array.
			if ( preg_match( '/(.*?)\((.*?)_words\)/', $tag_params['tag'], $word_limit_matches ) ) {
				$tag_params['tag'] = $word_limit_matches[1];
				$transformation    = array(
					'transformation' => 'words',
					'arguments'      => array(
						absint( $word_limit_matches[2] ),
					),
				);
			} elseif ( preg_match( '/(.*?)\((.*?)_sentences\)/', $tag_params['tag'], $sentence_limit_matches ) ) {
				$tag_params['tag'] = $sentence_limit_matches[1];
				$transformation    = array(
					'transformation' => 'sentences',
					'arguments'      => array(
						absint( $sentence_limit_matches[2] ),
					),
				);
			} elseif ( preg_match( '/(.*?)\((.*?)\)/', $tag_params['tag'], $character_limit_matches ) ) {
				$tag_params['tag'] = $character_limit_matches[1];
				$transformation    = array(
					'transformation' => 'characters',
					'arguments'      => array(
						absint( $character_limit_matches[2] ),
					),
				);
			}
			if ( isset( $transformation ) ) {
				if ( is_array( $tag_params['transformations'] ) ) {
					$tag_params['transformations'][] = $transformation;
				} else {
					$tag_params['transformations'] = array( $transformation );
				}
			}

			// If this Tag is a Custom Field, register it now.
			if ( preg_match( '/^custom_field_(.*)$/', $tag_params['tag'], $custom_field_matches ) ) {
				$this->register_post_meta_search_replacement( $tag_params['tag'], $custom_field_matches[1], $post );
			}

			// If this Tag is an Author Field, register it now.
			if ( preg_match( '/^author_field_(.*)$/', $tag_params['tag'], $custom_field_matches ) ) {
				$this->register_author_meta_search_replacement( $tag_params['tag'], $custom_field_matches[1], $author );
			}

			// If this Tag is a Taxonomy Tag, fetch some parameters that may be included in the tag.
			if ( preg_match( '/^taxonomy_(.*)_name$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Name Format.
				$tag_params['tag']                  = 'taxonomy_' . $taxonomy_matches[1];
				$tag_params['taxonomy']             = $taxonomy_matches[1];
				$tag_params['taxonomy_term_format'] = 'name';

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			} elseif ( preg_match( '/^taxonomy_(.*)_hashtag_retain_case$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Hashtag, Retain Case Format.
				$tag_params['tag']                  = 'taxonomy_' . $taxonomy_matches[1];
				$tag_params['taxonomy']             = $taxonomy_matches[1];
				$tag_params['taxonomy_term_format'] = 'hashtag_retain_case';

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			} elseif ( preg_match( '/^taxonomy_(.*)_hashtag_underscore$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Hashtag, Underscore Spaces.
				$tag_params['tag']                  = 'taxonomy_' . $taxonomy_matches[1];
				$tag_params['taxonomy']             = $taxonomy_matches[1];
				$tag_params['taxonomy_term_format'] = 'hashtag_underscore';

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			} elseif ( preg_match( '/^taxonomy_(.*?)$/', $tag_params['tag'], $taxonomy_matches ) ) {
				// Taxonomy with Hashtag Format.
				$tag_params['taxonomy'] = str_replace( 'taxonomy_', '', $tag_params['tag'] );

				if ( $tag_params['transformations'] ) {
					foreach ( $tag_params['transformations'] as $transformation ) {
						if ( ! is_numeric( $transformation['transformation'] ) ) {
							continue;
						}

						$tag_params['taxonomy_term_limit'] = $transformation['transformation'];
						break;
					}
				}
			}

			// Fetch possible tag replacement value.
			$replacement = ( isset( $this->all_possible_searches_replacements[ $tag_params['tag'] ] ) ? $this->all_possible_searches_replacements[ $tag_params['tag'] ] : '' );

			// If this is a taxonomy replacement, replace according to the tag parameters.
			if ( $tag_params['taxonomy'] !== false ) {
				// Define a string to hold the list of terms.
				$term_names = '';

				// Iterate through terms, building string.
				foreach ( $replacement as $term_index => $term ) {
					// If there's a term limit and this term exceeds it, exit the loop.
					if ( $tag_params['taxonomy_term_limit'] > 0 && $term_index + 1 > $tag_params['taxonomy_term_limit'] ) {
						break;
					}

					// Depending on the tag, build the output now.
					switch ( $tag_params['taxonomy_term_format'] ) {
						/**
						 * Name
						 * e.g. Bathroom Installations --> Bathroom Installations
						 */
						case 'name':
							$term_name = $term->name;

							/**
							 * Defines the Taxonomy Term Name to replace the status template tag.
							 *
							 * @since   3.0.0
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( $this->base->plugin->filter_name . '_publish_parse_text_term_name', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;

						/**
						 * Hashtag, retaining case
						 * e.g. Bathroom Installations --> #BathroomInstallations
						 */
						case 'hashtag_retain_case':
							// Decode HTML.
							$term_name = str_replace( ' ', '', html_entity_decode( $term->name ) );

							// Remove anything that isn't alphanumeric or an underscore, to ensure the whole hashtag is linked
							// when posted to social media and not broken by e.g. a full stop.
							$term_name = '#' . preg_replace( '/[^[:alnum:]_]/u', '', $term_name );

							/**
							 * Defines the Taxonomy Term Hashtag to replace the status template tag.
							 *
							 * @since   3.0.0
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( $this->base->plugin->filter_name . '_publish_parse_text_term_hashtag_retain_case', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;

						/**
						 * Hashtag, underscore
						 * e.g. Bathroom Installations --> #bathroom_installations
						 */
						case 'hashtag_underscore':
							// Lowercase and decode HTML.
							$term_name = strtolower( str_replace( ' ', '_', html_entity_decode( $term->name ) ) );

							// Remove anything that isn't alphanumeric or an underscore, to ensure the whole hashtag is linked
							// when posted to social media and not broken by e.g. a full stop.
							$term_name = '#' . preg_replace( '/[^[:alnum:]_]/u', '', $term_name );

							/**
							 * Defines the Taxonomy Term Hashtag to replace the status template tag.
							 *
							 * @since   4.9.5
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( $this->base->plugin->filter_name . '_publish_parse_text_term_hashtag_underscore', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;

						/**
						 * Hashtag
						 * e.g. Bathroom Installations --> #bathroominstallations
						 */
						case 'hashtag':
						default:
							// Lowercase and decode HTML.
							$term_name = strtolower( str_replace( ' ', '', html_entity_decode( $term->name ) ) );

							// Remove anything that isn't alphanumeric or an underscore, to ensure the whole hashtag is linked
							// when posted to social media and not broken by e.g. a full stop.
							$term_name = '#' . preg_replace( '/[^[:alnum:]_]/u', '', $term_name );

							/**
							 * Defines the Taxonomy Term Hashtag to replace the status template tag.
							 *
							 * @since   3.0.0
							 *
							 * @param   string      $term_name                          Term Name.
							 * @param   string      $tag_params['taxonomy_term_format'] Term Format.
							 * @param   WP_Term     $term                               Term.
							 * @param   string      $tag_params['taxonomy']             Taxonomy.
							 * @param   string      $text                               Status Text.
							 */
							$term_name = apply_filters( $this->base->plugin->filter_name . '_publish_parse_text_term_hashtag', $term_name, $tag_params['taxonomy_term_format'], $term, $tag_params['taxonomy'], $text );
							break;
					}

					/**
					 * Backward compat filter to define the Taxonomy Term Name to replace the status template tag.
					 * _publish_parse_text_term_name and _publish_parse_text_term_hashtag should be used instead.
					 *
					 * @since   3.0.0
					 *
					 * @param   string      $term_name                              Term Name.
					 * @param   string      $term->name                             Term Name.
					 * @param   string      $tag_params['taxonomy']                 Taxonomy.
					 * @param   string      $text                                   Status Text.
					 * @param   string      $tag_params['taxonomy_term_format']     Term Format.
					 */
					$term_name = apply_filters( $this->base->plugin->filter_name . '_term', $term_name, $term->name, $tag_params['taxonomy'], $text, $tag_params['taxonomy_term_format'] );

					// Add term to term names string.
					$term_names .= $term_name . ' ';
				}

				// Finally, replace the array of terms with the string of formatted terms.
				$replacement = trim( $term_names );
			}

			// Trim replacement.
			$replacement = trim( $replacement );

			// Apply Transformations.
			if ( $tag_params['transformations'] ) {
				foreach ( $tag_params['transformations'] as $transformation ) {
					$replacement = $this->apply_text_transformation(
						$tag_params['tag'],
						$transformation['transformation'],
						$replacement,
						$transformation['arguments']
					);
				}
			}

			// Add the search and replacement to the array.
			$this->searches_replacements[ $tag_params['tag_with_braces'] ] = $replacement;

		} // Close foreach tag match in text.

		// Search and Replace.
		$text = str_replace( array_keys( $this->searches_replacements ), $this->searches_replacements, $text );

		// Execute any shortcodes in the text now.
		$text = do_shortcode( $text );

		// Convert to plain text.
		$text = $this->convert_to_plain_text( $text );

		/**
		 * Filters the parsed status message text on a status.
		 *
		 * @since   3.0.0
		 *
		 * @param   string      $text                                       Parsed Text, no Tags.
		 * @param   string      $message                                    Unparsed Text with Tags.
		 * @param   array       $this->searches_replacements                Specific Tag Search and Replacements for the given Text.
		 * @param   array       $this->all_possible_searches_replacements   All Registered Tag Search and Replacements.
		 * @param   WP_Post     $post                                       WordPress Post.
		 * @param   WP_User     $author                                     WordPress User (Author).
		 */
		$text = apply_filters( $this->base->plugin->filter_name . '_publish_parse_text', $text, $message, $this->searches_replacements, $this->all_possible_searches_replacements, $post, $author );

		return $text;

	}

	/**
	 * Parses the status' Google Business configuration to return an array of compatible
	 * arguments that can be used to send the status.
	 *
	 * @since   4.9.0
	 *
	 * @param   WP_Post $post               Post.
	 * @param   array   $status             Status.
	 * @return  bool|array                  Google Business Profile status configuration
	 */
	public function parse_google_business( $post, $status ) {

		// Bail if no Google Business configuration exists in the status.
		if ( ! isset( $status['googlebusiness'] ) ) {
			return false;
		}
		if ( ! is_array( $status['googlebusiness'] ) ) {
			return false;
		}
		if ( ! isset( $status['googlebusiness']['post_type'] ) ) {
			return false;
		}

		// Start building arguments.
		$google_business_args = array(
			'post_type' => $status['googlebusiness']['post_type'],
			'link'      => $this->get_permalink( $post ),
		);

		// Depending on the Google Business Post Type, build arguments.
		switch ( $status['googlebusiness']['post_type'] ) {
			case 'offer':
			case 'event':
				// Title.
				$google_business_args['title'] = $this->parse_text( $post, $status['googlebusiness']['title'] );

				// Code and Terms: Offers.
				if ( $status['googlebusiness']['post_type'] === 'offer' ) {
					$google_business_args = array_merge(
						$google_business_args,
						array(
							'code'  => $this->parse_text( $post, $status['googlebusiness']['code'] ),
							'terms' => $this->parse_text( $post, $status['googlebusiness']['terms'] ),
						)
					);
				} else {
					// Event: Button.
					$google_business_args['cta'] = $status['googlebusiness']['cta'];
				}

				// Start Date.
				switch ( $status['googlebusiness']['start_date_option'] ) {
					/**
					 * Custom Post Meta
					 */
					case 'custom':
						// Fetch the Post's Meta Value based on the given Custom Field Key.
						$date = get_post_meta( $post->ID, $status['googlebusiness']['start_date'], true );

						// If the post date is numeric, it's most likely a timestamp
						// Convert it to a date and time.
						if ( is_numeric( $date ) ) {
							$date = date( 'Y-m-d H:i:s', $date ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						}

						// Set start date.
						$google_business_args['start_date'] = strtotime( $date );
						$google_business_args['start_time'] = date( 'H:i', strtotime( $date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						break;

					/**
					 * None
					 */
					case '':
						break;

					/**
					 * Third Party integrations
					 */
					default:
						$date = false;

						/**
						 * Allows integrations to define the status' start date for a Google Business Profile Offer or Event.
						 *
						 * @since   4.9.0
						 *
						 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
						 * @param   array       $google_business_args   Google Business specific arguments for status.
						 * @param   array       $status                 Status.
						 * @param   WP_Post     $post                   WordPress Post.
						 */
						$date = apply_filters( $this->base->plugin->filter_name . '_publish_parse_google_business_start_date_' . $status['googlebusiness']['start_date_option'], $date, $google_business_args, $status, $post );

						// Ignore if no date defined.
						if ( ! $date ) {
							break;
						}

						// Set start date.
						$google_business_args['start_date'] = strtotime( $date );
						$google_business_args['start_time'] = date( 'H:i', strtotime( $date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						break;
				}

				// End Date.
				switch ( $status['googlebusiness']['end_date_option'] ) {
					/**
					 * Custom Post Meta
					 */
					case 'custom':
						// Fetch the Post's Meta Value based on the given Custom Field Key.
						$date = get_post_meta( $post->ID, $status['googlebusiness']['end_date'], true );

						// If the post date is numeric, it's most likely a timestamp
						// Convert it to a date and time.
						if ( is_numeric( $date ) ) {
							$date = date( 'Y-m-d H:i:s', $date ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						}

						// Set end date.
						$google_business_args['end_date'] = strtotime( $date );
						$google_business_args['end_time'] = date( 'H:i', strtotime( $date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						break;

					/**
					 * None
					 */
					case '':
						break;

					/**
					 * Third Party integrations
					 */
					default:
						$date = false;

						/**
						 * Allows integrations to define the status' end date for a Google Business Profile Offer or Event.
						 *
						 * @since   4.9.0
						 *
						 * @param   bool|string $date                   Date (yyyy-mm-dd hh:mm:ss format).
						 * @param   array       $google_business_args   Google Business specific arguments for status.
						 * @param   array       $status                 Status.
						 * @param   WP_Post     $post                   WordPress Post.
						 */
						$date = apply_filters( $this->base->plugin->filter_name . '_publish_parse_google_business_end_date_' . $status['googlebusiness']['end_date_option'], $date, $google_business_args, $status, $post );

						// Ignore if no date defined.
						if ( ! $date ) {
							break;
						}

						// Set end date.
						$google_business_args['end_date'] = strtotime( $date );
						$google_business_args['end_time'] = date( 'H:i', strtotime( $date ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
						break;
				}
				break;

			case 'whats_new':
			default:
				$google_business_args['cta'] = $status['googlebusiness']['cta'];
				break;
		}

		return $google_business_args;

	}

	/**
	 * Returns default tag parameters for the given tag e.g. {title:transformation(args)} or {title}.
	 *
	 * @since   4.5.9
	 *
	 * @param   string $tag_with_braces    Tag with Braces e.g. {title:transformation(args)} or {title}.
	 * @param   string $tag                Tag without Braces e.g. title:transformation(args) or title.
	 * @return  array                       Tag Parameters
	 * */
	private function get_default_tag_params( $tag_with_braces, $tag ) {

		// Define array of tag parameters to be populated.
		$tag_params = array(
			'tag_with_braces'      => $tag_with_braces,    // Original tag with braces, including transformations.
			'tag'                  => $tag,                // No braces, no transformations.
			'transformations'      => false,
			'taxonomy'             => false,
			'taxonomy_term_limit'  => false,
			'taxonomy_term_format' => false,
		);

		// If no transformations exist, return.
		if ( strpos( $tag, ':' ) === false ) {
			return $tag_params;
		}

		// Extract transformations.
		$tag_params['transformations'] = explode( ':', substr( $tag_params['tag'], strpos( $tag_params['tag'], ':' ) + 1 ) );

		// Remove transformations from tag.
		$tag_params['tag'] = substr( $tag_params['tag'], 0, strpos( $tag_params['tag'], ':' ) );

		// Iterate through transformations to see if arguments are attached.
		foreach ( $tag_params['transformations'] as $index => $transformation ) {
			// If no arguments exist for this transformation, update the array structure and continue.
			if ( strpos( $transformation, '(' ) === false ) {
				$tag_params['transformations'][ $index ] = array(
					'transformation' => $transformation,
					'arguments'      => false,
				);
				continue;
			}

			// Extract arguments.
			$arguments = explode( '(', substr( $transformation, strpos( $transformation, '(' ) + 1 ) );
			foreach ( $arguments as $a_index => $argument ) {
				$arguments[ $a_index ] = str_replace( ')', '', $argument );
			}

			// Remove arguments from transformation.
			$transformation = substr( $transformation, 0, strpos( $transformation, '(' ) );

			// Update array structure.
			$tag_params['transformations'][ $index ] = array(
				'transformation' => $transformation,
				'arguments'      => $arguments,
			);
		}

		// Return.
		return $tag_params;

	}

	/**
	 * Applies a transformation to the given value
	 *
	 * @since   4.5.8
	 *
	 * @param   string $tag                        Tag e.g. title, date.
	 * @param   string $transformation             Transformation.
	 * @param   string $value                      Value.
	 * @param   mixed  $transformation_arguments   false | array of arguments to apply to the transformation e.g. character limit, date format.
	 * @return  string                              Transformed Value
	 */
	private function apply_text_transformation( $tag, $transformation, $value, $transformation_arguments = false ) {

		switch ( $transformation ) {
			/**
			 * Uppercase
			 */
			case 'uppercase_all':
			case 'uppercase':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_convert_case' ) ) {
					return mb_convert_case( $value, MB_CASE_UPPER );
				}

				// Fallback to basic version which doesn't support i18n.
				return strtoupper( $value );

			/**
			 * Lowercase
			 */
			case 'lowercase_all':
			case 'lowercase':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_convert_case' ) ) {
					return mb_convert_case( $value, MB_CASE_LOWER );
				}

				// Fallback to basic version which doesn't support i18n.
				return strtolower( $value );

			/**
			 * Upperchase first character
			 */
			case 'uppercase_first_character':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_strtoupper' ) ) {
					return mb_strtoupper( mb_substr( $value, 0, 1 ) ) . mb_substr( $value, 1 );
				}

				// Fallback to basic version which doesn't support i18n.
				return ucfirst( $value );

			/**
			 * Uppercase first character of each word
			 */
			case 'uppercase_first_character_words':
				// Use i18n compatible method if available.
				if ( function_exists( 'mb_convert_case' ) ) {
					return mb_convert_case( $value, MB_CASE_TITLE );
				}

				// Fallback to basic version which doesn't support i18n.
				return ucwords( $value );

			/**
			 * First Word
			 */
			case 'first_word':
				$term_parts = explode( ' ', $value );
				return $term_parts[0];

			/**
			 * Last Word
			 */
			case 'last_word':
				$term_parts = explode( ' ', $value );
				return $term_parts[ count( $term_parts ) - 1 ];

			/**
			 * URL
			 */
			case 'url':
				return sanitize_title( $value );

			/**
			 * URL, Underscore
			 */
			case 'url_underscore':
				return str_replace( '-', '_', sanitize_title( $value ) );

			/**
			 * URL, Encode to RFC 3986
			 */
			case 'url_encode':
				return rawurlencode( $value );

			/**
			 * Date
			 */
			case 'date':
				// Don't attempt to format the date if no format is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				// Don't attempt to format the date if the value isn't a date/time.
				$timestamp = strtotime( $value );
				if ( $timestamp === false ) {
					return $value;
				}

				return date_i18n( $transformation_arguments[0], $timestamp );

			/**
			 * Word Limit
			 */
			case 'words':
				// Don't attempt to apply limit if the tag doesn't support it.
				if ( ! $this->can_apply_character_limit_to_tag( $tag ) ) {
					return $value;
				}

				// Don't attempt to apply limit if no limit is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				return $this->apply_word_limit( $value, $transformation_arguments[0] );

			/**
			 * Sentence Limit
			 */
			case 'sentences':
				// Don't attempt to apply limit if the tag doesn't support it.
				if ( ! $this->can_apply_character_limit_to_tag( $tag ) ) {
					return $value;
				}

				// Don't attempt to apply limit if no limit is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				return $this->apply_sentence_limit( $value, $transformation_arguments[0] );

			/**
			 * Character Limit
			 */
			case 'characters':
				// Don't attempt to apply limit if the tag doesn't support it.
				if ( ! $this->can_apply_character_limit_to_tag( $tag ) ) {
					return $value;
				}

				// Don't attempt to apply limit if no limit is given.
				if ( ! $transformation_arguments ) {
					return $value;
				}

				return $this->apply_character_limit( $value, $transformation_arguments[0] );

			/**
			 * Other Transformations
			 */
			default:
				/**
				 * Applies the given transformation to the given value
				 *
				 * @since   4.5.8
				 *
				 * @param   string  $value              Value.
				 * @param   string  $transformation     Transformation.
				 */
				$value = apply_filters( $this->base->plugin->filter_name . '_publish_apply_text_transformation', $value, $transformation );

				return $value;
		}

	}

	/**
	 * Parses profile mentions, adding entities arguments to the $args array and
	 * updating the text that's sent to the API
	 *
	 * @since   4.5.6
	 *
	 * @param   array   $args                       API standardised arguments.
	 * @param   WP_Post $post                       WordPress Post.
	 * @param   string  $profile_id                 Social Media Profile ID.
	 * @param   string  $service                    Social Media Service.
	 * @param   array   $status                     Parsed Status Message Settings.
	 * @param   string  $action                     Action (publish|update|repost|bulk_publish).
	 * @return  array                               Status Arguments
	 */
	private function process_profile_mentions( $args, $post, $profile_id, $service, $status, $action ) {

		// Bail if Facebook Mentions aren't supported.
		if ( ! $this->base->supports( 'facebook_mentions' ) ) {
			return $args;
		}

		// Bail if this isn't a service we need to process/link profile mentions for.
		if ( $service !== 'facebook' ) {
			return $args;
		}

		// Find all patterns in the form @string[number] e.g. @WP Zinc[123456789].
		preg_match_all( '/@(.*?)\[(.*?)\]/', $args['text'], $matches );

		// Bail if no matches found.
		if ( empty( $matches[0] ) ) {
			return $args;
		}

		switch ( $status['image'] ) {

			/**
			 * Use Feat. Image, Linked to Post
			 * Use Text to Image, Linked to Post
			 * - Remove Post URL from the status text, so Buffer doesn't process the URL
			 * and break the indicies
			 */
			case 1:
			case 3:
				$args['text'] = str_replace( $this->get_permalink( $post ), '', $args['text'] );
				break;

		}

		// Iterate through each match, building the entities array and fb_text arguments.
		$args['entities'] = array();
		while ( ! empty( $matches[0] ) ) {
			// Newlines must be removed.
			$text_without_newlines = str_replace( "\n", '', $args['text'] );

			// Process the first mention.
			$mention_full            = $matches[0][0];
			$mention_name            = $matches[1][0]; // e.g. @WP Zinc.
			$mention_name_without_at = str_replace( '@', '', $mention_name ); // e.g. WP Zinc.
			$mention_id              = $matches[2][0]; // e.g. 123456789.

			// Get the start and end position of the full mention.
			$mention_start_pos = strpos( $text_without_newlines, $mention_full );
			$mention_end_pos   = $mention_start_pos + strlen( $mention_name_without_at );

			// Add mention to entities array.
			$args['entities'][] = array(
				'indices' => array( $mention_start_pos, $mention_end_pos ),
				'content' => (string) $mention_id,
				'text'    => $mention_name_without_at,
				'url'     => 'https://www.' . $service . '.com/' . $mention_id,
			);

			// Replace the full mention with just the mention name, excluding @.
			$args['text'] = str_replace( $mention_full, $mention_name_without_at, $args['text'] );

			// Find remaining patterns in the form @string[number] e.g. @WP Zinc[123456789].
			// We do this because the indices will now have changed, because the previous mention will have the @ and [id] removed.
			preg_match_all( '/@(.*?)\[(.*?)\]/', $args['text'], $matches );
		}

		// If no entities exist, bail.
		if ( ! count( $args['entities'] ) ) {
			unset( $args['entities'] );
			return $args;
		}

		// Link shortening must be disabled, otherwise our indicies will be incorrect when a long link is changed to e.g.
		// http://buff.ly/2k2vfeo by Buffer when this status is added.
		$args['shorten'] = 'false';

		// Trim the text.
		$args['text'] = trim( $args['text'] );

		// Add fb_text argument, which is a copy of the text argument.
		// This is required for Buffer to work and link the entities' text to the FB profiles.
		$args['fb_text'] = $args['text'];

		/**
		 * Parses profile mentions, adding entities arguments to the $args array and
		 * updating the text that's sent to the API
		 *
		 * @since   4.5.6
		 *
		 * @param   array   $args                       API standardised arguments.
		 * @param   WP_Post $post                       WordPress Post.
		 * @param   string  $profile_id                 Social Media Profile ID.
		 * @param   string  $service                    Social Media Service.
		 * @param   array   $status                     Parsed Status Message Settings.
		 * @param   string  $action                     Action (publish|update|repost|bulk_publish).
		 */
		$args = apply_filters( $this->base->plugin->filter_name . '_process_profile_mentions', $args, $post, $profile_id, $service, $status, $action );

		// Return.
		return $args;

	}

	/**
	 * Returns an array comprising of all supported tags and their Post / Author / Taxonomy data replacements.
	 *
	 * @since   3.7.8
	 *
	 * @param   WP_Post $post       WordPress Post.
	 * @param   WP_User $author     WordPress User (Author of the Post).
	 * @return  array                   Search / Replacement Key / Value pairs
	 */
	private function register_all_possible_searches_replacements( $post, $author ) {

		// Start with no searches or replacements.
		$searches_replacements = array();

		// Register Post Tags and Replacements.
		$searches_replacements = $this->register_post_searches_replacements( $searches_replacements, $post );

		// Register Post Author Tags and Replacements.
		$searches_replacements = $this->register_author_searches_replacements( $searches_replacements, $author );

		// Register Taxonomy Tags and Replacements.
		// Add Taxonomies.
		$taxonomies = get_object_taxonomies( $post->post_type, 'names' );
		if ( count( $taxonomies ) > 0 ) {
			$searches_replacements = $this->register_taxonomy_searches_replacements( $searches_replacements, $post, $taxonomies );
		}

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported.
		 *
		 * @since   3.7.8
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_get_all_possible_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Registers status message tags and their data replacements for the given Post.
	 *
	 * @since   3.7.8
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @return  array                           Registered Supported Tags and their Replacements
	 */
	private function register_post_searches_replacements( $searches_replacements, $post ) {

		// Check Plugin Settings to see if the excerpt should fallback to the content if no
		// Excerpt defined.
		$excerpt_fallback = ( $this->base->get_class( 'settings' )->get_option( 'disable_excerpt_fallback', false ) ? false : true );

		$searches_replacements['sitename']         = get_bloginfo( 'name' );
		$searches_replacements['title']            = $this->get_title( $post );
		$searches_replacements['excerpt']          = $this->get_excerpt( $post, $excerpt_fallback );
		$searches_replacements['content']          = $this->get_content( $post );
		$searches_replacements['content_more_tag'] = $this->get_content( $post, true );
		$searches_replacements['date']             = $this->get_date( $post );
		$searches_replacements['url']              = $this->get_permalink( $post );
		$searches_replacements['url_short']        = $this->get_short_permalink( $post );
		$searches_replacements['id']               = absint( $post->ID );

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for the given Post.
		 *
		 * @since   3.7.8
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_post_searches_replacements', $searches_replacements, $post );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Registers status message tags and their data replacements for the given Post Author.
	 *
	 * @since   3.7.8
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_User $author                 WordPress Author.
	 * @return  array                           Registered Supported Tags and their Replacements
	 */
	private function register_author_searches_replacements( $searches_replacements, $author ) {

		// If author isn't specified, return blank replacements.
		if ( ! $author ) {
			$searches_replacements['author']               = '';
			$searches_replacements['author_user_login']    = '';
			$searches_replacements['author_user_nicename'] = '';
			$searches_replacements['author_user_email']    = '';
			$searches_replacements['author_user_url']      = '';
			$searches_replacements['author_display_name']  = '';
		} else {
			$searches_replacements['author']               = $author->display_name;
			$searches_replacements['author_user_login']    = $author->user_login;
			$searches_replacements['author_user_nicename'] = $author->user_nicename;
			$searches_replacements['author_user_email']    = $author->user_email;
			$searches_replacements['author_user_url']      = $author->user_url;
			$searches_replacements['author_display_name']  = $author->display_name;
		}

		/**
		 * Registers any additional status message tags, and their Author data replacements, that are supported
		 * for the given Post Author.
		 *
		 * @since   3.7.8
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_User     $author                 WordPress Post Author.
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_author_searches_replacements', $searches_replacements, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Registers status message tags and their data replacements for the given Post Taxonomies.
	 *
	 * @since   3.7.8
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   array   $taxonomies             Post Taxonomies.
	 * @return  array   $searches_replacements  Registered Supported Tags and their Replacements.
	 */
	private function register_taxonomy_searches_replacements( $searches_replacements, $post, $taxonomies ) {

		foreach ( $taxonomies as $taxonomy ) {
			$searches_replacements[ 'taxonomy_' . $taxonomy ] = wp_get_post_terms( $post->ID, $taxonomy );
		}

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for the given Post.
		 *
		 * @since   3.7.8
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   array       $taxonomies             Post Taxonomies.
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_post_searches_replacements', $searches_replacements, $post, $taxonomies );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Adds a search and replacement to the existing array of possible searches
	 * and replacements for Post Meta / Custom Field.
	 *
	 * @since   3.7.8
	 *
	 * @param   string  $tag        Tag.
	 * @param   string  $meta_key   Meta Key.
	 * @param   WP_Post $post       WordPress Post.
	 */
	private function register_post_meta_search_replacement( $tag, $meta_key, $post ) {

		// Bail if the search / replacement already exists.
		if ( isset( $this->all_possible_searches_replacements[ $tag ] ) ) {
			return;
		}

		// Extract just the meta key, in case the tag included square brackets to fetch
		// the post meta array value.
		$meta_key_only = ( strpos( $meta_key, '[' ) !== false ? substr( $meta_key, 0, strpos( $meta_key, '[' ) ) : $meta_key );

		// Fetch post meta.
		$value = get_post_meta( $post->ID, $meta_key_only, true );

		// If the meta value is a string, add it to the search/replace array and return.
		if ( is_string( $value ) ) {
			// If JSON doesn't validate, it's just a string.
			if ( is_null( json_decode( $value ) ) ) {
				$this->all_possible_searches_replacements[ $tag ] = $value;
				return;
			}

			// Convert value from JSON string to array.
			$value = json_decode( $value, true );
		}

		// $value is an array.
		// Extract the string from the array and register it as the replacement for the tag.
		$this->all_possible_searches_replacements[ $tag ] = $this->get_array_value_by_query_string( $meta_key, $value );

	}

	/**
	 * Returns the given array value as a string, by the query string.
	 *
	 * If the value of the full array hierarchy of keys isn't a string,
	 * nothing will be retu
	 *
	 * @since   5.1.3
	 *
	 * @param   string $query_string   Query string (e.g. my-meta-key[key][sub-key]).
	 * @param   array  $value          Array.
	 * @return  string
	 */
	private function get_array_value_by_query_string( $query_string, $value ) {

		// Extract the array keys e.g. my-meta-key[key][another-key].
		preg_match_all( '/\[([^\]]*)\]/', $query_string, $matches );

		// Iterate through the requested array key hierarchy.
		foreach ( $matches[1] as $key ) {
			// If the meta value is an object, convert it to an array.
			if ( is_object( $value ) ) {
				$value = json_decode( json_encode( $value ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			}

			// If this key does not exist in the post meta array, bail.
			if ( ! array_key_exists( $key, $value ) ) {
				return '';
			}

			// Update the value.
			$value = $value[ $key ];
		}

		// If the 'final' value is still an array, bail.
		if ( is_array( $value ) ) {
			return '';
		}

		// Return string.
		return $value;

	}

	/**
	 * Adds a search and replacement to the existing array of possible searches
	 * and replacements for Author Meta / Custom Field.
	 *
	 * @since   3.7.8
	 *
	 * @param   string  $tag        Tag.
	 * @param   string  $meta_key   Meta Key.
	 * @param   WP_User $user       WordPress User.
	 */
	private function register_author_meta_search_replacement( $tag, $meta_key, $user ) {

		// Bail if the search / replacement already exists.
		if ( isset( $this->all_possible_searches_replacements[ $tag ] ) ) {
			return;
		}

		$this->all_possible_searches_replacements[ $tag ] = get_user_meta( $user->ID, $meta_key, true );

	}

	/**
	 * Safely generate a title, stripping tags and shortcodes, and applying filters so that
	 * third party plugins (such as translation plugins) can determine the final output.
	 *
	 * @since   3.7.3
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @return  string                          Title
	 */
	private function get_title( $post ) {

		// Define title.
		$title = $this->convert_to_plain_text( get_the_title( $post ), false );

		/**
		 * Filters the dynamic {title} replacement, when a Post's status is being built.
		 *
		 * @since   3.7.3
		 *
		 * @param   string      $title      Post Title.
		 * @param   WP_Post     $post       WordPress Post.
		 */
		$title = apply_filters( $this->base->plugin->filter_name . '_publish_get_title', $title, $post );

		// Return.
		return $title;

	}

	/**
	 * Safely generate an excerpt, stripping tags, shortcodes, falling back
	 * to the content if the Post Type doesn't have excerpt support, and applying filters so that
	 * third party plugins (such as translation plugins) can determine the final output.
	 *
	 * @since   3.7.3
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @param   bool    $fallback           Use Content if no Excerpt exists.
	 * @return  string                          Excerpt
	 */
	private function get_excerpt( $post, $fallback = true ) {

		// Fetch excerpt.
		if ( empty( $post->post_excerpt ) ) {
			if ( $fallback ) {
				$excerpt = $post->post_content;
			} else {
				$excerpt = $post->post_excerpt;
			}
		} else {
			// Remove some third party Plugin filters that wrongly output content that we don't want in a status.
			remove_filter( 'get_the_excerpt', 'powerpress_content' );

			$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );
		}

		// Convert to plain text.
		$excerpt = $this->convert_to_plain_text( $excerpt, false );

		/**
		 * Filters the dynamic {excerpt} replacement, when a Post's status is being built.
		 *
		 * @since   3.7.3
		 *
		 * @param   string      $excerpt    Post Excerpt.
		 * @param   WP_Post     $post       WordPress Post.
		 */
		$excerpt = apply_filters( $this->base->plugin->filter_name . '_publish_get_excerpt', $excerpt, $post );

		// Return.
		return $excerpt;

	}

	/**
	 * Safely generate a title, stripping tags and shortcodes, and applying filters so that
	 * third party plugins (such as translation plugins) can determine the final output.
	 *
	 * @since   3.7.3
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @param   bool    $to_more_tag        Only return content up to the <!-- more --> tag.
	 * @return  string                          Content
	 */
	private function get_content( $post, $to_more_tag = false ) {

		// Fetch content.
		// get_the_content() only works for WordPress 5.2+, which added the $post param.
		if ( $to_more_tag ) {
			$extended = get_extended( $post->post_content );

			if ( isset( $extended['main'] ) && ! empty( $extended['main'] ) ) {
				$content = $extended['main'];
			} else {
				// Fallback to the Post Content.
				$content = $post->post_content;
			}
		} else {
			$content = $post->post_content;
		}

		// Strip shortcodes.
		$content = strip_shortcodes( $content );

		// Remove the wpautop filter, as this converts double newlines into <p> tags.
		// In turn, <p> tags are correctly discarded later on in this function, as social networks don't support HTML.
		// However, this results in separation between paragraphs going from two newlines to one newline.
		// Some social media services further drop a single newline, meaning paragraphs become one long block of text, which isn't
		// intended.
		remove_filter( 'the_content', 'wpautop' );

		// Remove some third party Plugin filters that wrongly output content that we don't want in a status.
		remove_filter( 'the_content', 'powerpress_content' );

		// Apply filters to get true output.
		$content = apply_filters( 'the_content', $content );

		// Restore wpautop that we just removed.
		add_filter( 'the_content', 'wpautop' );

		// If the content originates from Gutenberg, remove double newlines and convert breaklines
		// into newlines.
		$is_gutenberg_request_content = $this->is_gutenberg_post_content( $post );
		if ( $is_gutenberg_request_content ) {
			// Remove double newlines, which may occur due to using Gutenberg blocks.
			// (blocks are separated with HTML comments, stripped using apply_filters( 'the_content' ), which results in double, or even triple, breaklines).
			$content = preg_replace( '/(?:(?:\r\n|\r|\n)\s*){2}/s', "\n\n", $content );

			// Convert <br> and <br /> into newlines.
			$content = preg_replace( '/<br(\s+)?\/?>/i', "\n", $content );
		}

		// Convert to plain text.
		$content = $this->convert_to_plain_text( $content );

		/**
		 * Filters the dynamic {content} replacement, when a Post's status is being built.
		 *
		 * @since   3.7.3
		 *
		 * @param   string      $content                    Post Content.
		 * @param   WP_Post     $post                       WordPress Post.
		 * @param   bool        $is_gutenberg_request_content  Is Gutenberg Post Content.
		 */
		$content = apply_filters( $this->base->plugin->filter_name . '_publish_get_content', $content, $post, $is_gutenberg_request_content );

		// Return.
		return $content;

	}

	/**
	 * Returns the date in the locale specified in WordPress.
	 *
	 * @since   4.7.7
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              Date
	 */
	private function get_date( $post ) {

		$date = date_i18n( get_option( 'date_format' ), strtotime( $post->post_date ) );

		/*
		 * Filters the dynamic {date} replacement, when a Post's status is being built.
		 *
		 * @since   4.7.7
		 *
		 * @param   string      $date                       Date.
		 * @param   WP_Post     $post                       WordPress Post.
		 */
		$date = apply_filters( $this->base->plugin->filter_name . '_publish_get_date', $date, $post );

		// Return.
		return $date;

	}

	/**
	 * Returns the Permalink, including or excluding a trailing slash, depending on the Plugin settings.
	 *
	 * @since   4.0.6
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @return  string                          WordPress Post Permalink
	 */
	private function get_permalink( $post ) {

		$force_trailing_forwardslash = $this->base->get_class( 'settings' )->get_option( 'force_trailing_forwardslash', false );

		// Define the URL, depending on whether it should end with a forwardslash or not.
		// This is by design; more users complain that they get 301 redirects from site.com/post/ to site.com/post
		// than from site.com/post to site.com/post/.
		// We can't control misconfigured WordPress installs, so this option gives them the choice.
		if ( $force_trailing_forwardslash ) {
			$url = get_permalink( $post->ID );

			// If the Permalink doesn't have a forwardslash at the end of it, add it now.
			if ( substr( $url, -1 ) !== '/' ) {
				$url .= '/';
			}
		} else {
			$url = rtrim( get_permalink( $post->ID ), '/' );
		}

		/**
		 * Filters the Post's Permalink, including or excluding a trailing slash, depending on the Plugin settings
		 *
		 * @since   4.0.6
		 *
		 * @param   string      $url                            WordPress Post Permalink.
		 * @param   WP_Post     $post                           WordPress Post.
		 * @param   bool        $force_trailing_forwardslash    Force Trailing Forwardslash.
		 */
		$url = apply_filters( $this->base->plugin->filter_name . '_publish_get_permalink', $url, $post, $force_trailing_forwardslash );

		// Return.
		return $url;

	}

	/**
	 * Returns the Short Permalink
	 *
	 * @since   4.2.7
	 *
	 * @param   WP_Post $post               WordPress Post.
	 * @return  string                          WordPress Post Permalink
	 */
	private function get_short_permalink( $post ) {

		// Define short permalink e.g http://yoursite.com/?p=1.
		$url = rtrim( get_bloginfo( 'url' ), '/' ) . '/?p=' . $post->ID;

		/**
		 * Filters the Post's Permalink, including or excluding a trailing slash, depending on the Plugin settings
		 *
		 * @since   4.2.7
		 *
		 * @param   string      $url                            WordPress Post Permalink.
		 * @param   WP_Post     $post                           WordPress Post.
		 */
		$url = apply_filters( $this->base->plugin->filter_name . '_publish_get_short_permalink', $url, $post );

		// Return.
		return $url;

	}

	/**
	 * Converts the given string (which is typically HTML from a WordPress Post or Post Meta Field)
	 * to plain text, by performing several functions:
	 * - stripping shortcodes (if shortcodes need processing, do so before calling this function)
	 * - removing all inline <style> elements and their contents,
	 * - stripping HTML tags, excluding <br>, <br />, <a>, <li>
	 * - decoding HTML entities to avoid encoding issues on status output
	 * - converting <br> and <br /> to newlines
	 * - removing double spaces
	 * - trimming the final result of any leading or trailing spaces
	 *
	 * @since   4.6.9
	 *
	 * @param   string $text                           Text.
	 * @param   bool   $convert_links_to_inline        true: Convert e.g. `<a href="http://foo.com">text</a>` to `text (http://foo.com)`.
	 *                                                 false: Convert e.g. `<a href="http://foo.com">text</a>` to `text`.
	 * @return  string                                      Text
	 */
	private function convert_to_plain_text( $text, $convert_links_to_inline = true ) {

		// Strip any shortcodes still remaining.
		// If shortcodes need to be processed, they should be processed before calling this function.
		$text = strip_shortcodes( $text );

		// Wrap content in <html>, <head> and <body> tags with an UTF-8 Content-Type meta tag.
		// Forcibly tell DOMDocument that this HTML uses the UTF-8 charset.
		// <meta charset="utf-8"> isn't enough, as DOMDocument still interprets the HTML as ISO-8859, which breaks character encoding
		// Use of mb_convert_encoding() with HTML-ENTITIES is deprecated in PHP 8.2, so we have to use this method.
		// If we don't, special characters render incorrectly.
		$text = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $text . '</body></html>';

		// Load the HTML into a DOMDocument.
		libxml_use_internal_errors( true );
		$html = new DOMDocument();
		$html->loadHTML( $text );

		// Load DOMDocument into XPath.
		$xpath = new DOMXPath( $html );

		// Remove inline <style> tags and their contents.
		foreach ( $xpath->query( '//style' ) as $node ) {
			$node->parentNode->removeChild( $node ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}

		// Fetch revised HTML.
		$text = $html->saveHTML();

		// Remove HTML, except breaklines, links and unordered list items.
		$text = strip_tags( $text, '<br><a><li>' );

		// Decode excerpt to avoid encoding issues on status output.
		$text = html_entity_decode( $text );

		// Convert <br> and <br /> into newlines.
		$text = preg_replace( '/<br(\s+)?\/?>/i', "\n", $text );

		// Convert <a> to text and inline link.
		if ( $convert_links_to_inline ) {
			// Extract the text from the link, and add the link in brackets after the text.
			$text = preg_replace( '/<a[^>]+href=\"(.*?)\"[^>]*>(.*?)<\/a>/i', '$2 ($1)', $text );
		} else {
			// Just extract the text from the link and output it.
			$text = preg_replace( '/<a[^>]+href=\"(.*?)\"[^>]*>(.*?)<\/a>/i', '$2', $text );
		}

		// Convert <li> to hyphenated.
		$text = preg_replace( '/<li[^>]*>(.*?)<\/li>/i', '- $1', $text );

		// Remove double spaces, but retain newlines and accented characters.
		$text = preg_replace( '/[ ]{2,}/', ' ', $text );

		// Remove tabs.
		$text = str_replace( "\t", '', $text );

		// Finally, trim the text.
		$text = trim( $text );

		// Return.
		return $text;

	}

	/**
	 * Returns a flag denoting whether a character limit can safely be applied
	 * to the given tag.
	 *
	 * @since   3.7.8
	 *
	 * @param   string $tag    Tag.
	 * @return  bool            Can apply character limit
	 */
	private function can_apply_character_limit_to_tag( $tag ) {

		// Get Tags.
		$tags = $this->base->get_class( 'common' )->get_tags_excluded_from_character_limit();

		// If the tag is in the array of tags excluded from character limits, we
		// cannot apply a character limit to this tag.
		if ( in_array( $tag, $tags, true ) ) {
			return false;
		}

		// Can apply character limit to tag.
		return true;

	}

	/**
	 * Applies the given word limit to the given text
	 *
	 * @since   3.8.9
	 *
	 * @param   string $text          Text.
	 * @param   int    $word_limit    Word Limit.
	 * @return  string                 Text
	 */
	private function apply_word_limit( $text, $word_limit = 0 ) {

		// Store original text.
		$original_text = $text;

		// Bail if the word limit is zero or false.
		if ( ! $word_limit || $word_limit === 0 ) {
			return $text;
		}

		// Limit text.
		$text = wp_trim_words( $text, $word_limit, '' );

		/**
		 * Applies the given word limit to the given text.
		 *
		 * @since   3.8.9
		 *
		 * @param   string      $text               Text, with word limit applied.
		 * @param   int         $word_limit         Sentence Limit.
		 * @param   string      $original_text      Original Text, with no limit applied.
		 */
		$text = apply_filters( $this->base->plugin->filter_name . '_publish_apply_word_limit', $text, $word_limit, $original_text );

		return $text;

	}

	/**
	 * Applies the given sentence limit to the given text
	 *
	 * @since   4.3.1
	 *
	 * @param   string $text            Text.
	 * @param   int    $sentence_limit  Sentence Limit.
	 * @return  string                  Text
	 */
	public function apply_sentence_limit( $text, $sentence_limit = 0 ) {

		// Store original text.
		$original_text = $text;

		// Bail if the sentence limit is zero or false.
		if ( ! $sentence_limit || $sentence_limit === 0 ) {
			return $text;
		}

		// Define end of sentence delimiters.
		$stops = array(
			'. ',
			'! ',
			'? ',
			'...',
		);

		// Build array of sentences.
		$sentences = preg_split( '/(?<=[.?!])\s+(?=[a-z])/i', $text, -1, PREG_SPLIT_DELIM_CAPTURE );

		// Implode into text.
		$text = implode( ' ', array_slice( $sentences, 0, $sentence_limit ) );

		/**
		 * Applies the given sentence limit to the given text.
		 *
		 * @since   4.3.1
		 *
		 * @param   string      $text               Text, with word limit applied.
		 * @param   int         $sentence_limit     Sentence Limit.
		 * @param   string      $original_text      Original Text, with no limit applied.
		 */
		$text = apply_filters( $this->base->plugin->filter_name . '_publish_apply_sentence_limit', $text, $sentence_limit, $original_text );

		// Return.
		return $text;

	}

	/**
	 * Applies the given character limit to the given text
	 *
	 * @sine    3.7.3
	 *
	 * @param   string $text               Text.
	 * @param   int    $character_limit    Character Limit.
	 * @return  string                      Text
	 */
	private function apply_character_limit( $text, $character_limit = 0 ) {

		// Bail if the character limit is zero or false.
		if ( ! $character_limit || $character_limit === 0 ) {
			return $text;
		}

		// Bail if the content isn't longer than the character limit.
		if ( strlen( $text ) <= $character_limit ) {
			return $text;
		}

		// Limit text.
		$text = substr( $text, 0, $character_limit );

		/**
		 * Filters the character limited text.
		 *
		 * @since   3.7.3
		 *
		 * @param   string      $text               Text, with character limit applied.
		 * @param   int         $character_limit    Character Limit used.
		 */
		$text = apply_filters( $this->base->plugin->filter_name . '_publish_apply_character_limit', $text, $character_limit );

		// Return.
		return $text;

	}

	/**
	 * Helper method to iterate through statuses, sending each via a separate API call
	 * to the API.
	 *
	 * @since   3.0.0
	 *
	 * @param   array  $statuses   Statuses.
	 * @param   int    $post_id    Post ID.
	 * @param   string $action     Action.
	 * @param   array  $profiles   All Enabled Profiles.
	 * @param   bool   $test_mode  Test Mode (won't send to API).
	 * @return  array               API Result for each status
	 */
	public function send( $statuses, $post_id, $action, $profiles, $test_mode = false ) {

		// Assume no errors.
		$errors = false;

		// Setup API.
		$this->base->get_class( 'api' )->set_tokens(
			$this->base->get_class( 'settings' )->get_access_token(),
			$this->base->get_class( 'settings' )->get_refresh_token(),
			$this->base->get_class( 'settings' )->get_token_expires()
		);

		// Setup logging.
		$logs        = array();
		$log_error   = array();
		$log_enabled = $this->base->get_class( 'log' )->is_enabled();

		foreach ( $statuses as $index => $status ) {
			// If the status is a WP_Error, something went wrong in building the status to be sent.
			// Log the error and continue to the next status.
			if ( isset( $status['error'] ) && is_wp_error( $status['error'] ) ) {
				// Error.
				$errors      = true;
				$logs[]      = array(
					'action'         => $action,
					'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'profile_id'     => $status['profile_ids'][0],
					'profile_name'   => $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'],
					'result'         => 'error',
					'result_message' => sprintf(
						/* translators: %1$s: Plugin Error string, %2$s: Error message from Plugin */
						'%1$s: %2$s',
						__( 'Plugin Error', 'wp-to-social-pro' ),
						$status['error']->get_error_message()
					),
					'status_text'    => false,
				);
				$log_error[] = ( $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'] . ': ' . $status['error']->get_error_message() );
				continue;
			}

			// If this is a test, add to the log array only.
			if ( $test_mode ) {
				$logs[] = array(
					'action'            => $action,
					'request_sent'      => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'profile_id'        => $status['profile_ids'][0],
					'profile_name'      => $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'],
					'result'            => 'test',
					'result_message'    => '',
					'status_text'       => $status['text'],
					'status_created_at' => date( 'Y-m-d H:i:s', strtotime( 'now' ) ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'status_due_at'     => ( isset( $status['scheduled_at'] ) ? $status['scheduled_at'] : '' ),
				);

				continue;
			}

			// Send request.
			$result = $this->base->get_class( 'api' )->updates_create( $status );

			// Store result in log array.
			if ( is_wp_error( $result ) ) {
				// Error.
				$errors      = true;
				$logs[]      = array(
					'action'         => $action,
					'request_sent'   => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'profile_id'     => $status['profile_ids'][0],
					'profile_name'   => $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'],
					'result'         => 'error',
					'result_message' => $result->get_error_message(),
					'status_text'    => $status['text'],
				);
				$log_error[] = ( $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'] . ': ' . $result->get_error_message() );
			} else {
				// OK.
				$logs[] = array(
					'action'            => $action,
					'request_sent'      => date( 'Y-m-d H:i:s' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'profile_id'        => $result['profile_id'],
					'profile_name'      => $profiles[ $status['profile_ids'][0] ]['formatted_service'] . ': ' . $profiles[ $status['profile_ids'][0] ]['formatted_username'],
					'result'            => 'success',
					'result_message'    => $result['message'],
					'status_text'       => $result['status_text'],
					'status_created_at' => date( 'Y-m-d H:i:s', $result['status_created_at'] ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					'status_due_at'     => ( $result['due_at'] !== '0000-00-00 00:00:00' ? date( 'Y-m-d H:i:s', $result['due_at'] ) : '0000-00-00 00:00:00' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				);
			}
		}

		// Set the last sent timestamp, which we may use to prevent duplicate statuses.
		update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_last_sent', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp

		// If we're reposting, update the last reposted date against the Post.
		// We do this here to ensure the Post isn't reposting again where e.g. one profile status worked + one profile status failed,
		// which would be deemed a failure.
		if ( $action === 'repost' && ! $test_mode ) {
			$this->base->get_class( 'repost' )->update_last_reposted_date( $post_id );
		}

		// If no errors were reported, set a meta key to show a success message.
		// This triggers admin_notices() to tell the user what happened.
		if ( ! $errors ) {
			// Only set a success message if test mode is disabled.
			if ( ! $test_mode ) {
				update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_success', 1 );
			}
			delete_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_error' );
			delete_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_errors' );

			// Request that the user review the plugin. Notification displayed later,
			// can be called multiple times and won't re-display the notification if dismissed.
			$this->base->dashboard->request_review();
		} else {
			update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_success', 0 );
			update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_error', 1 );
			update_post_meta( $post_id, '_' . $this->base->plugin->filter_name . '_errors', $log_error );
		}

		// Save the log, if logging is enabled.
		if ( $log_enabled ) {
			foreach ( $logs as $log ) {
				$this->base->get_class( 'log' )->add( $post_id, $log );
			}
		}

		// Return log results.
		return $logs;

	}

	/**
	 * Clears any searches and replacements stored in this class.
	 *
	 * @since   3.8.0
	 */
	private function clear_search_replacements() {

		$this->all_possible_searches_replacements = array();
		$this->searches_replacements              = array();

	}

}
