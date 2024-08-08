<?php
/**
 * Access Control class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Controls if parts of the Plugin can be accessed by the logged in user.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.4.3
 */
class WP_To_Social_Pro_Access {

	/**
	 * Holds the base object.
	 *
	 * @since   4.4.3
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds an array of submenus to display, if any
	 *
	 * @since   4.4.3
	 *
	 * @var     mixed   false | array
	 */
	private $show_submenus = false;

	/**
	 * Holds an array of permitted users, if any
	 *
	 * @since   4.4.3
	 *
	 * @var     mixed   false | array
	 */
	private $permitted_users = false;

	/**
	 * Holds the current logged in User
	 *
	 * @since   4.4.3
	 *
	 * @var     mixed   false | WP_User
	 */
	private $current_user = false;

	/**
	 * Constructor
	 *
	 * @since   4.4.3
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Determines whether the logged in WordPress User has access to a particular
	 * feature, by:
	 * - checking if the license key has access control options,
	 * - the feature is defined in the wp-config file,
	 * - the value in the wp-config file permits or denies access
	 *
	 * This function assumes access until a condition revokes it.
	 *
	 * @since   4.4.3
	 *
	 * @param   string $parameter  Feature Parameter the user is attempting to access.
	 * @return  bool                User can access feature
	 */
	public function can_access( $parameter ) {

		// If the logged in user is always permitted to use the Plugin, always allow access,
		// ignoring any other setting.
		if ( $this->is_logged_in_user_always_permitted() ) {
			return true;
		}

		switch ( $parameter ) {

			/**
			 * Menu
			 */
			case 'show_menu':
				return $this->display_menu();

			/**
			 * Submenu
			 */
			default:
				list( $ignored, $submenu ) = explode( 'show_menu_', $parameter );
				return $this->display_submenu( $submenu );

		}

	}

	/**
	 * Determines if the Plugin's Top Level Menu should be displayed
	 *
	 * @since   4.4.3
	 *
	 * @return  bool    Display Menu
	 */
	private function display_menu() {

		return $this->base->licensing->get_feature_parameter( 'access_control', 'show_menu', true );

	}

	/**
	 * Determines if the given Plugin's Child / Sub Menu should be displayed
	 *
	 * @since   4.4.3
	 *
	 * @param   string $submenu    Submenu to Display.
	 * @return  bool                Display Submenu
	 */
	private function display_submenu( $submenu ) {

		// Get submenus to display.
		if ( ! $this->show_submenus ) {
			$this->show_submenus = $this->base->licensing->get_feature_parameter( 'access_control', 'show_submenus', true );
		}

		// For backward compatibility, check some other submenu constants that might exist in 2.1.7 - 2.4.3
		// e.g. PAGE-GENERATOR_PRO_SHOW_MENU_SETTINGS, which is now PAGE-GENERATOR-PRO-SHOW_SUBMENUS = settings.
		if ( defined( strtoupper( $this->base->plugin->name ) . '_SHOW_MENU_' . strtoupper( $submenu ) ) ) {
			return constant( strtoupper( $this->base->plugin->name ) . '_SHOW_MENU_' . strtoupper( $submenu ) );
		}

		// If no submenus to display are specified, allow all submenus.
		if ( ! is_array( $this->show_submenus ) ) {
			return true;
		}
		if ( ! count( $this->show_submenus ) ) {
			return true;
		}

		// Check if the submenu is a permitted submenu.
		return in_array( $submenu, $this->show_submenus, true );

	}

	/**
	 * Determines if the logged in User is always permitted to access the Plugin, regardless
	 * of any other settings that might be defined.
	 *
	 * @since   4.4.3
	 *
	 * @return  bool    User is permitted
	 */
	private function is_logged_in_user_always_permitted() {

		// Get permitted users.
		if ( ! $this->permitted_users ) {
			$this->permitted_users = $this->base->licensing->get_feature_parameter( 'access_control', 'permitted_users', false );
		}

		// If no permitted users are specified, the user is not permitted to override access control settings.
		if ( ! $this->permitted_users ) {
			return false;
		}
		if ( empty( $this->permitted_users ) ) {
			return false;
		}

		// Fetch the logged in User.
		if ( ! $this->current_user ) {
			$this->current_user = wp_get_current_user();
		}

		// Check if the logged in User is a permitted User.
		foreach ( $this->permitted_users as $permitted_user ) {
			// Permitted user can be a User ID, username or email address.
			if ( $this->current_user->user_login === $permitted_user ) {
				return true;
			}
			if ( $this->current_user->user_email === $permitted_user ) {
				return true;
			}
			if ( $this->current_user->ID === absint( $permitted_user ) ) {
				return true;
			}
		}

		// If here, the user is not permitted.
		return false;

	}

}
