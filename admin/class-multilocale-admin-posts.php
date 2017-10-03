<?php
/**
 * Contains admin related post functionality
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Admin related posts functionality.
 *
 * @since 0.0.1
 */
class Multilocale_Admin_Posts {

	/**
	 * Instance of this class.
	 *
	 * @since    0.0.1
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Locale taxonomy name.
	 *
	 * @since    0.0.1
	 *
	 * @var      string
	 */
	private $_locale_taxonomy;

	/**
	 * Options page identifier.
	 *
	 * @since    0.0.1
	 *
	 * @var      string
	 */
	private $_options_page;

	/**
	 * Post translation taxonomy name.
	 *
	 * @since 0.0.1
	 *
	 * @var string
	 */
	private $_post_translation_taxonomy;

	/**
	 * Initialize class.
	 *
	 * @since 0.0.1
	 */
	private function __construct() {

		$this->_locale_taxonomy = multilocale()->locale_taxonomy;
		$this->_options_page = multilocale()->options_page;
		$this->_post_translation_taxonomy = multilocale()->post_translation_taxonomy;

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
	 */
	private function add_actions_and_filters() {

		// Enqueue styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		// Add dropdown to filter by locale on the edit.php page.
		add_action( 'restrict_manage_posts', array( $this, 'add_locale_dropdown_filter' ) );

		// Add columns for supported post types.
		add_filter( 'manage_posts_columns', array( $this, 'manage_posts_columns' ), 10, 2 );
		add_filter( 'manage_pages_columns', array( $this, 'manage_pages_columns' ) );
		add_action( 'manage_pages_custom_column', array( $this, 'manage_post_columns_content' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'manage_post_columns_content' ), 10, 2 );

		// Maybe add states for translations of pages used as static front page and posts page.
		add_filter( 'display_post_states', array( $this, 'filter_post_states' ), 10, 2 );

		// Add locale tabs to post edit screen.
		add_action( 'edit_form_top', array( $this, 'edit_form_advanced_tabs' ), 10, 1 );

		// Redirect post-new.php to existing post if one exists with the requested locale and translation.
		add_action( 'load-post-new.php', array( $this, 'maybe_redirect_post_new' ) );

		// Save post locale and translation group.
		add_action( 'save_post', array( $this, 'action_save_post_locale_and_translation_group' ), 10, 2 );

		// Filter post permalinks for the admin.
		add_filter( 'pre_post_link', array( $this, 'filter_pre_post_link' ), 10, 2 );

		// Filter page permalinks for the admin.
		add_filter( '_get_page_link', array( $this, 'filter_post_type_link' ), 10, 2 );

		// Filter custom post type permalinks for the admin.
		add_filter( 'post_type_link', array( $this, 'filter_post_type_link' ), 10, 2 );

		// Do stuff on plugin init, that is, when the init form is submitted.
		add_action( 'multilocale_init', array( $this, 'action_multilocale_init' ) );

		// Do stuff before deleting a term in the locale taxonomy.
		add_action( 'pre_delete_term', array( $this, 'pre_delete_locale' ), 10, 2 );

		// Delete post translation group if it ends up empty.
		add_action( 'deleted_term_relationships', array( $this, 'action_deleted_term_relationships' ), 10, 2 );

		// Modify page parent dropdown in "Page Attributes" meta box.
		// Todo: Temporarily disabled, fix!
		add_filter( 'page_attributes_dropdown_pages_args', array( $this, 'filter_page_attributes_dropdown_pages_args' ), 10, 2 );

		// Show post list in the default locale by default.
		add_action( 'admin_menu', array( $this, 'filter_admin_menu_links_for_posts' ) );

		// Save page_for_posts and page_on_front options per locale.
		add_action( 'update_option_page_for_posts', array( $this, 'localize_page_for_posts_and_page_on_front_options' ), 10, 3 );
		add_action( 'update_option_page_on_front', array( $this, 'localize_page_for_posts_and_page_on_front_options' ), 10, 3 );

		// Maybe save page_for_posts and page_on_front option when saving a post.
		add_action( 'save_post', array( $this, 'update_localized_page_for_posts_and_page_on_front' ), 10, 2 );

		// Maybe remove page_for_posts and page_on_front option when saving a post.
		add_action( 'delete_post', array( $this, 'delete_localized_page_for_posts_and_page_on_front' ) );
	}

	/**
	 * Delete or reassign existing posts when deleting a locale.
	 *
	 * Todo: If is last locale, do not delete posts, just remove their locale and translation group.
	 * Todo: In stead of deleting posts, assign an 'Undefined' locale?
	 * Todo: Improve, only handles admin form for now, should also handle deleting locale terms in general.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param int    $term Term ID.
	 * @param string $taxonomy Taxonomy Name.
	 * @return void
	 */
	public function pre_delete_locale( $term, $taxonomy ) {

		if ( $taxonomy !== $this->_locale_taxonomy ) {
			return;
		}

		if ( ! wp_verify_nonce( wp_unslash( $_POST['multilocale_settings'] ), 'multilocale_settings' ) ) {
			return;
		}

		if ( ! empty( $_POST['delete_option'] ) ) { // WPCS: input var okay.

			if ( 'delete' === $_POST['delete_option'] ) { // WPCS: input var okay.

				$args = array(
					'post_type' => get_post_types_by_support( 'multilocale' ),
					'tax_query' => array( // WPCS: tax_query ok.
						array(
							'taxonomy' => $this->_locale_taxonomy,
							'field'    => 'term_id',
							'terms'    => absint( $_POST['locale_id'] ), // WPCS: input var okay.
						),
					),
				);

				$posts = get_posts( $args );

				if ( $posts ) {
					foreach ( $posts as $post ) {
						/*
						 * Note: The translation group is removed if the post is the last one in it
						 *       via an action on delete_term_relationships.
						 */
						wp_delete_post( $post->ID, true );
					}
				}
			}
		}
	}

	/**
	 * Delete post translation group if the last post is removed from it.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param int   $object_id Object ID.
	 * @param array $tt_ids    An array of term taxonomy IDs.
	 */
	public function action_deleted_term_relationships( $object_id, $tt_ids ) {

		global $wpdb;

		foreach ( $tt_ids as $tt_id ) {
			$tt = $wpdb->get_row( $wpdb->prepare( "SELECT taxonomy, count FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d ", $tt_id ) ); // WPCS: db call ok, cache ok.
			if ( $tt && $this->_post_translation_taxonomy === $tt->taxonomy && 0 === absint( $tt->count ) ) {
				wp_delete_term( $tt_id, $this->_post_translation_taxonomy );
			}
		}
	}

	/**
	 * Do stuff on plugin init.
	 *
	 * Currently sets the locale of all supported post types to the default locale.
	 * Note: The action is fired in the Multilocale_Admin_Locales class.
	 *
	 * @todo In stead of passing the term ID we might as well get it via multilocale_get_default_locale().
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param int $term_id The `term_id` of the default locale.
	 */
	public function action_multilocale_init( $term_id ) {

		if ( ! doing_action( 'multilocale_init' ) ) {
			// Using _doing_it_wrong() is probably doing it wrong.
			_doing_it_wrong( __METHOD__, esc_html__( "This function should only be called on the 'multilocale_init' hook.", 'multilocale' ), '0.0.1' );
			return;
		}

		/*
		 * Todo: Temporary.
		 *       Fix this stuff as it will break if there are more than a handful posts in the database.
		 *
		 */
		$args = array(
			'post_type' => get_post_types_by_support( 'multilocale' ),
			'post_status' => 'any',
			'nopaging' => true,
		);

		$posts = get_posts( $args ); // Todo: Use WP_Query?

		if ( $posts ) {
			foreach ( $posts as $post ) {
				if ( ! multilocale_get_post_locale( $post ) ) {
					$terms = wp_set_object_terms( $post->ID, absint( $term_id ), $this->_locale_taxonomy );
					$translation_group = multilocale_insert_post_translation_group( $post );
				}
			}
		}
	}

	/**
	 * Enqueue styles.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @return void
	 */
	public function enqueue_styles() {

		$current_screen = get_current_screen();

		if ( 'post' === $current_screen->base && post_type_supports( $current_screen->post_type, 'multilocale' ) ) {
			wp_enqueue_style(
				'plugin_multilocale',
				MULTILOCALE_PLUGIN_URL . '/admin/css/posts.css'
			);
		}
	}

	/**
	 * Add locale dropdown filter to post list on edit.php.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function add_locale_dropdown_filter() {

		global $typenow;

		if ( ! post_type_supports( $typenow, 'multilocale' ) ) {
			return;
		}

		$tax_obj = get_taxonomy( $this->_locale_taxonomy );
		$terms = get_terms( $this->_locale_taxonomy );

		if ( count( $terms ) > 0 ) {

			foreach ( $terms as $term ) {

				if ( empty( $_GET[ $tax_obj->name ] ) || $_GET[ $tax_obj->name ] !== $term->slug ) { // WPCS: input var okay.
					$selected = '';
				} else {
					$selected = ' selected="selected"';
				}
				$options[] = "<option value='{$term->slug}'{$selected}>{$term->name}</option>";
			}

			printf(
				'<select name="%s" id="%s" class="postform"><option value="0">%s</option>%s</select>',
				esc_attr( $tax_obj->name ),
				esc_attr( $tax_obj->name ),
				esc_html( $tax_obj->labels->all_items ),
				wp_kses( implode( $options ), array( 'option' => array( 'value' => array(), 'selected' => array() ) ) )
			);
		}
	}

	/**
	 * Add column with post translations to edit.php.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param array  $columns   An array of column names.
	 * @param string $post_type The post type slug.
	 */
	public function manage_posts_columns( $columns, $post_type ) {

		if ( ! post_type_supports( $post_type, 'multilocale' ) ) {
			return $columns;
		}

		$date_column = false;

		if ( array_key_exists( 'date', $columns ) ) {
			$date_column = $columns['date'];
			unset( $columns['date'] );
		}

		$new_columns = array();
		$tax_obj = get_taxonomy( $this->_post_translation_taxonomy );

		if ( array_key_exists( 'taxonomy-' . $this->_locale_taxonomy, $columns ) ) {
			foreach ( $columns as $key => $value ) {
				$new_columns[ $key ] = $value;
				if ( 'taxonomy-' . $this->_locale_taxonomy === $key ) {
					$new_columns['translations'] = $tax_obj->labels->name;
				}
			}
		} else {
			$new_columns = $columns;
			$new_columns['translations'] = $tax_obj->labels->name;
		}

		if ( $date_column ) {
			$new_columns['date'] = $date_column;
		}

		return apply_filters( 'multilocale_manage_post_columns', $new_columns );
	}

	/**
	 * Helper function to apply the manage_posts_columns to the 'page' post type.
	 *
	 * @param array $columns An array of column names.
	 */
	public function manage_pages_columns( $columns ) {
		return $this->manage_posts_columns( $columns, 'page' );
	}

	/**
	 * Render the content for the post translations column.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The current post ID.
	 */
	public function manage_post_columns_content( $column_name, $post_id ) {

		global $post;

		if ( 'translations' !== $column_name ) {
			return;
		}

		$content      = '&mdash;';
		$translations = multilocale_get_post_translations( $post );

		if ( ! empty( $translations )  ) {

			$post_edit_links = array();
			$locales         = multilocale_get_locales();

			foreach ( $locales as $locale ) {

				if ( array_key_exists( $locale->term_id, $translations ) ) {

					$_post = $translations[ $locale->term_id ];
					$href = get_edit_post_link( $_post );
					$post_edit_links[] = sprintf(
						'<a href="%s" title="%s" class="locale-%s translation-%s">%s</a>',
						$href,
						esc_attr( sprintf( _x( 'Edit &quot;%s&quot;', 'String refers to the post title', 'multilocale' ), apply_filters( 'the_title', $_post->post_title ) ) ),
						esc_attr( $locale->description ),
						esc_attr( $_post->post_status ),
						esc_html( $locale->name )
					);
				}
			}

			if ( ! empty( $post_edit_links ) ) {
				$content = implode( ', ', $post_edit_links );
			}
		}

		echo $content; // WPCS: XSS ok.
	}

	/**
	 * Maybe add states for translations of pages used as static front page and posts page.
	 *
	 * @since 0.0.1
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 * @return array An array of post display states.
	 */
	public function filter_post_states( $post_states, $post ) {

		if ( multilocale_page_is_page_on_front( $post, true ) ) {
			$post_states['page_on_front'] = __( 'Front Page' );
		}

		if ( multilocale_page_is_page_for_posts( $post, true ) ) {
			$post_states['page_for_posts'] = __( 'Posts Page' );
		}

		return $post_states;
	}

	/**
	 * Add tabs for each locale to the post edit screen.
	 *
	 * @todo Create tabs class.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param WP_Post $post Post object.
	 */
	public function edit_form_advanced_tabs( $post ) {

		if ( ! post_type_supports( $post->post_type, 'multilocale' ) ) {
			return;
		}

		$locales = multilocale_get_locales();

		if ( ! $locales ) {
			$this->admin_notice_no_locales_found();
			return;
		}

		$post_locale = multilocale_get_post_locale( $post );

		if ( ! $post_locale ) {
			include( MULTILOCALE_PLUGIN_DIR . 'admin/templates/edit-form-advanced-select.php' );
			return;

			/*
			 * Todo: Fix this if the post has not been assigned a locale!
			 *       This could happen if we add post type support after the plugin was activated.
			 *       Also consider what happens when importing posts, WPML etc.
			 */
		}

		$translation_id = multilocale_get_post_translation_group_id( $post );
		$translations   = multilocale_get_posts_by_translation_group_id( $translation_id );
		$post_type_obj  = get_post_type_object( $post->post_type );
		$filter_links   = array();

		if ( 'post' === $post->post_type ) {
			$post_new_file = 'post-new.php';
		} else {
			$post_new_file = "post-new.php?post_type=$post->post_type";
		}

		foreach ( $locales as $locale ) {

			$current_post = false;
			$classes = array( 'locale-tab-' . $locale->description );

			if ( (int) $post_locale->term_id === (int) $locale->term_id ) {
				$classes[] = 'current';
				$current_post = $post;
			} elseif ( array_key_exists( $locale->term_id, $translations ) ) {
				$current_post = $translations[ $locale->term_id ];
			}

			if ( ! $current_post ) {

				$classes[] = 'locale-tab-new';
				$href = add_query_arg( array( 'locale_id' => $locale->term_id, 'translation_id' => $translation_id ), admin_url( $post_new_file ) );
				$title_attr = $post_type_obj->labels->add_new;

			} else {

				$classes[] = 'locale-tab-' . $current_post->post_status;
				$title_attr = $this->get_post_status_label( $current_post->post_status );

				/*
				 * If the tab locale exists in the post translations and the user can edit the translation,
				 * show an edit link. Else, if the post is published show a post link, if not, well not sure
				 * what to do, lets display the locale name and add the author to the link attribute for now.
				 */
				if ( current_user_can( 'edit_post', $current_post->ID ) ) {

					$href = get_edit_post_link( $current_post->ID );

				} elseif ( 'publish' === $current_post->post_status ) {

					$href = get_post_link( $current_post->ID );

				} else {
					/*
					 * Todo: Allow the author of a post to edit translations of that specific post,
					 *       even if the translation was created by another user?
					 */
					$classes[] = 'locale-tab-disabled';
					$href = false;

				}
			}

			$filter_links[] = sprintf(
				'<li><%s%s class="%s" title="%s">%s</%s></li>',
				$href ? 'a' : 'span',
				$href ? " href='{$href}'" : '',
				join( ' ', $classes ),
				esc_attr( $title_attr ),
				esc_html( $locale->name ),
				$href ? 'a' : 'span'
			);
		}

		include( MULTILOCALE_PLUGIN_DIR . 'admin/templates/edit-form-advanced-tabs.php' );
	}

	/**
	 * Redirect post-new.php to existing post if one exists with the requested locale and translation.
	 *
	 * The locale tabs add query args to the post new link to be able to link translations to one another.
	 * Reloading this same URL multiple times can be quite undesirable.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function maybe_redirect_post_new() {

		if ( empty( $_GET['translation_id'] ) || empty( $_GET['locale_id'] ) ) {
			return;
		}

		$current_screen = get_current_screen();

		if ( ! post_type_supports( $current_screen->post_type, 'multilocale' ) ) {
			return;
		}

		// Could also just query for a post with translation_id and locale_id?
		$translations = multilocale_get_posts_by_translation_group_id( (int) wp_unslash( $_GET['translation_id'] ) );

		if ( $translations ) {
			foreach ( $translations as $translation ) {
				$translation_locale = multilocale_get_post_locale( $translation->ID );
				if ( (int) $_GET['locale_id'] === (int) $translation_locale->term_id ) {
					wp_safe_redirect( get_edit_post_link( $translation->ID, '' ) );
					exit;
				}
			}
		}
	}

	/**
	 * Save post locale and translation group.
	 *
	 * Add a post locale and translation group to a post on save.
	 * Even if it is an auto-draft, keeping things simple for the time being.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param string $post_id Post ID.
	 * @param object $post    Post object.
	 */
	public function action_save_post_locale_and_translation_group( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! post_type_supports( $post->post_type, 'multilocale' ) ) {
			return;
		}

		// Get the post type object.
		$post_type = get_post_type_object( $post->post_type );

		// Check if the current user has permission to edit this post.
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return;
		}

