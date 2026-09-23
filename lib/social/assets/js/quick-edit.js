/**
 * Handles populating data in the Quick Edit screen for this Plugin.
 *
 * @since 	3.9.6
 *
 * @package
 * @author WP Zinc
 */

jQuery(document).ready(function ($) {
	/**
	 * Quick Edit
	 * Bulk Edit
	 */
	if (typeof inlineEditPost !== 'undefined') {
		// Create a copy of the WordPress inline edit post function.
		const wp_inline_edit = inlineEditPost.edit;

		// Oerwrite the function with our own code.
		inlineEditPost.edit = function (id) {
			// "call" the original WP edit function.
			// we don't want to leave WordPress hanging.
			wp_inline_edit.apply(this, arguments);

			// Get the post ID.
			let post_id = 0;
			if (typeof id === 'object') {
				post_id = parseInt(this.getId(id));
			}

			if (post_id > 0) {
				// Get the Edit and Post Row Elements.
				const edit_row = $('#edit-' + post_id);
				const post_row = $('#post-' + post_id);

				// Get this Plugin's hidden field value.
				const override = $(
					'input[name="' +
						wpzinc_social_quick_edit.filter_name +
						'_override_' +
						post_id +
						'"]',
					$(post_row)
				).val();

				// Populate Quick Edit Fields with data from the above hidden fields.
				// These are output in page_columns_output() in our plugin.
				const select_name =
					'select[name="' +
					wpzinc_social_quick_edit.plugin_name +
					'[override]"]';
				if ($(select_name, $(edit_row)).length > 0) {
					$(select_name, $(edit_row)).val(override);
				}
			}
		};

		// Remove all hidden inputs when a search is performed.
		// This stops them from being included in the GET URL, otherwise we'd have a really long search URL
		// which breaks some nginx configurations.
		$('form#posts-filter').on('submit', function () {
			$(
				"input[name*='" + wpzinc_social_quick_edit.filter_name + "']"
			).remove();
		});
	}
});
