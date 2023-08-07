<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
/**
 * FilterClass.
 *
 * @since 1.0.0
 */
class FilterClass {

	/**
	 * Function- filter_by_category.
	 * Used to Apply Filter on Product Page
	 *
	 * @since 1.0.0
	 */
	public function ced_amazon_filters_on_products() {
		if ( isset( $_POST['ced_amazon_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_filter_nonce'] ), 'ced_amazon_product_filter_page_nonce' ) ) {
			$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
			$user_id         = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
			$seller_id       = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
			$seller_id       = str_replace( '|', '%7C', $seller_id );
			if ( ( isset( $sanitized_array['status_sorting'] ) && '' != $sanitized_array['status_sorting'] ) || ( isset( $sanitized_array['pro_cat_sorting'] ) && '' != $sanitized_array['pro_cat_sorting'] && '' != $sanitized_array['pro_cat_sorting'] ) || ( isset( $sanitized_array['pro_type_sorting'] ) && '' != $sanitized_array['pro_type_sorting'] ) ) {
				$status_sorting    = isset( $sanitized_array['status_sorting'] ) ? ( $sanitized_array['status_sorting'] ) : '';
				$pro_cat_sorting   = isset( $sanitized_array['pro_cat_sorting'] ) ? ( $sanitized_array['pro_cat_sorting'] ) : '';
				$pro_type_sorting  = isset( $sanitized_array['pro_type_sorting'] ) ? ( $sanitized_array['pro_type_sorting'] ) : '';
				$pro_stock_sorting = isset( $sanitized_array['pro_stock_sorting'] ) ? ( $sanitized_array['pro_stock_sorting'] ) : '';
				$current_url       = admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id;
				wp_redirect( $current_url . '&status_sorting=' . $status_sorting . '&pro_cat_sorting=' . $pro_cat_sorting . '&pro_type_sorting=' . $pro_type_sorting . '&pro_stock_sorting=' . $pro_stock_sorting );
			} else {
				$url = admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id;
				wp_redirect( $url );
			}
		}

	}

	public function ced_amazon_product_search_box() {

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
		$seller_id = str_replace( '|', '%7C', $seller_id );
		if ( isset( $_POST['ced_amazon_filter_product_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_filter_product_nonce'] ), 'ced_amazon_filter_product_action_nonce' ) ) {
			$current_url             = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$current_url             = remove_query_arg( array( 'searchType', 'searchQuery', 'searchCriteria' ), $current_url );
			$product_filter          = isset( $_POST['product-filter'] ) ? sanitize_text_field( wp_unslash( $_POST['product-filter'] ) ) : '';
			$product_filter_criteria = isset( $_POST['product-filter-criteria'] ) ? sanitize_text_field( wp_unslash( $_POST['product-filter-criteria'] ) ) : '';
			if ( ! empty( $product_filter ) ) {
				if ( strpos( $product_filter, 'custom_search|' ) !== false ) {
					$product_filter = trim( str_replace( 'custom_search|', '', $product_filter ) );
					wp_redirect( $current_url . '&searchType=productCustomSearch&searchQuery=' . ( urlencode( $product_filter ) ) . '&searchCriteria=' . ( $product_filter_criteria ) . '&user_id=' . ( $user_id ) . '&seller_id=' . ( $seller_id ) );
					exit;
				} else {
					wp_redirect( $current_url . '&searchType=productId&searchQuery=' . ( $product_filter ) . '&searchCriteria=' . ( $product_filter_criteria ) . '&user_id=' . ( $user_id ) . '&seller_id=' . ( $seller_id ) );
					exit;
				}
			}

			if ( isset( $_POST['s'] ) && ! empty( $_POST['s'] ) ) {

				$searchdata = str_replace( ' ', '+', $searchdata );
				wp_redirect( $current_url . '&searchBy=' . ( $searchdata ) . '&user_id=' . ( $user_id ) . '&seller_id=' . ( $seller_id ) );
				exit;
			} else {
				$url = admin_url( 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . ( $user_id ) . '&seller_id=' . ( $seller_id ) );
				wp_redirect( $url );
				exit;
			}
		}
	}
}//end class
