<?php
/**
 * Image class.
 *
 * @package WPZinc\Social\Pro
 * @author WP Zinc
 */

namespace WPZinc\Social\Pro;

/**
 * Determines optimal image sizes and aspect ratios for each
 * social networks, detects if such sizes are registered
 * in WordPress and (where possible) resizes and crops
 * images.
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 * @version 4.6.6
 */
class Image {

	/**
	 * Holds the base class object.
	 *
	 * @since   4.6.6
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   4.6.6
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Helper method to retrieve Image Options
	 *
	 * @since   3.4.3
	 *
	 * @param   bool        $network    Network (false = defaults).
	 * @param   bool|string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	/**
	 * Returns the available video source options for a status.
	 *
	 * @since   6.2.5
	 *
	 * @return  array   Video source options.
	 */
	public function get_status_video_options() {

		$options = array(
			'media_library' => __( 'Media Library', 'wpzinc-social' ),
			'url'           => __( 'URL', 'wpzinc-social' ),
		);

		/**
		 * Defines the available video source options for a status.
		 *
		 * @since   6.2.5
		 *
		 * @param   array   $options    Video source options.
		 */
		$options = apply_filters( $this->base->plugin->filter_name . '_get_status_video_options', $options );

		return $options;

	}

	/**
	 * Helper method to retrieve Image Options
	 *
	 * @since   3.4.3
	 *
	 * @param   bool        $network    Network (false = defaults).
	 * @param   bool|string $post_type  Post Type.
	 * @return  array
	 */
	public function get_status_image_options( $network = false, $post_type = false ) {

		// If a Post Type has been specified, get its featured_image label.
		$label = __( 'Feat. Image', 'wpzinc-social' );
		if ( $post_type !== false && $post_type !== 'bulk' ) {
			$post_type_object = get_post_type_object( $post_type );
			$label            = $post_type_object->labels->featured_image;
		}

		// Build featured image options.
		$options = array(
			'featured_image' => array(
				'label'             => $label,
				'additional_images' => true,
				'text_to_image'     => false,
			),
		);

		// Add Text to Image option if supported by the Plugin.
		if ( $this->base->supports( 'text_to_image' ) ) {
			$options['text_to_image'] = array(
				'label'             => __( 'Text to Image', 'wpzinc-social' ),
				'additional_images' => false,
				'text_to_image'     => true,
			);
		}

		/**
		 * Defines the available Featured Image select dropdown options on a status, depending
		 * on the Plugin and Social Network the status message is for.
		 *
		 * @since   3.4.3
		 *
		 * @param   array   $options    Featured Image Dropdown Options.
		 * @param   bool|string $network    Social Network.
		 * @param   string  $post_type  Post Type.
		 */
		$options = apply_filters( $this->base->plugin->filter_name . '_get_status_image_options', $options, $network, $post_type );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Additional Image Options that are supported by the given network and post type.
	 *
	 * @since   5.3.4
	 *
	 * @param   bool        $network    Network (false = defaults).
	 * @param   bool|string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	public function get_status_image_options_supporting_additional_images( $network = false, $post_type = false ) {

		return array_filter(
			$this->get_status_image_options( $network, $post_type ),
			function ( $option ) {
				return $option['additional_images'];
			}
		);

	}

	/**
	 * Helper method to retrieve Text to Image Options that are supported by the given network and post type.
	 *
	 * @since   5.3.4
	 *
	 * @param   bool        $network    Network (false = defaults).
	 * @param   bool|string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	public function get_status_image_options_supporting_text_to_image( $network = false, $post_type = false ) {

		return array_filter(
			$this->get_status_image_options( $network, $post_type ),
			function ( $option ) {
				return $option['text_to_image'];
			}
		);

	}

	/**
	 * Helper method to retrieve Additional Image Options
	 *
	 * @since   5.3.4
	 *
	 * @param   bool        $network    Network (false = defaults).
	 * @param   bool|string $post_type  Post Type.
	 * @return  array              Image Options
	 */
	public function get_status_additional_image_options( $network = false, $post_type = false ) {

		// If a Post Type has been specified, get its label.
		$label = __( 'Post', 'wpzinc-social' );
		if ( $post_type !== false && $post_type !== 'bulk' ) {
			$post_type_object = get_post_type_object( $post_type );
			$label            = $post_type_object->labels->singular_name;
		}

		$options = array(
			''              => __( 'None', 'wpzinc-social' ),
			'post_settings' => __( 'Specified in Post settings', 'wpzinc-social' ),
			'post_content'  => sprintf(
				/* translators: Translated name for a Post Type's Featured Image (e.g. for WooCommerce, might be "Product image") */
				__( 'Auto populate from %s content', 'wpzinc-social' ),
				$label
			),
		);

		/**
		 * Defines the available Additional Images select dropdown options on a status.
		 *
		 * @since   5.3.4
		 *
		 * @param   array   $options    Featured Image Dropdown Options.
		 * @param   bool|string $network    Social Network.
		 * @param   string  $post_type  Post Type.
		 */
		$options = apply_filters( $this->base->plugin->filter_name . '_get_status_additional_image_options', $options, $network, $post_type );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve the image size for the given service.
	 *
	 * @since   4.6.6
	 *
	 * @param   string      $service              Social Media Service.
	 * @param   bool|string $status_post_type     Status format (for example, 'story' or 'post' for Instagram).
	 * @return  array|bool                        Social Media Image Size
	 */
	public function get_social_media_image_size( $service, $status_post_type = false ) {

		// Get image sizes for all social networks.
		$image_sizes = $this->get_social_media_image_sizes();

		// If a format is defined, append it to the $service to produce e.g instagram_story.
		switch ( $status_post_type ) {
			case 'story':
				$key = $service . '_story';
				break;
			default:
				$key = $service;
				break;
		}

		// Bail if the service doesn't have an image size defined.
		if ( ! isset( $image_sizes[ $key ] ) ) {
			return false;
		}

		$image_size = $image_sizes[ $key ];

		/**
		 * Defines the image size limit for the given social media service.
		 *
		 * @since   4.6.6
		 *
		 * @param   array       $image_size         Image Size (width, height).
		 * @param   string      $service            Social Media Service.
		 * @param   bool|string $status_post_type   Status Post Type.
		 */
		$image_size = apply_filters( $this->base->plugin->filter_name . '_get_social_media_image_size', $image_size, $service, $status_post_type );

		// Return filtered result.
		return $image_size;

	}

	/**
	 * Returns the image for the given Attachment ID, based on the social media service
	 * the image will be used for.
	 *
	 * If the image isn't a compatible mime type, this function will attempt to convert the image
	 * from e.g. webp --> jpg.
	 *
	 * Checks that the image will meet the aspect ratio requirements for posting to Instagram,
	 * returning a valid image if the large size would fail.
	 *
	 * @since   4.6.6
	 *
	 * @param   int         $image_id             Image ID.
	 * @param   string      $source               Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
	 * @param   bool|string $service              Social Media Service the image is for. If not defined, just return the large version.
	 * @param   bool|string $status_post_type     Status format (for example, 'story' or 'post' for Instagram).
	 * @return  array|\WP_Error                    Image ID, Image URLs, Source
	 */
	public function get_image_sources( $image_id, $source, $service = false, $status_post_type = false ) {

		// Load WordPress image libraries, if they are not currently loaded.
		if ( ! class_exists( 'WP_Image_Editor' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
			require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
		}

		$image_mime_type = get_post_mime_type( $image_id );

		// If the image is a webp, attempt to convert it to a JPEG and store in the Media Library
		// as webp isn't supported by all social media services.
		// Check that the image source is a supported format i.e. not a webp.
		switch ( $image_mime_type ) {
			/**
			 * Webp
			 */
			case 'image/webp':
				// Don't do anything if the service supports webp and the image isn't for Instagram.
				// If it is for Instagram, we want to convert to a JPEG as we might need to resize/crop
				// later in this function.
				if ( $this->base->supports( 'webp' ) && $service !== 'instagram' ) {
					break;
				}

				// Get image.
				$image_path_and_file = get_attached_file( $image_id );

				// Return the large image sources if we couldn't get the image path and file.
				if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
					return $this->get_image_source_by_size( $image_id, $source, 'large' );
				}

				// Load webp image.
				$image = wp_get_image_editor( $image_path_and_file );

				// Bail if an error occured.
				if ( is_wp_error( $image ) ) {
					return $image;
				}

				// Save to temporary file on disk.
				$converted_image = $image->save( get_temp_dir() . 'wpzinc-social-' . $image_id . '-converted-' . bin2hex( random_bytes( 5 ) ) );

				// Bail if an error occured.
				if ( is_wp_error( $converted_image ) ) {
					return $converted_image;
				}

				// Upload to Media Library.
				$converted_image_id = $this->base->get_class( 'media_library' )->upload_local_image( $converted_image['path'] );

				// Bail if an error occured.
				if ( is_wp_error( $converted_image_id ) ) {
					return $converted_image_id;
				}

				// Assign image ID.
				$image_id = $converted_image_id;
				break;

			default:
				/**
				 * Defines the image ID to use as the image or additional image for the status message.
				 * If an image's mime type is not supported by the social media scheduling service, this
				 * filter can be used to convert the image to a supported type, store it in the Media Library
				 * and return the converted image ID.
				 *
				 * This is already performed for webp images.
				 *
				 * @since   4.6.8
				 *
				 * @param   int     $image_id           Image ID.
				 * @param   string  $source             Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
				 * @param   string  $service            Social Media Service the image is for. If not defined, just return the large version.
				 * @param   string  $image_mime_type    Image MIME Type.
				 */
				$image_id = apply_filters( $this->base->plugin->filter_name . '_image_get_images_sources_convert', $image_id, $source, $service, $image_mime_type );
				break;
		}

		switch ( $service ) {

			/**
			 * Instagram
			 */
			case 'instagram':
				// Get image.
				$image_path_and_file = get_attached_file( $image_id );

				// Just return the original image ID if we couldn't get the image path and file.
				if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
					return $this->get_image_source_by_size( $image_id, $source, 'large' );
				}

				// Get image aspect ratio.
				$size         = getimagesize( $image_path_and_file );
				$aspect_ratio = $size[0] / $size[1];

				switch ( $status_post_type ) {
					/**
					 * Instagram Story
					 */
					case 'story':
						// If the aspect ratio of the image = 0.5625, just return the image.
						if ( $aspect_ratio === 0.5625 ) {
							return $this->get_image_source_by_size( $image_id, $source, 'large' );
						}

						// If here, the image's aspect ratio would result in the image being cropped when Buffer posts to
						// Instagram Stories.
						// Produce a resized copy that meets the required aspect ratio.
						return $this->get_resized_image_sources( $image_id, $source, $size, $aspect_ratio, 0.5625, 0.5625 );

					/**
					 * Instagram Post
					 */
					default:
						// If the aspect ratio of the image falls within the required limits, just return the image.
						if ( $aspect_ratio >= 0.8 && $aspect_ratio <= 1.91 ) {
							return $this->get_image_source_by_size( $image_id, $source, 'large' );
						}

						// If here, the image's aspect ratio would cause posting to Instagram to fail.
						// Produce a resized copy that meets the required aspect ratio.
						return $this->get_resized_image_sources( $image_id, $source, $size, $aspect_ratio, 1.91, 0.8 );
				}

				/**
				 * Other Social Networks
				 */
			default:
				return $this->get_image_source_by_size( $image_id, $source, 'large' );

		}

	}

	/**
	 * Returns the image for the given Attachment ID, resized to meet the provided aspect ratio requirements.
	 *
	 * @since   4.6.6
	 *
	 * @param   int    $image_id                           Image ID.
	 * @param   string $source                             Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
	 * @param   array  $size                               getimagesize() result.
	 * @param   float  $aspect_ratio                       Image's current aspect ratio.
	 * @param   float  $required_aspect_ratio_landscape    Image's required aspect ratio, if the image is landscape.
	 * @param   float  $required_aspect_ratio_portrait     Image's required aspect ratio, if the image is portrait.
	 * @return  \WP_Error|array                              Image ID, Image URLs, Source
	 */
	private function get_resized_image_sources( $image_id, $source, $size, $aspect_ratio, $required_aspect_ratio_landscape = 1.91, $required_aspect_ratio_portrait = 0.8 ) {

		if ( $aspect_ratio > 1 ) {
			// Original image is landscape.
			$width  = round( $size[1] * $required_aspect_ratio_landscape, 0 );
			$height = round( $size[1], 0 );

			// If the resulting width is greater than the original, cropping won't give us the required aspect ratio.
			// Instead, pad the left and right of the image to achieve the required aspect ratio.
			if ( $width > $size[0] ) {
				$resized_image_id = $this->pad( $image_id, $width, $height );
			} else {
				$resized_image_id = $this->crop( $image_id, $width, $height );
			}
		} else {
			// Original image is portrait.
			$width  = round( $size[0], 0 );
			$height = round( $size[0] / $required_aspect_ratio_portrait, 0 );

			// If the resulting height is greater than the original, cropping won't give us the required aspect ratio.
			// Instead, pad the top and bottom of the image to achieve the required aspect ratio.
			if ( $height > $size[1] ) {
				$resized_image_id = $this->pad( $image_id, $width, $height );
			} else {
				$resized_image_id = $this->crop( $image_id, $width, $height );
			}
		}

		// Bail if an error occured.
		if ( is_wp_error( $resized_image_id ) ) {
			return $resized_image_id;
		}

		// Return Resized Image.
		return $this->get_image_source_by_size( $resized_image_id, $source, 'full' );

	}

	/**
	 * Crops the given image to the given width and height.
	 *
	 * @since   5.0.0
	 *
	 * @param   int   $image_id   Image ID.
	 * @param   float $width      Required Width.
	 * @param   float $height     Required Height.
	 * @return  \WP_Error|int                Error | Resized Image ID.
	 */
	private function crop( $image_id, $width, $height ) {

		// Get image.
		$image_path_and_file = get_attached_file( $image_id );

		// Just return the original image ID if we couldn't get the image path and file.
		if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
			return $image_id;
		}

		// Load image into image editor.
		$image = wp_get_image_editor( $image_path_and_file );

		// Bail if an error occured.
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		// Resize image, using cropping.
		$resize_result = $image->resize( (int) $width, (int) $height, true );

		// Bail if an error occured.
		if ( is_wp_error( $resize_result ) ) {
			return $resize_result;
		}

		// Save to temporary file on disk.
		$resized_image = $image->save( get_temp_dir() . 'wpzinc-social-resized-' . bin2hex( random_bytes( 5 ) ) );

		// Bail if an error occured.
		if ( is_wp_error( $resized_image ) ) {
			return $resized_image;
		}

		// Upload to Media Library, returning the result.
		return $this->base->get_class( 'media_library' )->upload_local_image( $resized_image['path'] );

	}

