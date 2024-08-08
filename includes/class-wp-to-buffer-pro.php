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
	 * @var     object
	 */
	public static $instance;

	/**
	 * Plugin
	 *
	 * @since   3.0.0
	 *
	 * @var     object
	 */
	public $plugin = '';

	/**
	 * Dashboard
	 *
	 * @since   3.1.4
	 *
	 * @var     object
	 */
	public $dashboard = '';

	/**
	 * Licensing
	 *
	 * @since   3.1.4
	 *
	 * @var     object
	 */
	public $licensing = '';

	/**
	 * Classes
	 *
	 * @since   3.4.9
	 *
	 * @var     array
	 */
	public $classes = '';

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
		$this->plugin->author_name       = 'WP Zinc';
		$this->plugin->settingsName      = 'wp-to-buffer-pro'; // Settings key - used in both Free + Pro, and for oAuth.
		$this->plugin->account           = 'Buffer';
		$this->plugin->version           = WP_TO_BUFFER_PRO_PLUGIN_VERSION;
		$this->plugin->buildDate         = WP_TO_BUFFER_PRO_PLUGIN_BUILD_DATE;
		$this->plugin->requires          = '5.0';
		$this->plugin->tested            = '6.0.1';
		$this->plugin->folder            = WP_TO_BUFFER_PRO_PLUGIN_PATH;
		$this->plugin->url               = WP_TO_BUFFER_PRO_PLUGIN_URL;
		$this->plugin->documentation_url = 'https://www.wpzinc.com/documentation/wordpress-buffer-pro';
		$this->plugin->support_url       = 'https://www.wpzinc.com/support';
		$this->plugin->upgrade_url       = 'https://www.wpzinc.com/plugins/wordpress-to-buffer-pro';
		$this->plugin->review_name       = 'wp-to-buffer';
		$this->plugin->review_notice     = sprintf(
			/* translators: Plugin Name */
			__( 'Thanks for using %s to schedule your social media statuses on Buffer!', 'wp-to-social-pro' ),
			$this->plugin->displayName
		);

		// Default Settings.
		$this->plugin->default_schedule = 'queue_bottom';

		// Licensing Submodule.
		if ( ! class_exists( 'LicensingUpdateManager' ) ) {
			require_once $this->plugin->folder . '_modules/licensing/class-licensingupdatemanager.php';
		}
		$this->licensing = new LicensingUpdateManager( $this->plugin, 'https://www.wpzinc.com' );

		// Run Plugin Display Name, URLs through Whitelabelling if available.
		$this->plugin->displayName       = $this->licensing->get_feature_parameter( 'whitelabelling', 'display_name', $this->plugin->displayName );
		$this->plugin->support_url       = $this->licensing->get_feature_parameter( 'whitelabelling', 'support_url', $this->plugin->support_url );
		$this->plugin->documentation_url = $this->licensing->get_feature_parameter( 'whitelabelling', 'documentation_url', $this->plugin->documentation_url );

		// Dashboard Submodule.
		if ( ! class_exists( 'WPZincDashboardWidget' ) ) {
			require_once $this->plugin->folder . '_modules/dashboard/class-wpzincdashboardwidget.php';
		}
		$this->dashboard = new WPZincDashboardWidget( $this->plugin, 'https://www.wpzinc.com/wp-content/plugins/lum-deactivation' );

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

		// Localization.
		add_action( 'plugins_loaded', array( $this, 'load_language_files' ) );

	}

	/**
	 * Initializes required and licensed classes
	 *
	 * @since   3.4.9
	 */
	public function initialize() {

		$this->classes = new stdClass();

		// Initialize required classes.
		$this->classes->access     = new WP_To_Social_Pro_Access( self::$instance );
		$this->classes->admin      = new WP_To_Social_Pro_Admin( self::$instance );
		$this->classes->common     = new WP_To_Social_Pro_Common( self::$instance );
		$this->classes->cron       = new WP_To_Social_Pro_Cron( self::$instance );
		$this->classes->image      = new WP_To_Social_Pro_Image( self::$instance );
		$this->classes->install    = new WP_To_Social_Pro_Install( self::$instance );
		$this->classes->log        = new WP_To_Social_Pro_Log( self::$instance );
		$this->classes->notices    = new WP_To_Social_Pro_Notices( self::$instance );
		$this->classes->screen     = new WP_To_Social_Pro_Screen( self::$instance );
		$this->classes->settings   = new WP_To_Social_Pro_Settings( self::$instance );
		$this->classes->validation = new WP_To_Social_Pro_Validation( self::$instance );

		// Licensed.
		if ( $this->licensing->check_license_key_valid() ) {
			// Initialize licensed classes.
			$this->classes->ajax          = new WP_To_Social_Pro_AJAX( self::$instance );
			$this->classes->api           = new WP_To_Social_Pro_Buffer_API( self::$instance );
			$this->classes->bulk_actions  = new WP_To_Social_Pro_Bulk_Actions( self::$instance );
			$this->classes->bulk_publish  = new WP_To_Social_Pro_Bulk_Publish( self::$instance );
			$this->classes->date          = new WP_To_Social_Pro_Date( self::$instance );
			$this->classes->export        = new WP_To_Social_Pro_Export( self::$instance );
			$this->classes->facebook_api  = new WP_To_Social_Pro_Facebook_API( self::$instance );
			$this->classes->import        = new WP_To_Social_Pro_Import( self::$instance );
			$this->classes->media_library = new WP_To_Social_Pro_Media_Library( self::$instance );
			$this->classes->post          = new WP_To_Social_Pro_Post( self::$instance );
			$this->classes->publish       = new WP_To_Social_Pro_Publish( self::$instance );
			$this->classes->repost        = new WP_To_Social_Pro_Repost( self::$instance );
			$this->classes->spintax       = new WP_To_Social_Pro_Spintax( self::$instance );
			$this->classes->twitter_api   = new WP_To_Social_Pro_Twitter_API( self::$instance );

			// Integrations.
			$this->classes->aioseo                 = new WP_To_Social_Pro_AIOSEO( self::$instance );
			$this->classes->envira_gallery         = new WP_To_Social_Pro_Envira_Gallery( self::$instance );
			$this->classes->events_manager         = new WP_To_Social_Pro_Events_Manager( self::$instance );
			$this->classes->featured_image_caption = new WP_To_Social_Pro_Featured_Image_Caption( self::$instance );
			$this->classes->modern_events_calendar = new WP_To_Social_Pro_Modern_Events_Calendar( self::$instance );
			$this->classes->rank_math              = new WP_To_Social_Pro_Rank_Math( self::$instance );
			$this->classes->seopress               = new WP_To_Social_Pro_SEOPress( self::$instance );
			$this->classes->the_events_calendar    = new WP_To_Social_Pro_The_Events_Calendar( self::$instance );
			$this->classes->woocommerce            = new WP_To_Social_Pro_WooCommerce( self::$instance );
			$this->classes->wpml                   = new WP_To_Social_Pro_WPML( self::$instance );
			$this->classes->yoast_seo              = new WP_To_Social_Pro_Yoast_SEO( self::$instance );

			// Run the migration routine from Free + Pro v2.x --> Pro v3.x.
			if ( is_admin() ) {
				$this->classes->settings->migrate_settings();
			}

			// Register CLI classes and commands.
			if ( class_exists( 'WP_CLI' ) ) {
				require_once $this->plugin->folder . '/includes/class-wp-to-buffer-pro-cli-bulk-publish.php';
				require_once $this->plugin->folder . '/includes/class-wp-to-buffer-pro-cli-repost.php';
				$this->classes->cli = new WP_To_Social_Pro_CLI( 'wp-to-buffer-pro', 'WP_To_Buffer_Pro' );
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

		load_plugin_textdomain( 'wp-to-social-pro', false, $this->plugin->name . '/languages/' );

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
					__( '%1$s: Error: Could not load Plugin class %2$s', 'wp-to-social-pro' ),
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
						esc_html__( '%s: Error', 'wp-to-social-pro' ),
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
			'url_shortening',
			'additional_images',
			'facebook_mentions',
			'drafts',
			'googlebusiness',
			'instagram_update_type',
			'webp',
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

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}

		return self::$instance;

	}

}
