<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$part              = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';
$current_page      = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
$user_id           = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : false;
$seller_id         = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : false;
$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );

if ( empty( $seller_id ) ) {
	$seller_id = $sellernextShopIds[$user_id]['ced_mp_seller_key'];
}
if ( isset( $part ) && !empty( $part ) ) {
	$sellernextShopIds[$user_id]['ced_amz_current_step'] = 1;
	update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
}

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


if ( isset( $_POST['ced_amazon_options_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_options_nonce'] ), 'ced_amazon_options_page_nonce' ) ) {
	if ( isset( $_POST['amazon_options'] ) ) {

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		$amazon_options  = isset( $sanitized_array['ced_amazon_general_options'] ) ? ( $sanitized_array['ced_amazon_general_options'] ) : array();
		if ( isset( $part ) && !empty( $part ) ) {
			$sellernextShopIds                                   = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$sellernextShopIds[$user_id]['ced_amz_current_step'] = 2;
			update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
		}	
		$general_options             = get_option( 'ced_amazon_general_options', array() );
		$general_options[$seller_id] = $amazon_options;
		update_option( 'ced_amazon_general_options', $general_options );
		// $message = 'saved';

		//echo $seller_id; die;
		$seller_id = str_replace('|', '%7C', $seller_id);
		wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&part=wizard-settings&user_id=' . $user_id . '&seller_id=' . $seller_id  );
	
	}

	if ( isset( $_POST['reset_amazon_options'] ) ) {
		
		$ced_amazon_general_options = get_option( 'ced_amazon_general_options', array() );
		unset( $ced_amazon_general_options[$seller_id] );
		update_option( 'ced_amazon_general_options', $ced_amazon_general_options );
		// $message = 'reset';
	}

	// $admin_success_notice = '<div class="saved_container"><span class="text-green-800"> Your configuration has been ' . $message . ' ! </span></div>';
	// print_r( $admin_success_notice );

}

$options = array(
	'item_sku'                 => array(
		'name' => 'Seller Sku',
		'type' => 'input',
	),
	'brand_name'               => array(
		'name' => 'Brand Name',
		'type' => 'input',
	),
	'item_name'                => array(
		'name' => 'Title',
		'type' => 'input',
	),
	'product_description'      => array(
		'name' => 'Description',
		'type' => 'input',
	),
	'manufacturer'             => array(
		'name' => 'Manufacturer',
		'type' => 'input',
	),
	'model'                    => array(
		'name' => 'Model Number',
		'type' => 'input',
	),
	'part_number'              => array(
		'name' => 'Part Number',
		'type' => 'input',
	),
	'external_product_id'      => array(
		'name' => 'Product ID',
		'type' => 'input',
	),
	'standard_price'           => array(
		'name' => 'Price',
		'type' => 'input',
	),
	'quantity'                 => array(
		'name' => 'Quantity',
		'type' => 'input',
	),
	'external_product_id_type' => array(
		'name' => ' Product ID Type',
		'type' => 'input',
	),
);
							
							

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

	.ced-progress li {
		width: 33% !important;
	}

	.ced_amazon_search_item_sepcifics_mapping + .select2-container{
	   width: 99% !important;
	}

	.woocommerce table.form-table .select2-container {
	min-width: unset !important;
}
	

</style>

<div class="woocommerce-progress-form-wrapper">
<h2 style="text-align: left;">Amazon Integration Onboarding</h2>
<ol class="wc-progress-steps ced-progress">
	<li class="active">Global Options</li>
	<li class="">General Settings</li>
	<li class="">Done!</li>
