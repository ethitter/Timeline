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
	 * Register actions and filters
	 *
	 * @uses add_action
	 * @uses add_filter
	 * @return null
	 */
	private function setup() {
		add_action( 'init', array( $this, 'action_init' ) );

		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts' ) );
		add_action( 'add_meta_boxes_' . $this->post_type, array( $this, 'action_add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'action_save_post' ) );

		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'filter_list_table_columns' ) );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'do_list_table_columns' ), 10, 2 );

		add_filter( 'enter_title_here', array( $this, 'filter_editor_title_prompt' ), 10, 2 );
	}

	/**
	 * Register post type and shortcode
	 *
	 * @uses register_post_type
	 * @uses add_shortcode
	 * @action init
	 * @return null
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
			'has_archive'         => false,
			'exclude_from_search' => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => true,
			'rewrite'             => false,
			'supports'            => array(
				'title',
				'editor',
				'author',
			)
		) );

		add_shortcode( 'eth-timeline', array( $this, 'do_shortcode' ) );
	}

	/**
	 * Force all timeline queries to be sorted by start date.
	 * Doesn't interfere with admin list table sorting.
	 *
	 * @param object $query
	 * @uses is_admin
	 * @action pre_get_posts
	 * @return null
	 */
	public function action_pre_get_posts( $query ) {
		if ( $query->is_main_query() && $this->post_type == $query->get( 'post_type' ) ) {
			if ( is_admin() && isset( $_GET['orderby'] ) )
				return;

			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', $this->meta_start );
		}
	}

	/**
	 ** ADMINISTRATION
	 */

	/**
	 * Enqueue admin assets
	 *
	 * @uses get_current_screen
	 * @uses is_wp_error
	 * @uses wp_enqueue_script
	 * @uses plugins_url
	 * @uses wp_enqueue_style
	 * @action admin_enqueue_scripts
	 * @return null
	 */
	public function action_admin_enqueue_scripts() {
		$screen = get_current_screen();

		if ( is_object( $screen ) && ! is_wp_error( $screen ) && $this->post_type = $screen->post_type ) {
			wp_enqueue_script( 'eth-timeline-admin', plugins_url( 'js/admin.js', __FILE__ ), array( 'jquery', 'jquery-ui-datepicker' ), 20130721, false );

			wp_enqueue_style( 'eth-timeline-admin', plugins_url( 'css/smoothness.min.css', __FILE__ ), array(), 20130721, 'screen' );
		}
	}

	/**
	 * Register custom date metabox
	 *
	 * @uses add_meta_box
	 * @action add_meta_boxes
	 * @return null
	 */
	public function action_add_meta_boxes() {
		add_meta_box( 'eth-timeline-dates', __( 'Dates', 'eth-timeline' ), array( $this, 'meta_box_dates' ), $this->post_type, 'normal', 'high' );
	}

	/**
	 * Render dates metabox
	 *
	 * @param object $post
	 * @uses get_post_meta
	 * @uses _e
	 * @uses wp_nonce_field
	 * @uses ths::get_field_name
	 * @uses this::get_nonce_name
	 * @action add_meta_boxes_{$this->post_type}
	 * @return string
	 */
	public function meta_box_dates( $post ) {
		$times = $this->get_times( $post->ID );

		?>
		<p id="eth-timeline-startbox">
			<label for="eth-timeline-start"><?php _e( 'Start:', 'eth-timeline' ); ?></label>
			<input type="text" name="eth-timeline[start]" id="eth-timeline-start" class="regular-text" style="width: 11em;" value="<?php echo date( 'F j, Y', $times['start'] ); ?>" />
		</p>

		<p id="eth-timeline-endbox">
			<label for="eth-timeline-end"><?php _e( 'End:', 'eth-timeline' ); ?></label>
			<input type="text" name="eth-timeline[end]" id="eth-timeline-end" class="regular-text" style="width: 11em;" value="<?php echo date( 'F j, Y', $times['end'] ); ?>" />
		</p>
		<?php

		wp_nonce_field( $this->get_field_name( 'date' ), $this->get_nonce_name( 'date' ), false );
	}

	/**
	 * Save custom dates
	 *
	 * @param int $post_id
	 * @uses get_post_type
	 * @uses this::get_nonce_name
	 * @uses this::get_field_name
	 * @uses update_post_meta
	 * @uses delete_post_meta
	 * @action save_post
	 * @return null
	 */
	public function action_save_post( $post_id ) {
		if ( $this->post_type != get_post_type( $post_id ) )
			return;

		if ( isset( $_POST[ $this->get_nonce_name( 'date' ) ] ) && wp_verify_nonce( $_POST[ $this->get_nonce_name( 'date' ) ], $this->get_field_name( 'date' ) ) ) {
			$dates = isset( $_POST['eth-timeline'] ) ? $_POST['eth-timeline'] : array();

			if ( empty( $dates ) )
				return;

			foreach ( $dates as $key => $date ) {
				if ( ! in_array( $key, array( 'start', 'end' ) ) )
					continue;

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
	 * Add new date columns to list table
	 *
	 * @param array $columns
	 * @uses __
	 * @filter manage_{$this->post_type}_posts_columns
	 * @return array
	 */
	public function filter_list_table_columns( $columns ) {
		$after = array_splice( $columns, 2 );

		$new_columns = array(
			'eth_timeline_start' => __( 'Start Date', 'eth-timeline' ),
			'eth_timeline_end'   => __( 'End Date (Optional)', 'eth-timeline' ),
		);

		$columns = $columns + $new_columns + $after;

		return $columns;
	}

	/**
	 * Display start and end dates in the post list table
	 *
	 * @param string $column
	 * @param int $post_id
	 * @uses get_post_meta
	 * @uses get_option
	 * @action manage_{$this->post_type}_posts_custom_column
	 * @return string or null
	 */
	public function do_list_table_columns( $column, $post_id ) {
		if ( in_array( $column, array( 'eth_timeline_start', 'eth_timeline_end' ) ) ) {
			$key = str_replace( 'eth_timeline_', '', $column );
			$date = get_post_meta( $post_id, $this->{'meta_' . $key}, true );

			if ( is_numeric( $date ) )
				echo date( get_option( 'date_format', 'F j, Y' ), $date );
		}
	}

	/**
	 ** PRESENTATION
	 */

	/**
	 * Render list of timeline entries
	 *
	 * @global $post
	 * @param mixed $atts
	 * @uses shortcode_atts
	 * @uses WP_Query
	 * @uses this::get_times
	 * @uses the_ID
	 * @uses this::format_date
	 * @uses the_title
	 * @uses get_the_content
	 * @uses remove_filter
	 * @uses the_content
	 * @uses add_filter
	 * @uses wp_reset_query
	 * @return string or null
	 */
	public function do_shortcode( $atts ) {
		// Parse and sanitize atts
		$atts = shortcode_atts( array(
			'posts_per_page' => 100,
			'order'          => 'DESC',
			'year'           => null,
		), $atts );

		$atts['posts_per_page'] = min( 200, max( (int) $atts['posts_per_page'], 1 ) );
		$atts['order']          = 'ASC' == $atts['order'] ? 'ASC' : 'DESC';
		$atts['year']           = is_numeric( $atts['year'] ) ? (int) $atts['year'] : null;

		// Build query
		$query = array(
			'post_type'      => $this->post_type,
			'posts_per_page' => $atts['posts_per_page'],
			'post_status'    => 'publish',
			'order'          => $atts['order'],
			'orderby'        => 'meta_value_num',
			'meta_key'       => $this->meta_start
		);

		if ( $atts['year'] ) {
			$query['meta_query'] = array(
				array(
					'key'     => $this->meta_start,
					'value'   => array( strtotime( $atts['year'] . '-01-01 00:00:00' ), strtotime( $atts['year'] . '-12-31 23:59:59' ) ),
					'type'    => 'numeric',
					'compare' => 'BETWEEN'
				)
			);
		}

		// Run query and build output, if possible
		$query = new WP_Query( $query );

		if ( $query->have_posts() ) {
			ob_start();

			global $post;

			echo '<div class="eth-timeline">';

			$year = $month = null;

			while ( $query->have_posts() ) {
				$query->the_post();

				$times = $this->get_times( $post->ID );

				// Deal with grouping by year
				if ( $year != date( 'Y', $times['start'] ) ) {
					if ( null !== $year ) {
						echo '</ul><!-- ' . $year . '-' . $month . ' --></ul><!-- ' . $year . ' -->' . "\n";
						$month = null;
					}

					$year = (int) date( 'Y', $times['start'] );

					echo '<div class="eth-timline-year-label">' . $year . '</div>' . "\n";
					echo '<ul class="eth-timeline-year eth-timeline-' . $year . '">' . "\n";
				}

				// Deal with grouping by month
				if ( $month != date( 'n', $times['start'] ) ) {
					if ( null !== $month )
						echo '</ul><!-- ' . $year . '-' . $month . ' --></li>' . "\n";

					$month = (int) date( 'n', $times['start'] );

					echo '<li class="eth-timeline-month eth-timeline-month-' . $month . '">';
					echo '<div class="eth-timeline-month-label">' . date( 'F', $times['start'] ) . '</div>' . "\n";
					echo '<ul class="eth-timeline-month-items eth-timeline-' . $year . '-' . $month . '">' . "\n";
				}

				// Info about the item
				?>
				<li class="eth-timeline-item" id="eth-timeline-<?php the_ID(); ?>">
					<span class="eth-timeline-date"><?php echo $this->format_date( $times['start'], $year, $month ); ?>&ndash;<?php echo $this->format_date( $times['end'], $year, $month, false ); ?>:</span>
					<span class="eth-timeline-location"><?php the_title(); ?></span>

					<?php
						$content = get_the_content();

						if ( ! empty( $content ) ) {
							$removed = remove_filter( 'the_content', 'wpautop' );

							echo ' <span class="eth-timeline-sep">&mdash;</span> <span class="eth-timeline-body">';
							the_content();
							echo '</span>';

							if ( $removed )
								add_filter( 'the_content', 'wpautop' );
						}
					?>
				</li><!-- .eth-timeline-item#eth-timeline-<?php the_ID(); ?> -->
				<?php
			}

			// Ensure our tags are balanced!
			echo '</ul><!-- ' . $year . '-' . $month . ' -->';
			echo '</ul><!-- ' . $year . ' -->';

			echo '</div><!-- .eth-timeline -->';

			wp_reset_query();
			return ob_get_clean();
		}
	}

	/**
	 ** HELPERS
	 */

	/**
	 * Retrieve timestamps for a given entry
	 *
	 * @param int $post_id
	 * @uses get_post_meta
	 * @return array
	 */
	private function get_times( $post_id ) {
		$post_id = (int) $post_id;

		$start = get_post_meta( $post_id, $this->meta_start, true );
		$start = is_numeric( $start ) ? (int) $start : '';

		$end = get_post_meta( $post_id, $this->meta_end, true );
		$end = is_numeric( $end ) ? (int) $end : '';

		return compact( 'start', 'end' );
	}

	/**
	 * Determine appropriate date format for display start and end dates together.
	 * Prevents duplication of month or year.
	 *
	 * @param int $timestamp
	 * @param int $loop_year
	 * @param int $loop_month
	 * @param bool $start
	 * @return string
	 */
	private function format_date( $timestamp, $loop_year, $loop_month, $start = true ) {
		$ts_year = date( 'Y', $timestamp );
		$ts_month = date( 'n', $timestamp );

		$format = 'j';

		if ( $loop_year != $ts_year )
			$format .= ', Y';

		if ( $start || $loop_month != $ts_month )
			$format = 'F ' . $format;

		return date( $format, $timestamp );
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