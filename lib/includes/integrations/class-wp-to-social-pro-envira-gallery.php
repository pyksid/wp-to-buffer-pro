<?php
/**
 * Envira Gallery Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Provides compatibility with All in One SEO
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.8.4
 */
class WP_To_Social_Pro_Envira_Gallery {

	/**
	 * Holds the base object.
	 *
	 * @since   4.8.4
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.8.4
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		add_filter( 'envira_gallery_metabox_ids', array( $this, 'permit_meta_boxes' ) );

	}

	/**
	 * Permits this Plugin's Post Metaboxes for display on Envira Galleries.
	 *
	 * @since   4.8.4
	 *
	 * @param   array $meta_box_ids   Meta Box ID names to display on Envira Galleries.
	 * @return  array                   Meta Box ID names to display on Envira Galleries
	 */
	public function permit_meta_boxes( $meta_box_ids ) {

		$meta_box_ids[] = $this->base->plugin->name;
		$meta_box_ids[] = $this->base->plugin->name . '-image';

		return $meta_box_ids;

	}

}
