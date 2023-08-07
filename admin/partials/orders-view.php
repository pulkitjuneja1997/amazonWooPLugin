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


class Ced_Amazon_List_Orders extends WP_List_Table {


	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Amazon Order', 'amazon-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'Amazon Orders', 'amazon-integration-for-woocommerce' ), // plural name of the listed records
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
		 *  Function to list order based on per page
		 * 
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page = apply_filters( 'ced_amazon_orders_list_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::ced_amazon_orders( $per_page, $current_page );
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
			$this->items = self::ced_amazon_orders( $per_page, $current_page );
			$this->renderHTML();
		}
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count() {
		global $wpdb;
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] )
			: '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		//$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s  group by `post_id` ", '_is_amazon_order', 1 ), 'ARRAY_A' );
		//$orders_post_id = array();
		$mplocation_arr = explode('|', $seller_id);
		$mplocation     = isset($mplocation_arr[1]) ? $mplocation_arr[0] : '';
		if ( !empty($mplocation) ) {
			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s group by `post_id` ", 'ced_amazon_order_countory_code', $mplocation ), 'ARRAY_A' );
		} else {
			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s  group by `post_id` ", '_is_amazon_order', 1 ), 'ARRAY_A' );
		}
		return count( $orders_post_id );
	}

	/*
	 *
	 * Text displayed when no  data is available
	 *
	 */
	public function no_items() {
		esc_html_e( 'No Orders To Display.', 'amazon-integration-for-woocommerce' );
	}
	/**
	 *
	 * Function for id column
	 */
	public function column_id( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			echo '<b>' . esc_attr( $displayOrders['order_id'] )
				. '</b>';
			$actions['order-detail'] = '<span class="amazon-order-detail" data-order-id="' . __( $displayOrders['order_id'] ) . '" > ' . __( 'View order detail', 'amazon-integration-for-woocommerce' ) . '</span>';
			break;
		}
		return $this->row_actions( $actions );
	}
	/**
	 *
	 * Function for name column
	 */
	public function column_name( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$productId     = $displayOrders['product_id'];
			$url           = get_edit_post_link( $productId, '' );
			echo '<b><a class="ced_amazon_prod_name" href="' . esc_attr( $url )
				. '" target="#">' . esc_attr( $displayOrders['name'] )
				. '</a></b><span><hr></span></br>';
		}
	}
	/**
	 *
	 * Function for order Id column
	 */
	public function column_amazon_order_id( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders   = $value->get_data();
			$orderID         = $displayOrders['order_id'];
			$details         = wc_get_order( $orderID );
			$details         = $details->get_data();
			$order_meta_data = $details['meta_data'];
			foreach ( $order_meta_data as $key1 => $value1 ) {
				$order_id = $value1->get_data();
				if ( 'amazon_order_id' == $order_id['key'] ) {
					echo '<b>' . esc_attr( $order_id['value'] )
						. '</b>';
				}
			}
			break;
		}
	}
	/**
	 *
	 * Function for order status column
	 */
	public function column_order_status( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders   = $value->get_data();
			$orderID         = $displayOrders['order_id'];
			$details         = wc_get_order( $orderID );
			$details         = $details->get_data();
			$order_meta_data = $details['meta_data'];
			foreach ( $order_meta_data as $key1 => $value1 ) {
				$order_status = $value1->get_data();
				if ( '_amazon_umb_order_status' == $order_status['key'] ) {
					echo '<b>' . esc_attr( $order_status['value'] )
						. '</b>';
				}
			}
			break;
		}
	}
	/**
	 *
	 * Function display amazon user id column
	 */
	public function column_amazon_user_id( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$amazonUserId  = get_post_meta( $orderID, 'amazonBuyerUserId', true );
			echo '<b>' . esc_attr( $amazonUserId ) . '</b>';
			break;
		}
	}
	/**
	 *
	 * Function for Edit order column
	 */
	public function column_action( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$woo_order_url = get_edit_post_link( $displayOrders['order_id'], '' );
			echo '<a href="' . esc_attr( $woo_order_url )
				. '" target="#">' . esc_attr( 'Edit', 'amazon-integration-for-woocommerce' ) . '</a>';
			break;
		}
	}
	/**
	 *
	 * Function for customer name column
	 */
	public function column_customer_name( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders = $value->get_data();
			$orderID       = $displayOrders['order_id'];
			$details       = wc_get_order( $orderID );
			$details       = $details->get_data();
			echo '<b>' . esc_attr( $details['billing']['first_name'] )
				. ' ' . esc_attr( $details['billing']['last_name'] ) . '</b>';
			break;
		}
	}

	/**
	 *
	 * Function display amazon fulfillment channel column
	 */
	public function column_fulfillment_channel( $items ) {
		foreach ( $items as $key => $value ) {
			$displayOrders      = $value->get_data();
			$orderID            = $displayOrders['order_id'];
			$fulfillmentChannel = get_post_meta( $orderID, 'ced_umb_amazon_fulfillment_channel', true );
			echo '<b>' . esc_attr( $fulfillmentChannel ) . '</b>';
			break;
		}
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */

	public function get_columns() {
		 $columns = array(
			 'id'              => __( 'WooCommerce Order', 'amazon-integration-for-woocommerce' ),
			 'name'            => __( 'Product Name', 'amazon-integration-for-woocommerce' ),
			 'amazon_order_id' => __( 'Amazon Order ID', 'amazon-integration-for-woocommerce' ),
			 'customer_name'   => __( 'Customer Name', 'amazon-integration-for-woocommerce' ),
			 'order_status'    => __( 'Order Status', 'amazon-integration-for-woocommerce' ),
			 'action'          => __( 'Action', 'amazon-integration-for-woocommerce' ),
			 //'amazon_user_id'  => __( 'Amazon User ID', 'amazon-integration-for-woocommerce' ),
			 'fulfillment_channel'  => __( 'Fulfillment Channel', 'amazon-integration-for-woocommerce' ),

		 );

		 /**
		 *  Function to list order based on per page
		 * 
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		 $columns = apply_filters( 'ced_amazon_orders_columns', $columns );
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
	 *
	 * Function to renderHTML
	 */
	public function renderHTML() {
		$user_id         = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$order_error_log = get_option( 'ced_amazon_order_fetch_log_' . $user_id );
		?>
		<div class="ced-amazon-v2-header">
			<div class="ced-amazon-v2-logo">
				
			</div>
			<div class="ced-amazon-v2-header-content">
				<div class="ced-amazon-v2-title">
					<h1>Amazon Orders</h1>
				</div>
				<div class="admin-custom-action-button-outer button-wrap">
				<div class="admin-custom-action-show-button-outer">
	
				</div>
				<div class="admin-custom-action-show-button-outer">
				<button id="ced_amazon_fetch_orders"  data-id="<?php echo esc_attr( isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '' ); ?>" style="margin-left:5px;"  title="Fetch Orders" type="button" class="button btn-normal-sbc">
				<span>Fetch Orders</span>
				</button>
								</div>

				

				</div>
			</div>
		</div>

		<!-- Render amazon order detail via ajax in modal -->
		<div class="ced-amazon-bootstrap-wrapper">
			<div class="modal fade in" id="orderDetailModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
					<div class="modal-content" >
						<div class="modal-header">
							<h5 class="modal-title" id="exampleModalLabel">Amazon Order Details</h5>
							<button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<div class="amazon-order-modal"></div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Close</button>
						</div>
					</div>
				</div>
			</div> 
		</div>
		
		<?php
		if ( ! empty( $order_error_log ) ) {
			?>
<section class="woocommerce-inbox-message plain">
			<div class="woocommerce-inbox-message__wrapper">
				<div class="woocommerce-inbox-message__content">
					<span class="woocommerce-inbox-message__date"><?php echo esc_html( ced_amazon_time_elapsed_string( $order_error_log['timestamp'] ) ); ?></span>
					<h3 class="woocommerce-inbox-message__title">Whoops! It looks like there were some errors in fetching your Amazon Orders.</h3>
					<div class="woocommerce-inbox-message__text">
					<?php
					foreach ( $order_error_log as $key => $fetch_error ) {
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
		

</div>
		<div id="post-body" class="metabox-holder columns-2">
			<div id="">
				<div class="meta-box-sortables ui-sortable">
					
					<?php
					  $this->display();
					?>
					
				</div>
			</div>
			<div class="clear"></div>
		</div>
		
		<?php
	}
	/*
	 *
	 *  Function to get all the orders
	 *
	 */
	public function ced_amazon_orders( $per_page = 10, $page_number = 1 ) {

		$filterFile = CED_AMAZON_DIRPATH . 'admin/partials/order-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}

		global $wpdb;
		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$offset    = ( $page_number - 1 ) * $per_page;
		//$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE `meta_key`=%s  group by `post_id` DESC LIMIT %d OFFSET %d", 'amazon_order_id', $per_page, $offset ), 'ARRAY_A' );
		$mplocation_arr = explode('|', $seller_id);
		$mplocation     = isset($mplocation_arr[1]) ? $mplocation_arr[0] : '';
		if ( !empty($mplocation) ) {
			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s group by `post_id` DESC LIMIT %d OFFSET %d", 'ced_amazon_order_countory_code', $mplocation, $per_page, $offset ), 'ARRAY_A' );
		} else {
			$orders_post_id = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE `meta_key`=%s AND `meta_value`=%s  group by `post_id` DESC LIMIT %d OFFSET %d", '_is_amazon_order', 1, $per_page, $offset ), 'ARRAY_A' );
		}
		foreach ( $orders_post_id as $key => $value ) {
			$post_id        = isset( $value['post_id'] ) ? $value['post_id'] : '';
			$post_details   = wc_get_order( $post_id );
			$order_detail[] = $post_details->get_items();
		}
		$order_detail = isset( $order_detail ) ? $order_detail : '';

		$filterFile = CED_AMAZON_DIRPATH . 'admin/partials/order-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}

		// $instanceOf_FilterClass = new FilterClass();

		// if ( isset( $_POST['ced_amazon_order_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_order_filter_nonce'] ), 'ced_amazon_order_filter_page_nonce' ) ) {
		// 	if ( isset( $_POST['filter_button'] ) ) {
		// 		$order_detail = $instanceOf_FilterClass->ced_amazon_filters_on_orders();
		// 	}
		// }

		return ( $order_detail );
	}
}

?>

<div class="ced-amazon-bootstrap-wrapper">
	<?php
		$ced_amazon_orders_obj = new Ced_Amazon_List_Orders();
		$ced_amazon_orders_obj->prepare_items();
	?>
</div>



<style>
	 .ced-amazon-v2-admin-menu{
	   margin-right: 20px !important;
   }
   
   .ced-amazon-v2-header{
		   margin: 10px 20px 20px 2px !important;
   }
</style>