	/**
	 * Pads the given image to the give width and height.
	 *
	 * @since   5.0.0
	 *
	 * @param   int   $image_id   Image ID.
	 * @param   float $width      Required Width.
	 * @param   float $height     Required Height.
	 * @return  \WP_Error|int                Error | Resized Image ID.
	 */
	private function pad( $image_id, $width, $height ) {

		// Get image.
		$image_path_and_file = get_attached_file( $image_id );

		// Just return the original image ID if we couldn't get the image path and file.
		if ( empty( $image_path_and_file ) || ! file_exists( $image_path_and_file ) ) {
			return $image_id;
		}

		// Load image into image editor.
		$image = wp_get_image_editor( $image_path_and_file );

		// Bail if an error occured.
		if ( is_wp_error( $image ) ) {
			return $image;
		}

		// Load image into our extended editor class, depending on the class
		// WordPress originally used.
		switch ( get_class( $image ) ) {
			case 'WP_Image_Editor_GD':
				$image = new Image_GD( $image_path_and_file );
				break;

			case 'WP_Image_Editor_Imagick':
				$image = new Image_Imagick( $image_path_and_file );
				break;

			default:
				return new \WP_Error( $this->base->plugin->filter_name . '_image_pad_unsupported_editor', __( 'Unsupported image editor.', 'wpzinc-social' ) );
		}

		// Load into our extended editor class.
		$loaded = $image->load();

		// Bail if an error occured.
		if ( is_wp_error( $loaded ) ) {
			return $loaded;
		}

		// Resize image, using padding.
		$resize_result = $image->pad( (int) $width, (int) $height );

		// Bail if an error occured.
		if ( is_wp_error( $resize_result ) ) {
			return $resize_result;
		}

		// Save to temporary file on disk.
		$resized_image = $image->save( get_temp_dir() . 'wpzinc-social-resized-' . bin2hex( random_bytes( 5 ) ) );

		// Bail if an error occured.
		if ( is_wp_error( $resized_image ) ) {
			return $resized_image;
		}

		// Upload to Media Library, returning the result.
		return $this->base->get_class( 'media_library' )->upload_local_image( $resized_image['path'] );

	}

