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
// Prepare dropdown for meta keys end
if ( '' !== $part ) {
	$connection_setup           = '';
	$integration_settings_setup = '';
	$amazon_options_setup       = '';
	$general_settings_setup     = '';
	if ( empty( $part ) || 'ced-amazon-login' == $part ) {
		$connection_setup = 'active';
	} elseif ( 'amazon-options' == $part ) {
		$amazon_options_setup = 'active';
	} elseif ( 'settings' == $part ) {
		$general_settings_setup = 'active';
	} elseif ( 'configuration' == $part ) {
		$integration_settings_setup = 'active';
	}
}	


if ( isset( $_POST['ced_amazon_setting_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_setting_nonce'] ), 'ced_amazon_setting_page_nonce' ) ) {
	if ( isset( $_POST['global_settings'] ) ) {

		$objDateTime                            = new DateTime( 'NOW' );
		$timestamp                              = $objDateTime->format( 'Y-m-d\TH:i:s\Z' );
		$global_setting_data                    = get_option( 'ced_amazon_global_settings', array() );
		$settings                               = array();
		$sanitized_array                        = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		if ( isset( $part ) && !empty( $part ) ) {
			$sellernextShopIds                                   = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$sellernextShopIds[$user_id]['ced_amz_current_step'] = 3;
			update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
		}
		

		$sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] = 'ced_amazon_6min';
		$sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync']  = 'ced_amazon_6min';

		// $inventory_schedule       = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ) && '0' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ? ( $sanitized_array['ced_amazon_global_settings']['ced_amazon_inventory_schedule_info'] ) : wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id );
		// $existing_product_sync    = isset( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) && '0' != $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ? ( $sanitized_array['ced_amazon_global_settings']['ced_amazon_existing_products_sync'] ) : wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id );
		
		$settings                               = get_option( 'ced_amazon_global_settings', array() );
		$settings[ $seller_id ]                 = isset( $sanitized_array['ced_amazon_global_settings'] ) ? ( $sanitized_array['ced_amazon_global_settings'] ) : array();
		$settings[ $seller_id ]['last_updated'] = $timestamp;
		
		update_option( 'ced_amazon_global_settings', $settings ); 

		wp_clear_scheduled_hook( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
		wp_schedule_event( time(), 'ced_amazon_6min', 'ced_amazon_inventory_scheduler_job_' . $seller_id, $seller_args );
		update_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id, $inventory_schedule );
		
		
		wp_clear_scheduled_hook( 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
		wp_schedule_event( time(), 'ced_amazon_6min', 'ced_amazon_existing_products_sync_job_' . $seller_id, $seller_args );
		update_option( 'ced_amazon_existing_products_sync_job_' . $seller_id, $existing_product_sync );
			

		$seller_id = str_replace('|', '%7C', $seller_id);
		wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&part=configuration&user_id=' . $user_id . '&seller_id=' . $seller_id  );
	
		
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
		
	}


}

?>






