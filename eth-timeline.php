<?php
/*
Plugin Name: ETH Timeline
Plugin URI: http://www.ethitter.com/plugins/
Description: List travel destinations
Author: Erick Hitter
Version: 0.1
Author URI: http://www.ethitter.com/

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ETH_Timeline {
	/**
	 * Singleton
	 */
	private static $instance = null;

	/**
	 * Class variables
	 */
	private $post_type = 'eth_timeline';
	// private $taxonomy = 'eth_timeline_event';

	/**
	 * Silence is golden!
	 */
	private function __construct() {}

	/**
	 * Instantiate singleton
	 */
	public static function get_instance() {
		if ( ! is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = new self;

			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 *
	 */
	private function setup() {
		add_action( 'init', array( $this, 'action_init' ) );

		add_action( 'post_submitbox_misc_actions', array( $this, 'metabox_end_date' ), 5 );
		add_action( 'save_post', array( $this, 'action_save_post' ) );

		add_filter( 'gettext', array( $this, 'filter_gettext' ) );
		add_filter( 'enter_title_here', array( $this, 'filter_editor_title_prompt' ), 10, 2 );
	}

	/**
	 *
	 */
	public function action_init() {
		register_post_type( $this->post_type, array(
			'label'               => __( 'Timeline', 'eth-timeline' ),
			'labels'              => array(
				'name'               => __( 'Timeline', 'eth-timeline' ),
				'singular_name'      => __( 'Timeline', 'eth-timeline' ),
				'menu_name'          => __( 'Timeline', 'eth-timeline' ),
				'all_items'          => __( 'All Entries', 'eth-timeline' ),
				'add_new'            => __( 'Add New', 'eth-timeline' ),
				'add_new_item'       => __( 'Add New', 'eth-timeline' ),
				'edit_item'          => __( 'Edit Entry', 'eth-timeline' ),
				'new_item'           => __( 'New Entry', 'eth-timeline' ),
				'view_item'          => __( 'View Entry', 'eth-timeline' ),
				'items_archive'      => __( 'Entries List', 'eth-timeline' ),
				'search_items'       => __( 'Search Timeline Entries', 'eth-timeline' ),
				'not_found'          => __( 'No entries found', 'eth-timeline' ),
				'not_found_in_trash' => __( 'No trashed entries', 'eth-timeline' ),
				'parent_item_colon'  => __( 'Entries:', 'eth-timeline' ),
			),
			'public'              => true,
			'has_archive'         => true,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'rewrite'             => array(
				'slug'       => 'timeline',
				'with_front' => false
			),
			'supports'            => array(
				'title',
				'editor',
				'author',
			)
		) );

		// register_taxonomy( $this->taxonomy, $this->post_type, array(
		//	'label'              => __( 'Events', 'eth-timeline' ),
		//	'labels'             => array(
		//		'name'              => __( 'Events', 'eth-timeline' ),
		//		'singular_name'     => __( 'Event', 'eth-timeline' ),
		//		'search_items'      => __( 'Search Events', 'eth-timeline' ),
		//		'all_items'         => __( 'All Events', 'eth-timeline' ),
		//		'parent_item'       => __( 'Parent Event', 'eth-timeline' ),
		//		'parent_item_colon' => __( 'Parent Event:', 'eth-timeline' ),
		//		'edit_item'         => __( 'Edit Event', 'eth-timeline' ),
		//		'update_item'       => __( 'Update Event', 'eth-timeline' ),
		//		'add_new_item'      => __( 'Add New Event', 'eth-timeline' ),
		//		'new_item_name'     => __( 'New Event Name', 'eth-timeline' ),
		//		'menu_name'         => __( 'Events', 'eth-timeline' ),
		//	),
		//	'public'             => true,
		//	'hierarchical'       => true,
		//	'show_in_nav_menus'  => false,
		//	'show_tagcloud'      => false,
		//	'rewrite'            => array(
		//		'slug'              => $this->taxonomy,
		//		'with_front'        => false,
		//		'hierarchical'      => true
		//	)
		// ) );
	}

	/**
	 *
	 */
	public function metabox_end_date() {
		$screen = get_current_screen();

		if ( is_object( $screen ) && ! is_wp_error( $screen ) && $this->post_type == $screen->post_type )
			$post_type_object = get_post_type_object( $screen->post_type );

			// Borrowed from Core: http://core.trac.wordpress.org/browser/trunk/wp-admin/includes/meta-boxes.php?rev=24522#L153
			$datef = __( 'M j, Y @ G:i' );

			// Need to set these based on postmeta
			$stamp = 'End:';
			$date = null;
			// $data = date_i18n( $datef, strtotime( current_time('mysql') ) );

			if ( current_user_can( $post_type_object->cap->publish_posts ) ) : // Contributors don't get to choose the date of publish ?>
			<div class="misc-pub-section endtime">
				<span id="timestamp-end">
				<?php printf($stamp, $date); ?></span>
				<!-- <a href="#edit_timestamp_end" class="edit-timestamp-end hide-if-no-js"><?php _e('Edit') ?></a> -->
				<div id="timestampdiv-end"><?php touch_time( ( $action == 'edit' ), 1, 1, true ); ?></div>
			</div><?php // /misc-pub-section ?>
		<?php endif; ?>
		<?php
	}

	/**
	 *
	 */
	public function action_save_post( $post_id ) {
		if ( $this->post_type != get_post_type( $post_id ) )
			return;

		// Location
		if ( isset( $_POST[ $this->get_nonce_name( 'location' ) ] ) && wp_verify_nonce( $_POST[ $this->get_nonce_name( 'location' ) ], $this->meta_key_location ) ) {
			$location = sanitize_text_field( $_POST[ $this->get_field_name( 'location' ) ] );

			if ( $location )
				update_post_meta( $post_id, $this->meta_key_location, $location );
			else
				delete_post_meta( $post_id, $this->meta_key_location );
		}
	}

	/**
	 * Translate certain admin strings for relevance
	 *
	 * @param string $text
	 * @uses is_admin
	 * @uses get_current_screen
	 * @uses is_wp_error
	 * @filter gettext
	 * @return string
	 */
	public function filter_gettext( $text ) {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();

			if ( is_object( $screen ) && ! is_wp_error( $screen ) && $this->post_type == $screen->post_type ) {
				$text = str_replace( array(
					'Published on',
					'Publish',
				), array(
					'Start',
					'Start'
				), $text );
			}


		}

		return $text;
	}

	/**
	 * Provide better prompt text for the editor title field
	 *
	 * @param string $text
	 * @param object $post
	 * @uses get_post_type
	 * @uses __
	 * @filter enter_title_here
	 * @return string
	 */
	public function filter_editor_title_prompt( $text, $post ) {
		if ( $this->post_type == get_post_type( $post ) )
			$text = __( 'Enter destination here', 'eth-timeline' );

		return $text;
	}

	/**
	 * Return formatted field name
	 *
	 * @param string $field
	 * @return string
	 */
	private function get_field_name( $field ) {
		return $this->post_type . '_' . $field;
	}

	/**
	 * Return formatted nonce name
	 *
	 * @param string $field
	 * @uses this::get_field_name
	 * @return string
	 */
	private function get_nonce_name( $field ) {
		return $this->get_field_name( $field ) . '_nonce';
	}
}

ETH_Timeline::get_instance();