	/**
	 * Returns an array comprising of the image ID, image URL and alt text for the requested size, thumbnail size
	 * and the source of the image.
	 *
	 * @since   4.6.6
	 *
	 * @param   int    $image_id   Image ID.
	 * @param   string $source     Source Image ID was derived from (plugin, featured_image, post_content, text_to_image).
	 * @param   string $size       WordPress Registered Image Size to return the image as.
	 * @return  array               Image ID, Image URLs, Source
	 */
	private function get_image_source_by_size( $image_id, $source, $size ) {

		// Get image at requested size.
		$image = wp_get_attachment_image_src( $image_id, $size );

		// Get thumbnail version, which some APIs might use for a small preview.
		$thumbnail = wp_get_attachment_image_src( $image_id, 'thumbnail' );

		// Return URLs only.
		return array(
			'id'        => $image_id,
			'image'     => ( is_array( $image ) ? strtok( $image[0], '?' ) : false ), // Strip query parameters that might break some APIs.
			'thumbnail' => ( is_array( $thumbnail ) ? strtok( $thumbnail[0], '?' ) : false ), // Strip query parameters that might break some APIs.
			'alt_text'  => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
			'source'    => $source,
			'width'     => ( is_array( $image ) ? $image[1] : '' ),
			'height'    => ( is_array( $image ) ? $image[2] : '' ),
		);

	}

