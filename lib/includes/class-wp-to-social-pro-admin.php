<?php
/**
 * Administration class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Plugin settings screen and JS/CSS.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 3.0.0
 */
class WP_To_Social_Pro_Admin {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.2.0
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the success and error messages
	 *
	 * @since   3.2.6
	 *
	 * @var     array
	 */
	public $notices = array(
		'success' => array(),
		'error'   => array(),
	);

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
		add_action( 'init', array( $this, 'oauth' ) );
		add_action( 'init', array( $this, 'check_plugin_setup' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_css' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_filter( 'plugin_action_links_' . $this->base->plugin->name . '/' . $this->base->plugin->name . '.php', array( $this, 'plugin_action_links_settings_page' ) );

	}

	/**
	 * Stores the access token if supplied, showing a success message
	 * Displays any errors from the oAuth process
	 *
	 * @since   3.3.3
	 */
	public function oauth() {

		// Setup notices class.
		$this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );

		/**
		 * Perform any pre-oAuth actions now, such as starting the oAuth process
		 *
		 * @since   4.2.0
		 */
		do_action( $this->base->plugin->filter_name . '_save_settings_auth' );

		// If we've returned from the oAuth process and an error occured, add it to the notices.
		if ( isset( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-error' ] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
			$oauth_error = sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-error' ] );  // phpcs:ignore WordPress.Security.NonceVerification
			switch ( $oauth_error ) {
				/**
				 * Access Denied
				 * - User denied our app access
				 */
				case 'access_denied':
					$this->base->get_class( 'notices' )->add_error_notice(
						sprintf(
							/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
							__( 'You did not grant our Plugin access to your %1$s account. We are unable to post to %2$s until you do this. Please click on the Authorize Plugin button.', 'wp-to-social-pro' ),
							$this->base->plugin->account,
							$this->base->plugin->account
						)
					);
					break;

				/**
				 * Invalid Grant
				 * - A parameter sent by the oAuth gateway is wrong
				 */
				case 'invalid_grant':
					$this->base->get_class( 'notices' )->add_error_notice(
						sprintf(
							'%1$s <a href="%2$s" target="_blank">%3$s</a>',
							sprintf(
								/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
								__( 'We were unable to complete authentication with %s.  Please try again, or', 'wp-to-social-pro' ),
								$this->base->plugin->account
							),
							esc_html( $this->base->plugin->support_url ),
							__( 'contact us for support', 'wp-to-social-pro' )
						)
					);
					break;

				/**
				 * Expired Token
				 * - The oAuth gateway did not exchange the code for an access token within 30 seconds
				 */
				case 'expired_token':
					$this->base->get_class( 'notices' )->add_error_notice(
						sprintf(
							'%1$s <a href="%2$s" target="_blank">%3$s</a> %4$s',
							__( 'The oAuth process has expired.  Please try again, or', 'wp-to-social-pro' ),
							esc_html( $this->base->plugin->support_url ),
							__( 'contact us for support', 'wp-to-social-pro' ),
							__( 'if this issue persists.', 'wp-to-social-pro' )
						)
					);
					break;

				/**
				 * Other Error
				 */
				default:
					$this->base->get_class( 'notices' )->add_error_notice(
						esc_html( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-error' ] )  // phpcs:ignore WordPress.Security.NonceVerification
					);
					break;
			}
		}

		// If an Access Token is included in the request, store it and show a success message.
		if ( isset( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-access-token' ] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification
			// Define expiry.
			$expiry = sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-expires' ] );  // phpcs:ignore WordPress.Security.NonceVerification
			if ( $expiry > 0 ) {
				$expiry = strtotime( '+' . sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-expires' ] ) . ' seconds' );  // phpcs:ignore WordPress.Security.NonceVerification
			}

			// Setup API.
			$this->base->get_class( 'api' )->set_tokens(
				sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-access-token' ] ), // phpcs:ignore WordPress.Security.NonceVerification
				sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-refresh-token' ] ), // phpcs:ignore WordPress.Security.NonceVerification
				$expiry
			);

			// Fetch Profiles.
			$profiles = $this->base->get_class( 'api' )->profiles( true, $this->base->get_class( 'common' )->get_transient_expiration_time() );

			// If something went wrong, show an error.
			if ( is_wp_error( $profiles ) ) {
				$this->base->get_class( 'notices' )->add_error_notice( $profiles->get_error_message() );
				return;
			}

			// Test worked! Save Tokens and Expiry.
			$this->base->get_class( 'settings' )->update_tokens(
				sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-access-token' ] ), // phpcs:ignore WordPress.Security.NonceVerification
				sanitize_text_field( $_REQUEST[ $this->base->plugin->settingsName . '-oauth-refresh-token' ] ), // phpcs:ignore WordPress.Security.NonceVerification
				$expiry
			);

			// Store success message.
			$this->base->get_class( 'notices' )->enable_store();
			$this->base->get_class( 'notices' )->add_success_notice(
				sprintf(
					/* translators: %1$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
					__( 'Thanks! You\'ve connected our Plugin to %1$s. Now select profiles below to enable, and define your statuses to start sending Posts to %2$s', 'wp-to-social-pro' ),
					$this->base->plugin->account,
					$this->base->plugin->account
				)
			);

			// Redirect to Post tab.
			wp_safe_redirect( 'admin.php?page=' . $this->base->plugin->name . '-settings&tab=post&type=post' );
			die();
		}

	}

	/**
	 * Checks that the oAuth authorization flow has been completed, and that
	 * at least one Post Type with one Social Media account has been enabled.
	 *
	 * Displays a dismissible WordPress notification if this has not been done.
	 *
	 * @since   1.0.0
	 */
	public function check_plugin_setup() {

		// Show an error if cURL hasn't been installed.
		if ( ! function_exists( 'curl_init' ) ) {
			$this->base->get_class( 'notices' )->add_error_notice(
				sprintf(
					/* translators: Plugin Name */
					__( '%s requires the PHP cURL extension to be installed and enabled by your web host.', 'wp-to-social-pro' ),
					$this->base->plugin->displayName
				)
			);
		}

		// Bail if the product is not licensed.
		if ( ! $this->base->licensing->check_license_key_valid() ) {
			return;
		}

		// Check the API is connected.
		if ( ! $this->base->get_class( 'validation' )->api_connected() ) {
			// Don't display the notice if this request is for the settings auth screen.
			$screen = $this->base->get_class( 'screen' )->get_current_screen();
			if ( $screen['screen'] === 'settings' && $screen['section'] === 'auth' ) {
				return;
			}

			// Display the notice.
			$this->base->get_class( 'notices' )->add_error_notice(
				sprintf(
					'%1$s <a href="%2$s">%3$s</a>',
					sprintf(
						/* translators: %1$s: Plugin Name, %2$s, %3$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %4$s: URL to Authorize Plugin Screen, %5$s: URL to Register Account with Service */
						esc_html__( '%1$s needs to be authorized with %2$s before you can start sending Posts to %3$s.', 'wp-to-social-pro' ),
						$this->base->plugin->displayName,
						$this->base->plugin->account,
						$this->base->plugin->account
					),
					admin_url( 'admin.php?page=' . $this->base->plugin->name . '-settings' ),
					esc_html__( 'Click here to Authorize.', 'wp-to-social-pro' )
				)
			);
		}

	}

	/**
	 * Checks the transient to see if any admin notices need to be output now.
	 *
	 * @since   3.9.6
	 */
	public function admin_notices() {

		// Output notices.
		$this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );
		$this->base->get_class( 'notices' )->output_notices();

	}

	/**
	 * Register and enqueue any JS and CSS for the WordPress Administration
	 *
	 * @since 1.0.0
	 */
	public function admin_scripts_css() {

		global $id, $post;

		// Get current screen.
		$screen = $this->base->get_class( 'screen' )->get_current_screen();

		// CSS - always load.
		// Menu Icon is inline, because when Gravity Forms no conflict mode is ON, it kills all enqueued styles,
		// which results in a large menu SVG icon displaying.
		// However, don't load this on customize.php, as it wrongly outputs above the opening <html> tag.
		if ( $screen['screen'] !== 'customize' ) {
			?>
			<style type="text/css">
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->settingsName ); ?>-settings a div.wp-menu-image, 
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->settingsName ); ?> a div.wp-menu-image, 
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->name ); ?>-settings a div.wp-menu-image,
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->name ); ?> a div.wp-menu-image {
					background: url(<?php echo esc_attr( $this->base->plugin->url ); ?>/lib/assets/images/icons/<?php echo esc_attr( strtolower( $this->base->plugin->account ) ); ?>-light.svg) center no-repeat;
					background-size: 16px 16px;
				}
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->settingsName ); ?>-settings a div.wp-menu-image img, 
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->settingsName ); ?> a div.wp-menu-image img, 
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->name ); ?>-settings a div.wp-menu-image img,
				li.toplevel_page_<?php echo esc_attr( $this->base->plugin->name ); ?> a div.wp-menu-image img {
					display: none;
				}
			</style>
			<?php
		}

		wp_enqueue_style( $this->base->plugin->name, $this->base->plugin->url . 'lib/assets/css/admin.css', array(), $this->base->plugin->version );

		// Don't load anything else if we're not on a Plugin or Post screen.
		if ( ! $screen['screen'] ) {
			return;
		}

		// Determine whether to load minified versions of JS.
		$minified = $this->base->dashboard->should_load_minified_js();

		// Define JS and localization.
		wp_register_script( $this->base->plugin->name . '-bulk-publish', $this->base->plugin->url . 'lib/assets/js/' . ( $minified ? 'min/' : '' ) . 'bulk-publish' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-log', $this->base->plugin->url . 'lib/assets/js/' . ( $minified ? 'min/' : '' ) . 'log' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-quick-edit', $this->base->plugin->url . 'lib/assets/js/' . ( $minified ? 'min/' : '' ) . 'quick-edit' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-settings', $this->base->plugin->url . 'lib/assets/js/' . ( $minified ? 'min/' : '' ) . 'settings' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery', 'wp-color-picker' ), $this->base->plugin->version, true );
		wp_register_script( $this->base->plugin->name . '-statuses', $this->base->plugin->url . 'lib/assets/js/' . ( $minified ? 'min/' : '' ) . 'statuses' . ( $minified ? '-min' : '' ) . '.js', array( 'jquery' ), $this->base->plugin->version, true );

