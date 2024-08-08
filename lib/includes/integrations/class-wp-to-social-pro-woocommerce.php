<?php
/**
 * WooCommerce Plugin Class.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

/**
 * Provides compatibility with WooCommerce
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 4.3.8
 */
class WP_To_Social_Pro_WooCommerce {

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

		// Register Status Tags.
		add_filter( $this->base->plugin->filter_name . '_get_tags', array( $this, 'register_status_tags' ), 10, 2 );

		// Replace Tags with Values.
		add_filter( $this->base->plugin->filter_name . '_publish_get_all_possible_searches_replacements', array( $this, 'register_searches_replacements' ), 10, 3 );

	}

	/**
	 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
	 * These tags are also added to any 'Insert Tag' dropdowns.
	 *
	 * @since   4.3.8
	 *
	 * @param   array  $tags       Tags.
	 * @param   stirng $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function register_status_tags( $tags, $post_type ) {

		// Bail if WooCommerce isn't active.
		if ( ! $this->is_active() ) {
			return $tags;
		}

		// Bail if this isn't a Product Post Type.
		if ( $post_type !== 'product' ) {
			return $tags;
		}

		// Register Status Tags.
		return array_merge(
			$tags,
			array(
				'woocommerce' => array(
					'{woocommerce_price}'          => __( 'Price', 'wp-to-social-pro' ),
					'{woocommerce_regular_price}'  => __( 'Regular Price', 'wp-to-social-pro' ),
					'{woocommerce_sale_price}'     => __( 'Sale Price', 'wp-to-social-pro' ),
					'{woocommerce_sale_date_from}' => __( 'Sale Date: Start', 'wp-to-social-pro' ),
					'{woocommerce_sale_date_to}'   => __( 'Sale Date: To', 'wp-to-social-pro' ),
					'{woocommerce_sku}'            => __( 'SKU', 'wp-to-social-pro' ),
					'{woocommerce_quantity}'       => __( 'Quantity', 'wp-to-social-pro' ),
					'{woocommerce_weight}'         => __( 'Weight', 'wp-to-social-pro' ),
					'{woocommerce_dimensions}'     => __( 'Dimensions', 'wp-to-social-pro' ),
					'{woocommerce_rating}'         => __( 'Average Rating', 'wp-to-social-pro' ),
					'{woocommerce_reviews}'        => __( 'Review Count', 'wp-to-social-pro' ),
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

		// Bail if WooCommerce isn't active.
		if ( ! $this->is_active() ) {
			return $searches_replacements;
		}

		// Bail if this isn't a Product Post Type.
		if ( $post->post_type !== 'product' ) {
			return $searches_replacements;
		}

		// Register Tags and their replacement values.
		return array_merge( $searches_replacements, $this->get_searches_replacements( $post, $author ) );

	}

	/**
	 * Returns tags and their Post data replacements, that are supported for WooCommerce
	 *
	 * @since   4.3.8
	 *
	 * @param   WP_Post $post                   WordPress Post.
	 * @param   WP_User $author                 WordPress User (Author of the Post).
	 * @return  array                               Registered Supported Tags and their Replacements
	 */
	private function get_searches_replacements( $post, $author ) {

		// Get Product.
		$product = wc_get_product( $post );

		// Register Searches and Replacements.
		$searches_replacements = array(
			'woocommerce_price'          => wc_format_localized_price( $product->get_price() ),
			'woocommerce_regular_price'  => wc_format_localized_price( $product->get_regular_price() ),
			'woocommerce_sale_price'     => wc_format_localized_price( $product->get_sale_price() ),
			'woocommerce_sale_date_from' => date_i18n( get_option( 'date_format' ), strtotime( $product->get_date_on_sale_from() ) ),
			'woocommerce_sale_date_to'   => date_i18n( get_option( 'date_format' ), strtotime( $product->get_date_on_sale_to() ) ),
			'woocommerce_sku'            => $product->get_sku(),
			'woocommerce_quantity'       => $product->get_stock_quantity(),
			'woocommerce_weight'         => wc_format_weight( $product->get_weight() ),
			'woocommerce_dimensions'     => wc_format_dimensions( $product->get_dimensions( false ) ),
			'woocommerce_rating'         => $product->get_average_rating(),
			'woocommerce_reviews'        => $product->get_review_count(),
		);

		/**
		 * Registers any additional status message tags, and their Post data replacements, that are supported
		 * for WooCommerce.
		 *
		 * @since   3.8.1
		 *
		 * @param   array       $searches_replacements  Registered Supported Tags and their Replacements.
		 * @param   WP_Post     $post                   WordPress Post.
		 * @param   WP_User $author                     WordPress User (Author of the Post).
		 */
		$searches_replacements = apply_filters( $this->base->plugin->filter_name . '_publish_register_woocommerce_searches_replacements', $searches_replacements, $post, $author );

		// Return filtered results.
		return $searches_replacements;

	}

	/**
	 * Checks if the WooCommerce Plugin is active
	 *
	 * @since   4.3.8
	 *
	 * @return  bool    WooCommerce Plugin Active
	 */
	private function is_active() {

		return class_exists( 'WooCommerce' );

	}

}
