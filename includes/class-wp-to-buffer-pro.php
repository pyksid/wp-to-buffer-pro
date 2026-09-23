<?php
/**
 * WP to Buffer Pro class.
 *
 * @package WP_To_Buffer_Pro
 * @author WP Zinc
 */

/**
 * Main WP to Buffer Pro class, used to load the Plugin.
 *
 * @package   WP_To_Buffer_Pro
 * @author    WP Zinc
 * @version   1.0.0
 */
class WP_To_Buffer_Pro {

	/**
	 * Holds the class object.
	 *
	 * @since   3.1.4
	 *
	 * @var     object|null
	 */
	public static $instance;

	/**
	 * Plugin
	 *
	 * @since   3.0.0
	 *
	 * @var     object
	 */
	public $plugin;

	/**
	 * Dashboard
	 *
	 * @since   3.1.4
	 *
	 * @var     object
	 */
	public $dashboard;

	/**
	 * Licensing
	 *
	 * @since   3.1.4
	 *
	 * @var     object
	 */
	public $licensing;

	/**
	 * Classes
	 *
	 * @since   3.4.9
	 *
	 * @var     object
	 */
	public $classes;

	/**
	 * Constructor. Acts as a bootstrap to load the rest of the plugin
	 *
	 * @since   1.0.0
	 */
	public function __construct() {

		// Plugin Details.
		$this->plugin                    = new stdClass();
		$this->plugin->name              = 'wp-to-buffer-pro';
		$this->plugin->filter_name       = 'wp_to_buffer_pro';
		$this->plugin->displayName       = 'WP to Buffer Pro';
		$this->plugin->description       = 'Send WordPress Pages, Posts or Custom Post Types to your Buffer (buffer.com) account for scheduled publishing to social networks.';
		$this->plugin->author_name       = 'WP Zinc';
		$this->plugin->settingsName      = 'wp-to-buffer-pro'; // Settings key - used in both Free + Pro, and for oAuth.
		$this->plugin->account           = 'Buffer';
		$this->plugin->version           = WP_TO_BUFFER_PRO_PLUGIN_VERSION;
		$this->plugin->buildDate         = WP_TO_BUFFER_PRO_PLUGIN_BUILD_DATE;
		$this->plugin->folder            = WP_TO_BUFFER_PRO_PLUGIN_PATH;
		$this->plugin->url               = WP_TO_BUFFER_PRO_PLUGIN_URL;
		$this->plugin->documentation_url = 'https://www.wpzinc.com/documentation/wordpress-buffer-pro';
		$this->plugin->support_url       = 'https://www.wpzinc.com/support';
		$this->plugin->upgrade_url       = 'https://www.wpzinc.com/plugins/wordpress-to-buffer-pro';

		// Logo.
		$this->plugin->logo                        = WP_TO_BUFFER_PRO_PLUGIN_URL . 'lib/social/assets/images/icons/buffer-dark.svg';
		$this->plugin->header_background_color     = '#ffffff';
		$this->plugin->header_primary_text_color   = '#3d3d3d';
		$this->plugin->header_secondary_text_color = '#6e6e6e';

		// Review.
		$this->plugin->review_name   = 'wp-to-buffer';
		$this->plugin->review_notice = sprintf(
			'Thanks for using %s to schedule your social media statuses on %s!',
			$this->plugin->displayName,
			$this->plugin->account
		);

		// Default Settings.
		$this->plugin->default_schedule = 'queue_end';

		// Licensing Submodule.
		if ( ! class_exists( 'LicensingUpdateManager' ) ) {
			require_once $this->plugin->folder . '_modules/licensing/class-licensingupdatemanager.php';
		}
		$this->licensing = new LicensingUpdateManager( $this->plugin, 'https://www.wpzinc.com' );

		// Run Plugin Display Name, URLs through Whitelabelling if available.
		$this->plugin->displayName       = $this->licensing->get_feature_parameter( 'whitelabelling', 'display_name', $this->plugin->displayName );
		$this->plugin->support_url       = $this->licensing->get_feature_parameter( 'whitelabelling', 'support_url', $this->plugin->support_url );
		$this->plugin->documentation_url = $this->licensing->get_feature_parameter( 'whitelabelling', 'documentation_url', $this->plugin->documentation_url );
		if ( ! empty( $this->licensing->get_feature_parameter( 'whitelabelling', 'logo', $this->plugin->logo ) ) ) {
			$this->plugin->logo = $this->licensing->get_feature_parameter( 'whitelabelling', 'logo', $this->plugin->logo );
		}
		if ( ! empty( $this->licensing->get_feature_parameter( 'whitelabelling', 'header_background_color', $this->plugin->header_background_color ) ) ) {
			$this->plugin->header_background_color = $this->licensing->get_feature_parameter( 'whitelabelling', 'header_background_color', $this->plugin->header_background_color );
		}
		if ( ! empty( $this->licensing->get_feature_parameter( 'whitelabelling', 'header_primary_text_color', $this->plugin->header_primary_text_color ) ) ) {
			$this->plugin->header_primary_text_color = $this->licensing->get_feature_parameter( 'whitelabelling', 'header_primary_text_color', $this->plugin->header_primary_text_color );
		}
		if ( ! empty( $this->licensing->get_feature_parameter( 'whitelabelling', 'header_secondary_text_color', $this->plugin->header_secondary_text_color ) ) ) {
			$this->plugin->header_secondary_text_color = $this->licensing->get_feature_parameter( 'whitelabelling', 'header_secondary_text_color', $this->plugin->header_secondary_text_color );
		}

		// Shared admin module (autoloaded from lib/shared).
		$this->dashboard = new \WPZinc\Shared\Admin_UI( $this->plugin );

		// Show Support Menu and hide Upgrade Menu.
		$this->dashboard->show_support_menu();
		$this->dashboard->hide_upgrade_menu();

		// Disable Review Notification if whitelabelling is enabled.
		if ( $this->licensing->has_feature( 'whitelabelling' ) ) {
			$this->dashboard->disable_review_request();
		}

		// Defer loading of Plugin Classes.
		add_action( 'init', array( $this, 'initialize' ), 1 );
		add_action( 'init', array( $this, 'upgrade' ), 2 );

		// Admin Menus.
		add_action( $this->plugin->filter_name . '_admin_admin_menu', array( $this, 'admin_menus' ) );

		// Add this Plugin's schedule options.
		add_filter( $this->plugin->filter_name . '_get_schedule_options', array( $this, 'get_schedule_options' ), 10, 3 );

		// Localization.
		add_action( 'init', array( $this, 'load_language_files' ) );

	}

