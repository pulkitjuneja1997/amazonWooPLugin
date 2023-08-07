<?php


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$user_id       = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id     = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$template_type = isset( $_GET['template_type'] ) ? sanitize_text_field( $_GET['template_type'] ) : '';

global $wpdb;
$tableName       = $wpdb->prefix . 'ced_amazon_profiles';
$amazon_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s ", $seller_id ), 'ARRAY_A' );
// $amazon_profiles      = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` =%s", 'ARRAY_A' );
$amazon_wooCategories = array();

if ( ! empty( $amazon_profiles ) ) {
	foreach ( $amazon_profiles as $amazon_profile ) {

		$wooCatIds = json_decode( $amazon_profile['wocoommerce_category'], true );
		if ( !empty( $wooCatIds ) ) {
			foreach ( $wooCatIds as $wooCatId ) {
				$amazon_wooCategories[] = $wooCatId;
			}
		}
		
	}
} 

$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

if ( empty($seller_id) ) {
	echo '<div class="notice notice-error is-dismissable">
	 	<p>Seller id is missing, please check your amazon account connected properlly!</p>
	</div>';
	return;
}

$profile_data = false;


$woo_store_categories = ced_amazon_get_categories_hierarchical(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	)
);
$shopDetails          = get_option( 'ced_amz_redirect_token_data' );

if ( isset( $shopDetails['data'] ) && ! empty( $shopDetails['data'] ) && is_array( $shopDetails['data'] ) ) {
	$shop_id = $shopDetails['data']['shop_id'];
}


if ( isset( $_POST['ced_amazon_profile_edit'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_profile_edit'] ), 'ced_amazon_profile_edit_page_nonce' ) ) {
	if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_amazon_profile_save_button'] ) ) {

		$sanitized_array     = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		$amazon_profile_data = isset( $sanitized_array['ced_amazon_profile_data'] ) ? ( $sanitized_array['ced_amazon_profile_data'] ) : array();

		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		if ( empty($seller_id) ) {
			echo '<div class="notice notice-error is-dismissable">
			 	<p>Seller id is missing, please check your amazon account connected properlly!</p>
			</div>';
			return;
		}
		
		$profileDetails = array(
			'profile_name'         => $amazon_profile_data['profile_name'],
			'primary_category'     => $amazon_profile_data['primary_category'],
			'secondary_category'   => $amazon_profile_data['secondary_category'],
			'browse_nodes'         => $amazon_profile_data['browse_nodes'],
			'wocoommerce_category' => $amazon_profile_data['wocoommerce_category'],
		);

		$profileDetails['category_attributes_structure'] = json_encode( $amazon_profile_data['ref_attribute_list'] );

		unset( $amazon_profile_data['profile_name'] );
		unset( $amazon_profile_data['primary_category'] );
		unset( $amazon_profile_data['secondary_category'] );
		unset( $amazon_profile_data['browse_nodes'] );
		unset( $amazon_profile_data['ref_attribute_list'] );
		unset( $amazon_profile_data['wocoommerce_category'] );

		if ( isset($amazon_profile_data['template_type']) ) {
			unset( $amazon_profile_data['template_type'] );
		}
		if ( isset($amazon_profile_data['file_url']) ) {
			unset( $amazon_profile_data['file_url'] );
		}

		$profileDetails['category_attributes_data'] = json_encode( $amazon_profile_data );

		/* -------------------------------------------------------- Saving Missing Fields Starts ----------------------------------------------------- */


		/*$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

		// $shop_loc            = get_option( 'ced_umb_amazon_bulk_profile_loc' );
		// $location_for_seller = get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );

		if( empty( $seller_id ) ){
			$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
			$seller_id = isset( $ced_amazon_sellernext_shop_ids['user_id'] ) ? $ced_amazon_sellernext_shop_ids['user_id']['ced_mp_seller_key'] : '';
	
		}

		$userData    = $ced_amzon_configuration_validated[ $seller_id ];
		$userCountry = $userData['ced_mp_name'];

		$upload_dir           = wp_upload_dir();
		$missing_fields_json_path  = $upload_dir['basedir'] . '/ced-amazon/templates/' . $userCountry . '/' . $profileDetails['primary_category'] . '/' . $profileDetails['secondary_category'] . '/missingFields.json';
		

		$missing_fields_array = array();
		// update missing file.
		
		if( !empty(  $amazon_profile_data ) ){
			foreach( $amazon_profile_data as $row_key => $row_array ){

				$index =  strpos( $row_key, "_custom_field" );

				if( $index  > -1 ){
					$slug  =  substr( $row_key, 0, $index );
					$missing_fields_array[$slug] = Array (
														'definition' => 'Please enter custom field value',
														'accepted_value' => '',
														'productTypeSpecific' => Array(
																				'FoodServiceSupply' => Array ( 'condition' => 'optional')
																			),
														'label' => $row_array['label']
													);
									
					
				} 

			}

		}

		//  $profileDetails['category_attributes_data']

		if( file_exists( $missing_fields_json_path ) ){
		 
			$jsonFile = fopen( $missing_fields_json_path, 'w' );
			$missing_fields_encoded = file_get_contents( $missing_fields_json_path );
			$missing_fields_decoded = json_decode( $missing_fields_encoded, true );
			$missing_fields_decoded['Custom'] = $missing_fields_array;
			fwrite( $jsonFile, json_encode($missing_fields_decoded) );
			fclose( $jsonFile );
			chmod( $missing_fields_json_path, 0777 );

		} else{

			$missing_fields_json = array( 'Custom' => $missing_fields_array );
			$jsonFile = fopen( $missing_fields_json_path, 'w' );
			fwrite( $jsonFile, json_encode($missing_fields_json) );
			fclose( $jsonFile );
			chmod( $missing_fields_json_path, 0777 );
		}*/




		/* -------------------------------------------------------- Saving Missing Fields Ends ----------------------------------------------------- */


		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_amazon_profiles';

		$template_id = isset( $_GET['template_id'] ) ? sanitize_text_field( $_GET['template_id'] ) : '';
		if ( empty( $template_id ) ) {

			$wpdb->insert(
				$tableName,
				array(
					'profile_name'                  => $profileDetails['profile_name'],
					'primary_category'              => $profileDetails['primary_category'],
					'secondary_category'            => $profileDetails['secondary_category'],
					'category_attributes_response'  => '',
					'wocoommerce_category'          => json_encode( $profileDetails['wocoommerce_category'] ),
					'category_attributes_structure' => $profileDetails['category_attributes_structure'],
					'browse_nodes'                  => $profileDetails['browse_nodes'],
					'category_attributes_data'      => $profileDetails['category_attributes_data'],
					'seller_id'                     => $seller_id
				),
				array( '%s' )
			);

		} else {

			$wpdb->update(
				$tableName,
				array(
					'profile_name'                  => $profileDetails['profile_name'],
					'primary_category'              => $profileDetails['primary_category'],
					'secondary_category'            => $profileDetails['secondary_category'],
					'category_attributes_response'  => '',
					'wocoommerce_category'          => json_encode( $profileDetails['wocoommerce_category'] ),
					'category_attributes_structure' => $profileDetails['category_attributes_structure'],
					'browse_nodes'                  => $profileDetails['browse_nodes'],
					'category_attributes_data'      => $profileDetails['category_attributes_data'],
					'seller_id'                     => $seller_id
				),
				array( 'id' => $template_id ),
				array( '%s' )
			);

		}

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_id = str_replace( '|', '%7C', $seller_id );
		wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=profiles-view&user_id=' . $user_id . '&seller_id=' . $seller_id );

	}
}

$current_amazon_profile = array();
$template_id            = isset( $_GET['template_id'] ) ? sanitize_text_field( $_GET['template_id'] ) : '';

if ( ! empty( $template_id ) ) {
	global $wpdb;
	$tableName = $wpdb->prefix . 'ced_amazon_profiles';
	$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $template_id ), 'ARRAY_A' );

	$current_amazon_profile = isset( $result[0] ) ? $result[0] : array();

}


