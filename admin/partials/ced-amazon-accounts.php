<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}
if ( isset( $_GET['part'] ) ) {
	$file = CED_AMAZON_DIRPATH . 'admin/partials/' . sanitize_text_field( $_GET['part'] ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
	wp_die();
}

$part                           = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';
$user_id                        = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );

$contract_id = isset( $_GET['contract_id'] ) ? sanitize_text_field( $_GET['contract_id'] ) : '';

if ( empty($contract_id) ) {
	$contract_id = get_option( 'ced_amazon_contract_id', '');

} else {
	update_option( 'ced_amazon_contract_id', $contract_id );
}


$subscriptionVerified = 0;

if ( !empty($contract_id) ) {

	$amzonBilling = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-billing-apis.php';

	if ( file_exists( $amzonBilling ) ) { 
		require_once $amzonBilling;
		$amzonBilling = new Billing_Apis();
	} 

	$currentPlan = $amzonBilling->getAmazonPlanById($contract_id);

	//$currentPlan['status'] = false;

	if ( isset( $currentPlan['status'] ) && $currentPlan['status'] ) {

		$responseBody       = $currentPlan['data'];
		$subscriptionStatus = isset($responseBody['status']) ? $responseBody['status'] : '';
		
		$end_date   = $responseBody['end_date'];
		$timestamp1 = strtotime($end_date);
		
		$currentTimestamp = time();
		$currentDateTime  = gmdate('Y-m-d H:i:s', $currentTimestamp);
		
		$timestamp2 = strtotime($currentDateTime);


		if ( 'active' == $subscriptionStatus || ( 'canceled' == $subscriptionStatus && $timestamp1 > $timestamp2 ) ) {
			$subscriptionVerified = 1;
		} else { 
			
			// echo 
			require_once CED_AMAZON_DIRPATH . 'admin/partials/purchase-subscription.php';
			die;
			
			?>

			 <!-- <div class="ced-amazon-bootstrap-wrapper" >
				  <div class="jumbotron ced_subscription_warning" >
					  <h1 class="display-4">Hello, User!</h1>
					  <p class="lead">At this mome3nt we are unable to load your current plan details, please Refresh the page or contact support.</p>
					  <hr class="my-4">
	
					  <p class="lead">
						 <a class="btn btn-primary btn-lg" href="#" role="button" onclick="history.back()" >Go Back</a>
					  </p>
				  </div>
			   </div> -->
		
		<?php    
		}

	} else { 

		require_once CED_AMAZON_DIRPATH . 'admin/partials/purchase-subscription.php';
		die;


		?>

		 <!-- <div class="ced-amazon-bootstrap-wrapper" >
			 <div class="jumbotron ced_subscription_warning"  >
				 <h1 class="display-4">Hello, User!</h1>
				 <p class="lead">At this moment we are unable to verfiy your subscription, please Refresh the page or contact support.</p>
				 <hr class="my-4">

				<p class="lead">
				   <a class="btn btn-primary btn-lg" href="#" role="button">Learn more</a>
				</p>
			 </div>
		 </div> -->
		 


<?php
		die;
	}

}

