<?php
/**
 * Common class.
 *
 * @package WP_To_Social_Pro
 * @author WP Zinc
 */

/**
 * Common functions that don't fit into other classes.
 *
 * @package WP_To_Social_Pro
 * @author  WP Zinc
 * @version 3.0.0
 */
class WP_To_Social_Pro_Common {

	/**
	 * Holds the base class object.
	 *
	 * @since   3.4.7
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
	 * Helper method to retrieve three character day names and their full names
	 *
	 * @since   4.1.1
	 *
	 * @return  array   Days
	 */
	public function get_days() {

		// Define days.
		$days = array(
			'mon' => __( 'Monday', 'wp-to-social-pro' ),
			'tue' => __( 'Tuesday', 'wp-to-social-pro' ),
			'wed' => __( 'Wednesday', 'wp-to-social-pro' ),
			'thu' => __( 'Thursday', 'wp-to-social-pro' ),
			'fri' => __( 'Friday', 'wp-to-social-pro' ),
			'sat' => __( 'Saturday', 'wp-to-social-pro' ),
			'sun' => __( 'Sunday', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available days.
		 *
		 * @since   4.1.1
		 *
		 * @param   array   $days   Days.
		 */
		$days = apply_filters( $this->base->plugin->filter_name . '_get_days', $days );

		// Return filtered results.
		return $days;

	}

	/**
	 * Helper method to retrieve schedule options
	 *
	 * @since   3.0.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @param   bool  $is_post_screen     Displaying the Post Screen.
	 * @return  array                       Schedule Options
	 */
	public function get_schedule_options( $post_type = false, $is_post_screen = false ) {

		// Build schedule options, depending on the Plugin.
		switch ( $this->base->plugin->name ) {

			case 'wp-to-buffer':
				$schedule = array(
					'queue_bottom' => sprintf(
						/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Add to End of %s Queue', 'wp-to-social-pro' ),
						$this->base->plugin->account
					),
				);
				break;

			case 'wp-to-buffer-pro':
				$schedule = array(
					'queue_bottom'    => sprintf(
						/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Add to End of %s Queue', 'wp-to-social-pro' ),
						$this->base->plugin->account
					),
					'queue_top'       => sprintf(
						/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Add to Start of %s Queue', 'wp-to-social-pro' ),
						$this->base->plugin->account
					),
					'now'             => __( 'Post Immediately', 'wp-to-social-pro' ),
					'custom'          => __( 'Custom Time', 'wp-to-social-pro' ),
					'custom_relative' => __( 'Custom Time (Relative Format)', 'wp-to-social-pro' ),
					'custom_field'    => __( 'Custom Time (based on Custom Field / Post Meta Value)', 'wp-to-social-pro' ),
				);

				// If we're on the Post Screen, add a specific option now.
				if ( $is_post_screen ) {
					$schedule['specific'] = __( 'Specific Date and Time', 'wp-to-social-pro' );
				}
				break;

			case 'wp-to-hootsuite':
				$schedule = array(
					'now' => __( 'Post Immediately', 'wp-to-social-pro' ),
				);
				break;

			case 'wp-to-hootsuite-pro':
				$schedule = array(
					'now'             => __( 'Post Immediately', 'wp-to-social-pro' ),
					'custom'          => __( 'Custom Time', 'wp-to-social-pro' ),
					'custom_relative' => __( 'Custom Time (Relative Format)', 'wp-to-social-pro' ),
					'custom_field'    => __( 'Custom Time (based on Custom Field / Post Meta Value)', 'wp-to-social-pro' ),
				);

				// If we're on the Post Screen, add a specific option now.
				if ( $is_post_screen ) {
					$schedule['specific'] = __( 'Specific Date and Time', 'wp-to-social-pro' );
				}
				break;

			case 'wp-to-socialpilot':
				$schedule = array(
					'queue_bottom' => sprintf(
						/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Add to End of %s Queue', 'wp-to-social-pro' ),
						$this->base->plugin->account
					),
				);
				break;

			case 'wp-to-socialpilot-pro':
				$schedule = array(
					'queue_bottom'    => sprintf(
						/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Add to End of %s Queue', 'wp-to-social-pro' ),
						$this->base->plugin->account
					),
					'queue_top'       => sprintf(
						/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
						__( 'Add to Start of %s Queue', 'wp-to-social-pro' ),
						$this->base->plugin->account
					),
					'now'             => __( 'Post Immediately', 'wp-to-social-pro' ),
					'custom'          => __( 'Custom Time', 'wp-to-social-pro' ),
					'custom_relative' => __( 'Custom Time (Relative Format)', 'wp-to-social-pro' ),
					'custom_field'    => __( 'Custom Time (based on Custom Field / Post Meta Value)', 'wp-to-social-pro' ),
				);

				// If we're on the Post Screen, add a specific option now.
				if ( $is_post_screen ) {
					$schedule['specific'] = __( 'Specific Date and Time', 'wp-to-social-pro' );
				}
				break;

		}

		/**
		 * Defines the available schedule options for each individual status.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $schedule           Schedule Options.
		 * @param   string  $post_type          Post Type.
		 * @param   bool    $is_post_screen     On Post Edit Screen.
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_schedule_options', $schedule, $post_type, $is_post_screen );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve days used for the schedule relative days option
	 *
	 * @since   3.9.8
	 *
	 * @return  array   Days
	 */
	public function get_schedule_relative_days() {

		// Build days.
		$days = array(
			'today'     => __( 'Today', 'wp-to-social-pro' ),
			'tomorrow'  => __( 'Tomorrow', 'wp-to-social-pro' ),
			'monday'    => __( 'Next Monday', 'wp-to-social-pro' ),
			'tuesday'   => __( 'Next Tuesday', 'wp-to-social-pro' ),
			'wednesday' => __( 'Next Wednesday', 'wp-to-social-pro' ),
			'thursday'  => __( 'Next Thursday', 'wp-to-social-pro' ),
			'friday'    => __( 'Next Friday', 'wp-to-social-pro' ),
			'saturday'  => __( 'Next Saturday', 'wp-to-social-pro' ),
			'sunday'    => __( 'Next Sunday', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available days for a status' Custom Time (Relative Format) option.
		 *
		 * @since   3.9.8
		 *
		 * @param   array   $days   Days.
		 */
		$days = apply_filters( $this->base->plugin->filter_name . '_get_schedule_relative_days', $days );

		// Return filtered results.
		return $days;

	}

	/**
	 * Helper method to retrieve schedule custom relation options
	 *
	 * @since   3.3.3
	 *
	 * @return  array   Schedule Custom Relation Options
	 */
	public function get_schedule_custom_relation_options() {

		// Build schedule options.
		$schedule = array(
			'before' => __( 'Before Custom Field Value', 'wp-to-social-pro' ),
			'after'  => __( 'After Custom Field Value', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available schedule options, relative to a custom field, for each individual status.
		 *
		 * @since   3.3.3
		 *
		 * @param   array   $schedule   Schedule Options.
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_schedule_custom_relation_options', $schedule );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve Google Business Start Date options
	 *
	 * @since   4.9.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @return  array   Start Date Options
	 */
	public function get_google_business_start_date_options( $post_type = false ) {

		// Build schedule options.
		$schedule = array(
			'custom' => __( 'Custom Field / Post Meta Value', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available start date options for a Google Business Profile status.
		 *
		 * @since   4.9.0
		 *
		 * @param   array   $schedule   Schedule Options.
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_google_business_start_date_options', $schedule, $post_type );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve Google Business Start Date options
	 *
	 * @since   4.9.0
	 *
	 * @param   mixed $post_type          Post Type (false | string).
	 * @return  array   End Date Options
	 */
	public function get_google_business_end_date_options( $post_type = false ) {

		// Build schedule options.
		$schedule = array(
			'custom' => __( 'Custom Field / Post Meta Value', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available start date options for a Google Business Profile status.
		 *
		 * @since   4.9.0
		 *
		 * @param   array   $schedule   Schedule Options.
		 */
		$schedule = apply_filters( $this->base->plugin->filter_name . '_get_google_business_end_date_options', $schedule, $post_type );

		// Return filtered results.
		return $schedule;

	}

	/**
	 * Helper method to retrieve public Post Types
	 *
	 * @since   3.0.0
	 *
	 * @return  array   Public Post Types
	 */
	public function get_post_types() {

		// Get public Post Types.
		$types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		// Filter out excluded post types.
		$excluded_types = $this->get_excluded_post_types();
		if ( is_array( $excluded_types ) ) {
			foreach ( $excluded_types as $excluded_type ) {
				unset( $types[ $excluded_type ] );
			}
		}

		/**
		 * Defines the available Post Type Objects that can have statues defined and be sent to social media.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( $this->base->plugin->filter_name . '_get_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Helper method to retrieve excluded Post Types, which should not send
	 * statuses to the API
	 *
	 * @since   3.0.0
	 *
	 * @return  array   Excluded Post Types
	 */
	public function get_excluded_post_types() {

		// Get excluded Post Types.
		$types = array(
			'attachment',
			'revision',
			'elementor_library',
		);

		/**
		 * Defines the Post Type Objects that cannot have statues defined and not be sent to social media.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $types  Post Types.
		 */
		$types = apply_filters( $this->base->plugin->filter_name . '_get_excluded_post_types', $types );

		// Return filtered results.
		return $types;

	}

	/**
	 * Helper method to retrieve excluded Taxonomies
	 *
	 * @since   3.0.5
	 *
	 * @return  array   Excluded Post Types
	 */
	public function get_excluded_taxonomies() {

		// Get excluded Post Types.
		$taxonomies = array(
			'post_format',
			'nav_menu',
		);

		/**
		 * Defines taxonomies to exclude from the Conditions: Taxonomies dropdowns for each individual status.
		 *
		 * @since   3.0.5
		 *
		 * @param   array   $taxonomies     Excluded Taxonomies.
		 */
		$taxonomies = apply_filters( $this->base->plugin->filter_name . '_get_excluded_taxonomies', $taxonomies );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve a Post Type's taxonomies
	 *
	 * @since   3.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Taxonomies
	 */
	public function get_taxonomies( $post_type ) {

		// Get Post Type Taxonomies.
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		// Get excluded Taxonomies.
		$excluded_taxonomies = $this->get_excluded_taxonomies();

		// If excluded taxonomies exist, remove them from the taxonomies array now.
		if ( is_array( $excluded_taxonomies ) && count( $excluded_taxonomies ) > 0 ) {
			foreach ( $excluded_taxonomies as $excluded_taxonomy ) {
				unset( $taxonomies[ $excluded_taxonomy ] );
			}
		}

		/**
		 * Defines available taxonomies for the given Post Type, which are used in the Conditions: Taxonomies dropdowns
		 * for each individual status.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $taxonomies             Taxonomies.
		 * @param   string  $post_type              Post Type.
		 */
		$taxonomies = apply_filters( $this->base->plugin->filter_name . '_get_taxonomies', $taxonomies, $post_type );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve all taxonomies
	 *
	 * @since   3.6.7
	 *
	 * @return  array               Taxonomies
	 */
	public function get_all_taxonomies() {

		// Get Post Type Taxonomies.
		$taxonomies = get_taxonomies( false, 'objects' );

		// Get excluded Taxonomies.
		$excluded_taxonomies = $this->get_excluded_taxonomies();

		// If excluded taxonomies exist, remove them from the taxonomies array now.
		if ( is_array( $excluded_taxonomies ) && count( $excluded_taxonomies ) > 0 ) {
			foreach ( $excluded_taxonomies as $excluded_taxonomy ) {
				unset( $taxonomies[ $excluded_taxonomy ] );
			}
		}

		/**
		 * Defines available taxonomies, regardless of Post Type, which are used in the Conditions: Taxonomies dropdowns
		 * for each individual status.
		 *
		 * @since   3.6.7
		 *
		 * @param   array   $taxonomies             Taxonomies.
		 */
		$taxonomies = apply_filters( $this->base->plugin->filter_name . '_get_all_taxonomies', $taxonomies );

		// Return filtered results.
		return $taxonomies;

	}

	/**
	 * Helper method to retrieve all WordPress Roles
	 *
	 * @since   3.0.6
	 *
	 * @return  array   Roles
	 */
	public function get_user_roles() {

		// Define roles.
		$roles = get_editable_roles();

		// Remove excluded roles.
		$excluded_roles = $this->get_excluded_user_roles();
		foreach ( $roles as $role_name => $role ) {
			if ( in_array( $role_name, $excluded_roles, true ) ) {
				unset( $roles[ $role_name ] );
			}
		}

		/**
		 * Defines WordPress User Roles.
		 *
		 * @since   3.0.6
		 *
		 * @param   array   $roles  WordPress User Roles.
		 */
		$roles = apply_filters( $this->base->plugin->filter_name . '_get_user_roles', $roles );

		// Return filtered results.
		return $roles;

	}

	/**
	 * Helper method to retrieve all excluded WordPress Roles
	 *
	 * These roles are implied to have full access
	 *
	 * @since   3.0.6
	 *
	 * @return  array   Excluded Roles
	 */
	public function get_excluded_user_roles() {

		// Define excluded roles.
		$excluded_roles = array();

		/**
		 * Defines WordPress User Roles to exclude from Settings screens.
		 *
		 * @since   3.0.6
		 *
		 * @param   array   $excluded_roles     Excluded WordPress User Roles
		 */
		$excluded_roles = apply_filters( $this->base->plugin->filter_name . '_get_excluded_user_roles', $excluded_roles );

		// Return filtered results.
		return $excluded_roles;

	}

	/**
	 * Helper method to retrieve all repost frequency units (days, weeks etc)
	 *
	 * @since   3.7.2
	 *
	 * @return  array   Repost Frequency Units
	 */
	public function get_repost_frequency_units() {

		// Define units.
		$units = array(
			'days'   => __( 'Days', 'wp-to-social-pro' ),
			'months' => __( 'Months', 'wp-to-social-pro' ),
			'years'  => __( 'Years', 'wp-to-social-pro' ),
		);

		/**
		 * Defines available Reposting frequency units when defining Repost status(es).
		 *
		 * @since   3.7.2
		 *
		 * @param   array   $units  Repost Frequency Units.
		 */
		$units = apply_filters( $this->base->plugin->filter_name . '_get_repost_frequency_units', $units );

		// Return filtered results.
		return $units;

	}

	/**
	 * Helper method to retrieve available tags for status updates
	 *
	 * @since   3.0.0
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function get_tags( $post_type ) {

		// Get post type.
		$post_types = $this->get_post_types();

		// Build tags array.
		$tags = array(
			'post'   => array(
				'{sitename}'              => __( 'Site Name', 'wp-to-social-pro' ),
				'{title}'                 => __( 'Post Title', 'wp-to-social-pro' ),
				'{excerpt}'               => __( 'Post Excerpt (Full)', 'wp-to-social-pro' ),
				'{excerpt:characters(?)}' => array(
					'question'      => __( 'Enter the maximum number of characters the Post Excerpt should display.', 'wp-to-social-pro' ),
					'default_value' => '150',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Character Limited)', 'wp-to-social-pro' ),
				),
				'{excerpt:words(?)}'      => array(
					'question'      => __( 'Enter the maximum number of words the Post Excerpt should display.', 'wp-to-social-pro' ),
					'default_value' => '55',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Word Limited)', 'wp-to-social-pro' ),
				),
				'{excerpt:sentences(?)}'  => array(
					'question'      => __( 'Enter the maximum number of sentences the Post Excerpt should display.', 'wp-to-social-pro' ),
					'default_value' => '1',
					'replace'       => '?',
					'label'         => __( 'Post Excerpt (Sentence Limited)', 'wp-to-social-pro' ),
				),
				'{content}'               => __( 'Post Content (Full)', 'wp-to-social-pro' ),
				'{content_more_tag}'      => __( 'Post Content (Up to More Tag)', 'wp-to-social-pro' ),
				'{content:characters(?)}' => array(
					'question'      => __( 'Enter the maximum number of characters the Post Content should display.', 'wp-to-social-pro' ),
					'default_value' => '150',
					'replace'       => '?',
					'label'         => __( 'Post Content (Character Limited)', 'wp-to-social-pro' ),
				),
				'{content:words(?)}'      => array(
					'question'      => __( 'Enter the maximum number of words the Post Content should display.', 'wp-to-social-pro' ),
					'default_value' => '55',
					'replace'       => '?',
					'label'         => __( 'Post Content (Word Limited)', 'wp-to-social-pro' ),
				),
				'{content:sentences(?)}'  => array(
					'question'      => __( 'Enter the maximum number of sentences the Post Content should display.', 'wp-to-social-pro' ),
					'default_value' => '1',
					'replace'       => '?',
					'label'         => __( 'Post Content (Sentence Limited)', 'wp-to-social-pro' ),
				),
				'{date}'                  => __( 'Post Date', 'wp-to-social-pro' ),
				'{url}'                   => __( 'Post URL', 'wp-to-social-pro' ),
				'{url_short}'             => __( 'Post URL, Shortened', 'wp-to-social-pro' ),
				'{id}'                    => __( 'Post ID', 'wp-to-social-pro' ),
			),

			'author' => array(
				'{author_user_login}'    => __( 'Author Login', 'wp-to-social-pro' ),
				'{author_user_nicename}' => __( 'Author Nice Name', 'wp-to-social-pro' ),
				'{author_user_email}'    => __( 'Author Email', 'wp-to-social-pro' ),
				'{author_user_url}'      => __( 'Author URL', 'wp-to-social-pro' ),
				'{author_display_name}'  => __( 'Author Display Name', 'wp-to-social-pro' ),
				'{author_field_NAME}'    => __( 'Author Meta Field', 'wp-to-social-pro' ),
			),
		);

		// Add any taxonomies for the given Post Type, if the Post Type exists.
		$taxonomies = array();
		if ( isset( $post_types[ $post_type ] ) ) {
			// Get taxonomies specific to the Post Type.
			$taxonomies = $this->get_taxonomies( $post_type );
		} else {
			// We're on the Bulk Publishing Settings, so return all Taxonomies.
			$taxonomies = $this->get_all_taxonomies();
		}

		if ( count( $taxonomies ) > 0 ) {
			$tags['taxonomy'] = array();

			foreach ( $taxonomies as $tax => $details ) {
				$tags['taxonomy'][ '{taxonomy_' . $tax . '}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format', 'wp-to-social-pro' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_hashtag_retain_case}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format, Retaining Case', 'wp-to-social-pro' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_hashtag_underscore}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Hashtag Format, Underscores', 'wp-to-social-pro' ),
					$details->labels->singular_name
				);
				$tags['taxonomy'][ '{taxonomy_' . $tax . '_name}' ] = sprintf(
					/* translators: Taxonomy Name, Singular */
					__( 'Taxonomy: %s: Name Format', 'wp-to-social-pro' ),
					$details->labels->singular_name
				);
			}
		}

		/**
		 * Defines Dynamic Status Tags that can be inserted into status(es) for the given Post Type.
		 * These tags are also added to any 'Insert Tag' dropdowns.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $tags       Dynamic Status Tags.
		 * @param   string  $post_type  Post Type.
		 */
		$tags = apply_filters( $this->base->plugin->filter_name . '_get_tags', $tags, $post_type );

		// If there are any Custom Tags defined in the Plugin Settings for this Post Type,
		// add them now.
		$existing_custom_tags = $this->base->get_class( 'settings' )->get_setting( 'custom_tags', $post_type, '' );
		if ( ! empty( $existing_custom_tags ) && is_array( $existing_custom_tags ) && isset( $existing_custom_tags['key'] ) ) {
			foreach ( $existing_custom_tags['key'] as $index => $existing_custom_tag ) {
				// Skip empty keys.
				if ( empty( $existing_custom_tag ) ) {
					continue;
				}

				// Add custom tag to array.
				$tags['post'][ '{custom_field_' . $existing_custom_tags['key'][ $index ] . '}' ] = $existing_custom_tags['label'][ $index ];
			}
		}

		// Finally, append the generic Post Custom Field tag.
		$tags['post']['{custom_field_NAME}'] = __( 'Post Meta Field', 'wp-to-social-pro' );

		// Return filtered results.
		return $tags;

	}

	/**
	 * Helper method to retrieve available tags for status updates, in a flattened
	 * key/value array
	 *
	 * @since   4.5.7
	 *
	 * @param   string $post_type  Post Type.
	 * @return  array               Tags
	 */
	public function get_tags_flat( $post_type ) {

		$tags_flat = array();
		foreach ( $this->get_tags( $post_type ) as $tag_group => $tag_group_tags ) {
			foreach ( $tag_group_tags as $tag => $tag_attributes ) {
				$tags_flat[] = array(
					'key'   => $tag,
					'value' => $tag,
				);
			}
		}

		return $tags_flat;

	}

	/**
	 * Helper method to retrieve Post actions
	 *
	 * @since   3.0.0
	 *
	 * @return  array           Post Actions
	 */
	public function get_post_actions() {

		// Build post actions.
		$actions = array(
			'publish'      => __( 'Publish', 'wp-to-social-pro' ),
			'update'       => __( 'Update', 'wp-to-social-pro' ),
			'repost'       => __( 'Repost', 'wp-to-social-pro' ),
			'bulk_publish' => __( 'Bulk Publish', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the Post actions which trigger status(es) to be sent to social media.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $actions    Post Actions.
		 */
		$actions = apply_filters( $this->base->plugin->filter_name . '_get_post_actions', $actions );

		// Return filtered results.
		return $actions;

	}

	/**
	 * Helper method to retrieve Post actions, with labels in the past tense.
	 *
	 * @since   3.7.2
	 *
	 * @return  array           Post Actions
	 */
	public function get_post_actions_past_tense() {

		// Build post actions.
		$actions = array(
			'publish'      => __( 'Published', 'wp-to-social-pro' ),
			'update'       => __( 'Updated', 'wp-to-social-pro' ),
			'repost'       => __( 'automatically reposted by this Plugin', 'wp-to-social-pro' ),
			'bulk_publish' => __( 'manually bulk published using this Plugin\'s Bulk Publish functionality', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the Post actions which trigger status(es) to be sent to social media,
		 * with labels set to the past tense.
		 *
		 * @since   3.0.0
		 *
		 * @param   array   $actions    Post Actions.
		 */
		$actions = apply_filters( $this->base->plugin->filter_name . '_get_post_actions_past_tense', $actions );

		// Return filtered results.
		return $actions;

	}

	/**
	 * Helper method to retrieve Conditional Options
	 *
	 * @since   3.2.0
	 *
	 * @return  array           Condition Options
	 */
	public function get_condition_options() {

		// Build condition options.
		$options = array(
			''            => __( 'No Conditions', 'wp-to-social-pro' ),
			'include_any' => __( 'Post(s) must include ANY Terms', 'wp-to-social-pro' ),
			'include_all' => __( 'Post(s) must include ALL Terms', 'wp-to-social-pro' ),
			'exclude_any' => __( 'Post(s) must exclude ANY Terms', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available Options for Taxonomy Terms Conditionals.
		 *
		 * @since   3.2.0
		 *
		 * @param   array   $options    Condition Options.
		 */
		$options = apply_filters( $this->base->plugin->filter_name . '_get_condition_options', $options );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Post Override Options
	 *
	 * @since   3.2.5
	 *
	 * @return  array       Post Override Options
	 */
	public function get_override_options() {

		// Build condition options.
		$options = array(
			'-1' => sprintf(
				/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
				__( 'Do NOT Post to %s', 'wp-to-social-pro' ),
				$this->base->plugin->account
			),
			'0'  => __( 'Use Plugin Settings', 'wp-to-social-pro' ),
			'1'  => sprintf(
				/* translators: Social Media Service Name (Buffer, Hootsuite, SocialPilot) */
				__( 'Post to %s using Manual Settings', 'wp-to-social-pro' ),
				$this->base->plugin->account
			),
		);

		/**
		 * Defines the available override options to display in the meta box for individual Posts.
		 *
		 * @since   3.2.5
		 *
		 * @param   array   $options    Condition Options.
		 */
		$options = apply_filters( $this->base->plugin->filter_name . '_get_override_options', $options );

		// Return filtered results.
		return $options;

	}

	/**
	 * Helper method to retrieve Authors
	 *
	 * @since   3.3.8
	 *
	 * @return  array   Authors
	 */
	public function get_authors() {

		// Filter arguments to get Authors.
		$args = apply_filters(
			$this->base->plugin->filter_name . '_get_authors_args',
			array(
				'role__not_in' => 'subscriber',
			)
		);

		// Run query.
		$user_query = new WP_User_Query( $args );

		// Get Authors.
		$authors = $user_query->results;

		/**
		 * Defines the available override options to display in the meta box for individual Posts.
		 *
		 * @since   3.3.8
		 *
		 * @param   array   $authors    WordPress Users.
		 */
		$authors = apply_filters( $this->base->plugin->filter_name . '_get_authors', $authors );

		// Return filtered results.
		return $authors;

	}

	/**
	 * Helper method to retrieve Post comparison operators, used for Conditional Options on status(es).
	 *
	 * @since   3.3.8
	 *
	 * @return  array   Meta Compare options
	 */
	public function get_comparison_operators() {

		// Define meta compare options.
		$comparison_operators = array(
			'='         => __( 'Equals', 'wp-to-social-pro' ),
			'!='        => __( 'Does not Equal', 'wp-to-social-pro' ),
			'>'         => __( 'Greater Than', 'wp-to-social-pro' ),
			'>='        => __( 'Greater Than or Equal To', 'wp-to-social-pro' ),
			'<'         => __( 'Less Than', 'wp-to-social-pro' ),
			'<='        => __( 'Less Than or Equal To', 'wp-to-social-pro' ),
			'IN'        => __( 'In (Comma Separated Values)', 'wp-to-social-pro' ),
			'NOT IN'    => __( 'Not In (Comma Separated Values)', 'wp-to-social-pro' ),
			'LIKE'      => __( 'Like', 'wp-to-social-pro' ),
			'NOT LIKE'  => __( 'Not Like', 'wp-to-social-pro' ),
			'EMPTY'     => __( 'Empty (Value Ignored)', 'wp-to-social-pro' ),
			'NOT EMPTY' => __( 'Not Empty (Value Ignored)', 'wp-to-social-pro' ),
		);

		/**
		 * Backward compatible filter; defines the available Post comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   3.3.8
		 *
		 * @param   array   $comparison_operators    Comparison Operators.
		 */
		$comparison_operators = apply_filters( $this->base->plugin->filter_name . '_get_meta_compare', $comparison_operators );

		/**
		 * Defines the available Post comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   4.0.7
		 *
		 * @param   array   $comparison_operators    Comparison Operators.
		 */
		$comparison_operators = apply_filters( $this->base->plugin->filter_name . '_get_comparison_operators', $comparison_operators );

		// Return filtered results.
		return $comparison_operators;

	}

	/**
	 * Helper method to retrieve Custom Field comparison operators, used for Conditional Options on status(es).
	 *
	 * @since   3.3.8
	 *
	 * @return  array   Meta Compare options
	 */
	public function get_custom_field_comparison_operators() {

		// Define comparison operators.
		$comparison_operators = array(
			'='          => __( 'Equals', 'wp-to-social-pro' ),
			'!='         => __( 'Does not Equal', 'wp-to-social-pro' ),
			'>'          => __( 'Greater Than', 'wp-to-social-pro' ),
			'>='         => __( 'Greater Than or Equal To', 'wp-to-social-pro' ),
			'<'          => __( 'Less Than', 'wp-to-social-pro' ),
			'<='         => __( 'Less Than or Equal To', 'wp-to-social-pro' ),
			'IN'         => __( 'In (Comma Separated Values)', 'wp-to-social-pro' ),
			'NOT IN'     => __( 'Not In (Comma Separated Values)', 'wp-to-social-pro' ),
			'LIKE'       => __( 'Like', 'wp-to-social-pro' ),
			'NOT LIKE'   => __( 'Not Like', 'wp-to-social-pro' ),
			'EMPTY'      => __( 'Empty (Value Ignored)', 'wp-to-social-pro' ),
			'NOT EMPTY'  => __( 'Not Empty (Value Ignored)', 'wp-to-social-pro' ),
			'NOT EXISTS' => __( 'Not Exists (Value Ignored)', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available Custom Field comparison operators, used for Conditional Options on status(es).
		 *
		 * @since   4.0.8
		 *
		 * @param   array   $comparison_operators   Comparison Operators.
		 */
		$comparison_operators = apply_filters( $this->base->plugin->filter_name . '_get_custom_field_comparison_operators', $comparison_operators );

		// Return filtered results.
		return $comparison_operators;

	}

	/**
	 * Helper method to retrieve order by options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Order By
	 */
	public function get_order_by() {

		// Define order by.
		$order_by = array(
			'date'          => __( 'Published Date', 'wp-to-social-pro' ),
			'ID'            => __( 'Post ID', 'wp-to-social-pro' ),
			'author'        => __( 'Post Author', 'wp-to-social-pro' ),
			'title'         => __( 'Title', 'wp-to-social-pro' ),
			'name'          => __( 'Post Name', 'wp-to-social-pro' ),
			'modified'      => __( 'Modified Date', 'wp-to-social-pro' ),
			'rand'          => __( 'Random', 'wp-to-social-pro' ),
			'comment_count' => __( 'Number of Comments', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available WP_Query compatible order by options.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $order_by   Order By options.
		 */
		$order_by = apply_filters( $this->base->plugin->filter_name . '_get_order_by', $order_by );

		// Return filtered results.
		return $order_by;

	}

	/**
	 * Helper method to retrieve order options
	 *
	 * @since   1.0.0
	 *
	 * @return  array   Order
	 */
	public function get_order() {

		// Define order.
		$order = array(
			'DESC' => __( 'Descending (Z-A / Newest to Oldest)', 'wp-to-social-pro' ),
			'ASC'  => __( 'Ascending (A-Z / Oldest to Newest)', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available WP_Query compatible order options.
		 *
		 * @since   1.0.0
		 *
		 * @param   array   $order   Order options.
		 */
		$order = apply_filters( $this->base->plugin->filter_name . '_get_order', $order );

		// Return filtered results.
		return $order;

	}

	/**
	 * Helper method to return template tags that cannot have a character limit applied to them.
	 *
	 * @since   3.7.8
	 *
	 * @return  array   Tags.
	 */
	public function get_tags_excluded_from_character_limit() {

		$tags = array(
			'date',
			'url',
			'id',
			'author_user_email',
			'author_user_url',
		);

		/**
		 * Defines the tags that cannot have a character limit applied to them, as doing so would
		 * wrongly concatenate data (e.g. a URL would become malformed).
		 *
		 * @since   3.7.8
		 *
		 * @param   array   $tags   Tags.
		 */
		$tags = apply_filters( $this->base->plugin->filter_name . '_get_tags_excluded_from_character_limit', $tags );

		// Return filtered results.
		return $tags;

	}

	/**
	 * Helper method to retrieve available TTF fonts for use with Text to Image
	 *
	 * @since   4.2.0
	 *
	 * @return  array   Fonts
	 */
	public function get_fonts() {

		$fonts = array(
			'Lato-Regular'         => __( 'Lato (Regular)', 'wp-to-social-pro' ),
			'Merriweather-Regular' => __( 'Merriweather (Regular)', 'wp-to-social-pro' ),
			'Montserrat-Regular'   => __( 'Montserrat (Regular)', 'wp-to-social-pro' ),
			'NotoSans-Regular'     => __( 'Noto Sans (Regular)', 'wp-to-social-pro' ),
			'OpenSans-Regular'     => __( 'Open Sans (Regular)', 'wp-to-social-pro' ),
			'Oswald-Regular'       => __( 'Oswald (Regular)', 'wp-to-social-pro' ),
			'Raleway-Regular'      => __( 'Raleway (Regular)', 'wp-to-social-pro' ),
		);

		/**
		 * Defines the available TTF fonts for use with Text to Image
		 *
		 * @since   4.2.0
		 *
		 * @param   array   $fonts  Fonts.
		 */
		$fonts = apply_filters( $this->base->plugin->filter_name . '_get_fonts', $fonts );

		// Return filtered results.
		return $fonts;

	}

	/**
	 * Helper method to retrieve transient expiration time
	 *
	 * @since   3.0.0
	 *
	 * @return  int     Expiration Time (seconds)
	 */
	public function get_transient_expiration_time() {

		// Set expiration time for all transients = 12 hours.
		$expiration_time = ( 12 * HOUR_IN_SECONDS );

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   3.0.0
		 *
		 * @param   int     $expiration_time    Transient Expiration Time, in seconds.
		 */
		$expiration_time = apply_filters( $this->base->plugin->filter_name . '_get_transient_expiration_time', $expiration_time );

		// Return filtered results.
		return $expiration_time;

	}

	/**
	 * Helper method to remove array keys that the given WordPress User Role doesn't have access to
	 *
	 * Checks if Restrict Roles is enabled
	 *
	 * The array can be either Post Type Settings or Post Settings, as the top level keys will always
	 * be profile_ids
	 *
	 * @since   3.0.6
	 *
	 * @param   array  $arr    Post Type or Post Settings.
	 * @param   string $role   Role.
	 * @return                  Post Type or Post Settings
	 */
	public function maybe_remove_profiles_by_role( $arr, $role ) {

		// Check if restrict roles is enabled.
		$restrict_roles = (bool) $this->base->get_class( 'settings' )->get_option( 'restrict_roles', 0 );
		if ( ! $restrict_roles ) {
			return $arr;
		}

		// Iterate through profiles, checking if the role has access to the profile.
		foreach ( $arr as $profile_id => $data ) {
			// Always grant access to default.
			if ( $profile_id === 'default' ) {
				continue;
			}

			// Get access for this role and profile combination.
			$access = (bool) $this->base->get_class( 'settings' )->get_setting( 'roles', '[' . $role . '][' . $profile_id . ']', 0 );

			// If no access, remove profile from array.
			if ( ! $access ) {
				unset( $arr[ $profile_id ] );
			}
		}

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   3.0.6
		 *
		 * @param   array   $arr    Post Type or Post Settings.
		 * @param   string  $role   WordPress Role Name.
		 */
		$arr = apply_filters( $this->base->plugin->filter_name . '_maybe_remove_profiles_by_role', $arr, $role );

		// Return filtered results.
		return $arr;

	}

	/**
	 * Helper method to remove array keys that the given WordPress User Role doesn't have access to
	 *
	 * Checks if Restrict Post Types is enabled
	 *
	 * @since   3.7.2
	 *
	 * @param   array  $post_types  Post Types.
	 * @param   string $role        Role.
	 * @return  array               Post Types
	 */
	public function maybe_remove_post_types_by_role( $post_types, $role ) {

		// Check if restrict post types is enabled.
		$restrict_post_types = (bool) $this->base->get_class( 'settings' )->get_option( 'restrict_post_types', 0 );
		if ( ! $restrict_post_types ) {
			return $post_types;
		}

		// Iterate through profiles, checking if the role has access to the profile.
		foreach ( $post_types as $post_type => $post_type_object ) {
			// Get access for this role and profile combination.
			$access = (bool) $this->base->get_class( 'settings' )->get_setting( 'roles', '[' . $role . '][' . $post_type . ']', 0 );

			// If no access, remove profile from array.
			if ( ! $access ) {
				unset( $post_types[ $post_type ] );
			}
		}

		/**
		 * Defines the number of seconds before expiring transients.
		 *
		 * @since   3.7.2
		 *
		 * @param   array   $post_types     Post Types.
		 * @param   string  $role           WordPress Role Name.
		 */
		$post_types = apply_filters( $this->base->plugin->filter_name . '_maybe_remove_post_types_by_role', $post_types, $role );

		// Return filtered results.
		return $post_types;

	}

	/**
	 * Defines the registered filters that can be used on the Log WP_List_Table
	 *
	 * @since   3.9.8
	 *
	 * @return  array   Filters
	 */
	public function get_log_filters() {

		// Define filters.
		$filters = array(
			'action',
			'profile_id',
			'result',
			'request_sent_start_date',
			'request_sent_end_date',
			'orderby',
			'order',
		);

		/**
		 * Defines the registered filters that can be used on the Log WP_List_Tables.
		 *
		 * @since   3.9.8
		 *
		 * @param   array   $filters    Filters.
		 */
		$filters = apply_filters( $this->base->plugin->filter_name . '_get_log_filters', $filters );

		// Return filtered results.
		return $filters;

	}

}
