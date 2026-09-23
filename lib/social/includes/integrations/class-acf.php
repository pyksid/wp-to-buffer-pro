<?php
/**
 * ACF Plugin Class.
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 */

namespace WPZinc\Social\Pro;

/**
 * Provides compatibility with Advanced Custom Fields
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 * @version 5.3.4
 */
class ACF {

	/**
	 * Holds the base object.
	 *
	 * @since   5.3.4
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   5.3.4
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

		// Check if the ACF Plugin is active.
		if ( ! $this->is_active() ) {
			return;
		}

		// Images.
		add_filter( $this->base->plugin->filter_name . '_get_status_image_options', array( $this, 'get_status_image_options' ), 10, 3 );
		add_filter( $this->base->plugin->filter_name . '_get_status_additional_image_options', array( $this, 'get_status_additional_image_options' ), 10, 3 );
		add_filter( $this->base->plugin->filter_name . '_publish_build_args_image', array( $this, 'get_image' ), 10, 8 );
		add_filter( $this->base->plugin->filter_name . '_publish_get_additional_images', array( $this, 'get_additional_images' ), 10, 6 );

	}

	/**
	 * Adds options to the Status' Image dropdown for any ACF image fields that are registered
	 * for the given Post Type.
	 *
	 * @since   5.3.4
	 *
	 * @param   array  $options    Featured Image Dropdown Options.
	 * @param   string $network    Social Network.
	 * @param   string $post_type  Post Type.
	 */
	public function get_status_image_options( $options, $network, $post_type ) {

		// Bail if no Post Type.
		if ( ! $post_type ) {
			return $options;
		}

		// Fetch ACF Field Groups assigned to Content Groups.
		$acf_fields = $this->get_acf_fields_by_type( $post_type, 'image' );

		// Bail if no ACF Fields.
		if ( ! $acf_fields ) {
			return $options;
		}

		// Add fields to Featured Image options.
		foreach ( $acf_fields as $field_name => $field_label ) {
			$options[ 'acf_' . $field_name ] = array(
				'label'             => sprintf(
					/* translators: ACF Field Label */
					__( 'ACF: %s', 'wpzinc-social' ),
					$field_label
				),
				'additional_images' => true,
				'text_to_image'     => false,
			);
		}

		// Return the options.
		return $options;

	}

	/**
	 * Adds options to the Status' Additional Images dropdown for any ACF gallery fields that are registered
	 * for the given Post Type.
	 *
	 * @since   5.3.4
	 *
	 * @param   array  $options    Additional Image Options.
	 * @param   string $network    Social Network.
	 * @param   string $post_type  Post Type.
	 */
	public function get_status_additional_image_options( $options, $network, $post_type ) {

		// Bail if no Post Type.
		if ( ! $post_type ) {
			return $options;
		}

		// Fetch ACF Field Groups assigned to Content Groups.
		$acf_fields = $this->get_acf_fields_by_type( $post_type, 'gallery' );

		// Bail if no ACF Fields.
		if ( ! $acf_fields ) {
			return $options;
		}

		// Add fields to Featured Image options.
		foreach ( $acf_fields as $field_name => $field_label ) {
			$options[ 'acf_' . $field_name ] = sprintf(
				/* translators: ACF Field Label */
				__( 'ACF: %s', 'wpzinc-social' ),
				$field_label
			);
		}

		// Return the options.
		return $options;

	}

	/**
	 * Get the image from the given Post's ACF Field, to be used as the status'
	 * featured image.
	 *
	 * @since   5.3.4
	 *
	 * @param   false       $image          Image.
	 * @param   string      $image_setting  Image Setting.
	 * @param   \WP_Post    $post           WordPress Post.
	 * @param   string      $profile_id     Social Media Profile ID.
	 * @param   string      $service        Social Media Service.
	 * @param   array       $status         Parsed Status Message Settings.
	 * @param   string      $action         Action (publish|update|repost|bulk_publish).
	 * @param   bool|string $format         Status format (for example, 'story' or 'post' for Instagram).
	 */
	public function get_image( $image, $image_setting, $post, $profile_id, $service, $status, $action, $format ) {

		// Bail if the image setting isn't for ACF.
		if ( strpos( $image_setting, 'acf_' ) !== 0 ) {
			return $image;
		}

		// Get the image from the ACF Field.
		$image = get_field( str_replace( 'acf_', '', $image_setting ), $post->ID );

		// Bail if no image.
		if ( ! $image ) {
			return $image;
		}

		// Depending on the field's setting, the value might be an array, image URL or image ID.
		if ( is_array( $image ) ) {
			return $this->base->get_class( 'image' )->get_image_sources( $image['ID'], 'featured_image', $service, $format );
		}

		// Image ID.
		if ( is_numeric( $image ) ) {
			return $this->base->get_class( 'image' )->get_image_sources( $image, 'featured_image', $service, $format );
		}

		// Image URL.
		return $this->base->get_class( 'image' )->get_image_sources( attachment_url_to_postid( $image ), 'featured_image', $service, $format );

	}

