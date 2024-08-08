<?php
/**
 * Imagick Image class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Adds functionality to WordPress' Imagick Image Editor class
 * for adding padding to a source image to produce an
 * image that is the required width and height.
 *
 * This is primarily used by WP_To_Social_Pro_Image when
 * an image needs to meet a required aspect ratio for
 * publication to social media.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 5.0.0
 */
class WP_To_Social_Pro_Image_Imagick extends WP_Image_Editor_Imagick {

	/**
	 * Copies the image into the center of a new, larger canvas.
	 *
	 * @since   5.0.0
	 *
	 * @param   int $dst_w  Destination image width.
	 * @param   int $dst_h  Destination image height.
	 * @return  bool|WP_Error
	 */
	public function pad( $dst_w, $dst_h ) {

		self::set_imagick_time_limit();

		// Create target image canvas that matches the required width and height.
		$dst = new Imagick();
		$dst->newImage( $dst_w, $dst_h, 'none' );
		$dst->setImageFormat( 'png' );

		// Calculate padding for the original image.
		$padding_top    = ( $dst_h - $this->size['height'] ) / 2;
		$padding_bottom = $dst_h - $this->size['height'] - $padding_top;

		// Composite the source image onto the target image with padding.
		$dst->compositeImage(
			$this->image,
			Imagick::COMPOSITE_OVER,
			( $dst_w - $this->image->getImageWidth() ) / 2,
			$padding_top
		);

		$this->image = $dst;
		return $this->update_size();

	}

}