	/**
	 * Defines the schedule options available for statuses in WP to Buffer Pro,
	 * in addition to the always-available immediate option provided by the library.
	 *
	 * @since   6.2.0
	 *
	 * @param   array       $schedule        Schedule Options.
	 * @param   string|bool $post_type       Post Type.
	 * @param   bool        $is_post_screen  Whether the request originates from the Post editing screen.
	 * @return  array                   Schedule Options
	 */
	public function get_schedule_options( $schedule, $post_type = false, $is_post_screen = false ) {

		// Buffer supports a queue and custom scheduling.
		$schedule['queue_end']       = __( 'Add to End of Queue', 'wpzinc-social' );
		$schedule['queue_start']     = __( 'Add to Start of Queue', 'wpzinc-social' );
		$schedule['custom']          = __( 'Custom Time', 'wpzinc-social' );
		$schedule['custom_relative'] = __( 'Custom Time (Relative Format)', 'wpzinc-social' );
		$schedule['custom_field']    = __( 'Custom Time (based on Custom Field / Post Meta Value)', 'wpzinc-social' );

		// If we're on the Post Screen, add a specific date/time option.
		if ( $is_post_screen ) {
			$schedule['specific'] = __( 'Specific Date and Time', 'wpzinc-social' );
		}

		return $schedule;

	}

