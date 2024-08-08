<?php
/**
 * Rank Math Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Provides compatibility with Rank Math
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.3.8
 */
class WP_To_Social_Pro_Rank_Math {

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

		$plugins[] = 'seo-by-rank-math/rank-math.php';
		$plugins[] = 'seo-by-rank-math-pro/rank-math-pro.php';
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

		// Bail if Rank Math isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'rank_math' => array(
					'{rank_math_meta_title}'       => __( 'Meta Title', 'wp-to-social-pro' ),
					'{rank_math_meta_description}' => __( 'Meta Description', 'wp-to-social-pro' ),
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

		// Bail if Rank Math isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for Rank Math.
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		// Store Title and Description.
		$searches_replacements = array(
			'rank_math_meta_title'       => $this->get_title( $post ),
			'rank_math_meta_description' => $this->get_description( $post ),
		);

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for Rank Math SEO.
		 *
		 * @since   4.3.8
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User     $author                 WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_rank_math_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Return the Title
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              Rank Math Post Title
	 */
	private function get_title( $post ) {

		// Get title from Post Meta.
		return RankMath\Post::get_meta( 'title', $post->ID );

	}

	/**
	 * Return the Description
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post   WordPress Post.
	 * @return  string              Rank Math Post Description
	 */
	private function get_description( $post ) {

		// Get description from Post Meta.
		return RankMath\Post::get_meta( 'description', $post->ID );

	}

	/**
	 * Checks if the Rank Math Plugin is active
	 *
	 * @since   4.3.8
	 *
	 * @return  bool    Rank Math Plugin Active
	 */
	private function is_active() {

		return defined( 'RANK_MATH_PATH' );

	}

}
