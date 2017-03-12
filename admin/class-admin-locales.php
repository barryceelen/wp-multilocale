<?php
/**
 * Contains admin related locales functionality
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Admin-related locales functionality.
 *
 * @since 0.0.1
 */
class Multilocale_Admin_Locales {

	/**
	 * Instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Locale taxonomy name.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_locale_taxonomy;

	/**
	 * Options page identifier.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_options_page;

	/**
	 * Error messages array.
	 *
	 * @since 0.0.1
	 *
	 * @var array
	 */
	private $_error_messages;

	/**
	 * Initialize class.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		$this->_locale_taxonomy = multilocale()->locale_taxonomy;
		$this->_options_page = multilocale()->options_page;

		$this->_error_messages = array(
			'db_insert_error'        => __( 'Could not insert locale into the database' ,'multilocale' ),
			'empty_term_description' => __( 'The WordPress locale is required.', 'multilocale' ),
			'empty_term_name'        => __( 'A name is required', 'multilocale' ),
			'empty_term_slug'        => __( 'A slug is required', 'multilocale' ),
			'invalid_taxonomy'       => __( 'Invalid locale taxonomy', 'multilocale' ),
			'invalid_term'           => __( 'Locale not found', 'multilocale' ),
			'invalid_term_id'        => __( 'Invalid locale term ID', 'multilocale' ),
			'term_exists'            => __( 'A locale with the name provided already exists.' ),
		);

		$this->add_actions_and_filters();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 0.0.1
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return void
	 */
	private function add_actions_and_filters() {
		/*
		 * Add installed locales to the update checks in wp-includes/update.php.
		 *
		 * Note: Core language packs are installed when adding a locale. The updater looks at
		 *       installed language packs when checking for updates.
		 */
		add_filter( 'plugins_update_check_locales', array( $this, 'filter_update_check_locales' ) );
		add_filter( 'themes_update_check_locales', array( $this, 'filter_update_check_locales' ) );

		// Do stuff before the settings page loads.
		add_action( 'load-settings_page_' . $this->_options_page, array( $this, 'handle_get_request' ), 10 );
		add_action( 'load-settings_page_' . $this->_options_page, array( $this, 'handle_post_request' ), 11 );

		// Add explanation to init page.
		add_action( 'multilocale_pre_init_form', array( $this, 'add_init_page_text' ) );

		// Add locale selection to init page.
		add_action( 'multilocale_init_form_table_rows', array( $this, 'add_init_page_form_table_row' ) );

		// Render content for the plugin settings page.
		add_action( 'multilocale_settings_page_content', array( $this, 'render_settings_page_content' ), 10, 2 );

		// Install core language pack when inserting a locale.
		// Todo: Install theme and plugin ones, or leave this to the updater?
		add_action( "create_{$this->_locale_taxonomy}" , array( $this, 'install_language_pack' ) );

		// Delete core, theme and plugin language packs and preferences when deleting a locale.
		add_action( 'pre_delete_term' , array( $this, 'delete_language_packs_and_user_preferences' ), 10, 2 );
	}

	/**
	 * Make WordPress check for updates to our installed locales.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param array $locales List of locales.
	 * @return array
	 */
	function filter_update_check_locales( $locales ) {

		$installed_locales = multilocale_get_locales();

		if ( $installed_locales ) {
			$locales = array_unique( array_merge( $locales, wp_list_pluck( $installed_locales, 'description' ) ) );
		}

		return $locales;
	}

	/**
	 * Handle post requests on the settings page.
	 *
	 * @todo Nonce.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return void
	 */
	public function handle_post_request() {

		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) { // WPCS: input var okay.
			return;
		}

		if ( ! isset( $_POST['multilocale_settings'] ) || ! wp_verify_nonce( wp_unslash( $_POST['multilocale_settings'] ), 'multilocale_settings' ) || empty( $_POST['action'] ) ) { // WPCS: input var okay, sanitization ok.
			return;
		}

		$post_data = wp_unslash( $_POST );  // WPCS: input var okay.

		$locale_taxonomy_obj = get_taxonomy( $this->_locale_taxonomy );

