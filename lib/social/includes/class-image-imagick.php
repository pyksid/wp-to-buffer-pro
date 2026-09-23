<?php
/**
 * Imagick Image class.
 *
 * @package WPZinc\Social\Pro
 * @author WP Zinc
 */

namespace WPZinc\Social\Pro;

/**
 * Adds functionality to WordPress' Imagick Image Editor class
 * for adding padding to a source image to produce an
 * image that is the required width and height.
 *
 * This is primarily used by Image when
 * an image needs to meet a required aspect ratio for
 * publication to social media.
 *
 * @package WPZinc\Social\Pro
 * @author  WP Zinc
 * @version 5.0.0
 */
class Image_Imagick extends \WP_Image_Editor_Imagick {

	/**
	 * Copies the image into the center of a new, larger canvas.
	 *
	 * @since   5.0.0
	 *
	 * @param   int $dst_w  Destination image width.
	 * @param   int $dst_h  Destination image height.
	 * @return  bool|\WP_Error
	 */
	public function pad( $dst_w, $dst_h ) {

		// Create target image canvas that matches the required width and height.
		$dst = new \Imagick();
		$dst->newImage( $dst_w, $dst_h, 'none' );
		$dst->setImageFormat( 'png' );

		// Calculate padding for the original image.
		$padding_top    = (int) round( ( $dst_h - $this->size['height'] ) / 2 );
		$padding_bottom = $dst_h - $this->size['height'] - $padding_top;

		// Composite the source image onto the target image with padding.
		$dst->compositeImage(
			$this->image,
			\Imagick::COMPOSITE_OVER,
			(int) round( ( $dst_w - $this->image->getImageWidth() ) / 2 ),
			$padding_top
		);

		$this->image = $dst;
		return $this->update_size();

	}

}
