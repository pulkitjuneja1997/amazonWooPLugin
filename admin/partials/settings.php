<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$part              = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';
$current_page      = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
$user_id           = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id         = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );
$amazon_accounts = get_option( 'ced_amzon_configuration_validated', array() );  


if ( empty( $seller_id ) ) {
	$seller_id = $sellernextShopIds[$user_id]['ced_mp_seller_key'];
}
if ( isset( $part ) && !empty( $part ) ) {
	$sellernextShopIds[$user_id]['ced_amz_current_step'] = 2;
	update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds ); 
}

//Set this argument to pass in CRON scheduler
$seller_args = array( $seller_id ); 

// Prepare dropdown for meta keys start
function ced_amazon_profile_dropdown( $field_id, $metakey_val ) {
	global $wpdb;
	$results = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta WHERE meta_key NOT LIKE '%wcf%' AND meta_key NOT LIKE '%elementor%' AND meta_key NOT LIKE '%_menu%'", 'ARRAY_A' );
	foreach ( $results as $key => $meta_key ) {
		$post_meta_keys[] = $meta_key['meta_key'];
	}
	$addedMetaKeys      = get_option( 'CedUmbProfileSelectedMetaKeys', false );
	$optionalFields     = array();
	$selectDropdownHTML = '';
	$selectDropdownHTML = '<select class="select2 custom_category_attributes_select"  name="' . $field_id . '">';
	/* select dropdown setup */
	ob_start();
	$fieldID             = '{{*fieldID}}';
	$selectId            = $fieldID . '_attibuteMeta';
	$selectDropdownHTML .= '<option value=""> -- select -- </option>';
	$selected_value2     = isset( $metakey_val ) ? $metakey_val : '';
	if ( ! empty( $post_meta_keys ) ) {
		$post_meta_keys      = array_unique( $post_meta_keys );
		$selectDropdownHTML .= '<optgroup label="Custom Fields">';
		foreach ( $post_meta_keys as $key7 => $p_meta_key ) {
			$selected = '';
			if ( $selected_value2 == $p_meta_key ) {
				$selected = 'selected';
			}
			$selectDropdownHTML .= '<option ' . $selected . ' value="' . $p_meta_key . '">' . $p_meta_key . '</option>';
		}
	}
	$selectDropdownHTML .= '</select>';
	return $selectDropdownHTML;
}


?>

<?php
	$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}
	