	/**
	 * Register menus and submenus.
	 *
	 * @since   5.2.3
	 *
	 * @param   string $minimum_capability     Minimum required capability.
	 */
	public function admin_menus( $minimum_capability ) {

		// Bail if we cannot access any menus.
		if ( ! $this->licensing->can_access( 'show_menu' ) ) {
			return;
		}

		// Licensing.
		add_menu_page( $this->plugin->displayName, $this->plugin->displayName, $minimum_capability, $this->plugin->name, array( $this->licensing, 'licensing_screen' ), $this->plugin->logo );
		add_submenu_page( $this->plugin->name, __( 'Licensing', 'wpzinc-social' ), __( 'Licensing', 'wpzinc-social' ), $minimum_capability, $this->plugin->name, array( $this->licensing, 'licensing_screen' ) );

		// Bail if the product is not licensed.
		if ( ! $this->licensing->check_license_key_valid() ) {
			return;
		}

		// Licensed - add additional menu entries, if access permitted.
		if ( $this->licensing->can_access( 'show_menu_settings' ) ) {
			$settings_page = add_submenu_page( $this->plugin->name, __( 'Settings', 'wpzinc-social' ), __( 'Settings', 'wpzinc-social' ), $minimum_capability, $this->plugin->name . '-settings', array( $this->get_class( 'admin' ), 'settings_screen' ) );
		}

		// Only show Bulk Publish and Logs if connected to the API.
		if ( $this->get_class( 'settings' )->account_connected() ) {
			// Bulk Publish.
			if ( $this->licensing->can_access( 'show_menu_bulk_publish' ) ) {
				$bulk_publish_page = add_submenu_page( $this->plugin->name, __( 'Bulk Publish', 'wpzinc-social' ), __( 'Bulk Publish', 'wpzinc-social' ), $minimum_capability, $this->plugin->name . '-bulk-publish', array( $this->get_class( 'admin' ), 'bulk_publish_screen' ) );
			}

			// Logs.
			if ( $this->licensing->can_access( 'show_menu_logs' ) ) {
				if ( $this->get_class( 'log' )->is_enabled() ) {
					$log_page = add_submenu_page( $this->plugin->name, __( 'Logs', 'wpzinc-social' ), __( 'Logs', 'wpzinc-social' ), $minimum_capability, $this->plugin->name . '-log', array( $this->get_class( 'admin' ), 'log_screen' ) );
					add_action( "load-$log_page", array( $this->get_class( 'log' ), 'add_screen_options' ) );
				}
			}
		}

		// Import & Export.
		if ( $this->licensing->can_access( 'show_menu_import_export' ) ) {
			do_action( $this->plugin->filter_name . '_admin_menu_import_export' );
		}

		// Support.
		if ( $this->licensing->can_access( 'show_menu_support' ) ) {
			do_action( $this->plugin->filter_name . '_admin_menu_support' );
		}

	}

	/**
	 * Initializes required and licensed classes
	 *
	 * @since   3.4.9
	 */
	public function initialize() {

		$this->classes = new stdClass();

		// Initialize required classes.
		$this->classes->access      = new \WPZinc\Social\Pro\Access( self::$instance );
		$this->classes->admin       = new \WPZinc\Social\Pro\Admin( self::$instance );
		$this->classes->api         = new \WPZinc\Social\Pro\Buffer_API( self::$instance );
		$this->classes->common      = new \WPZinc\Social\Pro\Common( self::$instance );
		$this->classes->cron        = new \WPZinc\Social\Pro\Cron( self::$instance );
		$this->classes->image       = new \WPZinc\Social\Pro\Image( self::$instance );
		$this->classes->install     = new \WPZinc\Social\Pro\Install( self::$instance );
		$this->classes->log         = new \WPZinc\Social\Pro\Log( self::$instance );
		$this->classes->notices     = new \WPZinc\Social\Pro\Notices( self::$instance );
		$this->classes->screen      = new \WPZinc\Social\Pro\Screen( self::$instance );
		$this->classes->settings    = new \WPZinc\Social\Pro\Settings( self::$instance );
		$this->classes->twitter_api = new \WPZinc\Social\Pro\Twitter_API( self::$instance );
		$this->classes->validation  = new \WPZinc\Social\Pro\Validation( self::$instance );

		// Licensed.
		if ( $this->licensing->check_license_key_valid() ) {
			// Initialize licensed classes.
			$this->classes->ajax          = new \WPZinc\Social\Pro\Ajax( self::$instance );
			$this->classes->bulk_actions  = new \WPZinc\Social\Pro\Bulk_Actions( self::$instance );
			$this->classes->bulk_publish  = new \WPZinc\Social\Pro\Bulk_Publish( self::$instance );
			$this->classes->date          = new \WPZinc\Social\Pro\Date( self::$instance );
			$this->classes->export        = new \WPZinc\Social\Pro\Export( self::$instance );
			$this->classes->facebook_api  = new \WPZinc\Social\Pro\Facebook_API( self::$instance );
			$this->classes->import        = new \WPZinc\Social\Pro\Import( self::$instance );
			$this->classes->media_library = new \WPZinc\Social\Pro\Media_Library( self::$instance );
			$this->classes->post          = new \WPZinc\Social\Pro\Post( self::$instance );
			$this->classes->publish       = new \WPZinc\Social\Pro\Publish( self::$instance );
			$this->classes->repost        = new \WPZinc\Social\Pro\Repost( self::$instance );
			$this->classes->spintax       = new \WPZinc\Social\Pro\Spintax( self::$instance );

			// Integrations.
			$this->classes->acf                    = new \WPZinc\Social\Pro\ACF( self::$instance );
			$this->classes->aioseo                 = new \WPZinc\Social\Pro\AIOSEO( self::$instance );
			$this->classes->envira_gallery         = new \WPZinc\Social\Pro\Envira_Gallery( self::$instance );
			$this->classes->events_manager         = new \WPZinc\Social\Pro\Events_Manager( self::$instance );
			$this->classes->featured_image_caption = new \WPZinc\Social\Pro\Featured_Image_Caption( self::$instance );
			$this->classes->modern_events_calendar = new \WPZinc\Social\Pro\Modern_Events_Calendar( self::$instance );
			$this->classes->rank_math              = new \WPZinc\Social\Pro\Rank_Math( self::$instance );
			$this->classes->seopress               = new \WPZinc\Social\Pro\SEOPress( self::$instance );
			$this->classes->the_events_calendar    = new \WPZinc\Social\Pro\The_Events_Calendar( self::$instance );
			$this->classes->woocommerce            = new \WPZinc\Social\Pro\WooCommerce( self::$instance );
			$this->classes->wpml                   = new \WPZinc\Social\Pro\WPML( self::$instance );
			$this->classes->yoast_seo              = new \WPZinc\Social\Pro\Yoast_SEO( self::$instance );

			// Register CLI classes and commands.
			if ( class_exists( 'WP_CLI' ) ) {
				require_once $this->plugin->folder . '/includes/class-wp-to-buffer-pro-cli-bulk-publish.php';
				require_once $this->plugin->folder . '/includes/class-wp-to-buffer-pro-cli-repost.php';
				$this->classes->cli = new \WPZinc\Social\Pro\CLI( 'wp-to-buffer-pro', 'WP_To_Buffer_Pro' );
			}
		}

	}

