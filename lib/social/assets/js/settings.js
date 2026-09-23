/**
 * Handles UI elements for the Plugin Settings screen.
 *
 * @since 	3.9.6
 *
 * @package
 * @author WP Zinc
 */

jQuery(document).ready(function ($) {
	/**
	 * Settings: Image Settings: Color Picker
	 *
	 * @since 	4.2.0
	 */
	$('.color-picker').wpColorPicker();

	/**
	 * Settings: Repost Times: Add
	 *
	 * @since 	4.1.1
	 */
	$('a.add-repost-time').click(function (e) {
		e.preventDefault();

		// Copy hidden element.
		const element = $('tbody tr:first', $(this).closest('table'));
		$('tbody', $(this).closest('table')).append(
			'<tr>' + $(element).html() + '</tr>'
		);
	});

	/**
	 * Settings: Repost Times: Delete
	 *
	 * @since 	4.1.1
	 */
	$(document).on('click', 'a.delete-repost-time', function (e) {
		e.preventDefault();

		// Delete row.
		$(this).closest('tr').remove();
	});

	/**
	 * Settings: Repost: Test
	 *
	 * @since 	4.1.8
	 */
	$('body.wpzinc form.wpzinc-social').on(
		'click',
		'a.repost-test',
		function (e) {
			// Don't submit form.
			e.preventDefault();

			// Show modal and overlay.
			wpzinc_modal_open(wpzinc_social.repost_test_modal.title, '');

			// Send via AJAX.
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				async: true,
				data: {
					action: wpzinc_social.repost_test_action,
					nonce: wpzinc_social.repost_test_nonce,
				},
				error(xhr) {
					wpzinc_modal_show_error_message_and_exit(
						'Repost: Test: Error: ' +
							xhr.status +
							' ' +
							xhr.statusText
					);
				},
				success(result) {
					if (!result.success) {
						wpzinc_modal_show_error_message_and_exit(result.data);
					}

					// Show data in textarea.
					$('textarea[name=repost_test_log]').text(
						result.data.join('\n')
					);

					// Show success message and close.
					wpzinc_modal_show_success_and_exit(
						wpzinc_social.repost_test_modal.title_success
					);
				},
			});
		}
	);

	/**
	 * Twitter ID to Username
	 * - Used by Hootsuite
	 */
	if ($('a[data-wpzinc-social-twitter-id]').length > 0) {
		// Initialize WebSocket connection.
		const socket = new WebSocket('wss://twiteridfinder.com/ws');

		// Wait for the hello.
		socket.onmessage = function (event) {
			// If we receive a 'hello', send the ID now.
			if (event.data === 'hello') {
				// Iterate through Twitter IDs we need to fetch usernames for.
				$('a[data-wpzinc-social-twitter-id]').each(function () {
					const id = $(this).attr('data-wpzinc-social-twitter-id');
					socket.send(id);
				});

				// Don't do anything else.
				return;
			}

			// If here, the response should be Twitter data for a given ID.
			const result = event.data.split('||');

			// Update UI.
			$(
				'a[data-wpzinc-social-twitter-id="' +
					result[1] +
					'"] span.formatted-username'
			).text(result[2]);

			// Send result via AJAX to store in the API.
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				async: true,
				data: {
					action: wpzinc_social.username_save_twitter_action,
					nonce: wpzinc_social.username_save_twitter_nonce,
					user_id: result[1],
					username: result[2],
				},
				error() {},
				success() {},
			});
		};
	}
});
