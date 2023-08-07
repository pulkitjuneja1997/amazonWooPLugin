<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';

if ( file_exists( $file ) ) {
	require_once $file;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class Ced_Amazon_List_Feeds extends WP_List_Table {


	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Amazon feed', 'amazon-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'Amazon feeds', 'amazon-integration-for-woocommerce' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);
	}
	/**
	 *
	 * Function for preparing data to be displayed
	 */
	public function prepare_items() {

		/**
		 * Function to list order based on per page
		 * 
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page = apply_filters( 'ced_amazon_feeds_list_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::ced_amazon_feeds( $per_page, $current_page );
		$count       = self::get_count();
		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::ced_amazon_feeds( $per_page, $current_page );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}

	/**
	 *
	* Function for getting current status
	*/
	public function current_action() {
		if ( isset( $_GET['panel'] ) ) {
			$action = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			if ( ! isset( $_POST['amazon_feed_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_feed_view_actions'] ) ), 'amazon_feed_view' ) ) {
				return;
			}
			$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
			return $action;
		}
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count() {

		global $wpdb;
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		if ( !empty($seller_id) ) {
			$mplocation_arr = explode('|', $seller_id);
			$mplocation     = isset($mplocation_arr[0]) ? $mplocation_arr[0] : '';
			$allFeeds       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_location` = %s", $mplocation ), 'ARRAY_A' );
		} else {
			$allFeeds = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds", 'ARRAY_A' );
		}
		return count( $allFeeds );
	}


	/**
	 *
	 * Render bulk actions
	 */

	protected function bulk_actions( $which = '' ) {
		if ( 'top' == $which ) :
			if ( is_null( $this->_actions ) ) {
				$this->_actions = $this->get_bulk_actions();
				/**
				 * Filters the list table Bulk Actions drop-down.
				 *
				 * The dynamic portion of the hook name, `$this->screen->id`, refers
				 * to the ID of the current screen, usually a string.
				 *
				 * This filter can currently only be used to remove bulk actions.
				 *
				 * @since 3.5.0
				 *
				 * @param array $actions An array of the available bulk actions.
				 */
				$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
				$two            = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_attr( 'Select bulk action' ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-action-selector ">';
			echo '<option value="-1">' . esc_attr( 'Bulk Actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_amazon_feed_bulk_operation' ) );
			echo "\n";
		endif;
	}

	/*
	 *
	 * Text displayed when no  data is available
	 *
	 */
	public function no_items() {
		esc_html_e( 'No feeds To Display.', 'amazon-integration-for-woocommerce' );
	}

	/*
	 * Render the bulk edit checkbox
	 *
	 */
	public function column_cb( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return sprintf(
			'<input type="checkbox" name="amazon_feed_ids[]" class="amazon_feeds_ids" value="%s" /></div></div>',
			$item['id']
		);
	}

	/**
	 *
	 * Function for id column
	 */
	public function column_id( $item ) {

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		echo '<b>' . esc_attr( $item['feed_id'] ) . '</b>';
		$actions['view'] = '<a class="feed-view" href="' . get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=feed-view&woo-feed-id=' . __( $item['id'] ) . '&feed-id=' . __( $item['feed_id'] ) . '&feed-type=' . __( $item['feed_action'] ) . '&user_id=' . $user_id . '&seller_id=' . $seller_id . '" > ' . __( 'View', 'amazon-integration-for-woocommerce' ) . '</a>';

		return $this->row_actions( $actions );

	}


	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'amazon-integration-for-woocommerce' ),
		);
		return $actions;
	}

	/**
	 *
	 * Function for feed date column
	 */
	public function column_date( $item ) {
		echo '<b>' . esc_attr( $item['feed_date_time'] ) . '</b>';
	}


	/**
	 *
	 * Function display amazon feed action column
	 */
	public function column_feedFor( $item ) {

			echo '<b>' . esc_attr( $item['feed_action'] ) . '</b>';

	}

	/**
	 *
	 * Function for feed location column
	 */
	public function column_location( $item ) {

		echo '<b>' . esc_attr( strtoupper( $item['feed_location'] ) ) . '</b>';

	}


	/**
	 *
	 * Function for feed response column
	 */
	public function column_feedResponse( $item ) {

		$html  = '<div class="admin-custom-action-button-outer feed-response-main">';
		$html .= '<div class="admin-custom-action-show-button-outer">';
		$html .=  '<a href="javascript:void(0)" type="button" style="background:#5850ec !important;" class="button btn-normal-tt feed-response" data-attr="' . $item['feed_id'] . '"><span>Quick Response</span></a>';
		$html .=  '</div></div>';

		print_r($html);

	}


	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */

	public function get_columns() {
		 $columns = array(
			 'cb'           => '<input type="checkbox" />',
			 'id'           => __( 'Feed Id', 'amazon-integration-for-woocommerce' ),
			 'date'         => __( 'Date & Time', 'amazon-integration-for-woocommerce' ),
			 'feedFor'      => __( 'Feed For', 'amazon-integration-for-woocommerce' ),
			 'location'     => __( 'Location', 'amazon-integration-for-woocommerce' ),
			 'feedResponse' => __( 'Feed Response', 'amazon-integration-for-woocommerce' ),

		 );

		 /**
		 * Function to list order based on per page

		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since 1.0.0
		 */
		 $columns = apply_filters( 'ced_amazon_feeds_columns', $columns );
		 return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 * Function to renderHTML
	 */


	public function renderHTML() {
		$user_id        = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$feed_error_log = get_option( 'ced_amazon_feed_fetch_log_' . $user_id );
		?>
		<div class="ced-amazon-v2-header">
			<div class="ced-amazon-v2-logo">
				
			</div>

			<div class="ced-amazon-v2-header-content">
				<div class="ced-amazon-v2-title"> <h1>Amazon feeds</h1> </div>

				<div class="admin-custom-action-button-outer">
					<div class="admin-custom-action-show-button-outer"> </div>
				
					<div class="admin-custom-action-show-button-outer">
						<button style="background:#5850ec !important;"  type="button" class="button btn-normal-tt">
							<span><a style="all:unset;" href="https://docs.cedcommerce.com/woocommerce/amazon-integration-woocommerce/?section=delete-the-profiles-2" target="_blank">
							Documentation</a></span>
						</button>
					</div>

				</div>
			</div>
		</div>	

		<!-- Render feed response via ajax in modal -->
		<div class="ced-amazon-bootstrap-wrapper">
			<div class="container" >
				<div class="modal fade in" id="feedViewModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
					<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
						<div class="modal-content" style="max-width: 1000px; height: 400px; margin: auto;">
							<div class="modal-header">
								<h5 class="modal-title" id="exampleModalLabel">Feed response</h5>
								<button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">×</span>
								</button>
								
							</div>
							<div class="modal-body">
								<div class="feed-response-modal"></div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Close</button>
							</div>
						</div>
					</div>
				</div> 
			</div>
		</div>	

		<?php
		if ( ! empty( $feed_error_log ) ) { 
			?>
				<section class="woocommerce-inbox-message plain">
					<div class="woocommerce-inbox-message__wrapper">
						<div class="woocommerce-inbox-message__content">
							<span class="woocommerce-inbox-message__date"><?php echo esc_html( ced_amazon_time_elapsed_string( $feed_error_log['timestamp'] ) ); ?></span>
							<h3 class="woocommerce-inbox-message__title">Whoops! It looks like there were some errors in fetching your Amazon feeds.</h3>
							<div class="woocommerce-inbox-message__text">
							<?php
							foreach ( $feed_error_log as $key => $fetch_error ) {
								if ( is_numeric( $key ) ) {
									?>
											<b><span><?php echo esc_html( $fetch_error ); ?></span></b><br>
										<?php
								}
							}
							?>
							</div>
						</div>
					</div>
				</section>
		<?php

		}
		?>
		
		<div id="post-body" class="metabox-holder columns-2">
			<div id="">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
					   <?php
						wp_nonce_field( 'amazon_feed_view', 'amazon_feed_view_actions' );
						$this->display();
						?>
					</form>
				</div>
			</div>
			<div class="clear"></div>
		</div>
		
		<?php
	}
	/*
	 *
	 *  Function to get all the feeds
	 *
	 */
	public function ced_amazon_feeds( $per_page = 1, $page_number = 10 ) {

		global $wpdb;
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		if ( !empty($seller_id) ) {
			$mplocation_arr = explode('|', $seller_id);
			$mplocation     = isset($mplocation_arr[0]) ? $mplocation_arr[0] : '';
			$offset         = ( $page_number - 1 ) * $per_page;
			$requiredFeeds  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_location` = %s ORDER BY `id` DESC LIMIT %d OFFSET %d", $mplocation, $per_page, $offset ), 'ARRAY_A' );
		} else {
			$offset        = ( $page_number - 1 ) * $per_page;
			$requiredFeeds = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds ORDER BY `id` DESC LIMIT %d OFFSET %d", $per_page, $offset ), 'ARRAY_A' );
		}

		return ( $requiredFeeds );
	}

	 /**
	  *
	  * Function for processing bulk actions
	  */
	public function process_bulk_action() {
		$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		if ( ! session_id() ) {
			session_start();
		}
		wp_nonce_field( 'ced_amazon_feed_view_page_nonce', 'ced_amazon_feed_view_nonce' );
		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) ) {
			if ( ! isset( $_POST['amazon_feed_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_feed_view_actions'] ) ), 'amazon_feed_view' ) ) {
				return;
			}
			$feedIds = isset( $sanitized_array['amazon_feed_ids'] ) ? $sanitized_array['amazon_feed_ids'] : array();
			
			if ( is_array( $feedIds ) && ! empty( $feedIds ) ) {

				global $wpdb;

				$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
				$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( wp_unslash( $_GET['seller_id'] ) ) : '';
				foreach ( $feedIds as $id ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_amazon_feeds WHERE `id` IN (%s)", $id ) );
				}

				header( 'Location: ' . get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=feeds-view&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) );
				exit();
			}
		} elseif ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {

			$file = CED_AMAZON_DIRPATH . 'admin/partials/profile-edit-view.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

}

$ced_amazon_feeds_obj = new Ced_Amazon_List_Feeds();
$ced_amazon_feeds_obj->prepare_items();

?>


<style>
	 .ced-amazon-v2-admin-menu{
	   margin-right: 20px !important;
   }
   
   .ced-amazon-v2-header{
		   margin: 10px 20px 20px 2px !important;
   }
</style>