		if ( in_array( $post_data['action'], array( 'init', 'insert_locale' ), true ) ) {

			// Note: We're only using input from the locale dropdown for the time being.
			// Todo: Use https://github.com/Automattic/wp-cldr plugin if it is activated.
			require_once( MULTILOCALE_PLUGIN_DIR . 'admin/includes/vendor/glotpress/locales.php' );

			$gp_locales_obj = new GP_Locales;
			$gp_locales_array = $gp_locales_obj->locales();

			if ( ! array_key_exists( $post_data['select_locale'], $gp_locales_array ) ) {
				/*
				 * Todo: Set correct 'Unknown locale' error message and
				 *       add a link to a custom locale form.
				 */
				$this->add_settings_error(
					'locale_unknown',
					sprintf( __( 'Unknown locale. %s', 'multilocale' ), '<a href="#">Add custom locale</a>' )
				);
				return;
			}

			$gp_locale = $gp_locales_array[ $post_data['select_locale'] ];
			$wp_locale = $this->get_wp_locale_code_from_gp_locale_obj( $gp_locale );

			$locale_array = array( $gp_locale->native_name, $wp_locale, $gp_locale->slug );
		}

		switch ( $post_data['action'] ) {
			case 'init' :

				$term = multilocale_insert_locale( $locale_array[0], $locale_array[1], $locale_array[2] );

				if ( is_wp_error( $term ) ) {

					$this->add_settings_error( $term->get_error_code(), $term->get_error_message() );

				} else {

					// Todo: Should we not use meta for the default locale but just use the existing options?
					update_term_meta( $term['term_id'], 'blogname', get_option( 'blogname' ) );
					update_term_meta( $term['term_id'], 'blogdescription', get_option( 'blogdescription' ) );

					multilocale_set_default_locale( $term['term_id'] );

					$this->add_settings_error(
						'default_locale_added',
						sprintf(
							__( 'Default %1$s added. <a href="%2$s">%3$s</a>', 'multilocale' ),
							$locale_taxonomy_obj->labels->singular_name,
							$edit_url = add_query_arg( array( 'action' => 'edit_locale', 'locale_id' => $term['term_id'] ),
							admin_url( 'options-general.php?page=' . $this->_options_page ) ),
							$locale_taxonomy_obj->labels->edit_item
						),
						'updated'
					);

					/**
					 * Fires after the default locale has been added via the plugin init screen.
					 *
					 * @since 0.0.1
					 *
					 * @param string $term['term_id'] Default locale term ID.
					 */
					do_action( 'multilocale_init', $term['term_id'] );
				}
				break;
			case 'insert_locale' :

				$term = multilocale_insert_locale( $locale_array[0], $locale_array[1], $locale_array[2] );

				if ( is_wp_error( $term ) ) {

					$this->add_settings_error( $term->get_error_code(), $term->get_error_message() );

				} else {

					update_term_meta( $term['term_id'], '_blogname', get_option( 'blogname' ) );
					update_term_meta( $term['term_id'], '_blogdescription', get_option( 'blogdescription' ) );

					$this->add_settings_error(
						'default_locale_added',
						sprintf(
							__( '%1$s added. <a href="%2$s">%3$s</a>', 'multilocale' ),
							$locale_taxonomy_obj->labels->singular_name,
							$edit_url = add_query_arg( array( 'action' => 'edit_locale', 'locale_id' => $term['term_id'] ),
							admin_url( 'options-general.php?page=' . $this->_options_page ) ),
							$locale_taxonomy_obj->labels->edit_item
						),
						'updated'
					);
				}
				break;
			case 'edit_locale' :

				$args = array();

				/*
				 * The locale name, eg. "Deutsch".
				 */
				if ( isset( $post_data['name'] ) ) {
					$term_exists = term_exists( $post_data['name'], $this->_locale_taxonomy );
					if ( $term_exists && (int) $term_exists['term_id'] !== (int) $post_data['locale_id']  ) {
						$this->add_settings_error( 'term_exists' );
					} else {
						$args['name'] = $post_data['name'];
					}
				}

				/*
				 * The WP Locale, eg. "de_DE".
				 *
				 * Saved to term description.
				 * wp_update_term() allows an empty description, let's enforce it here.
				 *
				 * We're not requiring WP_Locale to be unique.
				 * This enables using one WP_Locale for multiple locales.
				 */
				if ( isset( $post_data['description'] ) ) {
					if ( empty( $post_data['description'] ) ) {
						$this->add_settings_error( 'empty_term_description' );
					} else {
						$args['description'] = $post_data['description'];
					}
				}

				/*
				 * The locale slug, eg. "de".
				 * Uniqueness enforced by wp_update_term().
				 */
				if ( isset( $post_data['slug'] ) ) {
					$args['slug'] = $post_data['slug'];
				}

				if ( $args ) {
					$term = wp_update_term( absint( $post_data['locale_id'] ), $this->_locale_taxonomy, $args );
					if ( is_wp_error( $term ) ) {
						if ( 'duplicate_term_slug' === $term->get_error_code() ) {
							$this->add_settings_error(
								'duplicate_term_slug',
								sprintf(
									__( 'The slug &#8220;%s&#8221; is already in use by another locale', 'multilocale' ),
									esc_html( $post_data['slug'] )
								)
							);
						} else {
							$this->add_settings_error( $term->get_error_code(), $term->get_error_message() );
						}
					}
				}

				update_term_meta( absint( $post_data['locale_id'] ), 'blogname', $post_data['blogname'] );
				update_term_meta( absint( $post_data['locale_id'] ), 'blogdescription', $post_data['blogdescription'] );

				if ( ! empty( $post_data['date_format'] )
					&& isset( $post_data['date_format_custom'] )
					&& 'custom' === wp_unslash( $post_data['date_format'] )
				) {
					update_term_meta( absint( $post_data['locale_id'] ), 'date_format', $post_data['date_format_custom'] );
				} else {
					update_term_meta( absint( $post_data['locale_id'] ), 'date_format', $post_data['date_format'] );
				}

				if ( ! empty( $post_data['time_format'] )
					&& isset( $post_data['time_format_custom'] )
					&& 'custom' === wp_unslash( $post_data['time_format'] )
				) {
					update_term_meta( absint( $post_data['locale_id'] ), 'time_format', $post_data['time_format_custom'] );
				} else {
					update_term_meta( absint( $post_data['locale_id'] ), 'time_format', $post_data['time_format'] );
				}

				if ( ! empty( $post_data['default_locale'] ) ) {
					$term = get_term_by( 'id', absint( $term['term_id'] ), $this->_locale_taxonomy );
					if ( $term && ! is_wp_error( $term ) ) {
						multilocale_set_default_locale( $term->term_id );
					}
				}

				$this->add_settings_error( 'locale_updated', __( 'Locale updated', 'multilocale' ), 'updated' );

				break;
			case 'delete_locale' :

				$term = get_term( absint( $post_data['locale_id'] ), $this->_locale_taxonomy );

				if ( ! $term || is_wp_error( $term ) ) {

					if ( is_wp_error( $term ) ) {
						$this->add_settings_error( $term->get_error_code(), $term->get_error_message() );
					} else {
						$this->add_settings_error( 'invalid_term', $this->_error_messages['invalid_term'] );
					}
				} else {

					if ( ! empty( $post_data['default_locale_id'] ) ) {
						multilocale_set_default_locale( $post_data['default_locale_id'] );
					}

					multilocale_delete_locale( $post_data['locale_id'] );

					wp_safe_redirect( admin_url( 'options-general.php?page=' . $this->_options_page ) );
					exit();
				}
				break;
		}
	}

	/**
	 * Handle get requests on the settings page.
	 *
	 * This probably falls in the "doing it wrong" category?
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return void
	 */
	public function handle_get_request() {

		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) { // WPCS: input var okay.
			return;
		}

		$get_data = wp_unslash( $_GET ); // WPCS: input var okay.

		$action = ( empty( $get_data['action'] ) ) ? false : (string) $get_data['action'];

		switch ( $action ) {
			case 'edit_locale' :
			case 'delete_locale' :

				$locale_id = (int) $get_data['locale_id'];
				$locale = get_term( $locale_id, $this->_locale_taxonomy, OBJECT, 'edit' );

				if ( ! $locale ) {
					wp_die( esc_html__( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );
				}
				if ( is_wp_error( $locale ) ) {
					wp_die( esc_html__( 'You did not select an item for editing.' ) );
				}
				break;
		}
	}

	/**
	 * Add explanation to plugin init page.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return void
	 */
	public function add_init_page_text() {

		$supported_post_types = get_post_types_by_support( 'multilocale' );
		$messages = array();

		if ( ! $supported_post_types ) {
			/*
			 * If we have no registered supported post types, let the user know.
			 * Todo: Improve and/or offer interface for registering post types.
			 */
			$messages[] = __( 'Select the default locale.', 'multilocale' );
			$messages[] = __( '<strong>Note:</strong> No post types with multilocale support detected.' );
		} else {
			/*
			 * Alrighty then, we have one or more supported post types.
			 * Compose a comma separated list of supported post type labels.
			 */
			$post_type_names = array();

			foreach ( $supported_post_types as $post_type ) {

				$post_type_obj = get_post_type_object( $post_type );

				if ( $post_type_obj ) {
					if ( ! empty( $post_type_obj->labels->name ) ) {
						$post_type_names[] = $post_type_obj->labels->name;
					} else {
						$post_type_names[] = $post_type;
					}
				}
			}
			if ( 1 === count( $post_type_names ) ) {
				$labels = $post_type_names[0];
			} else {
				// Todo: There is a wp function taking care of this, use it.
				$last_label = array_pop( $post_type_names );
				$labels     = implode( ', ', $post_type_names );
				$labels     = sprintf( _x( '%1$s and %2$s', 'A comma-separated list where "and" comes before the last element, eg. Dutch, English and French', 'multilocale' ), esc_html( $labels ), esc_html( $last_label ) );
			}
			$messages[] = sprintf( _x( 'Select your default locale. Existing content (%s) will be automatically assigned to the default locale.', 'Refers to a comma separated list of supported post types', 'multilocale' ), esc_html( $labels ) );
		}

		foreach ( $messages as $message ) {
			echo '<p>' . wp_kses( $message, array( 'strong' => array() ) ) . '</p>';
		}
	}

	/**
	 * Add default locale select to plugin init form.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return void
	 */
	public function add_init_page_form_table_row() {

		$locale = get_locale();

		/*
		 * Determine the current locale.
		 *
		 * We're looking to get a locale native name and slug.
		 * If en_US is the current locale, set our own native name, as the GlotPress
		 * class uses 'English' as the native name for en_US.
		 */
		if ( 'en_US' === $locale ) {
			$native_name = 'English';
		} else {

			require_once( MULTILOCALE_PLUGIN_DIR . 'admin/includes/vendor/glotpress/locales.php' );

			$gp_locales_obj = new GP_Locales;
			$gp_locales_array = $gp_locales_obj->locales();
			$known_locales = wp_list_pluck( $gp_locales_array, 'slug', 'wp_locale' );

			if ( array_key_exists( $locale, $known_locales ) ) {
				$locale_obj = $gp_locales_array[ $known_locales[ $locale ] ];
				$native_name = $locale_obj->native_name;
				$slug = $locale_obj->slug;
			} else {
				/*
				 * The WP_Locale is not present in the locales object.
				 *
				 * Todo: Notify user about unknown locale before/after setting default locale.
				 * Todo: Offer a form with text inputs in stead of the locale dropdown.
				 */
				$native_name = __( 'Undefined', 'multilocale' );
			}
		}

		printf( // WPCS: XSS ok.
			'<tr><th scope="row"><label for="select_locale" />%s</label></th><td>%s</td></tr>',
			esc_html__( 'Default Locale', 'multilocale' ), // Todo: Get label via tax object.
			$this->get_locale_dropdown( array( 'selected' => array( $native_name ) ) )
		);
	}

	/**
	 * Render settings page content per action.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param string $action Current action, eg. 'edit_locale'.
	 */
	public function render_settings_page_content( $action ) {
		switch ( $action ) {
			case 'locales' :
				require_once( MULTILOCALE_PLUGIN_DIR . 'admin/templates/content-locales.php' );
				break;
			case 'edit_locale' :
				require_once( MULTILOCALE_PLUGIN_DIR . 'admin/templates/content-edit-locale.php' );
				break;
			case 'delete_locale' :
				require_once( MULTILOCALE_PLUGIN_DIR . 'admin/templates/content-delete-locale.php' );
				break;
		}
	}

	/**
	 * Delete core, theme and plugin language packs when a term is deleted from the locale taxonomy.
	 *
	 * @todo Make this optional?
	 *
	 * @access private
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @param string $term_id Taxonomy term ID.
	 * @param string $taxonomy Taxonomy name.
	 * @return string $term_id Term ID.
	 */
	public function delete_language_packs_and_user_preferences( $term_id, $taxonomy ) {

		global $wpdb;

		if ( $this->_locale_taxonomy !== $taxonomy ) {
			return $term_id;
		}

		$term  = get_term( $term_id, $this->_locale_taxonomy );
		$files = array();
		$types = array( 'core', 'plugins', 'themes' );

		foreach ( $types as $type ) {
			$installed_translations = wp_get_installed_translations( $type );
			foreach ( $installed_translations as $k => $v ) {
				if ( array_key_exists( $term->description, $v ) ) {
					$dir = ( 'core' === $type ) ? WP_LANG_DIR . '/' : WP_LANG_DIR . '/' . $type . '/';
					$prefix = ( 'core' === $type && 'default' === $k ) ? '' : $k . '-';
					$files[] = $dir . $prefix . $term->description . '.mo';
					$files[] = $dir . $prefix . $term->description . '.po';
				}
			}
		}

		foreach ( $files as $file ) {
			wp_delete_file( $file );
		}

		return $term_id;
	}

	/**
	 * Install a core language pack when a term is added to the locale taxonomy.
	 *
	 * @since 0.0.1
	 *
	 * @see wp_download_language_pack()
	 *
	 * @access private
	 * @param string $term_id Locale term ID.
	 * @return string
	 */
	public function install_language_pack( $term_id ) {

		require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );

		if ( ! wp_can_install_language_pack() ) {
			return false;
		}

		$term = get_term( $term_id, $this->_locale_taxonomy );

		if ( 'en_US' === $term->description ) {
			return $term_id;
		}

		$language_pack = wp_download_language_pack( $term->description );

		if ( ! $language_pack ) {
			$this->add_settings_error(
				'language_pack_not_installed',
				__( 'Core language pack not installed.', 'multipop' ),
				'error'
			);
		}

		return $term_id;
	}

	/**
	 * Get the WP_Locale code from a single gp_locale object.
	 *
	 * Generates a WP_Locale from the slug and country code if no WP_Locale is present in the object.
	 *
	 * @access private
	 * @param object $gp_locale A locale object from the GP_Locale class.
	 * @return string The WP_Locale code.
	 */
	private function get_wp_locale_code_from_gp_locale_obj( $gp_locale ) {

		if ( null !== $gp_locale->wp_locale ) {
			return $gp_locale->wp_locale;
		}

		$country_code = '';

		if ( $gp_locale->country_code ) {
			$country_code = '_' . strtoupper( $gp_locale->country_code );
		}

		return $gp_locale->slug . $country_code;
	}

	/**
	 * Get a select form element containing preset locales.
	 *
	 * @todo We're relying on the GlotPress locales class, which is not comprehensive.
	 *       eg. German has a core translation for de_DE and de_DE_formal, the latter is
	 *       not present in the GlotPress class.
	 *
	 * @since 0.0.1
	 *
	 * @param array $args Todo: Describe args.
	 * @return string Dropdown HTML.
	 */
	private function get_locale_dropdown( $args = array() ) {

		// Todo: Make sure we can pass slugs as array or a single slug as a string.
		$defaults = array(
			'selected' => array(),
			'disabled' => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		require_once( MULTILOCALE_PLUGIN_DIR . 'admin/includes/vendor/glotpress/locales.php' );

		$gp_locales_obj = new GP_Locales;
		$gp_locales_array = $gp_locales_obj->locales();
		$html = array();

		foreach ( $gp_locales_array as $locale ) {
			$html[] = sprintf(
				'<option value="%s"%s%s>%s</option>' . "\n",
				esc_attr( $locale->slug ),
				( in_array( $locale->native_name, $args['disabled'], true ) ) ? ' disabled' : '',
				( in_array( $locale->native_name, $args['selected'], true ) ) ? ' selected' : '',
				( 'en' === $locale->slug ) ? 'English (United States)' : esc_html( $locale->native_name )
			);
		}

		return sprintf( '
			<select class="js-select-locale" name="select_locale">%s</select>',
			join( '', $html )
		);
	}

	/**
	 * Convenience abstraction of the core add_settings_error() function.
	 *
	 * @since 0.0.1
	 *
	 * @see add_settings_error()
	 *
	 * @param string $code    Slug-name to identify the error. Used as part of 'id' attribute in HTML output.
	 * @param string $message The formatted message text to display to the user (will be shown inside styled
	 *                        `<div>` and `<p>` tags).
	 * @param string $type    Optional. Message type, controls HTML class. Accepts 'error' or 'updated'.
	 *                        Default 'error'.
	 */
	private function add_settings_error( $code, $message = '', $type = 'error' ) {

		if ( array_key_exists( $code, $this->_error_messages ) ) {
			$message = $this->_error_messages[ $code ];
		}

		add_settings_error( 'plugin_multilocale', $code, $message, $type );
	}
}

global $multilocale_admin_locales;
$multilocale_admin_locales = Multilocale_Admin_Locales::get_instance();