<style type="text/css">
.ced-label-wrap label {
	font-weight: 600;
	color: #1E1E1E;
}
.ced-label-wrap label {
	line-height: 32px;
}
.ced-label-wrap {
	margin-bottom: 15px;
}
.ced-progress li{
	width: 33% !important;
}
</style>
<div class="woocommerce-progress-form-wrapper">
	<h2 style="text-align: left;">Amazon Integration Onboarding</h2>
	<ol class="wc-progress-steps ced-progress">
		<li class="done">Global Options</li>
		<li class="active">General Settings</li>
		<li class="">Done!</li>
	</ol>
	<div class="wc-progress-form-content woocommerce-importer">
		<header>
			<h2>General Settings</h2>
			<p>Filling the following attributes can improve your listings on Amazon.</p>
		</header>

		<header>
			<form  method="post" >
				<h3>Listings Configuration</h3>
				<p>Increase or decrease the price of amazon listings, adjust stock levels from WooCommerce.</p>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Column name 
								</label>
							</th>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Map to fields
								</label>
							</th>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
								</label>
							</th>
						</tr>
						<tr>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Stock Levels <?php echo wc_help_tip('adsddasdsadsdsa', 'ebay-integration-for-woocommerce'); ?>
								</label>
							</th>
							<td class="forminp forminp-select">
								<?php
									$listing_stock = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_listing_stock'] : '';
									$stock_type    = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_stock_type'] : '';
								?>
							   

								<select style="width: 100%;" id="bulk-action-selector-top" name="ced_amazon_global_settings[ced_amazon_product_stock_type]" data-fieldId="ced_amazon_product_stock_type">
									<option value=""><?php esc_attr_e( 'Select', 'amazon-integration-for-woocommerce' ); ?></option>
									<option <?php echo ( 'MaxStock' == $stock_type ) ? 'selected' : ''; ?> value="MaxStock"><?php esc_attr_e( 'Maximum Stock', 'amazon-integration-for-woocommerce' ); ?></option>
								</select> 
							</td>
							<td class="forminp forminp-select">
							
								<input style="width: 100%; min-width:50px;" placeholder="Enter Value" type="text"  value="<?php echo esc_attr( $listing_stock ); ?>" id="ced_amazon_listing_stock" name="ced_amazon_global_settings[ced_amazon_listing_stock]">
												
								<!-- <input style="width: 100%;" name="" id="" type="text" style="min-width:50px;" value="" class="" placeholder="Enter Value"> -->
							</td>
						</tr>
						<tr>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Markup <?php echo wc_help_tip('adsddasdsadsdsa', 'ebay-integration-for-woocommerce'); ?>
								</label>
							</th>
							<td class="forminp forminp-select">
								<?php
									$markup_type = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup_type'] : '';
								?>
								<select style="width: 100%;"  id="bulk-action-selector-top" name="ced_amazon_global_settings[ced_amazon_product_markup_type]" data-fieldId="ced_amazon_product_markup">
									<option value=""><?php esc_attr_e( 'Select', 'amazon-integration-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Fixed_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Increased"><?php esc_attr_e( 'Fixed Increment', 'amazon-integration-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Fixed_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Fixed_Decreased"><?php esc_attr_e( 'Fixed Decrement', 'amazon-integration-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Percentage_Increased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Increased"><?php esc_attr_e( 'Percentage Increment', 'amazon-integration-for-woocommerce' ); ?></option>
									<option <?php echo ( 'Percentage_Decreased' == $markup_type ) ? 'selected' : ''; ?> value="Percentage_Decreased"><?php esc_attr_e( 'Percentage Decrement', 'amazon-integration-for-woocommerce' ); ?></option>
								</select>
													
							</td>
							<td class="forminp forminp-select">
								<?php
									$markup_price = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_product_markup'] : '';
								?>

								<input style="width: 100%; min-width:50px;" placeholder="Enter Value" type="text" value="<?php echo esc_attr( $markup_price ); ?>" id="ced_amazon_product_markup" name="ced_amazon_global_settings[ced_amazon_product_markup]">
								<!-- <input  name="" id="" type="text" style="min-width:50px;" value="" class="" placeholder="Enter Value"> -->
							</td>
						</tr>
					</tbody>
				</table>

				<!-- <h3>Scheduler Configuration</h3>
				<p>Manage the automatic sync of products, stock and orders.</p>
				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th colspan="2" scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Column name 
								</label>
							</th>
							<th colspan="1" scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Map to fields
								</label>
							</th>
						</tr>
						<tr>
							<th colspan="2" scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Order Sync <?php echo wc_help_tip('adsddasdsadsdsa', 'ebay-integration-for-woocommerce'); ?>
								</label>
							</th>
							<td colspan="3" class="forminp forminp-select">
							   
								<?php
								  $order_schedule = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_schedule_info'] ) ? $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_order_schedule_info'] : '';
								?>
								<select style="width: 100%;" id="bulk-action-selector-top" name="ced_amazon_global_settings[ced_amazon_order_schedule_info]" data-fieldId="ced_amazon_order_schedule_info">
									<option <?php echo ( '0' == $order_schedule ) ? 'selected' : ''; ?> value="0"><?php esc_attr_e( 'Disabled', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'daily' == $order_schedule ) ? 'selected' : ''; ?> value="daily"><?php esc_attr_e( 'Daily', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'twicedaily' == $order_schedule ) ? 'selected' : ''; ?> value="twicedaily"><?php esc_attr_e( 'Twice Daily', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_6min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_6min"><?php esc_attr_e( 'Every 6 Minutes', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_10min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_10min"><?php esc_attr_e( 'Every 10 Minutes', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_15min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_15min"><?php esc_attr_e( 'Every 15 Minutes', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_30min' == $order_schedule ) ? 'selected' : ''; ?> value="ced_amazon_30min"><?php esc_attr_e( 'Every 30 Minutes', 'ced-umb-amazon' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th colspan="2" scope="row" class="titledesc">
								<label for="woocommerce_currency">
										Inventory Sync <?php echo wc_help_tip('adsddasdsadsdsa', 'ebay-integration-for-woocommerce'); ?>
								</label>
							</th>
							<td colspan="3" class="forminp forminp-select">
							   
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
								
							</td>
						</tr>

						<tr>
							<th colspan="2" scope="row" class="titledesc">
								<label for="woocommerce_currency">
									Existing Products Sync <?php echo wc_help_tip('adsddasdsadsdsa', 'ebay-integration-for-woocommerce'); ?>
								</label>
							</th>
							<td colspan="3" class="forminp forminp-select">
							   
								<?php
								$existing_product_sync = isset( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_existing_products_sync'] ) ? ( $renderDataOnGlobalSettings[ $seller_id ]['ced_amazon_existing_products_sync'] ) : '';
								?>
								<select style="width: 100%;" id="bulk-action-selector-top" name="ced_amazon_global_settings[ced_amazon_existing_products_sync]" class="block mt-2 max-w-md" data-fieldId="ced_amazon_existing_products_sync">
									<option <?php echo ( '0' == $existing_product_sync ) ? 'selected' : ''; ?> value="0"><?php esc_attr_e( 'Disabled', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'daily' == $existing_product_sync ) ? 'selected' : ''; ?> value="daily"><?php esc_attr_e( 'Daily', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'twicedaily' == $existing_product_sync ) ? 'selected' : ''; ?> value="twicedaily"><?php esc_attr_e( 'Twice Daily', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_6min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_6min"><?php esc_attr_e( 'Every 6 Minutes', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_10min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_10min"><?php esc_attr_e( 'Every 10 Minutes', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_15min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_15min"><?php esc_attr_e( 'Every 15 Minutes', 'ced-umb-amazon' ); ?></option>
									<option <?php echo ( 'ced_amazon_30min' == $existing_product_sync ) ? 'selected' : ''; ?> value="ced_amazon_30min"><?php esc_attr_e( 'Every 30 Minutes', 'ced-umb-amazon' ); ?></option>
								</select>
								
							</td>
						</tr>


					</tbody>
				</table> -->
							
				<div class="wc-actions">
					<?php wp_nonce_field( 'ced_amazon_setting_page_nonce', 'ced_amazon_setting_nonce' ); ?>
					<button type="submit" class="components-button is-secondary general_settings_reset_button" id="rest_global_settings" name="reset_global_settings" >Reset all values</button>
					<button style="float: right;" type="submit" name="global_settings" class="components-button is-primary button-next">Save and continue</button>
					<a style="float: right;" data-attr='3' id="ced_amazon_continue_wizard_button" href="<?php echo esc_attr_e( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&part=configuration&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="components-button woocommerce-admin-dismiss-notification">Skip</a>
				</div>

			</form>
		</header>

	</div>
</div>