?>

<div class="ced-amazon-v2-header">
	<div class="ced-amazon-v2-logo">
		
	</div>
	<div class="ced-amazon-v2-header-content">
		<div class="ced-amazon-v2-title">

		<?php 

		if ( ! empty( $template_id ) ) { 
			?>
				<h2 style="font-size:2em !important;"><b><?php echo esc_attr_e( 'Edit Template' ); ?></h2>
			   <?php 
		} else { 
			?>
				<h2 style="font-size:2em !important;"><b><?php echo esc_attr_e( 'New Template' ); ?></h2>
				<?php 
		}


		?>
	
		</div>
		<div class="ced-amazon -v2-actions"> 
			<?php
			if ( ! empty( $template_id ) && ( ! isset($current_amazon_profile['template_type']) || 'amazonTemplate' != $current_amazon_profile['template_type'] ) ) {
				?>
				<button class="ced-amazon-v2-btn" id="update_template" > Update Template</button> 
				<?php
			}

			?>

		</div>
	</div>
</div>

<form action="" method="post">
	
	<div class="ced_amazon_profile_details_wrapper">
		<div class="ced_amazon_profile_details_fields">
			<div class="ced_amazon_loader">
				<img src="<?php echo esc_attr( CED_AMAZON_URL ) . 'admin/images/loading.gif'; ?>" width="50px" height="50px" class="ced_amazon_loading_img" >
			</div>
			<table class="profile_section">
				<thead>
					
					<?php

					if ( false ) {
						?>
							<div class="bg-green-200 px-6 py-4 mx-2 my-4 rounded-md text-lg flex items-center  w-3/4 xl:w-2/4">
								<span class="text-green-800"> We’ve saved your profile data. </span>
							</div>
						<?php
					}
					?>
						
				</div>
			</div>
				<?php
				$amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';
				// $shopId           = get_option( 'ced_amazon_sellernext_shop_id', true );
				if ( file_exists( $amzonCurlRequest ) ) {
					require_once $amzonCurlRequest;
					$amzonCurlRequestInstance   = new Ced_Amazon_Curl_Request();
					$amazonCategoryListResponse = $amzonCurlRequestInstance->ced_amazon_get_category( 'webapi/rest/v1/category/?shop_id=' . $user_id, $user_id, $seller_id );

					$decodedAmazonCategoryListResponse = json_decode( $amazonCategoryListResponse, true );
		
					if ( $decodedAmazonCategoryListResponse['status'] ) {
						$amazonCategoryList = $decodedAmazonCategoryListResponse['data'];
					} else {
						echo esc_attr( wp_send_json( $decodedAmazonCategoryListResponse ) );
						die; 
					}
				}

				?>
			</thead>
			<tbody>
				<!-- testing -->

				<tr style='visibility: collapse;'><td><input id='ced_amazon_profile_names' type="hidden" value="" /></td></tr>
				<tr>
					<th colspan="3" class="px-4 mt-4 py-6 sm:p-6 " style="text-align:left;margin:0;">
						<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'Profile Details', 'amazon-integration-for-woocommerce' ); ?></label>
					</th>
				</tr>
				<tr>
					<td>
						<label for="" class="tooltip">Profile Name
							<span class="ced_amazon_wal_required"  ><?php echo esc_attr( '[Required]' ); ?></span>
						</label>
					</td>
					<td>
						<input id="ced_amazon_profile_name" value='<?php echo ! empty( $current_amazon_profile ) ? esc_attr( $current_amazon_profile['profile_name'] ) : ''; ?>' type="text" name="ced_amazon_profile_data[profile_name]" required />
					</td>
				</tr>

				<?php if ( 'amazonTemplate' !== $template_type ) { ?>
				<tr>
					<th colspan="3" class="px-4 mt-4 py-6 sm:p-6 border-t-2 border-green-500" style="text-align:left;margin:0;">
						<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'Amazon Category', 'amazon-integration-for-woocommerce' ); ?></label>
					</th>
				</tr>
				<tr class="" id="amazon_parent_category">

					<td>
						<label for="" class="tooltip">Amazon Primary Category
							<span class="ced_amazon_wal_required"  ><?php echo esc_attr( '[Required]' ); ?></span>
						</label>
					</td>
					<td>
						<select data-level=1 required id="ced_amazon_primary_category_selection" name="ced_amazon_profile_data[primary_category]" class="select short ced_amazon_select_category" style="">
							<?php
							echo '<option value="">' . esc_attr( '-- Select --' ) . '</option>';
							if ( isset( $amazonCategoryList['response'] ) ) {
								foreach ( $amazonCategoryList['response'] as $key => $value ) {
										// $previousValue == $key
									$selected = '';
									if ( ! empty( $current_amazon_profile ) && $current_amazon_profile['primary_category'] == $value ) {
										$selected = 'selected';
									}
									if ( false ) {
										echo '<option value="' . esc_attr( $value['value'] ) . '" selected>' . esc_attr( $value['label'] ) . '</option>';
									} else {
										echo '<option value="' . esc_attr( $value ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( ucfirst( $value ) ) . '</option>';
									}
								}
							}
							?>
						</select>
					</td>

				</tr>

				<?php } ?>

				<tr class="" id="amazon_category_reference"> </tr>

				<tr> 
					<th colspan="3" class="profileSectionHeading" >
						<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'Woocommerce Category', 'amazon-integration-for-woocommerce' ); ?></label>
					</th>
				</tr>

				<tr> 
					<td>
					<label for="id_label_single">
						Select WooCommerce categories to map </td> <td>
						<select style="width: 90%; " class="select2 wooCategories" name="ced_amazon_profile_data[wocoommerce_category][]"  multiple="multiple" required>
							<option>--Select--</option>

							<?php
							
							ced_amazon_nestdiv( $woo_store_categories, $current_amazon_profile, 0, $amazon_wooCategories );

							?>

						</td>
					</select>
						</label>
					
					
				</tbody>
			</table>
			
		</div>
	</div>
	<?php wp_nonce_field( 'ced_amazon_profile_edit_page_nonce', 'ced_amazon_profile_edit' ); ?>
	<div>
		<button class="ced-amazon-v2-btn save_profile_button" name="ced_amazon_profile_save_button" ><?php esc_attr_e( 'Save Profile Data', 'amazon-integration-for-woocommerce' ); ?></button>
	</div>
</form>

<script>
	

	jQuery(document).ready(function() {
		jQuery('.ced_amazon_select_category').selectWoo();

		jQuery(".wooCategories").selectWoo({
			dropdownPosition: 'below',
			dropdownAutoWidth : true,
			allowClear: true,
			width: 'resolve'
		});
	});


</script>


<style>

   body{
	overflow-x: hidden;
   }
   
  .wooCategories + .select2-container--default .select2-selection--multiple {
		border: 1px solid #3c434a;
   }
   
   .ced-amazon-v2-admin-menu{
	   margin-right: 20px !important;
   }
   
   .ced-amazon-v2-header{
		   margin: 10px 20px 20px 2px !important;
   }
</style>