if ( isset( $_POST['ced_amazon_setting_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_setting_nonce'] ), 'ced_amazon_setting_page_nonce' ) ) {
	if ( isset( $_POST['global_settings'] ) ) {
		$objDateTime                            = new DateTime( 'NOW' );
		$timestamp                              = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );
		$global_setting_data                    = get_option( 'ced_amazon_global_settings', array() );
		$settings                               = array();
		$sanitized_array                        = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		$settings                               = get_option( 'ced_amazon_global_settings', array() );
		$settings[ $seller_id ]                 = isset( $sanitized_array['ced_amazon_global_settings'] ) ? ( $sanitized_array['ced_amazon_global_settings'] ) : array();
		$settings[ $seller_id ]['last_updated'] = $timestamp;

		if ( isset( $part ) && !empty( $part ) ) {
			$sellernextShopIds                                   = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$sellernextShopIds[$user_id]['ced_amz_current_step'] = 3;
			update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
		}

		update_option( 'ced_amazon_global_settings', $settings );
		$inventory_schedule       = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ) && '0' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ? ( $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ) : wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
		$order_schedule           = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_order_schedule_info'] ) && '0' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_order_schedule_info'] ? ( $sanitized_array['ced_amazon_global_settings']['ced_amazon_order_schedule_info'] ) : wp_clear_scheduled_hook( 'ced_amazon_order_scheduler_job_' . $seller_id );
		$existing_product_sync    = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) && '0' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ? ( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) : wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id );
		$request_report_id_sync   = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) && '0' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ? ( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) : wp_clear_scheduled_hook( 'ced_amazon_request_report_id_job_' . $seller_id );
		$amazon_catalog_asin_sync = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_catalog_asin_sync'] ) && '0' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_catalog_asin_sync'] ? ( $sanitized_array['ced_amazon_global_settings']['ced_amazon_catalog_asin_sync'] ) : wp_clear_scheduled_hook( 'ced_amazon_catalog_asin_sync_job_' . $seller_id );
		
		$current_inventory_sync = isset( $global_setting_data[$seller_id]['ced_amazon_inventory_schedule_info'] ) ? $global_setting_data[$seller_id]['ced_amazon_inventory_schedule_info'] : 0;
		if ( $current_inventory_sync !== $inventory_schedule ) {
				//wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
			wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
			if ( ! empty( $inventory_schedule ) ) {
					//wp_schedule_event( time(), $inventory_schedule, 'ced_amazon_inventory_scheduler_job_' . $seller_id );
				wp_schedule_event( time(), $inventory_schedule, 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $inventory_schedule );
			}
		}

		$current_order_sync = isset( $global_setting_data[$seller_id]['ced_amazon_order_schedule_info'] ) ? $global_setting_data[$seller_id]['ced_amazon_order_schedule_info'] : 0;
		if ( $current_order_sync !== $order_schedule ) {
				//wp_clear_scheduled_hook( 'ced_amazon_order_scheduler_job_' . $seller_id );
			wp_clear_scheduled_hook( 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args );
			if ( ! empty( $order_schedule ) ) {
					//wp_schedule_event( time(), $order_schedule, 'ced_amazon_order_scheduler_job_' . $seller_id );
				wp_schedule_event( time(), $order_schedule, 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_order_scheduler_job_' . $seller_id, $order_schedule );
			}
		}

		$current_exist_product_sync = isset( $global_setting_data[$seller_id]['ced_amazon_existing_products_sync'] ) ? $global_setting_data[$seller_id]['ced_amazon_existing_products_sync'] : 0;
		if ( $current_exist_product_sync !== $existing_product_sync ) {
				//wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id );
			wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
			if ( ! empty( $existing_product_sync ) ) {
					//wp_schedule_event( time(), $existing_product_sync, 'ced_amazon_existing_products_sync_job_' . $seller_id );
				wp_schedule_event( time(), $existing_product_sync, 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_existing_products_sync_job_' . $seller_id, $existing_product_sync );
			}
		}

		$current_asin_sync = isset( $global_setting_data[$seller_id]['ced_amazon_catalog_asin_sync'] ) ? $global_setting_data[$seller_id]['ced_amazon_catalog_asin_sync'] : 0;
		if ( $current_asin_sync !== $amazon_catalog_asin_sync ) {
				//wp_clear_scheduled_hook( 'ced_amazon_catalog_asin_sync_job_' . $seller_id );
			wp_clear_scheduled_hook( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args );
			if ( ! empty( $amazon_catalog_asin_sync ) ) {
					//wp_schedule_event( time(), $amazon_catalog_asin_sync, 'ced_amazon_catalog_asin_sync_job_' . $seller_id );
				wp_schedule_event( time(), $amazon_catalog_asin_sync, 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args );
				update_option( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $amazon_catalog_asin_sync );
				update_option( 'ced_amazon_catalog_asin_sync_page_number_' . $seller_id, '' );
			}
		}
		
		$message = 'saved';
	} elseif ( isset( $_POST['reset_global_settings'] ) ) {
		$ced_amazon_global_settings = get_option( 'ced_amazon_global_settings', array() );
		unset( $ced_amazon_global_settings[$seller_id] );
		update_option( 'ced_amazon_global_settings', $ced_amazon_global_settings );
		delete_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
		delete_option( 'ced_amazon_order_scheduler_job_' . $seller_id );
		delete_option( 'ced_amazon_existing_products_sync_job_' . $seller_id );
		delete_option( 'ced_amazon_request_report_id_job_' . $seller_id );
		delete_option( 'ced_amazon_catalog_asin_sync_job_' . $seller_id );
		delete_option( 'ced_amazon_catalog_asin_sync_page_number_' . $seller_id );
			// delete_option( 'ced_amazon_import_products_job_' . $seller_id );
		wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
		wp_clear_scheduled_hook( 'ced_amazon_order_scheduler_job_' . $seller_id, $seller_args );
		wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
		wp_clear_scheduled_hook( 'ced_amazon_request_report_id_job_' . $seller_id, $seller_args );
		wp_clear_scheduled_hook( 'ced_amazon_catalog_asin_sync_job_' . $seller_id, $seller_args );
			// wp_clear_scheduled_hook( 'ced_amazon_import_products_job_' . $seller_id );
		$message = 'reset';
	}

	$admin_success_notice = '<div class="saved_container" ><p class="text-green-800"> Your configuration has been ' . $message . ' ! </p> </div>';
	print_r( $admin_success_notice );

}
?>