	/**
	 * Defines the optimal image sizes for each social network.
	 *
	 * @since   4.6.6
	 *
	 * @return  array   Social Media Image Sizes by Social Media Type.
	 */
	private function get_social_media_image_sizes() {

		// Defines the optimal image sizes for each social network.
		$image_sizes = array(
			'twitter'         => array( 1600, 900 ),
			'pinterest'       => array( 1000, 1500 ), // also 1000 x 1000.
			'instagram'       => array( 1080, 1080 ),
			'instagram_post'  => array( 1080, 1080 ),
			'instagram_story' => array( 900, 1600 ),
			'facebook'        => array( 1200, 630 ),
			'linkedin'        => array( 1200, 627 ),
			'googlebusiness'  => array( 720, 720 ),
			'threads'         => array( 1200, 1200 ),
			'bluesky'         => array( 1280, 1280 ),
			'mastodon'        => array( 1280, 1280 ),
			'tiktok'          => array( 1080, 1920 ),
		);

		/**
		 * Defines the optimal image sizes for each social network.
		 *
		 * @since   4.2.0
		 *
		 * @param   array   $image_sizes   Image Sizes
		 */
		$image_sizes = apply_filters( $this->base->plugin->filter_name . '_get_social_media_image_sizes', $image_sizes );

		// Return filtered results.
		return $image_sizes;

	}

}