		/*
		 * Done checking, let's go.
		 *
		 * If a locale_id is present in the request set it as the post locale if a
		 * locale with that id exists.
		 */

		if ( ! empty( $_REQUEST['locale_id'] ) ) {
			$locale = get_term_by( 'id', absint( $_REQUEST['locale_id'] ), $this->_locale_taxonomy );
			if ( $locale ) {
				$locale_id = $locale->term_id;
			}
		}

		/*
		 * No locale id set before, which most likely means a locale with the requested id does not exist.
		 * If the post does not already have a locale set, add the post to the default locale.
		 */
		if ( empty( $locale_id ) ) {
			$locale = multilocale_get_post_locale( $post );
			if ( $locale ) {
				$locale_id = $locale->term_id;
			} else {
				$options   = get_option( 'plugin_multilocale' );
				$locale_id = $options['default_locale_id'];
			}
		}

		$post_locale = multilocale_update_post_locale( $post, $locale_id );

		if ( empty( $_REQUEST['translation_id'] ) ) {
			/*
			 * If the post is not yet part of a translation group,
			 * create one and add the post to it.
			 */
			$translation_id = multilocale_get_post_translation_group_id( $post );

			if ( ! $translation_id ) {
				multilocale_insert_post_translation_group( $post->ID );
			}
		} else {
			/*
			 * Maybe there are posts in the translation group.
			 * Does the group even exist?
			 */
			$translations = multilocale_get_posts_by_translation_group_id( (int) wp_unslash( $_REQUEST['translation_id'] ) );

			if ( ! $translations ) {
				multilocale_insert_post_translation_group( $post->ID );
			} else {
				/*
				 * There are translations in this group.
				 *
				 * First check if there is a translation for the current locale_id in the group.
				 * If there is, and it is not the current post, move it to its own translation group.
				 *
				 * After that, add the current post to the translation group, even if it is already in it.
				 */
				if ( array_key_exists( $locale_id, $translations ) ) {
					if ( $translations[ $locale_id ]->ID !== $post->ID ) {
						multilocale_insert_post_translation_group( $translations[ $locale_id ] );
					}
				}

				$object_terms = wp_set_object_terms(
					$post->ID,
					absint( $_REQUEST['translation_id'] ),
					$this->_post_translation_taxonomy
				);
			}
		}
	}

	/**
	 * Filter post link for the admin.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 * @param string      $permalink The post's permalink.
	 * @param int|WP_Post $post      The id or the post object of the post in question.
	 * @return string The modified permalink.
	 */
	public function filter_pre_post_link( $permalink, $post ) {
		if ( is_admin() && post_type_supports( $post->post_type, 'multilocale' ) ) {
			$options = get_option( 'plugin_multilocale' );
			$post_locale = multilocale_get_post_locale( $post );
			if ( $post_locale && $post_locale->term_id !== $options['default_locale_id'] ) {
				$permalink = '/' . $post_locale->slug . $permalink;
			}
		}
		return $permalink;
	}

	/**
	 * Filter custom post type link for the admin.
	 *
	 * @access private
	 * @param string      $post_link The post's permalink.
	 * @param int|WP_Post $post      The id or the post object of the post in question.
	 * @return string The modified permalink.
	 */
	public function filter_post_type_link( $post_link, $post ) {

		$_post = get_post( $post );

		if ( is_admin() && post_type_supports( $_post->post_type, 'multilocale' ) ) {

			$options        = get_option( 'plugin_multilocale' );
			$post_locale    = multilocale_get_post_locale( $_post );

			if ( $post_locale && $post_locale->term_id !== $options['default_locale_id'] ) {

				// Todo: Use user_trailingslashit?
				$home_url  = trailingslashit( get_home_url() );
				$post_link = $home_url . $post_locale->slug . '/' . str_replace( $home_url, '', $post_link );

				if ( multilocale_page_is_page_on_front( $post ) ) {
					$post_link = $home_url . $post_locale->slug;
				}
			}
		}
		return $post_link;
	}

	/**
	 * Render 'No locales found' admin notice.
	 *
	 * @since 0.0.1
	 *
	 * @access private
	 */
	public function admin_notice_no_locales_found() {

		if ( current_user_can( 'manage_options' ) ) {
			$msg = sprintf(
				__( 'No locales found. <a href="%s">Add locales</a>.', 'multilocale' ),
				admin_url( '/options-general.php?page=' . $this->_options_page )
			);
		} else {
			$msg = __( 'No locales found' );
		}

		printf( '<div class="error"><p>%s</p></div>', wp_kses( $msg, array( 'a' => array( 'href' ) ) ) );
	}

	/**
	 * Only show posts in the same locale in the page parent dropdown in "Page Attributes" meta box.
	 *
	 * @todo There must be a better way?
	 *
	 * @access private
	 * @param array   $dropdown_args Array of arguments used to generate the pages drop-down.
	 * @param WP_Post $post          The current WP_Post object.
	 * @return array
	 */
	public function filter_page_attributes_dropdown_pages_args( $dropdown_args, $post ) {

		if ( ! post_type_supports( $post->post_type, 'multilocale' ) ) {
			return $dropdown_args;
		}

		$post_locale = multilocale_get_post_locale( $post );

		if ( ! $post_locale ) {
			return $dropdown_args;
		}

		$args = array(
			'post_type' => $dropdown_args['post_type'],
			'fields' => 'ids',
			'tax_query' => array( // WPCS: tax_query ok.
				array(
					'taxonomy' => $this->_locale_taxonomy,
					'field' => 'term_id',
					'terms' => array( $post_locale->term_id ),
					'operator' => 'IN',
				),
			),
			'numberposts' => 100, // Todo: Magic number.
		);

		$posts = get_posts( $args );

		$new_dropdown_args = array(
			'include' => $posts,
			'exclude' => $post->ID,
		);

		return array_merge( $dropdown_args, $new_dropdown_args );
	}

	/**
	 * Show post list in the default locale by default by linking to a filtered list in the admin menu.
	 *
	 * @todo Make user configurable.
	 *
	 * @since 1.0.0
	 */
	public function filter_admin_menu_links_for_posts() {

		global $submenu;

		$array = array();
		$default_locale = multilocale_get_default_locale();

		if ( ! $default_locale ) {
			return;
		}

		foreach ( get_post_types_by_support( 'multilocale' ) as $post_type ) {

			if ( 'post' === $post_type ) {
				$array[] = 'edit.php';
			} else {
				$array[] = 'edit.php?post_type=' . $post_type;
			}
		}

		foreach ( $array as $old_key ) {

			$new_key = add_query_arg( array( 'locale' => $default_locale->slug ), $old_key );

			if ( array_key_exists( $old_key, $submenu ) ) {

				foreach ( $submenu[ $old_key ] as $key => $value ) {
					if ( $old_key === $value[2] ) {
						$submenu[ $old_key ][ $key ][2] = $new_key;
					}
				}
			}
		}
	}

	/**
	 * Get a descriptive label for a post status.
	 *
	 * @since 0.0.1
	 *
	 * @param string $status Post status.
	 * @return string Post status label
	 */
	private function get_post_status_label( $status ) {

		$labels = array(
			'private'    => __( 'Privately Published' ),
			'publish'    => __( 'Published' ),
			'future'     => __( 'Scheduled' ),
			'pending'    => __( 'Pending Review' ),
			'draft'      => __( 'Draft' ),
			'auto-draft' => __( 'Draft' ),
		);

		/**
		 * Modify the labels array.
		 *
		 * @since 0.0.1
		 *
		 * @var $labels array An array where key is post status and value is the label.
		 */
		$labels = apply_filters( 'multilocale_post_status_labels', $labels );

		if ( array_key_exists( $status, $labels ) ) {
			$status = $labels[ $status ];
		}

		return $status;
	}

	/**
	 * Get all posts of a supported post type which have no locale set.
	 *
	 * @since 0.0.1
	 *
	 * @return array List of WP_Post objects.
	 */
	private function get_supported_posts_without_post_locale() {

		global $wpdb;

		$posts = array();

		if ( count( get_post_types_by_support( 'multilocale' ) ) ) {

			$terms = get_terms( array( $this->_locale_taxonomy ), array( 'fields' => 'ids' ) );

			if ( count( $terms ) ) {

				$args = array(
					'post_type' => get_post_types_by_support( 'multilocale' ),
					'tax_query' => array( // WPCS: tax_query ok.
						array(
							'taxonomy' => $this->_locale_taxonomy,
							'terms' => $terms,
							'field' => 'term_id',
							'operator' => 'NOT IN',
						),
					),
				);
			}

			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {
				$posts = $query->posts;
			}
		}

		return $posts;
	}

	/**
	 * Save page_for_posts and page_on_front options per locale.
	 *
	 * @todo Also update this option when adding, removing or changing status of translations.
	 *
	 * @since 1.0.0
	 * @param mixed  $old_value The old option value.
	 * @param mixed  $value     The new option value.
	 * @param string $option    Option name.
	 */
	public function localize_page_for_posts_and_page_on_front_options( $old_value, $value, $option ) {

		if ( ! post_type_supports( 'page', 'multilocale' ) ) {
			return;
		}

		$options = get_option( 'plugin_multilocale' );
		$locales = multilocale_get_locales();

		if ( empty( $value ) ) {
			foreach ( $locales as $locale ) {
				$options[ $option ][ $locale->term_id ] = '';
			}
		} else {

			$translations = multilocale_get_post_translations( (int) $value, 'all', false );

			foreach ( $locales as $locale ) {
				if ( array_key_exists( $locale->term_id, $translations ) && in_array( $translations[ $locale->term_id ]->post_status, array( 'publish', 'private' ), true ) ) {
					$options[ $option ][ $locale->term_id ] = $translations[ $locale->term_id ]->ID;
				} else {
					$options[ $option ][ $locale->term_id ] = '';
				}
			}
		}

		update_option( 'plugin_multilocale', $options );
	}

	/**
	 * Maybe save page_for_posts and page_on_front option when saving a post.
	 *
	 * @todo On unpublish, remove setting.
	 *
	 * @since 1.0.0
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 */
	function update_localized_page_for_posts_and_page_on_front( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! post_type_supports( $post->post_type, 'multilocale' ) ) {
			return;
		}

		$options = get_option( 'plugin_multilocale' );

		if ( $this->page_is_page_for_posts( $post ) ) {

			$post_locale = multilocale_get_post_locale( $post );

			if ( in_array( $post->post_status, array( 'publish', 'private' ), true ) ) {
				$options['page_for_posts'][ $post_locale->term_id ] = $post_id;
				update_option( 'plugin_multilocale', $options );
			} else {
				$options['page_for_posts'][ $post_locale->term_id ] = '';
				update_option( 'plugin_multilocale', $options );
			}

			return;
		}

		if ( $this->page_is_front_page( $post ) ) {

			$post_locale = multilocale_get_post_locale( $post );

			if ( in_array( $post->post_status, array( 'publish', 'private' ), true ) && $options['page_on_front'][ $post_locale->term_id ] !== $post_id ) {
				$options['page_on_front'][ $post_locale->term_id ] = $post_id;
				update_option( 'plugin_multilocale', $options );
			}

			return;
		}
	}

	/**
	 * Maybe remove page_for_posts and page_on_front option when saving a post.
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID.
	 */
	function delete_localized_page_for_posts_and_page_on_front( $post_id ) {

		$_post = get_post( $post_id );

		if ( ! post_type_supports( $_post->post_type, 'multilocale' ) ) {
			return;
		}

		$post_locale = multilocale_get_post_locale( $_post );
		$options = get_option( 'plugin_multilocale' );

		if ( $post_locale && ! empty( $options['page_for_posts'][ $post_locale->term_id ] ) && $options['page_for_posts'][ $post_locale->term_id ] === $post_id ) {
			$options['page_for_posts'][ $post_locale->term_id ] = '';
			update_option( 'plugin_multilocale', $options );
		}
	}

	/**
	 * Check if the current page or a page in its translation group is 'page_on_front'.
	 *
	 * Note: Prefer using get_option[ 'plugin_multilocale' ][ 'front_page' ].
	 *
	 * @since 1.0.0
	 * @param WP_Post $post          The post in question.
	 * @param bool    $siblings_only Only look at post translations, ignore the current post.
	 * @return bool True if the current page or a page in its translation group is 'page_on_front'.
	 */
	private function page_is_front_page( $post, $siblings_only = false ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return false;
		}

		if ( 'page' === $_post->post_type && 'page' === get_option( 'show_on_front' ) ) {

			$page_on_front = get_option( 'page_on_front' );

			if ( ! $siblings_only && absint( $page_on_front ) === $_post->ID ) {
				return true;
			}

			if ( post_type_supports( $_post->post_type, 'multilocale' ) ) {

				$translations  = multilocale_get_post_translations( $_post, $siblings_only );
				$ids           = wp_list_pluck( $translations, 'ID' );

				if ( in_array( (int) $page_on_front, $ids, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if the current page or a page in its translation group is 'page_for_posts'.
	 *
	 * Note: Prefer using get_option[ 'plugin_multilocale' ][ 'page_for_posts' ].
	 *
	 * @since 1.0.0
	 * @param WP_Post $post          The post in question.
	 * @param bool    $siblings_only Only look at post translations, ignore the current post.
	 * @return bool True if the current page or a page in its translation group is 'page_on_front'.
	 */
	private function page_is_page_for_posts( $post, $siblings_only = false ) {

		$_post = get_post( $post );

		if ( ! $_post ) {
			return false;
		}

		if ( 'page' === $_post->post_type && 'page' === get_option( 'show_on_front' ) ) {

			$page_for_posts = get_option( 'page_for_posts' );

			if ( ! $siblings_only && absint( $page_for_posts ) === $_post->ID ) {
				return true;
			}

			if ( post_type_supports( $_post->post_type, 'multilocale' ) ) {

				$translations  = multilocale_get_post_translations( $_post, $siblings_only );
				$ids           = wp_list_pluck( $translations, 'ID' );

				if ( in_array( (int) $page_for_posts, $ids, true ) ) {
					return true;
				}
			}
		}

		return false;
	}
}

global $multilocale_admin_posts;
$multilocale_admin_posts = Multilocale_Admin_Posts::get_instance();