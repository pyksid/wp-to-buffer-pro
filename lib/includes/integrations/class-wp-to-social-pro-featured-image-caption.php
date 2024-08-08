<?php
/**
 * Featured Image Caption Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Provides compatibility with Featured Image Caption Plugin.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.7.5
 */
class WP_To_Social_Pro_Featured_Image_Caption {

	/**
	 * Holds the base object.
	 *
	 * @since   4.7.5
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.7.5
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Register Status Tags.
		add_filter( $this->base->plugin->filter_name . '_get_tags', array( $this, 'register_status_tags' ), 10, 1 );

		// Replace Tags with Values.
		add_filter( $this->base->plugin->filter_name . '_publish_get_all_possible_searches_replacements', array( $this, 'register_searches_replacements' ), 10, 3 );

	}

	/**
	 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
	 * These tags are also added to any 'Insert Tag' dropdowns.
	 *
	 * @since   4.7.5
	 *
	 * @param   array $tags       Tags.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags ) {

		// Bail if Plugin isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'featured_image_caption' => array(
					'{featured_image_caption_caption}' => __( 'Caption', 'wp-to-social-pro' ),
					'{featured_image_caption_text}'    => __( 'Source Text', 'wp-to-social-pro' ),
					'{featured_image_caption_url}'     => __( 'Source URL', 'wp-to-social-pro' ),
				),
			)
		);

	}

	/**
	 * Registers any additional status message tags, and their Post data replacements, that are supported.
	 *
	 * @since   4.7.5
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	public function register_searches_replacements( $searches_replacements, $post, $author ) {

		// Bail if Plugin isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for this Plugin.
	 *
	 * @since   4.7.5
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		// Get Featured Image Caption meta.
		$meta = get_post_meta( $post->ID, '_cc_featured_image_caption', true );

		// Register searches and replacement values.
		$searches_replacements = array(
			'featured_image_caption_caption' => ( is_array( $meta ) && isset( $meta['caption_text'] ) ? $meta['caption_text'] : '' ),
			'featured_image_caption_text'    => ( is_array( $meta ) && isset( $meta['source_text'] ) ? $meta['source_text'] : '' ),
			'featured_image_caption_url'     => ( is_array( $meta ) && isset( $meta['source_url'] ) ? $meta['source_url'] : '' ),
		);

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for the Featured Image Caption Plugin.
		 *
		 * @since   4.7.5
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements..
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_featured_image_caption_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Checks if the Plugin is active
	 *
	 * @since   4.7.5
	 *
	 * @return  bool    Plugin Active
	 */
	private function is_active() {

		return defined( 'CCFIC_ID' );

	}

}