		// Define localization for statuses.
		$localization = array(
			'ajax'                     => admin_url( 'admin-ajax.php' ),

			'character_count_action'   => $this->base->plugin->filter_name . '_character_count',
			'character_count_metabox'  => '#' . $this->base->plugin->name . '-override',
			'character_count_nonce'    => wp_create_nonce( $this->base->plugin->name . '-character-count' ),

			'clear_log_nonce'          => wp_create_nonce( $this->base->plugin->name . '-clear-log' ),
			'clear_log_completed'      => sprintf(
				/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
				__( 'No log entries exist, or no status updates have been sent to %s.', 'wp-to-social-pro' ),
				$this->base->plugin->account
			),

			'get_log_nonce'            => wp_create_nonce( $this->base->plugin->name . '-get-log' ),

			'delete_condition_message' => __( 'Are you sure you want to delete this condition?', 'wp-to-social-pro' ),
			'delete_status_message'    => __( 'Are you sure you want to delete this status?', 'wp-to-social-pro' ),

			'get_status_row_action'    => $this->base->plugin->filter_name . '_get_status_row',
			'get_status_row_nonce'     => wp_create_nonce( $this->base->plugin->name . '-get-status-row' ),

			'post_id'                  => ( isset( $post->ID ) ? $post->ID : (int) $id ),

			// Plugin specific Status Form Container and Status Form, so statuses.js knows where to look for the form
			// relative to this Plugin.
			'plugin_name'              => $this->base->plugin->name,
			'status_form_container'    => '#' . $this->base->plugin->name . '-status-form-container',
			'status_form'              => '#' . $this->base->plugin->name . '-status-form',

			// Search Nonces.
			'search_authors_nonce'     => wp_create_nonce( $this->base->plugin->name . '-search-authors' ),
			'search_roles_nonce'       => wp_create_nonce( $this->base->plugin->name . '-search-roles' ),
			'search_terms_nonce'       => wp_create_nonce( $this->base->plugin->name . '-search-terms' ),

			// status.js appends profile service to this e.g. twitter,facebook.
			'usernames_search_action'  => $this->base->plugin->filter_name . '_usernames_search_',
		);

