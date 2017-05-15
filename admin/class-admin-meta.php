<?php
/**
 * Contains post meta related functionality
 *
 * @package   Multilocale
 * @author    Barry Ceelen <b@rryceelen.com>
 * @link      https://github.com/barryceelen/multilocale
 * @copyright 2016 Barry Ceelen
 * @license   GPLv3+
 */

/**
 * Admin related meta functionality.
 *
 * @since 0.0.3
 */
class Multilocale_Admin_Meta {

	/**
	 * Instance of this class.
	 *
	 * @since 0.0.3
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize.
	 *
	 * @since 0.0.3
	 */
	private function __construct() {
		/*
		 * Defines the meta keys we want to propagate between translations.
		 *
		 * Note: Adds appropriate hooks if the array is not empty
		 */
		add_action( 'plugins_loaded', array( $this, 'propagate_post_meta_keys' ) );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since 0.0.3
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
	 * Defines the meta keys we want to propagate between translations and add hooks.
	 *
	 * @since 0.0.3
	 */
	public function propagate_post_meta_keys() {

		/**
		 * Filters the meta keys to propagate between translations.
		 *
		 * @var array $meta_keys A list of meta keys.
		 */
		$this->post_meta_keys = apply_filters( 'multilocale_propagate_post_meta_keys', array() );

		if ( empty( $this->post_meta_keys ) ) {
			return;
		} else {

			// Propagate selected post meta to all members of translation group.
			add_action( 'added_post_meta', array( $this, 'propagate_added_post_meta' ), 10, 4 );
			add_action( 'updated_post_meta', array( $this, 'propagate_updated_post_meta' ), 10, 4 );
			add_action( 'deleted_post_meta', array( $this, 'propagate_deleted_post_meta' ), 10, 4 );
		}
	}

	/**
	 * Propagate added post meta to all members of translation group.
	 *
	 * @todo What is with $unique?
	 *
	 * @since 0.0.3
	 * @param int    $mid         The meta ID after successful update.
	 * @param int    $object_id   Object ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $_meta_value Meta value.
	 */
	public function propagate_added_post_meta( $mid, $object_id, $meta_key, $_meta_value ) {

		if ( ! in_array( $meta_key, $this->post_meta_keys, true ) ) {
			return;
		}

		$_post = get_post( $object_id );

		if ( ! in_array( $_post->post_type, get_post_types_by_support( 'multilocale' ), true ) ) {
			return;
		}

		$translations = multilocale_get_post_translations( $object_id );

		if ( $translations ) {

			remove_action( 'added_post_meta', array( $this, 'propagate_added_post_meta' ), 10 );

			foreach ( $translations as $translation ) {
				add_post_meta( $translation->ID, $meta_key, $_meta_value );
			}

			add_action( 'added_post_meta', array( $this, 'propagate_added_post_meta' ), 10, 4 );
		}
	}

	/**
	 * Propagate updated post meta to all members of translation group.
	 *
	 * @todo What is with $unique?
	 *
	 * @since 0.0.3
	 *
	 * @param int    $meta_id     ID of updated metadata entry.
	 * @param int    $object_id   Object ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $_meta_value Meta value.
	 */
	public function propagate_updated_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {

		if ( ! in_array( $meta_key, $this->post_meta_keys, true ) ) {
			return;
		}

		$_post = get_post( $object_id );

		if ( ! in_array( $_post->post_type, get_post_types_by_support( 'multilocale' ), true ) ) {
			return;
		}

		$translations = multilocale_get_post_translations( $object_id );

		if ( $translations ) {

			remove_action( 'updated_post_meta', array( $this, 'propagate_updated_post_meta' ), 10 );

			foreach ( $translations as $translation ) {
				update_post_meta( $translation->ID, $meta_key, $_meta_value );
			}

			add_action( 'updated_post_meta', array( $this, 'propagate_updated_post_meta' ), 10, 4 );
		}
	}

	/**
	 * Propagate deleted post meta to all members of translation group.
	 *
	 * @since 0.0.3
	 * @param array  $meta_ids    An array of metadata entry IDs to delete.
	 * @param int    $object_id   Object ID.
	 * @param string $meta_key    Meta key.
	 * @param mixed  $_meta_value Meta value.
	 */
	public function propagate_deleted_post_meta( $meta_ids, $object_id, $meta_key, $_meta_value ) {

		if ( ! in_array( $meta_key, $this->post_meta_keys, true ) ) {
			return;
		}

		$_post = get_post( $object_id );

		if ( ! in_array( $_post->post_type, get_post_types_by_support( 'multilocale' ), true ) ) {
			return;
		}

		$translations = multilocale_get_post_translations( $object_id );

		if ( $translations ) {

			remove_action( 'deleted_post_meta', array( $this, 'propagate_deleted_post_meta' ), 10 );

			foreach ( $translations as $translation ) {
				delete_post_meta( $translation->ID, $meta_key, $_meta_value );
			}

			add_action( 'deleted_post_meta', array( $this, 'propagate_deleted_post_meta' ), 10, 4 );
		}
	}
}

global $multilocale_admin_meta;
$multilocale_admin_meta = Multilocale_Admin_Meta::get_instance();
