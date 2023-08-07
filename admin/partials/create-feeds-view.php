<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

$user_id     = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$seller_id   = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$sub_section = isset( $_GET['sub_section'] ) ? sanitize_text_field( $_GET['sub_section'] ) : '';

if ( isset( $_POST['ced_amazon_profile_edit'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_profile_edit'] ), 'ced_amazon_profile_edit_page_nonce' ) ) {
	if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_amazon_profile_save_button'] ) ) {

		$sanitized_array     = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		$amazon_profile_data = isset( $sanitized_array['ced_amazon_profile_data'] ) ? ( $sanitized_array['ced_amazon_profile_data'] ) : array();

		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : ''; 
		
		$profileDetails = array(
			'profile_name'         => $amazon_profile_data['profile_name'],
			'primary_category'     => isset( $amazon_profile_data['primary_category'] ) ? $amazon_profile_data['primary_category'] : '',
			'secondary_category'   => isset( $amazon_profile_data['secondary_category'] ) ?  $amazon_profile_data['secondary_category'] : '',
			'browse_nodes'         => isset( $amazon_profile_data['browse_nodes'] ) ? $amazon_profile_data['browse_nodes'] : '',
			'wocoommerce_category' => isset( $amazon_profile_data['wocoommerce_category'] ) ? $amazon_profile_data['wocoommerce_category'] : '',
			'template_type' => isset( $amazon_profile_data['template_type'] ) ? $amazon_profile_data['template_type'] : '',
			'file_url'      => isset( $amazon_profile_data['file_url'] ) ? $amazon_profile_data['file_url'] : '',
		);

		$profileDetails['category_attributes_structure'] = json_encode( $amazon_profile_data['ref_attribute_list'] );

		unset( $amazon_profile_data['profile_name'] );
		unset( $amazon_profile_data['primary_category'] );
		unset( $amazon_profile_data['secondary_category'] );
		unset( $amazon_profile_data['browse_nodes'] );
		unset( $amazon_profile_data['ref_attribute_list'] );
		unset( $amazon_profile_data['wocoommerce_category'] );
		unset( $amazon_profile_data['template_type'] );
		unset( $amazon_profile_data['file_url'] );

		$profileDetails['category_attributes_data'] = json_encode( $amazon_profile_data );

		/* -------------------------------------------------------- Saving Missing Fields Starts ----------------------------------------------------- */

		//die('opppppppp');

		$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

		$userData    = $ced_amzon_configuration_validated[ $seller_id ];
		$userCountry = $userData['ced_mp_name'];

		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_amazon_profiles';

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
				'seller_id'                     => $seller_id,
				'file_url'                      => $profileDetails['file_url'],
				'template_type'                 => $profileDetails['template_type']
			),
			array( '%s' )
		);

	}
}

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

$woo_store_categories = ced_amazon_get_categories_hierarchical(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
	)
);

?>