	/**
	 * Get the additional images from the given Post's ACF Field, to be used as the status'
	 * additional images.
	 *
	 * @since   5.3.4
	 *
	 * @param   bool|array  $images     Images.
	 * @param   string      $additional_images_source      Additional Images Source.
	 * @param   \WP_Post    $post       Post.
	 * @param   string      $service    Service.
	 * @param   array       $status     Status Settings.
	 * @param   bool|string $format     Status format (for example, 'story' or 'post' for Instagram).
	 */
	public function get_additional_images( $images, $additional_images_source, $post, $service, $status, $format ) {

		// Bail if the image setting isn't for ACF.
		if ( strpos( $additional_images_source, 'acf_' ) !== 0 ) {
			return $images;
		}

		// Get the images from the ACF Gallery Field.
		$acf_gallery_images = get_field( str_replace( 'acf_', '', $additional_images_source ), $post->ID );

		// Bail if no imagse.
		if ( ! $acf_gallery_images || ! count( $acf_gallery_images ) ) {
			return $images;
		}

		// If the images passed to this filter are not an array, set it to an empty array.
		if ( ! is_array( $images ) ) {
			$images = array();
		}

		foreach ( $acf_gallery_images as $image ) {
			// Depending on the field's setting, the value might be an array, image URL or image ID.
			if ( is_array( $image ) ) {
				$images[] = $this->base->get_class( 'image' )->get_image_sources( $image['ID'], 'additional_image', $service, $format );
				continue;
			}

			// Image ID.
			if ( is_numeric( $image ) ) {
				$images[] = $this->base->get_class( 'image' )->get_image_sources( $image, 'additional_image', $service, $format );
				continue;
			}

			// Image URL.
			$images[] = $this->base->get_class( 'image' )->get_image_sources( attachment_url_to_postid( $image ), 'additional_image', $service, $format );
		}

		return $images;

	}

	/**
	 * Returns ACF Fields that are of a specific type
	 *
	 * @since   5.3.4
	 *
	 * @param   string $post_type  Post Type.
	 * @param   string $field_type Field Type.
	 * @return  bool|array
	 */
	private function get_acf_fields_by_type( $post_type, $field_type = 'image' ) {

		// Get ACF Field Groups.
		if ( ! count( acf_get_field_groups() ) ) {
			return false;
		}

		$field_groups = array();

		// Find ACF Field Groups assigned to the Post Type.
		foreach ( acf_get_field_groups() as $acf_field_group ) {
			foreach ( $acf_field_group['location'] as $group_locations ) {
				foreach ( $group_locations as $rule ) {
					if ( $rule['param'] === 'post_type' && $rule['operator'] === '==' && $rule['value'] === $post_type ) {
						$field_groups[] = $acf_field_group['key'];
					}
				}
			}
		}

		// If no field groups were found, return false.
		if ( ! count( $field_groups ) ) {
			return false;
		}

		$fields = array();

		// Get the fields for the field groups.
		foreach ( $field_groups as $field_group ) {
			if ( ! count( acf_get_fields( $field_group ) ) ) {
				continue;
			}

			foreach ( acf_get_fields( $field_group ) as $field ) {
				if ( $field['type'] !== $field_type ) {
					continue;
				}

				$fields[ $field['name'] ] = $field['label'];
			}
		}

		// Return.
		return $fields;

	}

	/**
	 * Checks if the Advanced Custom Fields Plugin is active
	 *
	 * @since   5.3.4
	 *
	 * @return  bool    Advanced Custom Fields Plugin Active
	 */
	private function is_active() {

		return defined( 'ACF_VERSION' );

	}

}
