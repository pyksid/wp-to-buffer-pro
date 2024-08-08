<?php
/**
 * Outputs the Logs WP_List_Table.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 */

?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo esc_html( $this->base->plugin->displayName ); ?>

		<span>
			<?php esc_html_e( 'Logs', 'wp-to-social-pro' ); ?>
		</span>
	</h1>

	<?php
	// Search Subtitle.
	if ( $table->is_search() ) {
		?>
		<span class="subtitle left"><?php esc_html_e( 'Search results for', 'wp-to-social-pro' ); ?> &#8220;<?php echo esc_html( $table->get_search() ); ?>&#8221;</span>
		<?php
	}
	?>

	<form action="admin.php?page=<?php echo esc_attr( $this->base->plugin->name ); ?>-log" method="post" id="posts-filter">
		<?php
		// Output Search Box.
		$table->search_box( __( 'Search', 'wp-to-social-pro' ), 'wp-to-social-log' );

		// Output Table.
		$table->display();
		?>
	</form>
</div><!-- /.wrap -->