	/**
	 * Runs the upgrade routine once the plugin has loaded
	 *
	 * @since   3.2.5
	 */
	public function upgrade() {

		// Run upgrade routine.
		$this->get_class( 'install' )->upgrade();

	}

	/**
	 * Loads plugin textdomain
	 *
	 * @since   1.0.0
	 */
	public function load_language_files() {

		load_plugin_textdomain( 'wpzinc-social', false, $this->plugin->name . '/languages/' );

	}

	/**
	 * Returns the given class
	 *
	 * @since   3.4.9
	 *
	 * @param   string $name   Class Name.
	 */
	public function get_class( $name ) {

		// If the class hasn't been loaded, throw a WordPress die screen
		// to avoid a PHP fatal error.
		if ( ! isset( $this->classes->{ $name } ) ) {
			// Define the error.
			$error = new WP_Error(
				'wp_to_buffer_pro_get_class',
				sprintf(
					/* translators: %1$s: Plugin Name, %2$s: PHP class name */
					__( '%1$s: Error: Could not load Plugin class %2$s', 'wpzinc-social' ),
					$this->plugin->displayName,
					$name
				)
			);

			// Depending on the request, return or display an error.
			// Admin UI.
			if ( is_admin() ) {
				wp_die(
					esc_html( $error->get_error_message() ),
					sprintf(
						/* translators: Plugin Name */
						esc_html__( '%s: Error', 'wpzinc-social' ),
						esc_html( $this->plugin->displayName )
					),
					array(
						'back_link' => true,
					)
				);
			}

			// Cron / CLI.
			return $error;
		}

		// Return the class object.
		return $this->classes->{ $name };

	}

	/**
	 * Helper method to determine whether this Plugin supports a specific feature.
	 *
	 * Typically used by the lib/ classes.
	 *
	 * @since   3.5.5
	 *
	 * @param   string $feature    Feature.
	 * @return  bool                Feature Supported
	 */
	public function supports( $feature ) {

		// Define supported featured.
		$supported_features = array(
			'additional_images',
			'annotations',
			'drafts',
			'googlebusiness',
			'pinterest_title',
			'webp',
			'multiple_accounts',
			'first_comment',
			'text_to_image',
			'video',
		);

		return in_array( $feature, $supported_features, true );

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @since   3.1.4
	 *
	 * @return  object Class.
	 */
	public static function get_instance() {

		if ( ! self::$instance instanceof self ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
