<?php
/**
 * AIOSEO Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Provides compatibility with All in One SEO
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.3.8
 */
class WP_To_Social_Pro_AIOSEO {

	/**
	 * Holds the base object.
	 *
	 * @since   4.3.8
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.3.8
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Register this integration as supporting OpenGraph.
		add_filter( $this->base->plugin->filter_name . '_get_opengraph_seo_plugins', array( $this, 'register_opengraph_seo_plugins' ) );

		// Register Status Tags.
		add_filter( $this->base->plugin->filter_name . '_get_tags', array( $this, 'register_status_tags' ), 10, 1 );

		// Replace Tags with Values.
		add_filter( $this->base->plugin->filter_name . '_publish_get_all_possible_searches_replacements', array( $this, 'register_searches_replacements' ), 10, 3 );

	}

	/**
	 * Register this integration as supporting OpenGraph, so that the Plugin
	 * can check if it's active, and if so offer the "Use OpenGraph Settings"
	 * image option on statuses.
	 *
	 * @since   4.8.4
	 *
	 * @param   array $plugins    Plugins supporting OpenGraph.
	 * @return  array               Plugins
	 */
	public function register_opengraph_seo_plugins( $plugins ) {

		$plugins[] = 'all-in-one-seo-pack/all_in_one_seo_pack.php';
		$plugins[] = 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php';
		return $plugins;

	}

	/**
	 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
	 * These tags are also added to any 'Insert Tag' dropdowns.
	 *
	 * @since   4.3.8
	 *
	 * @param   array $tags       Tags.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags ) {

		// Bail if All in One SEO isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'all_in_one_seo_pack' => array(
					'{aioseo_meta_title}'       => __( 'Meta Title', 'wp-to-social-pro' ),
					'{aioseo_meta_description}' => __( 'Meta Description', 'wp-to-social-pro' ),
				),
			)
		);

	}

	/**
	 * Registers any additional status message tags, and their Post data replacements, that are supported.
	 *
	 * @since   4.3.8
	 *
	 * @param   array   $searches_replacements  Registered Supported Tags and their Replacements.
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	public function register_searches_replacements( $searches_replacements, $post, $author ) {

		// Bail if All in One SEO isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for AIOSEO.
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		global $aiosp;

		// Store Title and Description.
		$searches_replacements = array(
			'aioseo_meta_title'       => $this->get_title( $post ),
			'aioseo_meta_description' => $this->get_description( $post ),
		);

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for AIOSEO.
		 *
		 * @since   3.8.1
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_aio_seo_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Return the Title
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              AIOSEO Post Title
	 */
	private function get_title( $post ) {

		global $aiosp;

		// Get Title.
		// Can't use get_aioseop_title() as it checks e.g. is_single() which is false here.
		$title = false;

		// Attempt to fetch the title from Post Meta, falling back to Plugin Settings.
		$title = $aiosp->internationalize( get_post_meta( $post->ID, '_aioseop_title', true ) );
		if ( ! $title ) {
			$title = $aiosp->internationalize( get_post_meta( $post->ID, 'title_tag', true ) );
		}
		if ( ! $title ) {
			$title = $aiosp->internationalize( $post->post_title );
		}
		if ( ! $title ) {
			$title = $aiosp->internationalize( $aiosp->get_original_title( '', false ) );
		}

		// Apply Custom Field Filters.
		$title = $aiosp->apply_cf_fields( $title );

		// Apply some other Filters.
		$title = apply_filters( 'aioseop_title', $title );
		$title = apply_filters( 'aioseop_title_single', $title );

		// Return.
		return $title;

	}

	/**
	 * Return the Description
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              AIOSEO Post Description
	 */
	private function get_description( $post ) {

		global $aiosp;

		// Get Description.
		$description = $aiosp->get_aioseop_description( $post );
		$description = $aiosp->trim_description( $description );
		$description = apply_filters( 'aioseop_description_full', $aiosp->apply_description_format( $description, $post ) );

		return $description;

	}

	/**
	 * Checks if the All in One SEO Plugin is active
	 *
	 * @since   4.3.8
	 *
	 * @return  bool    All in One SEO Plugin Active
	 */
	private function is_active() {

		return defined( 'AIOSEOP_VERSION' );

	}

}
