<?php
/**
 * WP Zinc Facebook API class
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Calls WP Zinc's API to perform ID to username lookups.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.5.7
 */
class WP_To_Social_Pro_Facebook_API extends WP_To_Social_Pro_API {

	/**
	 * Holds the base class object.
	 *
	 * @since   4.5.7
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Holds the API endpoint
	 *
	 * @since   4.5.7
	 *
	 * @var     string
	 */
	public $api_endpoint = 'https://www.wpzinc.com/?facebook_api=1';

	/**
	 * Constructor
	 *
	 * @since   4.5.7
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Returns usernames for the given search term
	 *
	 * @since   4.5.7
	 *
	 * @param   string $search     Search Term.
	 * @return  mixed               WP_Error | array
	 */
	public function usernames_search( $search ) {

		return $this->post(
			'users_search',
			array(
				'input' => $search,
			)
		);

	}

}
