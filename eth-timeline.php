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

	private $meta_start = '_eth_timeline_start';
	private $meta_end = '_eth_timeline_end';

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

		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
		add_action( 'add_meta_boxes_' . $this->post_type, array( $this, 'action_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );

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
	public function action_admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( is_object( $screen ) && ! is_wp_error( $screen ) && $this->post_type = $screen->post_type ) {
			wp_enqueue_script( 'eth-timeline-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-datepicker' ), time(), false );

			wp_enqueue_style( 'eth-timeline-admin', plugins_url( 'css/smoothness.min.css', __FILE__ ), array(), 20130721, 'screen' );
		}
	}

	/**
	 *
	 */
	public function action_add_meta_boxes() {
		add_meta_box( 'eth-timeline-dates', __( 'Dates', 'eth-timeline' ), array( $this, 'meta_box_dates' ), $this->post_type, 'normal', 'high' );
	}

	/**
	 *
	 */
	public function meta_box_dates( $post ) {
		$start = get_post_meta( $post->ID, $this->meta_start, true );
		$start = is_numeric( $start ) ? (int) $start : '';

		$end = get_post_meta( $post->ID, $this->meta_end, true );
		$end = is_numeric( $end ) ? (int) $end : '';

		?>
		<p id="eth-timeline-startbox">
			<label for="eth-timeline-start"><?php _e( 'Start:', 'eth-timeline' ); ?></label>
			<input type="text" name="eth-timeline[start]" id="eth-timeline-start" class="regular-text" style="width: 11em;" value="<?php echo date( 'F j, Y', $start ); ?>" />
		</p>

		<p id="eth-timeline-endbox">
			<label for="eth-timeline-end"><?php _e( 'End:', 'eth-timeline' ); ?></label>
			<input type="text" name="eth-timeline[end]" id="eth-timeline-end" class="regular-text" style="width: 11em;" value="<?php echo date( 'F j, Y', $end ); ?>" />
		</p>
		<?php

		wp_nonce_field( $this->get_field_name( 'date' ), $this->get_nonce_name( 'date' ), false );
	}

	/**
	 *
	 */
	public function action_save_post( $post_id ) {
		if ( $this->post_type != get_post_type( $post_id ) )
			return;

		if ( isset( $_POST[ $this->get_nonce_name( 'date' ) ] ) && wp_verify_nonce( $_POST[ $this->get_nonce_name( 'date' ) ], $this->get_field_name( 'date' ) ) ) {
			$dates = isset( $_POST['eth-timeline'] ) ? $_POST['eth-timeline'] : array();

			foreach ( $dates as $key => $date ) {
				// Timestamp comes from JS
				if ( empty( $date ) )
					$timestamp = false;
				else
					$timestamp = strtotime( $date );

				if ( $timestamp )
					update_post_meta( $post_id, $this->{'meta_' . $key}, $timestamp );
				else
					delete_post_meta( $post_id, $this->{'meta_' . $key} );
			}
		}
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