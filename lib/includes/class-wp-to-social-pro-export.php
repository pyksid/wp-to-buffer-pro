<?php
/**
 * Export class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Exports settings to a JSON or ZIP file, for use on other
 * Plugin installations.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.2.2
 */
class WP_To_Social_Pro_Export {

	/**
	 * Holds the base object.
	 *
	 * @since   4.2.2
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor.
	 *
	 * @since   4.2.2
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Import.
		add_action( $this->base->plugin->filter_name . '_export', array( $this, 'export' ) );

	}

	/**
	 * Export data
	 *
	 * @since   4.2.2
	 *
	 * @param   array $data   Export Data.
	 * @return  array           Export Data
	 */
	public function export( $data ) {

		return array_merge( $data, $this->base->get_class( 'settings' )->get_all() );

	}

}