<div class="ced_amazon_create_feed_wrapper" >

	<div class="ced_amazon_left_sidebar" >
		<ul>
			<?php 
				$subMenus = array( 'Upload Template', 'Product Upload' );

			foreach ( $subMenus as $subMenu ) {  
				$active = '';
				if ( empty($sub_section) && 'Upload Template' == $subMenu ) {
					$active = 'ced-amazon-v3-btn';
				} elseif ( $sub_section == $subMenu ) {
					$active = 'ced-amazon-v3-btn';
				} else {
					$active = 'ced-amazon-v2-btn';
				}
				?>
					<li class="<?php echo esc_attr($active); ?> "  > <?php echo esc_attr($subMenu) ; ?></li>                           

					<?php 
					
			}

			?>
		</ul>

	</div>

	<div class="ced_amazon_right_sidebar">
		<div class="ced_amazon_subsection_container" >

			<?php if ( '' == $sub_section || 'upload_template' == $sub_section ) { ?>

					<div class="ced_amazon_upload_template_subsection" >

						<h3> Here you can upload your own XLSM feed template, generated on Seller Central.</h3>
						
						<b> To generate your own feed templates, log in to Seller Central, visit Inventory / Add Products via Upload and open the 'Download an Inventory file' tab.</b>
						<div class="ced_amazon_btn_container">
						   <button type="button" class="ced_amazon_upload_image_button ced-amazon-v2-btn" data-tooltip-content="" > Upload Template </button> 
						</div> 
					</div>

				<?php 
			}
			?>
				<form class="ced_amazon_update_template_form" action="" method="post" style = "display: none" >
	
					<div class="ced_amazon_profile_details_wrapper">
						<div class="ced_amazon_profile_details_fields">
							
							<table class="update_template_body">
								
								<tbody>
									<tr>
										<th colspan="3" class="" style="text-align:left;margin:0;">
											<label style="font-size: 1.25rem;color: #6574cd;">Profile Details</label>
										</th>
									</tr>
									<tr>
										<td>
											<label for="" class="">Profile Name
												<span class="ced_amazon_wal_required">[Required]</span>
											</label>
										</td>
										<td>
											<input id="ced_amazon_profile_name" value="" type="text" name="ced_amazon_profile_data[profile_name]" required="">
										</td>
									</tr>

									<tr class="" id="amazon_category_reference"> </tr>

									<tr> 
										<th colspan="3" class="profileSectionHeading" >
											<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'Woocommerce Category', 'amazon-integration-for-woocommerce' ); ?></label>
										</th>
									</tr>

									<tr> 

										<td>
											<label for="id_label_single">
												Select WooCommerce categories to map
											</label>	
										</td>

										<td>  
											<select style="width:100%;" class="select2 wooCategories" name="ced_amazon_profile_data[wocoommerce_category][]"  multiple="multiple" required>
												<option>--Select--</option>
												<?php
													ced_amazon_nestdiv( $woo_store_categories, array(), 0, $amazon_wooCategories );
												?>
											</select>
										</td>		
									</tr>	

								</tbody>
							</table>


							<footer class='ced-amazon-wizard-footer'>
								<div class="ced-amazon-wlcm__btn-wrap"> 
									<?php wp_nonce_field( 'ced_amazon_profile_edit_page_nonce', 'ced_amazon_profile_edit' ); ?>
									<button class="ced-amazon-v2-btn save_profile_button" name="ced_amazon_profile_save_button" ><?php esc_attr_e( 'Save Profile Data', 'amazon-integration-for-woocommerce' ); ?></button>
								</div>
							</footer>	

						</div>
					</div>
					
				</form>		



		</div>
	</div>
	

</div>

<style>

	.ced_amazon_create_feed_wrapper{
		border: 1px solid #c1b9b9; 
		min-height: 600px; 
		margin-top: 1rem;
		width: 100%;
		background: white;
		border-radius: 5px;
		margin-right: 20px;
		display: flex;
	}

	.ced_amazon_left_sidebar{
		width: 20%; 
		border-right: 1px solid #c1b9b9;
	}

	.ced_amazon_right_sidebar{
		padding: 15px;
		width: 80%;
	}

	.ced-amazon-v3-btn{
		text-decoration: none !important;
		padding: 10px 15px;
		background-color: #ffffff;
		border-radius: 5px !important;
		margin: 0 5px;
		/* justify-self: right; */
		color: #5850ec !important;
		font-weight: 500;
		text-transform: uppercase;
		border: 1px solid;
	
	}

	#wpbody-content {
		background: #dddddd;
	}

	.ced_amazon_left_sidebar ul li{
		margin-bottom: 10px !important; 
	}

	.ced_amazon_right_sidebar{
		padding: 15px;
	}

	
	.ced_amazon_upload_template_subsection{
		border: 2px solid #767676;
		border-radius: 7px;
		margin: 5rem auto;
		padding: 40px;
		text-align: center;
		font-family: sans-serif;
		width: 550px;
		margin-top: 5rem;
		margin-bottom: auto;
	}

	.ced_amazon_btn_container{
		margin: 35px auto 0;
	}

	.ced_amazon_profile_details_fields table {
		
		background-color: #ffffff00 !important;

	}

	.save_profile_button{
		float: right;
	}

</style>
