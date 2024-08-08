<?php
/**
 * Install class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Runs any steps required on plugin activation and upgrade.
 *
 * @package  WP_To_Social_Pro
 * @author   WP Zinc
 * @version  3.2.5
 */
class WP_To_Social_Pro_Install {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.2.5
	 *
	 * @var     object
	 */
	public $base;

	/**
	 * Constructor
	 *
	 * @since   3.4.7
	 *
	 * @param   object $base    Base Plugin Class.
	 */
	public function __construct( $base ) {

		// Store base class.
		$this->base = $base;

	}

	/**
	 * Runs installation routines for first time users
	 *
	 * @since   3.4.0
	 */
	public function install() {

		// Enable logging by default.
		$this->base->get_class( 'settings' )->update_option(
			'log',
			array(
				'enabled'          => 1,
				'display_on_posts' => 1,
				'preserve_days'    => 30,
				'log_level'        => array(
					'success',
					'test',
					'pending',
					'warning',
					'error',
				),
			)
		);

		// Create logging database table.
		$this->base->get_class( 'log' )->activate();

		// Reschedule the cron events.
		$this->base->get_class( 'cron' )->schedule_log_cleanup_event();
		$this->base->get_class( 'cron' )->schedule_media_cleanup_event();
		$this->base->get_class( 'cron' )->schedule_repost_event();

		// Bail if settings already exist.
		$settings = $this->base->get_class( 'settings' )->get_settings( 'post' );
		if ( $settings !== false ) {
			return;
		}

		// Get default installation settings.
		$settings = $this->base->get_class( 'settings' )->default_installation_settings( 'post' );
		$this->base->get_class( 'settings' )->update_settings( 'post', $settings );

	}