</ol>
<div class="wc-progress-form-content woocommerce-importer">
	<header>
		<h2>Global Actions</h2>
		<p>Filling the following attributes can improve your listings on Amazon. </p>
	</header>

	<header>
	<form  method="post" >
		<table class="form-table ced_global_options">
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
							Custom Value
						</label>
					</th>
				</tr>

				<?php

					$ced_amazon_general_options = get_option( 'ced_amazon_general_options', array() );
					$ced_amazon_general_options = isset( $ced_amazon_general_options[$seller_id] ) ? $ced_amazon_general_options[$seller_id] : array();
					global $wpdb;
					$results       = $wpdb->get_results( "SELECT DISTINCT meta_key FROM {$wpdb->prefix}postmeta", 'ARRAY_A' );
					$query         = $wpdb->get_results( $wpdb->prepare( "SELECT `meta_value` FROM  {$wpdb->prefix}postmeta WHERE `meta_key` LIKE %s", '_product_attributes' ), 'ARRAY_A' );
					$addedMetaKeys = get_option( 'CedUmbProfileSelectedMetaKeys', false );
										
				foreach ( $options as $opt_key => $opt_value ) {
					?>
						<tr>
							<th scope="row" class="titledesc">
								<label for="woocommerce_currency">
							<?php echo esc_attr( $opt_value['name'] ); ?> <?php echo wc_help_tip('adsddasdsadsdsa', 'ebay-integration-for-woocommerce'); ?>
								</label>
							</th>

							<td class="forminp forminp-select">
							<?php

								$selected_value2    = isset( $ced_amazon_general_options[ $opt_key ]['metakey'] ) ? $ced_amazon_general_options[ $opt_key ]['metakey'] : '';
								$selectDropdownHTML = '<select style="width: 100%;" class="ced_amazon_search_item_sepcifics_mapping select2" id="" name="ced_amazon_general_options[' . $opt_key . '][metakey]" >';
							foreach ( $results as $key2 => $meta_key ) {
								$post_meta_keys[] = $meta_key['meta_key'];
							}

								$custom_prd_attrb = array();
							if ( ! empty( $query ) ) {
								foreach ( $query as $key3 => $db_attribute_pair ) {
									foreach ( maybe_unserialize( $db_attribute_pair['meta_value'] ) as $key4 => $attribute_pair ) {
										if ( 1 != $attribute_pair['is_taxonomy'] ) {
											$custom_prd_attrb[] = $attribute_pair['name'];
										}
									}
								}
							}
								$attrOptions = array();
							if ( $addedMetaKeys && count( $addedMetaKeys ) > 0 ) {
								foreach ( $addedMetaKeys as $metaKey ) {
									$attrOptions[ $metaKey ] = $metaKey;
								}
							}
								$attributes = wc_get_attribute_taxonomies();
							if ( ! empty( $attributes ) ) {
								foreach ( $attributes as $attributesObject ) {
									$attrOptions[ 'umb_pattr_' . $attributesObject->attribute_name ] = $attributesObject->attribute_label;
								}
							}
								/* select dropdown setup */
								ob_start();
								$fieldID             = '{{*fieldID}}';
								$selectId            = $fieldID . '_attibuteMeta';
								$selectDropdownHTML .= '<option value=""> -- select -- </option>';
							if ( is_array( $attrOptions ) ) {
								$selectDropdownHTML .= '<optgroup label="Global Attributes">';
								foreach ( $attrOptions as $attrKey => $attrName ) {
									$selected = '';
									if ( $selected_value2 == $attrKey ) {
										$selected = 'selected';
									}
									$selectDropdownHTML .= '<option ' . $selected . ' value="' . $attrKey . '">' . $attrName . '</option>';
								}
							}
							if ( ! empty( $custom_prd_attrb ) ) {
								$custom_prd_attrb    = array_unique( $custom_prd_attrb );
								$selectDropdownHTML .= '<optgroup label="Custom Attributes">';
								foreach ( $custom_prd_attrb as $key5 => $custom_attrb ) {
									$selected = '';
									if ( 'ced_cstm_attrb_' . esc_attr( $custom_attrb ) == $selected_value2 ) {
										$selected = 'selected';
									}
									$selectDropdownHTML .= '<option ' . $selected . ' value="ced_cstm_attrb_' . esc_attr( $custom_attrb ) . '">' . esc_html( $custom_attrb ) . '</option>';
								}
							}
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
										// custom attributes
								print_r( $selectDropdownHTML );

							?>
							</td>

							<td class="forminp forminp-select">
								<?php

								if ( 'select' == $opt_value['type'] ) {
									?>
										<select style="width: 100%;"  name="<?php echo 'ced_amazon_general_options[' . esc_attr( $opt_key ) . '][default]'; ?>" ><option value=''>--Select--</option>
										<?php
										$selected_value = isset( $ced_amazon_general_options[ $opt_key ]['default'] ) ? $ced_amazon_general_options[ $opt_key ]['default'] : '';
										foreach ( $opt_value['options'] as $key1 => $value ) {
											$selected = '';
											if ( $selected_value == $value ) {
												$selected = 'selected';
											}
											?>
												<option $selected value='<?php echo esc_attr( $value ); ?>' ><?php echo esc_attr( $value ); ?> </option> 
												<?php
										}
										?>
										</select>
										<?php
								} else {
									?>
										<input style="width: 100%;" placeholder="<?php echo 'Enter ' . esc_attr($opt_value['name']); ?>" type='text' value="<?php echo isset( $ced_amazon_general_options[ $opt_key ]['default'] ) ? esc_attr( $ced_amazon_general_options[ $opt_key ]['default'] ) : ''; ?>" name="<?php echo 'ced_amazon_general_options[' . esc_attr( $opt_key ) . '][default]'; ?>" />
										<?php
								}


								?>
							</td>	
						
						</tr>
						<?php
				}
					
				?>

				


			</tbody>
		</table>

		<div class="wc-actions">
			<?php wp_nonce_field( 'ced_amazon_options_page_nonce', 'ced_amazon_options_nonce' ); ?>
			<button type="submit" class="components-button is-secondary amazon_options_reset_button"  id="rest_amazon_options" name="reset_amazon_options"  >Reset all values</a>
			<button style="float: right;" type="submit" class="components-button is-primary amazon_options_save_button button-next" name="amazon_options" >Save and
				continue</button>
				<a style="float: right;" type="button" id="ced_amazon_continue_wizard_button" data-attr='2' href="<?php echo esc_attr_e( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&part=wizard-settings&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>"
				class="components-button woocommerce-admin-dismiss-notification">Skip</a>
		</div>

	</form>
	</header>
		
</div>
</div>

<script>

	jQuery('.ced_amazon_search_item_sepcifics_mapping').select2({width: '99% !important'});
</script>
