<?php

// die if called directly
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

class Ced_Amazon_Status_Feed extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Bulk Exchange Job', 'amazon-integration-for-woocommerce' ),
				'plural'   => __( 'Bulk Exchange Jobs', 'amazon-integration-for-woocommerce' ),
				'ajax'     => false,

			)
		);

	}

	public function get_columns() {
		$columns = array(
			'cb'             => '<input type="checkbox" />',
			'product_name'   => __( 'Product Name', 'amazon-integration-for-woocommerce' ),
			'product_type'   => __( 'Product Type', 'amazon-integration-for-woocommerce' ),
			'product_status' => __( 'Product Status', 'amazon-integration-for-woocommerce' ),
			'scheduled_time' => __( 'Scheduled', 'amazon-integration-for-woocommerce' ),
		);

		return $columns;
	}


	public function ced_amazon_get_bulk_upload_job_data( $per_page = 10, $page_number = 1 ) {

		global $wpdb;
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$offset  = ( $page_number - 1 ) * $per_page;
		$result  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_bulk_upload WHERE `user_id` = %s ORDER BY `scheduled_time` DESC LIMIT %d OFFSET %d", $user_id, $per_page, $offset ), 'ARRAY_A' );

		return $result;
	}


	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$sql     = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}ced_amazon_bulk_upload WHERE `user_id`=%s", $user_id ) );

		return $sql;
	}


	/** Text displayed when no customer data is available */
	public function no_items() {
		esc_attr_e( 'No Product upload found.', 'amazon-integration-for-woocommerce' );
	}


	/**
	 * Render the bulk action checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-exchange-job-action[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Render a column when no column specific method exists.
	 *
	 * @param array  $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'product_type':
			case 'product_name':
			case 'product_status':
			case 'scheduled_time':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	public function column_scheduled_time( $item ) {
		echo '<div class="admin-custom-action-button-outer" style="margin-top:0px;"><div class="admin-custom-action-show-button-outer">';
		$time = $item['scheduled_time'];
		$time = ced_amazon_time_elapsed_string( $time );
		echo '<button type="button" class="button btn-normal-sbc"><span>' . esc_attr( $time ) . '</span></button></div></div>';
	}

	public function column_product_type( $item ) {
		$product_id   = $item['product_id'];
		$product      = wc_get_product( $product_id );
		$product_type = $product->get_type();
		$title        = '<strong>' . $product_type . '</strong>';

		return $title;
	}


	public function column_product_name( $item ) {
		$product_id    = $item['product_id'];
		$product       = wc_get_product( $product_id );
		$product_title = get_the_title( $product_id );
		$title         = '<strong>' . $product_title . '</strong>';

		return $title;
	}

	public function column_product_status( $item ) {
		if ( 'Uploaded' == $item['operation_status'] ) {
			$title = '<strong>' . $item['operation_status'] . '</strong>';
		} elseif ( 'Error' == $item['operation_status'] ) {
			$upload_errors = $item['error'];
			if ( ! empty( $upload_errors ) ) {
				$upload_errors = json_decode( $upload_errors, true );
				if ( ! empty( $upload_errors ) && is_array( $upload_errors ) ) {
					$title = '<ul style="margin-top:0px;">';
					foreach ( $upload_errors as $key => $upload_err ) {
						$title .= '<li><b><span style="color:red;">[' . $upload_err['severity'] . ']</span> ' . $upload_err['message'] . '</b></li>';
					}
					$title .= '</ul>';
				}
			} else {
				$title = '<strong style="color:red;">Unable to fetch error log!</strong>';
			}
		}
		return $title;
	}


	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}
		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'upload_staus_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);

		$this->items = self::ced_amazon_get_bulk_upload_job_data( $per_page, $current_page );
		$this->renderHTML();

	}

	public function renderHTML() {
		?>
	<div class="ced-amazon-v2-header">
			<div class="ced-amazon-v2-logo">
			
			</div>
			<div class="ced-amazon-v2-header-content">
				<div class="ced-amazon-v2-title">
					<h1>Bulk Upload Feed</h1>
					<!-- <div class="ced-amazon-bootstrap-wrapper" > <div class="container" > <button>rwgyse5hy</button> </div></div> -->
				</div>
				<div class="ced-amazon-v2-actions">
				<div class="admin-custom-action-button-outer">
				<div class="admin-custom-action-show-button-outer">
				<button style="background:#135e96;" style="margin-left:5px;" id="ced_amazon_del_blk_upld_logs_btn" type="button" class="button btn-normal-tt">
<span>Delete Logs</span>
</button>
	</div>
	<div class="admin-custom-action-show-button-outer">
<button style="background:#5850ec !important;"  type="button" class="button btn-normal-tt">
<span><a style="all:unset;" href="#" target="_blank">
Documentation					</a></span>
</button>

</div>
</div>

			</div>
		</div>
</div>
	<div class="ced_amazon_wrap ced_amazon_wrap_extn">

		<div>
		   <?php
			if ( ! session_id() ) {
				session_start();
			}

			?>
			<div id="post-body" class="metabox-holder columns-2">
				<div id="">
					<div class="meta-box-sortables ui-sortable">
						<form method="post">
						   <?php
							wp_nonce_field( 'amazon_profile_view', 'amazon_profile_view_actions' );
							$this->display();
							?>
						</form>
					</div>
				</div>
				<div class="clear"></div>
			</div>
			<br class="clear">
		</div>
	</div>
		<?php
	}
}
$obj = new Ced_Amazon_Status_Feed();
$obj->prepare_items();

?>

<style>
table.wp-list-table .column-product_type {
	width:auto !important;
	}
</style>