		// If here, we're on a Plugin or Post screen.
		// Conditionally load scripts and styles depending on which section of the Plugin we're loading.
		switch ( $screen['screen'] ) {
			/**
			 * Post
			 */
			case 'post':
				switch ( $screen['section'] ) {
					/**
					 * WP_List_Table
					 */
					case 'wp_list_table':
						break;

					/**
					 * Add/Edit
					 */
					case 'edit':
						// JS.
						wp_enqueue_script( 'wpzinc-admin-autocomplete' );
						wp_enqueue_script( 'wpzinc-admin-autosize' );
						wp_enqueue_script( 'wpzinc-admin-conditional' );
						wp_enqueue_media();
						wp_enqueue_script( 'wpzinc-admin-media-library' );
						wp_enqueue_script( 'wpzinc-admin-modal' );
						wp_enqueue_script( 'wpzinc-admin-selectize' );
						wp_enqueue_script( 'wpzinc-admin-tables' );
						wp_enqueue_script( 'wpzinc-admin-tabs' );
						wp_enqueue_script( 'wpzinc-admin' );
						wp_enqueue_script( 'jquery-ui-sortable' );

						// Plugin JS.
						wp_enqueue_script( $this->base->plugin->name . '-log' );
						wp_enqueue_script( $this->base->plugin->name . '-statuses' );

						// Add Action and Nonce to allow AJAX saving.
						$localization['post_type']              = $post->post_type;
						$localization['prompt_unsaved_changes'] = false;
						$localization['save_statuses_action']   = $this->base->plugin->filter_name . '_save_statuses_post';
						$localization['save_statuses_modal']    = array(
							'title'         => __( 'Saving', 'wp-to-social-pro' ),
							'title_success' => __( 'Saved!', 'wp-to-social-pro' ),
						);
						$localization['save_statuses_nonce']    = wp_create_nonce( $this->base->plugin->name . '-save-statuses-post' );

						// Localize.
						wp_localize_script( $this->base->plugin->name . '-log', 'wp_to_social_pro', $localization );
						wp_localize_script( $this->base->plugin->name . '-statuses', 'wp_to_social_pro', $localization );

						// Localize Autocomplete.
						wp_localize_script( 'wpzinc-admin-autocomplete', 'wpzinc_autocomplete', $this->get_autocomplete_configuration( $localization['post_type'] ) );

						// CSS.
						wp_enqueue_style( 'wpzinc-admin-selectize' );
						break;
				}
				break;

			/**
			 * Settings
			 */
			case 'settings':
				// WordPress CSS.
				wp_enqueue_style( 'wp-color-picker' );

				// JS.
				wp_enqueue_script( 'wpzinc-admin-conditional' );
				wp_enqueue_media();
				wp_enqueue_script( 'wpzinc-admin-media-library' );
				wp_enqueue_script( 'wpzinc-admin-tables' );
				wp_enqueue_script( 'wpzinc-admin-tabs' );
				wp_enqueue_script( 'wpzinc-admin' );

				// Plugin JS.
				wp_enqueue_script( $this->base->plugin->name . '-settings' );

				switch ( $screen['section'] ) {
					/**
					 * General
					 */
					case 'auth':
						// JS.
						wp_enqueue_script( 'wpzinc-admin-modal' );

						// Add Repost Test Action and Nonce.
						$localization['repost_test_action'] = $this->base->plugin->filter_name . '_repost_test';
						$localization['repost_test_modal']  = array(
							'title'         => __( 'Testing', 'wp-to-social-pro' ),
							'title_success' => __( 'Finished', 'wp-to-social-pro' ),
						);
						$localization['repost_test_nonce']  = wp_create_nonce( $this->base->plugin->name . '-repost-test' );

						// Localize.
						wp_localize_script( $this->base->plugin->name . '-settings', 'wp_to_social_pro', $localization );
						break;

					/**
					 * Post Type
					 */
					default:
						// JS.
						wp_enqueue_script( 'wpzinc-admin-autocomplete' );
						wp_enqueue_script( 'wpzinc-admin-autosize' );
						wp_enqueue_script( 'wpzinc-admin-modal' );
						wp_enqueue_script( 'wpzinc-admin-selectize' );
						wp_enqueue_script( 'jquery-ui-sortable' );

						// Plugin JS.
						wp_enqueue_script( $this->base->plugin->name . '-statuses' );

						// Add Twitter Username Save Action and Nonce.
						$localization['username_save_twitter_action'] = $this->base->plugin->filter_name . '_username_save_twitter';
						$localization['username_save_twitter_nonce']  = wp_create_nonce( $this->base->plugin->name . '-username-save-twitter' );

						// Localize.
						wp_localize_script( $this->base->plugin->name . '-settings', 'wp_to_social_pro', $localization );

						// Add Post Type, Action and Nonce to allow AJAX saving.
						$localization['post_type']              = $this->get_post_type_tab();
						$localization['prompt_unsaved_changes'] = true;
						$localization['save_statuses_action']   = $this->base->plugin->filter_name . '_save_statuses';
						$localization['save_statuses_modal']    = array(
							'title'         => __( 'Saving', 'wp-to-social-pro' ),
							'title_success' => __( 'Saved!', 'wp-to-social-pro' ),
						);
						$localization['save_statuses_nonce']    = wp_create_nonce( $this->base->plugin->name . '-save-statuses' );

						// Localize Statuses.
						wp_localize_script( $this->base->plugin->name . '-statuses', 'wp_to_social_pro', $localization );

						// Localize Autocomplete.
						wp_localize_script( 'wpzinc-admin-autocomplete', 'wpzinc_autocomplete', $this->get_autocomplete_configuration( $localization['post_type'] ) );

						// CSS.
						wp_enqueue_style( 'wpzinc-admin-selectize' );
						break;
				}
				break;

			/**
			 * Bulk Publish
			 */
			case 'bulk_publish':
				// JS.
				wp_enqueue_script( 'wpzinc-admin-selectize' );
				wp_enqueue_script( 'jquery-ui-progressbar' );
				wp_enqueue_script( 'jquery-ui-sortable' );

				// Plugin JS.
				wp_enqueue_script( 'wpzinc-admin-synchronous-ajax' );
				wp_enqueue_script( $this->base->plugin->name . '-bulk-publish' );
				wp_enqueue_script( $this->base->plugin->name . '-statuses' );

				// Localization.
				wp_localize_script( $this->base->plugin->name . '-statuses', 'wp_to_social_pro', $localization );

				// CSS.
				wp_enqueue_style( 'wpzinc-admin-selectize' );
				break;

			/**
			 * Log
			 */
			case 'log':
				// Plugin JS.
				wp_enqueue_script( $this->base->plugin->name . '-log' );

				// Localize.
				wp_localize_script( $this->base->plugin->name . '-log', 'wp_to_social_pro', $localization );
				break;
		}

	}

	/**
	 * Returns configuration for tribute.js autocomplete instances for Tags, Facebook Pages and Twitter Username mentions.
	 *
	 * @since   4.5.7
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Javascript  Autocomplete Configuration
	 */
	private function get_autocomplete_configuration( $post_type ) {

		$autocomplete_configuration = array(
			// Tags.
			array(
				'fields'   => array(
					'textarea.message',
					'textarea.text-to-image',

					// Google Business.
					'input#googlebusiness_title',
					'input#googlebusiness_code',
					'input#googlebusiness_terms',
				),
				'triggers' => array(
					// Tags.
					array(
						'trigger' => '{',
						'values'  => $this->base->get_class( 'common' )->get_tags_flat( $post_type ),
					),
				),
			),
		);

		// Add Facebook Autocompleter, if supported by the Plugin.
		if ( $this->base->supports( 'facebook_mentions' ) ) {
			$autocomplete_configuration[] = array(
				'fields'   => array(
					'div.facebook textarea.message',
				),
				'triggers' => array(
					// Usernames.
					array(
						'trigger'           => '@',
						'url'               => admin_url( 'admin-ajax.php' ),
						'method'            => 'POST',
						'action'            => $this->base->plugin->filter_name . '_usernames_search_facebook',
						'nonce'             => wp_create_nonce( $this->base->plugin->name . '-usernames-search-facebook' ),
						'menuShowMinLength' => 3,
					),
				),
			);
		}

		/**
		 * Defines configuration for tribute.js autocomplete instances for Tags, Facebook Pages and Twitter Username mentions.
		 *
		 * @since   4.5.7
		 *
		 * @param   array   $autocomplete_configuration     Javascript  Autocomplete Configuration.
		 * @param   string  $post_type                      Post Type.
		 */
		$autocomplete_configuration = apply_filters( $this->base->plugin->filter_name . '_admin_get_autocomplete_configuration', $autocomplete_configuration );

		// Return.
		return $autocomplete_configuration;

	}

	/**
	 * Add the Plugin to the WordPress Administration Menu
	 *
	 * @since   1.0.0
	 */
	public function admin_menu() {

		// Bail if we cannot access any menus.
		if ( ! $this->base->get_class( 'access' )->can_access( 'show_menu' ) ) {
			return;
		}

		// Define the minimum capability required to access the Menu and Sub Menus.
		$minimum_capability = 'manage_options';

		/**
		 * Defines the minimum capability required to access the Media Library Organizer
		 * Menu and Sub Menus
		 *
		 * @since   4.3.6
		 *
		 * @param   string  $capability     Minimum Required Capability.
		 * @return  string                  Minimum Required Capability
		 */
		$minimum_capability = apply_filters( $this->base->plugin->filter_name . '_admin_admin_menu_minimum_capability', $minimum_capability );

		// Licensing.
		add_menu_page( $this->base->plugin->displayName, $this->base->plugin->displayName, $minimum_capability, $this->base->plugin->name, array( $this, 'licensing_screen' ), $this->base->plugin->url . 'lib/assets/images/icons/' . strtolower( $this->base->plugin->account ) . '-light.svg' );
		add_submenu_page( $this->base->plugin->name, __( 'Licensing', 'wp-to-social-pro' ), __( 'Licensing', 'wp-to-social-pro' ), $minimum_capability, $this->base->plugin->name, array( $this, 'licensing_screen' ) );

		// Bail if the product is not licensed.
		if ( ! $this->base->licensing->check_license_key_valid() ) {
			return;
		}

		// Licensed - add additional menu entries, if access permitted.
		if ( $this->base->get_class( 'access' )->can_access( 'show_menu_settings' ) ) {
			$settings_page = add_submenu_page( $this->base->plugin->name, __( 'Settings', 'wp-to-social-pro' ), __( 'Settings', 'wp-to-social-pro' ), $minimum_capability, $this->base->plugin->name . '-settings', array( $this, 'settings_screen' ) );
		}

		// Only show Bulk Publish and Logs if connected to the API.
		if ( $this->base->get_class( 'validation' )->api_connected() ) {
			// Bulk Publish.
			if ( $this->base->get_class( 'access' )->can_access( 'show_menu_bulk_publish' ) ) {
				$bulk_publish_page = add_submenu_page( $this->base->plugin->name, __( 'Bulk Publish', 'wp-to-social-pro' ), __( 'Bulk Publish', 'wp-to-social-pro' ), $minimum_capability, $this->base->plugin->name . '-bulk-publish', array( $this, 'bulk_publish_screen' ) );
			}

			// Logs.
			if ( $this->base->get_class( 'access' )->can_access( 'show_menu_logs' ) ) {
				if ( $this->base->get_class( 'log' )->is_enabled() ) {
					$log_page = add_submenu_page( $this->base->plugin->name, __( 'Logs', 'wp-to-social-pro' ), __( 'Logs', 'wp-to-social-pro' ), $minimum_capability, $this->base->plugin->name . '-log', array( $this, 'log_screen' ) );
					add_action( "load-$log_page", array( $this->base->get_class( 'log' ), 'add_screen_options' ) );
				}
			}
		}

		// Import & Export.
		if ( $this->base->get_class( 'access' )->can_access( 'show_menu_import_export' ) ) {
			do_action( $this->base->plugin->filter_name . '_admin_menu_import_export' );
		}

		// Support.
		if ( $this->base->get_class( 'access' )->can_access( 'show_menu_support' ) ) {
			do_action( $this->base->plugin->filter_name . '_admin_menu_support' );
		}

	}

	/**
	 * Define links to display below the Plugin Name on the WP_List_Table at in the Plugins screen.
	 *
	 * @since   5.0.2
	 *
	 * @param   array $links      Links.
	 * @return  array               Links
	 */
	public function plugin_action_links_settings_page( $links ) {

		// Bail if user access doesn't permit access to settings.
		if ( ! $this->base->get_class( 'access' )->can_access( 'show_menu_settings' ) ) {
			return $links;
		}

		// Add link to Plugin settings screen.
		$links['settings'] = sprintf(
			'<a href="%s">%s</a>',
			add_query_arg(
				array(
					'page' => $this->base->plugin->name . '-settings',
				),
				admin_url( 'admin.php' )
			),
			__( 'Settings', 'wp-to-social-pro' )
		);

		// Return.
		return $links;

	}

	/**
	 * Outputs the Licensing Screen
	 *
	 * @since   3.0.0
	 */
	public function licensing_screen() {

		// Load View.
		include_once $this->base->plugin->folder . '_modules/licensing/views/licensing.php';

	}

	/**
	 * Outputs the Settings Screen
	 *
	 * @since   3.0.0
	 */
	public function settings_screen() {

		// Setup notices class.
		$this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );

		// Maybe disconnect.
		if ( isset( $_GET[ $this->base->plugin->name . '-disconnect' ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->disconnect();
			$this->base->get_class( 'notices' )->add_success_notice(
				sprintf(
					/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
					__( '%s account disconnected successfully.', 'wp-to-social-pro' ),
					$this->base->plugin->account
				)
			);
		}

		// Maybe save settings.
		$result = $this->save_settings();
		if ( is_wp_error( $result ) ) {
			// Error notice.
			$this->base->get_class( 'notices' )->add_error_notice( $result->get_error_message() );
		} elseif ( $result === true ) {
			// Success notice.
			$this->base->get_class( 'notices' )->add_success_notice( __( 'Settings saved successfully.', 'wp-to-social-pro' ) );
		}

		// If the Plugin isn't connected to the API, show the screen to do this now.
		if ( ! $this->base->get_class( 'validation' )->api_connected() ) {
			$this->auth_screen();
			return;
		}

		// Authentication.
		$access_token  = $this->base->get_class( 'settings' )->get_access_token();
		$refresh_token = $this->base->get_class( 'settings' )->get_refresh_token();
		$expires       = $this->base->get_class( 'settings' )->get_token_expires();
		if ( ! empty( $access_token ) ) {
			$this->base->get_class( 'api' )->set_tokens( $access_token, $refresh_token, $expires );
		}

		// Profiles.
		$profiles = $this->base->get_class( 'api' )->profiles( true, $this->base->get_class( 'common' )->get_transient_expiration_time() );
		if ( is_wp_error( $profiles ) ) {
			// If the error is a 401, the user revoked access to the plugin.
			// Disconnect the Plugin, and explain why this happened.
			if ( $profiles->get_error_code() === 401 ) {
				// Disconnect the Plugin.
				$this->disconnect();

				// Error notice.
				$this->base->get_class( 'notices' )->add_error_notice(
					sprintf(
						/* translators: %1$s: Plugin Name, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Hmm, it looks like you revoked access to %1$s through your %2$s account, or your account no longer exists. This means we can no longer post updates to your social networks.  To re-authorize, click the Authorize Plugin button.', 'wp-to-social-pro' ),
						$this->base->plugin->displayName,
						$this->base->plugin->account
					)
				);
			} else {
				// Some other error.
				$this->base->get_class( 'notices' )->add_error_notice( $profiles->get_error_message() );
			}
		}

		// Get Settings Tab and Post Type we're managing settings for.
		$tab                 = $this->get_tab( $profiles );
		$post_type           = $this->get_post_type_tab();
		$disable_save_button = false;

		// Post Types.
		$post_types_public = $this->base->get_class( 'common' )->get_post_types();
		$post_types        = $this->base->get_class( 'common' )->maybe_remove_post_types_by_role(
			$post_types_public,
			wp_get_current_user()->roles[0]
		);

		// Depending on the screen we're on, load specific options.
		switch ( $tab ) {
			/**
			 * Settings
			 */
			case 'auth':
				// General Settings.
				$override_options = $this->base->get_class( 'common' )->get_override_options();

				// Text to Image Settings.
				$fonts = $this->base->get_class( 'common' )->get_fonts();

				// Log Settings.
				$log_levels = $this->base->get_class( 'log' )->get_level_options();

				// Repost Settings.
				$repost_event_next_scheduled = $this->base->get_class( 'cron' )->get_repost_event_next_scheduled( 'dS F Y, H:i:s' );
				$repost_schedule             = $this->get_setting( '', 'repost_time' );
				$repost_days                 = array_keys( $this->base->get_class( 'common' )->get_days() );

				// Roles.
				$roles = $this->base->get_class( 'common' )->get_user_roles();

				// Documentation URL.
				$documentation_url = $this->base->plugin->documentation_url . '/authentication-settings';
				break;

			/**
			 * No Profiles
			 */
			case 'profiles-missing':
				// Disable Save button, as there are no settings displayed to save.
				$disable_save_button = true;

				// Documentation URL.
				$documentation_url = $this->base->plugin->documentation_url . '/status-settings';
				break;

			/**
			 * Profiles Error
			 */
			case 'profiles-error':
				// Disable Save button, as there are no settings displayed to save.
				$disable_save_button = true;

				// Documentation URL.
				$documentation_url = $this->base->plugin->documentation_url . '/status-settings';
				break;

			/**
			 * Post Type
			 */
			default:
				// Run profiles through role restriction.
				$profiles = $this->base->get_class( 'common' )->maybe_remove_profiles_by_role( $profiles, wp_get_current_user()->roles[0] );

				// Get original statuses that will be stored in a hidden field so they are preserved if the screen is saved
				// with no changes that trigger an update to the hidden field.
				$original_statuses = $this->base->get_class( 'settings' )->get_settings( $post_type );

				// Get some other information.
				$post_type_object  = get_post_type_object( $post_type );
				$actions_plural    = $this->base->get_class( 'common' )->get_post_actions_past_tense();
				$post_actions      = $this->base->get_class( 'common' )->get_post_actions();
				$documentation_url = $this->base->plugin->documentation_url . '/status-settings';
				$is_post_screen    = false; // Disables the 'specific' schedule option, which can only be used on individual Per-Post Settings.

				// Check if this Post Type is enabled.
				if ( ! $this->base->get_class( 'settings' )->is_post_type_enabled( $post_type ) ) {
					$this->base->get_class( 'notices' )->add_warning_notice(
						sprintf(
							'%1$s <a href="%2$s" target="_blank">%3$s</a>',
							sprintf(
								/* translators: %1$s: Post Type, %2$s: Social Media Service Name (Buffer, Hootsuite, SocialPilot), %3$s: Documentation URL */
								__( 'To send %1$s to %2$s, at least one action on the Defaults tab must be enabled with a status defined, and at least one social media profile must be enabled below by clicking the applicable profile name and ticking the "Account Enabled" box.', 'wp-to-social-pro' ),
								$post_type_object->label,
								$this->base->plugin->account
							),
							$documentation_url,
							__( 'See Documentation', 'wp-to-social-pro' )
						)
					);
				}
				break;
		}

		// Load View.
		include_once $this->base->plugin->folder . 'lib/views/settings.php';

		// Add footer action to output overlay modal markup.
		add_action( 'admin_footer', array( $this, 'output_modal' ) );

	}

	/**
	 * Outputs the auth screen, allowing the user to begin the process of connecting the Plugin
	 * to the API, without showing other settings.
	 *
	 * @since   4.6.4
	 */
	public function auth_screen() {

		// Load View.
		include_once $this->base->plugin->folder . 'lib/views/settings-auth-required.php';

	}

	/**
	 * Outputs the hidden Javascript Modal and Overlay in the Footer
	 *
	 * @since   1.0.0
	 */
	public function output_modal() {

		// Load view.
		require_once $this->base->plugin->folder . '_modules/dashboard/views/modal.php';

	}

	/**
	 * Outputs the Bulk Publish Screen
	 *
	 * @since   3.0.5
	 */
	public function bulk_publish_screen() {

		// Setup notices class.
		$this->base->get_class( 'notices' )->set_key_prefix( $this->base->plugin->filter_name . '_' . wp_get_current_user()->ID );

		// Set access and refresh tokens.
		$this->base->get_class( 'api' )->set_tokens(
			$this->base->get_class( 'settings' )->get_access_token(),
			$this->base->get_class( 'settings' )->get_refresh_token(),
			$this->base->get_class( 'settings' )->get_token_expires()
		);

		// Get Profiles.
		$profiles = $this->base->get_class( 'api' )->profiles( true, $this->base->get_class( 'common' )->get_transient_expiration_time() );
		if ( is_wp_error( $profiles ) ) {
			// Set error notice.
			$this->base->get_class( 'notices' )->add_error_notice( $profiles->get_error_message() );

			// Load view.
			include_once $this->base->plugin->folder . 'lib/views/bulk-publish-error.php';
			return;
		}

		// Get Post Types.
		$post_types = $this->base->get_class( 'common' )->get_post_types();

		// Get URL parameters.
		$stage     = $this->get_bulk_publish_stage();
		$post_type = $this->get_post_type_tab();
		if ( empty( $post_type ) ) {
			$post_type = 'post';
		}
		$tags                              = $this->base->get_class( 'common' )->get_tags( $post_type );
		$taxonomies                        = $this->base->get_class( 'common' )->get_taxonomies( $post_type );
		$authors                           = $this->base->get_class( 'common' )->get_authors();
		$comparison_operators              = $this->base->get_class( 'common' )->get_comparison_operators();
		$custom_field_comparison_operators = $this->base->get_class( 'common' )->get_custom_field_comparison_operators();
		$orderby                           = $this->base->get_class( 'common' )->get_order_by();
		$order                             = $this->base->get_class( 'common' )->get_order();

		// Get some additional data, depending on which stage we're on.
		switch ( $stage ) {
			/**
			 * Select Posts
			 */
			case 1:
				// Invalid nonce.
				if ( ! wp_verify_nonce( $_REQUEST[ $this->base->plugin->name . '_nonce' ], $this->base->plugin->name ) ) {
					// Set error notice.
					$this->base->get_class( 'notices' )->add_error_notice( __( 'Invalid nonce specified.', 'wp-to-social-pro' ) );

					// Load view.
					include_once $this->base->plugin->folder . 'lib/views/bulk-publish-error.php';
					return;
				}

				// Build Search Params.
				$params = array(
					'start_date' => ( isset( $_POST[ $this->base->plugin->name ]['start_date'] ) && ! empty( $_POST[ $this->base->plugin->name ]['start_date'] ) ? sanitize_text_field( $_POST[ $this->base->plugin->name ]['start_date'] ) : false ),
					'end_date'   => ( isset( $_POST[ $this->base->plugin->name ]['end_date'] ) && ! empty( $_POST[ $this->base->plugin->name ]['end_date'] ) ? sanitize_text_field( $_POST[ $this->base->plugin->name ]['end_date'] ) : false ),
					'authors'    => ( isset( $_POST[ $this->base->plugin->name ]['authors'] ) && ! empty( $_POST[ $this->base->plugin->name ]['authors'] ) ? explode( ',', sanitize_text_field( $_POST[ $this->base->plugin->name ]['authors'] ) ) : false ),
					'meta'       => false,
					's'          => ( isset( $_POST[ $this->base->plugin->name ]['s'] ) && ! empty( $_POST[ $this->base->plugin->name ]['s'] ) ? sanitize_text_field( $_POST[ $this->base->plugin->name ]['s'] ) : false ),
					'taxonomies' => false,
					'orderby'    => ( isset( $_POST[ $this->base->plugin->name ]['orderby'] ) ? sanitize_text_field( $_POST[ $this->base->plugin->name ]['orderby'] ) : false ),
					'order'      => ( isset( $_POST[ $this->base->plugin->name ]['order'] ) ? sanitize_text_field( $_POST[ $this->base->plugin->name ]['order'] ) : false ),
				);

				// If the URL request includes Post IDs, we've come from a WP_List_Table Bulk Action
				// Use these IDs.
				if ( isset( $_REQUEST['post_ids'] ) ) {
					$post_ids = explode( ',', sanitize_text_field( $_REQUEST['post_ids'] ) );
					foreach ( $post_ids as $key => $post_id ) {
						$post_ids[ $key ] = absint( $post_id );
					}

					$params['post_ids'] = $post_ids;
				}

				// Build Taxonomy Search Params.
				$taxonomies = array();

				if ( isset( $_POST[ $this->base->plugin->name ]['taxonomies'] ) ) {
					foreach ( $_POST[ $this->base->plugin->name ]['taxonomies'] as $taxonomy => $terms ) {
						// Ignore if no Terms.
						if ( empty( $terms ) ) {
							continue;
						}

						$taxonomies[ $taxonomy ] = explode( ',', $terms );
					}
				}
				if ( ! empty( $taxonomies ) ) {
					$params['taxonomies'] = $taxonomies;
				}

				// Build Meta Search Params.
				$meta = array();
				if ( isset( $_POST[ $this->base->plugin->name ]['meta']['key'] ) ) {
					foreach ( $_POST[ $this->base->plugin->name ]['meta']['key'] as $index => $meta_key ) {
						// Ignore if the key is blank.
						if ( empty( $_POST[ $this->base->plugin->name ]['meta']['key'][ $index ] ) ) {
							continue;
						}

						// Add meta condition.
						$meta[] = array(
							'key'     => sanitize_text_field( $_POST[ $this->base->plugin->name ]['meta']['key'][ $index ] ),
							'value'   => sanitize_text_field( $_POST[ $this->base->plugin->name ]['meta']['value'][ $index ] ),
							'compare' => sanitize_text_field( $_POST[ $this->base->plugin->name ]['meta']['compare'][ $index ] ),
						);
					}
				}
				if ( ! empty( $meta ) ) {
					$params['meta'] = $meta;
				}

				// Get Post IDs.
				$post_ids = $this->base->get_class( 'bulk_publish' )->get_post_ids( $post_type, $params );

				// Bail if no Post IDs found.
				if ( ! count( $post_ids ) ) {
					// Revert back a stage with an error notice.
					$this->base->get_class( 'notices' )->add_error_notice(
						sprintf(
							/* translators: Post Type Plural Name */
							__( 'No %s found matching the given Bulk Publish conditions. Please adjust / remove conditions as necessary.', 'wp-to-social-pro' ),
							$post_types[ $post_type ]->labels->name
						)
					);
					$stage = 0;
				}
				break;

			/**
			 * Publish
			 */
			case '2':
				// Invalid nonce.
				if ( ! wp_verify_nonce( $_POST[ $this->base->plugin->name . '_nonce' ], $this->base->plugin->name ) ) {
					// Set error notice.
					$this->base->get_class( 'notices' )->add_error_notice( __( 'Invalid nonce specified. Settings NOT saved.', 'wp-to-social-pro' ) );

					// Load view.
					include_once $this->base->plugin->folder . 'lib/views/bulk-publish-error.php';
					return;
				}

				// Check at least one Post has been selected.
				if ( ! isset( $_POST[ $this->base->plugin->name ]['posts'] ) || count( $_POST[ $this->base->plugin->name ]['posts'] ) === 0 ) {
					// Revert back a stage with an error message.
					$this->base->get_class( 'notices' )->add_error_notice(
						sprintf(
							/* translators: %1$s: Post Type Singular Name, %2$s: Plugin Name */
							__( 'Please select at least one %1$s to publish to %2$s.', 'wp-to-social-pro' ),
							$post_types[ $post_type ]->labels->singular_name,
							$this->base->plugin->displayName
						)
					);
					$stage = 1;

					// Get Posts and Post IDs.
					$post_ids = explode( ',', $_POST['post_ids'] );
					$posts    = new WP_Query(
						array(
							'post__in' => $post_ids,
						)
					);
					break;
				}

				// If here, one or more Post(s) were selected.
				// Get Posts and Post IDs.
				$post_ids = $_POST[ $this->base->plugin->name ]['posts'];

				// Localize Bulk Publish script.
				wp_localize_script(
					$this->base->plugin->name . '-bulk-publish',
					'wp_to_social_pro_bulk_publish',
					array(
						'ajax'               => admin_url( 'admin-ajax.php' ),
						'action'             => $this->base->plugin->filter_name . '_bulk_publish',
						'nonce'              => wp_create_nonce( $this->base->plugin->name . '-bulk-publish' ),
						'post_ids'           => array_values( $post_ids ),
						'number_of_requests' => absint( count( $post_ids ) ),
						'finished'           => __( 'Finished.', 'wp-to-social-pro' ),
					)
				);
				break;

		}

		// Load View.
		include_once $this->base->plugin->folder . 'lib/views/bulk-publish.php';

	}

	/**
	 * Determines which stage of the Bulk Publish process the user is on.
	 *
	 * Takes into account whether the user is bulk publishing from a WP_List_Table or the Plugin's
	 * Bulk Publish option
	 *
	 * @since   1.0.0
	 *
	 * @return  int     Stage
	 */
	private function get_bulk_publish_stage() {

		if ( isset( $_REQUEST['stage'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return absint( $_REQUEST['stage'] ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		// If Post IDs are specified in the URL request, we've been redirected from a WP_List_Table.
		if ( isset( $_REQUEST['post_ids'] ) && ! empty( $_REQUEST['post_ids'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return 1;
		}

		return 0;

	}

	/**
	 * Outputs the Log Screen
	 *
	 * @since   3.9.6
	 */
	public function log_screen() {

		// Init table.
		$table = new WP_To_Social_Pro_Log_Table( $this->base );
		$table->prepare_items();

		// Load View.
		include_once $this->base->plugin->folder . 'lib/views/log.php';

	}

	/**
	 * Helper method to get the setting value from the plugin settings
	 *
	 * @since   3.0.0
	 *
	 * @param   string $type            Setting Type.
	 * @param   string $key             Setting Key.
	 * @param   mixed  $default_value   Default Value if Setting does not exist.
	 * @return  mixed                   Value
	 */
	public function get_setting( $type = '', $key = '', $default_value = '' ) {

		// Post Type Setting or Bulk Setting.
		if ( post_type_exists( $type ) ) {
			return $this->base->get_class( 'settings' )->get_setting( $type, $key, $default_value );
		}

		// Access token.
		if ( $key === 'access_token' ) {
			return $this->base->get_class( 'settings' )->get_access_token();
		}

		// Refresh token.
		if ( $key === 'refresh_token' ) {
			return $this->base->get_class( 'settings' )->get_refresh_token();
		}

		// Depending on the type, return settings / options.
		switch ( $type ) {
			case 'text_to_image':
			case 'log':
			case 'hide_meta_box_by_roles':
			case 'roles':
			case 'custom_tags':
			case 'repost':
				return $this->base->get_class( 'settings' )->get_setting( $type, $key, $default_value );

			default:
				return $this->base->get_class( 'settings' )->get_option( $key, $default_value );
		}

	}

	/**
	 * Disconnect by removing the access token
	 *
	 * @since   3.0.0
	 *
	 * @return  string Result
	 */
	public function disconnect() {

		return $this->base->get_class( 'settings' )->delete_tokens();

	}

	/**
	 * Helper method to save settings
	 *
	 * @since   3.0.0
	 *
	 * @return  mixed   WP_Error | bool
	 */
	public function save_settings() {

		// Check if a POST request was made.
		if ( ! isset( $_POST['submit'] ) ) {
			return false;
		}

		// Missing nonce.
		if ( ! isset( $_POST[ $this->base->plugin->name . '_nonce' ] ) ) {
			return new WP_Error(
				'wp_to_social_pro_admin_save_settings_error',
				__( 'Nonce field is missing. Settings NOT saved.', 'wp-to-social-pro' )
			);
		}

		// Invalid nonce.
		if ( ! wp_verify_nonce( $_POST[ $this->base->plugin->name . '_nonce' ], $this->base->plugin->name ) ) {
			return new WP_Error(
				'wp_to_social_pro_admin_save_settings_error',
				__( 'Invalid nonce specified. Settings NOT saved.', 'wp-to-social-pro' )
			);
		}

		// Get URL parameters.
		$tab       = $this->get_tab();
		$post_type = $this->get_post_type_tab();

		switch ( $tab ) {
			/**
			 * Authentication
			 */
			case 'auth':
				// oAuth settings are now handled by this class' oauth() function.
				// Save other Settings.

				// General Settings.
				$this->base->get_class( 'settings' )->update_option( 'test_mode', ( isset( $_POST['test_mode'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'is_draft', ( isset( $_POST['is_draft'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'disable_url_shortening', ( isset( $_POST['disable_url_shortening'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'force_trailing_forwardslash', ( isset( $_POST['force_trailing_forwardslash'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'disable_excerpt_fallback', ( isset( $_POST['disable_excerpt_fallback'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'proxy', ( isset( $_POST['proxy'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'cron', ( isset( $_POST['cron'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'override', ( isset( $_POST['override'] ) ? sanitize_text_field( $_POST['override'] ) : 0 ) );

				// Image Settings.
				$this->base->get_class( 'settings' )->update_option( 'text_to_image', ( isset( $_POST['text_to_image'] ) ? $_POST['text_to_image'] : array() ) );

				// Log Settings.
				// Always force errors.
				$log = $_POST['log'];
				if ( ! isset( $log['log_level'] ) ) {
					$log['log_level'] = array(
						'error',
					);
				} else {
					// 'Error' is disabled on the form and not sent if another option is chosen.
					// We always want errors to be logged so add it to the log levels now.
					$log['log_level'][] = 'error';
				}
				$this->base->get_class( 'settings' )->update_option( 'log', $log );

				// Repost Settings.
				$this->base->get_class( 'settings' )->update_option( 'repost_disable_cron', ( isset( $_POST['repost_disable_cron'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option(
					'repost_time',
					( isset( $_POST['repost_time'] ) ? $_POST['repost_time'] : array(
						'mon' => array( 0 ),
						'tue' => array( 0 ),
						'wed' => array( 0 ),
						'thu' => array( 0 ),
						'fri' => array( 0 ),
						'sat' => array( 0 ),
						'sun' => array( 0 ),
					) )
				);
				$this->base->get_class( 'settings' )->update_option( 'repost', ( isset( $_POST['repost'] ) ? $_POST['repost'] : '' ) );

				// User Access.
				$this->base->get_class( 'settings' )->update_option( 'hide_meta_box_by_roles', ( isset( $_POST['hide_meta_box_by_roles'] ) ? $_POST['hide_meta_box_by_roles'] : array() ) );
				$this->base->get_class( 'settings' )->update_option( 'restrict_post_types', ( isset( $_POST['restrict_post_types'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'restrict_roles', ( isset( $_POST['restrict_roles'] ) ? 1 : 0 ) );
				$this->base->get_class( 'settings' )->update_option( 'roles', ( isset( $_POST['roles'] ) ? $_POST['roles'] : array() ) );

				// Custom Tags.
				$this->base->get_class( 'settings' )->update_option( 'custom_tags', ( isset( $_POST['custom_tags'] ) ? $_POST['custom_tags'] : '' ) );

				// Reschedule CRON events.
				$this->base->get_class( 'cron' )->reschedule_log_cleanup_event();
				$this->base->get_class( 'cron' )->reschedule_repost_event();

				// Done.
				return true;

			/**
			 * Post Type
			 */
			default:
				// Unslash and decode JSON field.
				$settings = json_decode( wp_unslash( $_POST[ $this->base->plugin->name ]['statuses'] ), true );

				// Save Settings for this Post Type.
				return $this->base->get_class( 'settings' )->update_settings( $post_type, $settings );
		}

	}

	/**
	 * Returns the settings tab that the user has selected.
	 *
	 * @since   3.7.2
	 *
	 * @param   mixed $profiles   API Profiles (false|WP_Error|array).
	 * @return  string  Tab
	 */
	private function get_tab( $profiles = false ) {

		$tab = ( isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'auth' ); // phpcs:ignore WordPress.Security.NonceVerification

		// If we're on the Settings tab, return.
		if ( $tab === 'auth' ) {
			return $tab;
		}

		// If Profiles are an error, show error.
		if ( is_wp_error( $profiles ) ) {
			return 'profiles-error';
		}

		// If no Profiles exist, show error.
		if ( is_array( $profiles ) && ! count( $profiles ) ) {
			return 'profiles-missing';
		}

		// Return tab.
		return $tab;

	}

	/**
	 * Returns the Post Type tab that the user has selected.
	 *
	 * @since   3.7.2
	 *
	 * @return  string  Tab
	 */
	private function get_post_type_tab() {

		return ( isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification

	}

}