// var_dump($subscriptionVerified); die;
if ( ! $subscriptionVerified ) {
	require_once CED_AMAZON_DIRPATH . 'admin/partials/purchase-subscription.php';
	die;

} elseif ( isset( $_GET['section'] ) ) {
	$file = CED_AMAZON_DIRPATH . 'admin/partials/' . sanitize_text_field( $_GET['section'] ) . '.php';
	if ( file_exists( $file ) ) {
		require_once $file;
	}
} else {
	
	if ( ! session_id() ) {
		session_start();
	}
	$create_user_response = get_option( 'ced_amazon_sellernext_user_creation_response', array() );
	$user_name            = isset( $create_user_response['email'] ) ? $create_user_response['email'] : 'User';
	$sellernextShopIds    = get_option( 'ced_amazon_sellernext_shop_ids', array() );
	$current_step         = isset( $sellernextShopIds[$user_id] ) && isset( $sellernextShopIds[$user_id]['ced_amz_current_step'] ) ? $sellernextShopIds[$user_id]['ced_amz_current_step'] : '';
	
	
	?>
		

		<div class="ced-amazon-login-dashboard-wrapper">
			<div class="ced-amazon-login-dashboard">
				<div class="ced-amazon-wrap-login">
					<div class="ced-amazon-common-wrap-head">
						<h1>Amazon Integration for WooCommerce</h1>
					</div>
					<div class="ced-amazon-user-container">
						<div class="ced-amazon-user-holder">
							
							<?php
							if ( ! empty( $ced_amazon_sellernext_shop_ids ) && is_array( $ced_amazon_sellernext_shop_ids ) ) {
								?>
								
								<div class="admin-custom-action-button-outer">
									<div class="admin-custom-action-show-button-outer">
										<div id="ced_amazon_add_new_template_btnw" class="buttons btn-normal-ttq">
											<span><a id="ced_amazon_select_current_marketplacea" href="<?php echo esc_attr(get_admin_url()) . 'admin.php?page=ced_amazon&amp;section=setup-amazon&add_new_account=1'; ?>">Add New Account</a></span>
										</div>
									</div>
								</div>
								
								<div class="ced-amazon-user-head">
									<h3>Hello <?php echo esc_html( $user_name ) . '!'; ?></h3>
									<p >You have Successfully Connected your Amazon Store</p>
									<p >
										Get Started with Listing, Syncing, Managing & Automating your WooCommerce and Amazon Store to Boost Sales.
									</p>
								</div>
								<div class="ced-amazon-button-wrap">
									<select style="font-size:1rem;" name="ced_amazon_select_active_marketplaces_region" id="ced_amazon_select_active_marketplaces_region">
										<option value="">Select your Amazon marketplace</option>
										<?php
											$na_marketplaces    = '';
											$eu_marketplaces    = '';
											$fe_marketplaces    = ''; 
											$ced_amazon_regions = array(
											'A2EUQ1WTGCTBG2' => array(
												'country_name'  => 'Canada', 
												'country_value' => 'CA',
												'region_name'   => 'North America',
												'region_value'  => 'NA',
												'shop-name'     => 'us_ca'
											),
											'A1AM78C64UM0Y8' => array( 
												'country_name'  => 'Mexico', 
												'country_value' => 'MX',
												'region_name'   => 'North America',
												'region_value'  => 'NA',
												'shop-name'     => 'us_mx'
											),
											'ATVPDKIKX0DER' => array(
												'country_name'  => 'United States', 
												'country_value' => 'US',
												'region_name'   => 'North America',
												'region_value'  => 'NA',
												'shop-name'     => 'us'
											),
											'A2Q3Y263D00KWC' => array(
												'country_name'  => 'Brazil', 
												'country_value' => 'BR',
												'region_name'   => 'North America',
												'region_value'  => 'NA',
												'shop-name'     => 'br'
											),
											'A21TJRUUN4KGV' => array(
												'country_name'  => 'India', 
												'country_value' => 'IN',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'in'
											),
											'A1PA6795UKMFR9' => array(
												'country_name'  => 'Germany', 
												'country_value' => 'DE',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'uk_de'
											),
											'A1RKKUPIHCS9HS' => array(
												'country_name'  => 'Spain', 
												'country_value' => 'ES',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'uk_es'
											),
											'A13V1IB3VIYZZH' => array(
												'country_name'  => 'France', 
												'country_value' => 'FR',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'uk_fr'
											),
											'A1F83G8C2ARO7P' => array(
												'country_name'  => 'United Kingdom', 
												'country_value' => 'GB',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'uk'
											),
											'APJ6JRA9NG5V4' => array(
												'country_name'  => 'Italy', 
												'country_value' => 'IT',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'uk_it'
											),
											'A1805IZSGTT6HS' => array(
												'country_name'  => 'Netherlands', 
												'country_value' => 'NL',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'nl'
											),
											'AMEN7PMS3EDWL' => array(
												'country_name'  => 'Belgium', 
												'country_value' => 'BE',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'be'
											),
											'A2NODRKZP88ZB9' => array(
												'country_name'  => 'Sweden', 
												'country_value' => 'SE',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'se'
											),
											'A1C3SOZRARQ6R3' => array(
												'country_name'  => 'Poland', 
												'country_value' => 'PL',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'uk_pl'
											),
											'ARBP9OOSHTCHU' => array(
												'country_name'  => 'Egypt', 
												'country_value' => 'EG',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'eg'
											),
											'A33AVAJ2PDY3EV' => array(
												'country_name'  => 'Turkey', 
												'country_value' => 'TR',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'tr'
											),
											'A17E79C6D8DWNP' => array(
												'country_name'  => 'Saudi Arabia', 
												'country_value' => 'SA',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'sa'
											),
											'A2VIGQ35RCS4UG' => array(
												'country_name'  => 'United Arab Emirates', 
												'country_value' => 'AE',
												'region_name'   => 'Europe',
												'region_value'  => 'EU',
												'shop-name'     => 'uae'
											),
											'A39IBJ37TRP1C6' => array(
												'country_name'  => 'Australia', 
												'country_value' => 'AU',
												'region_name'   => 'Far East region',
												'region_value'  => 'FE',
												'shop-name'     => 'au'
											),
											'A19VAU5U5O7RUS' => array(
												'country_name'  => 'Singapore', 
												'country_value' => 'SG',
												'region_name'   => 'Far East region',
												'region_value'  => 'FE',
												'shop-name'     => 'sg'
											),
											'A1VC38T7YXB528' => array(
												'country_name'  => 'Japan', 
												'country_value' => 'JP',
												'region_name'   => 'Far East region',
												'region_value'  => 'FE',
												'shop-name'     => 'jp'
											)
										);


											foreach ( $ced_amazon_sellernext_shop_ids as $sellernext_shop_id => $sellernextData ) {
												$regionData = array_key_exists( $sellernextData['marketplace_id'], $ced_amazon_regions ); 
												if ( $regionData ) {
													$seller_id  = isset($sellernextData['ced_mp_seller_key']) ? $sellernextData['ced_mp_seller_key'] : '';
													$regionData = $ced_amazon_regions[ $sellernextData['marketplace_id'] ];
													$option     = '<option value="' . $regionData['country_value'] . '" country-name="' . $regionData['country_name'] . '" shop-name="' . $regionData['shop-name'] . '" mp-id="' . $sellernextData['marketplace_id'] . '" seller-id="' . $seller_id . '" sellernext-shop-id="' . $sellernext_shop_id . '" > ' . $regionData['country_name'] . ' </option>';
													if ( 'NA' == $regionData['region_value'] ) {
														$na_marketplaces .= $option;
													}
													if ( 'EU' == $regionData['region_value'] ) {
														$eu_marketplaces .= $option;
													}
													if ( 'FE' == $regionData['region_value'] ) {
														$fe_marketplaces .= $option;
													}
												}
											}

										$na_region_html = '<optgroup data-attr="NA" label="North America">' . $na_marketplaces . '</optgroup>';
										$eu_region_html = '<optgroup data-attr="EU" label="Europe">' . $eu_marketplaces . '</optgroup>';
										$fe_region_html = '<optgroup data-attr="FE" label="Far East region">' . $fe_marketplaces . '</optgroup>';
										
										print_r($na_region_html);
										print_r($eu_region_html);
										print_r($fe_region_html);
										
											?>
									</select>

									<a id="ced_amazon_select_current_marketplace" class="" seller-id="" style="display: none;"  current-url="<?php echo esc_attr( 'admin.php?page=ced_amazon' ); ?>">
										Click Here to Get Started
									</a>

									<button id="ced_amazon_disconnect_account_btn" data-attr="" seller-id="" ype="button" class="" style="display: none;">
									Disconnect Account
									</button>
								</div>
								

							<?php } else { ?>

									<div class="ced-amazon-user-head">
										<h3>Hello User</h3>
										<p>You have not connected your Amazon store. Click the button below </p>
									</div>
									<div class="ced-amazon-button-wrap ced-amazon-login-btn-cont">
										<div class="ced-login-button">
											<a href=<?php echo esc_attr( 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&' . $part ); ?> >Click here to get Started</a>
										</div>
									</div>

							

								<?php 

								   // wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&user_id=' . $user_id . '&seller_id=' . $seller_id );    
								   wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon' );


							
							} 
							?>
								
					
					</div>
					</div>
				</div>
			</div>
		</div>

	<?php 
	   //delete_option( 'ced_amazon_contract_id');
	if ( isset( $_GET['cedinfo'] ) ) {
		echo '<pre>';
		print_r( get_option('ced_amzon_configuration_validated', true) );
		echo '</pre>';
		echo '<pre>';
		print_r( get_option('ced_amazon_sellernext_shop_ids', true) );
		echo '</pre>';
	}
}
?>
<style>
	#wpfooter{
		display: none;
	}
	
	.ced_subscription_warning{
		
		background-color: #e8e8e8 !important;
		margin: 7rem auto;
		align-items: center;
		padding: 2rem;
		text-align: center;
		box-shadow: 0 2px 6px 0 rgba(67, 89, 113, 0.12);
		width: 600px;
		border: 0 solid #d9dee3;
		background: #fff;
		border-radius: 5px;
	}
	
	.admin-custom-action-button-outer .admin-custom-action-show-button-outer button#ced_amazon_add_new_template_btn{
		color: #000;
		text-decoration: none;
		padding: 16px 18px;
		background: #FFC638;
		margin-top: 20px;
		width: 50%;
		font-weight: bold;
		border: 2px solid #FFC638;
	}
	.admin-custom-action-button-outer .admin-custom-action-show-button-outer button#ced_amazon_add_new_template_btn:hover{
		background: transparent;
		transition: 0.3s all;
		color: #fff;
	}
	
</style>