<div class="ced-general-connection-wrap-wrapper">
	<div class="ced-general-connection-wrap">
		
		<div class="ced-general-setup-content">
			
				<div class="ced-general-setting-wrapper">
					<div class="ced-general-setting-container">
						<div class="ced-general-complet-wrapper">
							
							<form action="" method="post">
								<?php
								$renderDataOnGlobalSettings = get_option( 'ced_amazon_global_settings', false );
								?>
								<div class="ced-general-option-notification">
									<div class="ced-general-notification-wrap">
										<h2>Listing Configuration</h2>
										<p>Increase or decrease the Price of amazon Listings, Adjust Stock Levels from WooCommerce.</p>
									</div>
								</div>
								<div class="ced-general-setting-field-wrap">
									<div class="ced-general-option-form-wrap-control">
										<div class="ced-general-option-form-wrapper">
											<?php
											$listing_stock = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] : '';
											$stock_type    = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] : '';
											?>
											<label>Stock Levels</label>
											<div class="ced-general-option-field-wrapper">
												<select name="ced_amazon_global_settings[ced_amazon_product_stock_type]" data-fieldId="ced_amazon_product_stock_type">
													<option value=""><?php esc_attr_e( 'Select', 'amazon-integration-for-woocommerce' ); ?></option>
													<option <?php echo ( 'MaxStock' == $stock_type ) ? 'selected' : ''; ?> value="MaxStock"><?php esc_attr_e( 'Maximum Stock', 'amazon-integration-for-woocommerce' ); ?></option>
												</select> 
												<input type="text"  value="<?php echo esc_attr( $listing_stock ); ?>" id="ced_amazon_listing_stock" name="ced_amazon_global_settings[ced_amazon_listing_stock]">
											</div>
											<p>Set rules for Quantity of items listed on amazon.</p>
										</div>
										<div class="ced-general-option-form-wrapper">
											<label>Markup</label>
											<div class="ced-general-option-field-wrapper">
												<?php
												$markup_type = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] : '';
												?>
												<select name="ced_amazon_global_settings[ced_amazon_product_markup_type]" data-fieldId="ced_amazon_product_markup">
													<option value=""><?php esc_attr_e( 'Select', 'amazon-integration-for-woocommerce' ); ?></option>
													<option <?php echo ( 'Fixed_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Increased"><?php esc_attr_e( 'Fixed Increment', 'amazon-integration-for-woocommerce' ); ?></option>
													<option <?php echo ( 'Fixed_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Decreased"><?php esc_attr_e( 'Fixed Decrement', 'amazon-integration-for-woocommerce' ); ?></option>
													<option <?php echo ( 'Percentage_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Increased"><?php esc_attr_e( 'Percentage Increment', 'amazon-integration-for-woocommerce' ); ?></option>
													<option <?php echo ( 'Percentage_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Decreased"><?php esc_attr_e( 'Percentage Decrement', 'amazon-integration-for-woocommerce' ); ?></option>
												</select>
												<?php
												$markup_price = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] : '';
												?>
												<input type="text" value="<?php echo esc_attr( $markup_price ); ?>" id="ced_amazon_product_markup" name="ced_amazon_global_settings[ced_amazon_product_markup]">
											</div>
											<p>Set your preference for Increasing/Decreasing the price of your amazon Listings.</p>
										</div>
										<hr>
										<div class="ced-general-option-notification ced-second-notification">
											<div class="ced-general-notification-wrap">
												<h2>Scheduler Configuration</h2>
												<p>Manage the Automatic Sync of Products, Stock and Orders.</p>
											</div>
										</div>
										<div class="ced-general-option-form-wrapper">
											<label>Order Sync</label>
											<div class="ced-general-option-field-wrapper-wrap">
												<?php
												$order_schedule = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_schedule_info'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_schedule_info'] : '';
												?>
												<select name="ced_amazon_global_settings[ced_amazon_order_schedule_info]" data-fieldId="ced_amazon_order_schedule_info">
													<option <?php echo ( '0' == $order_schedule ) ? 'selected' : ''; ?> value="0"><?php esc_attr_e( 'Disabled', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'daily' == $order_schedule ) ? 'selected' : ''; ?> value="daily"><?php esc_attr_e( 'Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'twicedaily' == $order_schedule ) ? 'selected' : ''; ?> value="twicedaily"><?php esc_attr_e( 'Twice Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_6min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_6min"><?php esc_attr_e( 'Every 6 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_10min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_10min"><?php esc_attr_e( 'Every 10 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_15min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_15min"><?php esc_attr_e( 'Every 15 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_30min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_30min"><?php esc_attr_e( 'Every 30 Minutes', 'ced-umb-amazon' ); ?></option>
												</select>
											</div>
											<p>Fetch your amazon Orders automatically and create them in WooCommerce. Before running this scheduler, make sure the corresponding amazon SKU exists in your WooCommerce store.</p>
										</div>
										<div class="ced-general-option-form-wrapper-wrap">
											<label>Inventory Sync</label>
											<div class="ced-general-option-field-wrapper">
												<?php
												$inventory_schedule = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_inventory_schedule_info'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_inventory_schedule_info'] : '';
												?>
												<select name="ced_amazon_global_settings[ced_amazon_inventory_schedule_info]" class="block mt-2 max-w-md" data-fieldId="ced_amazon_inventory_schedule_info">
													<option <?php echo ( '0' == $inventory_schedule ) ? 'selected' : ''; ?> value="0"><?php esc_attr_e( 'Disabled', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'daily' == $inventory_schedule ) ? 'selected' : ''; ?> value="daily"><?php esc_attr_e( 'Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'twicedaily' == $inventory_schedule ) ? 'selected' : ''; ?> value="twicedaily"><?php esc_attr_e( 'Twice Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_6min' == $inventory_schedule ) ? 'selected' : ''; ?> value="ced_amazon_6min"><?php esc_attr_e( 'Every 6 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_10min' == $inventory_schedule ) ? 'selected' : ''; ?> value="ced_amazon_10min"><?php esc_attr_e( 'Every 10 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_15min' == $inventory_schedule ) ? 'selected' : ''; ?> value="ced_amazon_15min"><?php esc_attr_e( 'Every 15 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_30min' == $inventory_schedule ) ? 'selected' : ''; ?> value="ced_amazon_30min"><?php esc_attr_e( 'Every 30 Minutes', 'ced-umb-amazon' ); ?></option>
												</select>
											</div>
											<p>Update your amazon inventory automatically as soon as stock change is detected in your WooCommerce store.</p>
										</div>
										<div class="ced-general-option-form-wrapper-wrap">
											<label>Existing Products Sync</label>
											<div class="ced-general-option-field-wrapper">
												<?php
												$existing_product_sync = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_existing_products_sync'] ) ? ( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_existing_products_sync'] ) : '';
												?>
												<select name="ced_amazon_global_settings[ced_amazon_existing_products_sync]" class="block mt-2 max-w-md" data-fieldId="ced_amazon_existing_products_sync">
													<option <?php echo ( '0' == $existing_product_sync ) ? 'selected' : ''; ?> value="0"><?php esc_attr_e( 'Disabled', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'daily' == $existing_product_sync ) ? 'selected' : ''; ?> value="daily"><?php esc_attr_e( 'Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'twicedaily' == $existing_product_sync ) ? 'selected' : ''; ?> value="twicedaily"><?php esc_attr_e( 'Twice Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_6min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_6min"><?php esc_attr_e( 'Every 6 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_10min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_10min"><?php esc_attr_e( 'Every 10 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_15min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_15min"><?php esc_attr_e( 'Every 15 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_30min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_30min"><?php esc_attr_e( 'Every 30 Minutes', 'ced-umb-amazon' ); ?></option>
												</select>
											</div>
											<p>Sync the ASIN of your amazon Products to the corresponding Woocommerce Products on the basic SKU.</p>
										</div>
										<div class="ced-general-option-form-wrapper">
											<label>ASIN Sync</label>
											<div class="ced-general-option-field-wrapper ced-option-general-cotent">
												<?php
												$amazon_catalog_asin_sync = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync'] ) ? ( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync'] ) : '';
												?>
												<select name="ced_amazon_global_settings[ced_amazon_catalog_asin_sync]" class="" data-fieldId="ced_amazon_catalog_asin_sync">
													<option <?php echo ( '0' == $amazon_catalog_asin_sync ) ? 'selected' : ''; ?> value="0"><?php esc_attr_e( 'Disabled', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'daily' == $amazon_catalog_asin_sync ) ? 'selected' : ''; ?> value="daily"><?php esc_attr_e( 'Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'twicedaily' == $amazon_catalog_asin_sync ) ? 'selected' : ''; ?> value="twicedaily"><?php esc_attr_e( 'Twice Daily', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_6min' == $amazon_catalog_asin_sync ) ? 'selected' : ''; ?> value="ced_amazon_6min"><?php esc_attr_e( 'Every 6 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_10min' == $amazon_catalog_asin_sync ) ? 'selected' : ''; ?> value="ced_amazon_10min"><?php esc_attr_e( 'Every 10 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_15min' == $amazon_catalog_asin_sync ) ? 'selected' : ''; ?> value="ced_amazon_15min"><?php esc_attr_e( 'Every 15 Minutes', 'ced-umb-amazon' ); ?></option>
													<option <?php echo ( 'ced_amazon_30min' == $amazon_catalog_asin_sync ) ? 'selected' : ''; ?> value="ced_amazon_30min"><?php esc_attr_e( 'Every 30 Minutes', 'ced-umb-amazon' ); ?></option>
												</select>
												<?php  
												$metakey_val = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync_meta'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_catalog_asin_sync_meta'] : '';
												$html        = ced_amazon_profile_dropdown( 'ced_amazon_global_settings[ced_amazon_catalog_asin_sync_meta]', $metakey_val ); 
												print_r($html);
												?>
												<span class="ced-tooltip-holder
												">
												<i class="fa fa-info-circle" data-tooltip-content="Select woo meta of UPC/EAN number. Default meta is woo sku."></i>
											</span>
										</div>
										<p>Sync the ASIN of amazon catalog products to the corresding Woocommerce Products on the basis of UPC/EAN.</p>
									</div>
								 </div>
								<div class="ced-button-container-wrap">
									<div class="ced-button-content">
										<?php 
										wp_nonce_field( 'ced_amazon_setting_page_nonce', 'ced_amazon_setting_nonce' ); ?>

										<button id="save_global_settings" name="global_settings" class="ced-amazon-v2-btn"><?php esc_attr_e( 'Save', 'amazon-integration-for-woocommerce' ); ?></button>
										 
									</div>
								</div>
							</div>	
						</form>
					</div>
				</div>
			
		</div>
	</div>
</div>
</div>


<script>
	// Load search box in woo meta select box
	jQuery( '.custom_category_attributes_select' ).selectWoo();
	if ( jQuery( 'input[name="ced_amazon_global_settings[ced_amazon_import_amazon_categories]"]:checked' ).val() == 'Enabled') {
		jQuery('#ced_amazon_select_categories_type_to_import').show();
	} else if( jQuery( 'input[name="ced_amazon_global_settings[ced_amazon_import_amazon_categories]"]:checked' ).val() == 'Disabled') {
		jQuery('#ced_amazon_select_categories_type_to_import').hide();
	}


</script>