	/**
	 * Runs migrations for Pro to Pro version upgrades
	 *
	 * @since   3.2.5
	 */
	public function upgrade() {

		// Get current installed version number.
		// false | 1.1.7.
		$installed_version = get_option( $this->base->plugin->name . '-version' );

		// If the version number matches the plugin version, bail.
		if ( $installed_version === $this->base->plugin->version ) {
			return;
		}

		// Reschedule the cron events.
		$this->base->get_class( 'cron' )->reschedule_log_cleanup_event();
		$this->base->get_class( 'cron' )->reschedule_media_cleanup_event();
		$this->base->get_class( 'cron' )->reschedule_repost_event();

		// Migrate Bulk Publish Statuses from their own settings to each Post Type.
		$this->migrate_bulk_publish_statuses_to_post_types();

		/**
		 * 4.2.4: Migrate Log Level Settings
		 */
		if ( ! $installed_version || $installed_version < '4.2.4' ) {
			// Get Log Settings.
			$log_settings = get_option( $this->base->plugin->settingsName . '-log' );

			// If Log Level isn't an array, we need to update it.
			if ( is_array( $log_settings ) && ! is_array( $log_settings['log_level'] ) ) {
				// Depending on the log level, define the values.
				switch ( $log_settings['log_level'] ) {
					case 'test_warning_error':
						$log_levels = array(
							'test',
							'warning',
							'error',
						);
						break;

					case 'warning_error':
						$log_levels = array(
							'warning',
							'error',
						);
						break;

					case 'error':
						$log_levels = array(
							'error',
						);
						break;

					default:
						// All.
						$log_levels = array(
							'success',
							'test',
							'pending',
							'warning',
							'error',
						);
						break;
				}

				// Assign log levels to settings and save.
				$log_settings['log_level'] = $log_levels;
				update_option( $this->base->plugin->settingsName . '-log', $log_settings );
			}
		}

		/**
		 * 4.1.1: Migrate Repost Settings
		 */
		if ( ! $installed_version || $installed_version < '4.1.1' ) {
			// Get Repost Time.
			$time = get_option( $this->base->plugin->settingsName . '-repost_time' );

			// If time is not an array, we need to migrate to the new settings, where
			// we define repost times each day.
			if ( ! is_array( $time ) ) {
				if ( ! $time ) {
					$time = '00:00';
				}

				// Build array of days with this time.
				$days = array(
					'mon' => array( $time ),
					'tue' => array( $time ),
					'wed' => array( $time ),
					'thu' => array( $time ),
					'fri' => array( $time ),
					'sat' => array( $time ),
					'sun' => array( $time ),
				);

				// Save.
				update_option( $this->base->plugin->settingsName . '-repost_time', $days );
			}
		}

		/**
		 * 3.9.8: Migrate Log Settings
		 */
		if ( ! $installed_version || $installed_version < '3.9.8' ) {
			// Check if the log settings already migrated on Plugin activation.
			$log = get_option( $this->base->plugin->settingsName . '-log' );
			if ( ! is_array( $log ) ) {
				$this->base->get_class( 'settings' )->update_option(
					'log',
					array(
						'enabled'          => 1,
						'display_on_posts' => 1,
						'preserve_days'    => 30,
					)
				);
			}

			// Schedule the log cleanup event, now that the settings permit it.
			$this->base->get_class( 'cron' )->schedule_log_cleanup_event();
		}

		/**
		 * 3.9.6: Migrate Log to new DB Table
		 */
		if ( ! $installed_version || $installed_version < '3.9.6' ) {
			// Create logging database table.
			$this->base->get_class( 'log' )->activate();

			// Define Post Meta Log Key.
			$meta_key = '_' . str_replace( '-', '_', $this->base->plugin->settingsName ) . '_log';

			// Fetch all Posts that have a Log.
			$posts = new WP_Query(
				array(
					'post_type'              => 'any',
					'post_status'            => 'any',
					'posts_per_page'         => -1,

					// Where the log meta value exists.
					'meta_key'               => $meta_key,
					'meta_compare'           => 'EXISTS',

					// Performance.
					'fields'                 => 'ids',
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( $posts->post_count > 0 ) {
				foreach ( $posts->posts as $post_id ) {
					// Fetch Log from Post Meta.
					$log = get_post_meta( $post_id, $meta_key, true );

					// Iterate through log, adding to new database table.
					foreach ( $log as $log_entry ) {
						// Determine result.
						if ( $log_entry['success'] && isset( $log_entry['status_created_at'] ) ) {
							$result = 'success';
						} elseif ( $log_entry['success'] ) {
							$result = 'pending';
						} else {
							$result = 'error';
						}

						// Add to Log.
						$this->base->get_class( 'log' )->add(
							$post_id,
							array(
								'action'            => '', // not supplied from Post Meta logs.
								'request_sent'      => date( 'Y-m-d H:i:s', $log_entry['date'] ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
								'profile_id'        => ( isset( $log_entry['profile'] ) ? $log_entry['profile'] : '' ),
								'profile_name'      => ( isset( $log_entry['profile_name'] ) ? $log_entry['profile_name'] : '' ),
								'result'            => $result, // success, pending, error.
								'result_message'    => $log_entry['message'],
								'status_text'       => ( isset( $log_entry['status_text'] ) ? $log_entry['status_text'] : '' ),
								'status_created_at' => ( isset( $log_entry['status_created_at'] ) && is_numeric( $log_entry['status_created_at'] ) ? date( 'Y-m-d H:i:s', $log_entry['status_created_at'] ) : '' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
								'status_due_at'     => ( isset( $log_entry['status_due_at'] ) && is_numeric( $log_entry['status_due_at'] ) ? date( 'Y-m-d H:i:s', $log_entry['status_due_at'] ) : '' ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
							)
						);
					}

					// Delete Post Meta.
					delete_post_meta( $post_id, $meta_key );
				}
			}
		}

		/**
		 * 3.8.2: Remove Repost Frequency and Units from Statuses
		 */
		if ( ! $installed_version || $installed_version < '3.8.2' ) {
			// Fetch Repost Settings Defaults for each Post Type.
			$post_types = $this->base->get_class( 'common' )->get_post_types();
			foreach ( $post_types as $post_type ) {
				// Get Post Type Settings.
				$settings = $this->base->get_class( 'settings' )->get_settings( $post_type->name );

				// Bail if no settings.
				if ( ! is_array( $settings ) ) {
					continue;
				}

				// Iterate through profiles.
				foreach ( $settings as $profile => $actions ) {
					// Bail if defaults are not available.
					if ( ! isset( $settings[ $profile ] ) ) {
						continue;
					}

					// Bail if repost settings are not available.
					if ( ! isset( $settings[ $profile ]['repost'] ) ) {
						continue;
					}

					// Bail if frequency and frequency units are not defined.
					if ( ! isset( $settings[ $profile ]['repost']['frequency'] ) ||
						! isset( $settings[ $profile ]['repost']['frequency_unit'] ) ) {
						continue;
					}

					// Remove the Frequency settings from the defaults.
					unset( $settings[ $profile ]['repost']['frequency'], $settings[ $profile ]['repost']['frequency_unit'] );
				}

				// Save Post Type Status Settings.
				$this->base->get_class( 'settings' )->update_settings( $post_type->name, $settings );
			}
		}

		/**
		 * 3.2.5: Migrate Conditions from their own settings to each individual Status.
		 */
		if ( ! $installed_version || $installed_version < '3.2.5' ) {
			// Fetch Conditions for each Post Type.
			$post_types   = $this->base->get_class( 'common' )->get_post_types();
			$post_actions = $this->base->get_class( 'common' )->get_post_actions();

			// Bail if no Post Types.
			foreach ( $post_types as $post_type ) {
				// Get Post Type Settings.
				$settings = $this->base->get_class( 'settings' )->get_settings( $post_type->name );

				// Iterate through Profiles.
				if ( is_array( $settings ) ) {
					foreach ( $settings as $profile_id => $profile ) {
						// Skip profiles that don't have any conditions.
						if ( ! isset( $profile['conditions'] ) ) {
							continue;
						}

						// Skip profiles where conditions are not enabled.
						if ( ! isset( $profile['conditions']['enabled'] ) || $profile['conditions']['enabled'] != '1' ) {  // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
							continue;
						}

						// Setup array to store conditions and terms.
						$conditions = array();
						$terms      = array();
						foreach ( $profile['conditions'] as $taxonomy => $taxonomy_terms ) {
							// Skip profiles where conditions do not have any terms.
							if ( $taxonomy === 'enabled' ) {
								continue;
							}

							if ( empty( $taxonomy_terms ) ) {
								continue;
							}

							// If here, we have a taxonomy with a method and terms.
							// Migrate these settings to all publish and update statuses for this profile.
							if ( ! empty( $taxonomy_terms['method'] ) ) {
								$conditions[ $taxonomy ] = $taxonomy_terms['method'];
							}
							foreach ( $taxonomy_terms as $term_id => $enabled ) {
								// Skip method key.
								if ( $term_id === 'method' ) {
									continue;
								}

								if ( empty( $term_id ) ) {
									continue;
								}

								// Setup taxonomy term array if it's not been defined yet.
								if ( ! isset( $terms[ $taxonomy ] ) ) {
									$terms[ $taxonomy ] = array();
								}

								// Add Term ID.
								$terms[ $taxonomy ][] = $term_id;
							}
						}

						// Migrate conditions and terms to statuses.
						foreach ( $post_actions as $action => $label ) {
							foreach ( $settings[ $profile_id ][ $action ]['status'] as $index => $status ) {
								// Only migrate conditions if they're not empty.
								if ( ! empty( $conditions ) ) {
									$settings[ $profile_id ][ $action ]['status'][ $index ]['conditions'] = $conditions;
								}

								// Only migrate terms if they're not all empty.
								if ( ! empty( $terms ) ) {
									$settings[ $profile_id ][ $action ]['status'][ $index ]['terms'] = $terms;
								}
							}
						}

						// Remove conditions from settings, as they're now stored in statuses.
						unset( $settings[ $profile_id ]['conditions'] );
					}

					// Save settings for this Post Type.
					// We call update_option directly as our settings are always associative.
					update_option( $this->base->plugin->name . '-' . $post_type->name, $settings );
				}
			}
		}

		// Update the version number.
		update_option( $this->base->plugin->name . '-version', $this->base->plugin->version );

	}

	/**
	 * Migrates Bulk Publish Settings stored in the wp-to-social-pro-bulk option setting
	 * into each Post Type's Status Settings
	 *
	 * @since   3.8.1
	 *
	 * @return  bool    Success
	 */
	private function migrate_bulk_publish_statuses_to_post_types() {

		// Fetch Bulk Publish statuses.
		$bulk_publish_statuses = $this->base->get_class( 'settings' )->get_settings( 'bulk' );

		// Bail if no statuses exist.
		if ( ! $bulk_publish_statuses || empty( $bulk_publish_statuses ) || ! is_array( $bulk_publish_statuses ) ) {
			return false;
		}

		// Fetch each Post Type's statuses.
		$post_types_statuses = array();
		$post_types          = $this->base->get_class( 'common' )->get_post_types();

		// Bail if no Post Types exist.
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return false;
		}

		foreach ( $post_types as $post_type => $post_type_obj ) {
			$post_types_statuses[ $post_type ] = $this->base->get_class( 'settings' )->get_settings( $post_type );
		}

		// Bail if no Post Type statuses exist.
		if ( empty( $post_types_statuses ) || ! is_array( $post_types_statuses ) ) {
			return false;
		}

		// Iterate through Bulk Publish Profiles and Statuses, adding them to each Post Type.
		foreach ( $bulk_publish_statuses as $profile_id => $statuses ) {
			foreach ( $post_types_statuses as $post_type => $post_type_statuses ) {
				if ( ! isset( $post_types_statuses[ $post_type ][ $profile_id ] ) ) {
					$post_types_statuses[ $post_type ][ $profile_id ] = array();
				}
				if ( ! is_array( $post_types_statuses[ $post_type ][ $profile_id ] ) ) {
					$post_types_statuses[ $post_type ][ $profile_id ] = array();
				}

				$post_types_statuses[ $post_type ][ $profile_id ]['bulk_publish'] = $statuses['publish'];
			}
		}

		// Iterate through each Post Type, updating the settings with the combined statuses we now have.
		foreach ( $post_types as $post_type => $post_type_obj ) {
			// Call update_option(), as statuses are already associative and don't need to be made associative
			// through update_setting().
			$this->base->get_class( 'settings' )->update_option( $post_type, $post_types_statuses[ $post_type ] );
		}

		// Store a backup of the old bulk settings.
		update_option( $this->base->plugin->name . '-bulk-old', $bulk_publish_statuses );

		// Delete bulk statuses.
		delete_option( $this->base->plugin->name . '-bulk' );

		return true;

	}

	/**
	 * Runs uninstallation routines
	 *
	 * @since   3.7.2
	 */
	public function uninstall() {

		// Unschedule any CRON events.
		$this->base->get_class( 'cron' )->unschedule_log_cleanup_event();
		$this->base->get_class( 'cron' )->unschedule_media_cleanup_event();
		$this->base->get_class( 'cron' )->unschedule_repost_event();

	}

}
