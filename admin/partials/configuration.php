<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$user_id                       = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
$part                          = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : '';
$configuration_validated_array = get_option( 'ced_amzon_configuration_validated', array() );
$sellernextShopIds             = get_option( 'ced_amazon_sellernext_shop_ids', array() );
$participate_accounts          = isset( $sellernextShopIds[$user_id] ) && isset( $sellernextShopIds[$user_id]['marketplaces_participation'] ) ? $sellernextShopIds[$user_id]['marketplaces_participation'] : array();
$current_step                  = isset($sellernextShopIds[$user_id]) && isset( $sellernextShopIds[$user_id]['ced_amz_current_step'] ) ? $sellernextShopIds[$user_id]['ced_amz_current_step'] : '';
$seller_id                     = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : false;
$seller_id                     = str_replace('|', '%7C', $seller_id);

if ( isset( $participate_accounts) && !empty( $participate_accounts ) ) {
	$configuration_validated_array = array_values( $configuration_validated_array ); 
	$configuration_validated_array = $configuration_validated_array[0];
	$shop_loc                      = $configuration_validated_array['ced_mp_name'];

	$sellernextShopIds                                   = get_option( 'ced_amazon_sellernext_shop_ids', array() );
	$sellernextShopIds[$user_id]['ced_amz_current_step'] = 4;
	update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );

	wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=overview&user_id=' . $user_id . '&seller_id=' . $seller_id );
}
 
if ( ! empty( $user_id  ) ) {
	$sellernextShopIds        = get_option( 'ced_amazon_sellernext_shop_ids', array() );
	$marketplace_id           = isset( $sellernextShopIds[$user_id] ) ? $sellernextShopIds[$user_id]['marketplace_id'] : '';
	$ced_amazon_accounts_data = get_option( 'ced_amazon_account_data', array() );
	$access_token             = isset( $ced_amazon_accounts_data[$marketplace_id] ) && isset( $ced_amazon_accounts_data[$marketplace_id]['seller_next_access_token'] ) ? $ced_amazon_accounts_data[$marketplace_id]['seller_next_access_token'] : array();
	$amz_connected_accounts   = wp_remote_get(
		'https://amazon-sales-channel-api-backend.cifapps.com/webapi/rest/v1/connected-accounts/?remote_shop_id[]=' . $user_id,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
			),
		)
	);

	$all_data = get_option( 'ced_amazon_account_data', true );
	if ( ! empty( $amz_connected_accounts['body'] ) ) {
		// $amz_connected_accounts_data = json_decode( $amz_connected_accounts['body'], true );
		// $amz_connected_data = $amz_connected_accounts_data['data'][0];
		$amz_connected_accounts_data = json_decode( $amz_connected_accounts['body'], true );
		if ( '1' == $amz_connected_accounts_data['success'] ) {
			$amz_connected_data              = isset( $amz_connected_accounts_data['data'][0] ) ? $amz_connected_accounts_data['data'][0]['apps'][0] : array();
			$refresh_token                   = !empty( $amz_connected_data ) ? $amz_connected_data['refresh_token'] : '' ;
			$config_array                    = array(
				'ced_umb_amazon_marketplace_configuration'   => 1,
				'key_id'                                     => $amz_connected_data['access_key'],
				'merchant_id'                                => $amz_connected_data['seller_id'],
				'secret_key'                                 => $amz_connected_data['secret_key'],
				'auth_token'                                 => $amz_connected_data['mws_auth_token'],
				'spapi_oauth_code'                           => $amz_connected_data['spapi_oauth_code'],
				'seller_next_shop_id'                        => $user_id,
				'amazon_refresh_token'                       => $amz_connected_data['refresh_token'],
				'amazon_access_token'                        => $amz_connected_data['access_token'],
				'ced_umb_amazon_validate_marketplace_config' => 'Validate',
			); 
			$ced_amazon_data_store           = $all_data[ $amz_connected_data['marketplace_id'] ];
			$final_amazon_configuration      = array_merge( $ced_amazon_data_store, $config_array );
			$config_array_key                = $final_amazon_configuration['ced_mp_name'] . '|' . $final_amazon_configuration['merchant_id'];
			$final_amazon_configuration_save = array();
			$final_amazon_configuration_save[ $config_array_key ] = $final_amazon_configuration;
			$configuration_validated_array                        = get_option( 'ced_amzon_configuration_validated', array() );
			if ( is_array( $configuration_validated_array ) && ! empty( $configuration_validated_array ) ) {
				$configuration_validated_array = array_replace( $configuration_validated_array, $final_amazon_configuration_save );
			} else {
				$configuration_validated_array = $final_amazon_configuration_save;
			}
			update_option( 'ced_amzon_configuration_validated', $configuration_validated_array );
			unset( $all_data[$amz_connected_data['marketplace_id'] ] ); 
			update_option('ced_amazon_account_data', $all_data );
		} else {
			$refresh_token = '';
		}
		// if ( '1' == $amz_connected_accounts_data['success'] ) {
		// 	$refresh_token = $amz_connected_accounts_data['data'][0]['refresh_token'];
		// }
	}
}

if ( ! isset( $refresh_token ) ) {
	$refresh_token = '';
}

if ( isset( $_POST['ced_amazon_configuration_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_configuration_nonce'] ), 'ced_amazon_configuration_page_nonce' ) ) {
	if ( isset( $_POST['back_to_login'] ) ) {
		delete_option( 'ced_amz_redirect_token_data' );
		wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon' );
		wp_die();
	}
}

$part                       = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : false;
$connection_setup           = '';
$integration_settings_setup = '';
$amazon_options_setup       = '';
$general_settings_setup     = '';

if ( 'ced-amazon-login' == $part || '' == $part ) {
	$connection_setup = 'active';
} elseif ( 'configuration' == $part ) {
	$integration_settings_setup = 'active';
} elseif ( 'amazon-options' == $part ) {
	$amazon_options_setup = 'active';
} elseif ( 'settings' == $part ) {
	$general_settings_setup = 'active';
}

$configuration_validated_array = get_option( 'ced_amzon_configuration_validated', array() );
$sellerDetails                 = $configuration_validated_array[ $config_array_key ];
$shop_id                       = $sellerDetails['seller_next_shop_id'];
$merchant_id                   = $sellerDetails['merchant_id'];
$refresh_token                 = $sellerDetails['amazon_refresh_token'];
$marketplace_id                = $sellerDetails['marketplace_id'];
$ced_mp_name                   = $sellerDetails['ced_mp_name'];
$sellernextShopIds             = get_option( 'ced_amazon_sellernext_shop_ids', array() );
$amzonCurlRequest              = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';

if ( file_exists( $amzonCurlRequest ) ) {
	require_once $amzonCurlRequest;
	$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
} else {
	return;
}

$payload             = $amzonCurlRequestInstance->getMarketplaceParticipations( $refresh_token, $marketplace_id, $merchant_id );
$payload             = isset( $payload['payload'] ) ? json_decode( json_encode( $payload['payload'] ), true ) : array();
$accountData         = array();
$sellerParticipation = false;



if ( ! empty( $payload ) ) {
	foreach ( $payload as $index => $accountsConnected ) {
		if ( $accountsConnected['marketplace']['id'] == $marketplace_id ) {
			$accountData         = $accountsConnected;
			$sellerParticipation = isset($accountsConnected['participation']['isParticipating']) ? $accountsConnected['participation']['isParticipating'] : false;
			// $sellerParticipation      = $accountsConnected['marketplace']['id'];
			// update_option( 'ced_amazon_marketplaces_participation_' . $ced_mp_name, $accountsConnected['marketplace']['id'] );
			$current_mp_participation = array( $config_array_key => $sellerParticipation);
			if ( is_array($participate_accounts) && !empty($participate_accounts) ) {
				$participate_accounts = array_replace($participate_accounts, $current_mp_participation);
			} else {
				$participate_accounts = $current_mp_participation;
			}
			$sellernextShopIds[$user_id]['marketplaces_participation'] = $participate_accounts;
			$sellernextShopIds[$user_id]['ced_amz_current_step']       = 4;
			update_option( 'ced_amazon_sellernext_shop_ids', $sellernextShopIds );
			//  update_option( 'ced_amazon_marketplaces_participation', $participate_accounts );
		}
	}
}

$create_user_response = get_option( 'ced_amazon_sellernext_user_creation_response', array() );
// Save user data in log file start
$log_username            = isset( $create_user_response['username'] ) ? $create_user_response['username'] : '';
$log_user_email          = isset( $create_user_response['email'] ) ? $create_user_response['email'] : '';
$log_user_shop_id        = isset( $sellerDetails['seller_next_shop_id'] ) ? $sellerDetails['seller_next_shop_id'] : '';
$log_user_seller_id      = isset( $sellerDetails['merchant_id'] ) ? $sellerDetails['merchant_id'] : '';
$log_user_marketplace_id = isset( $sellerDetails['marketplace_id'] ) ? $sellerDetails['marketplace_id'] : '';
$log_user_setup_date     = gmdate( 'Y-m-d\Th:i:s' );
// Prepare user log data
$user_log_detail  = "\n******************************************************************\n";
$user_log_detail .= 'User info :: ';
$user_log_detail .= $log_user_marketplace_id;
$user_log_detail .= "\n";
$user_log_detail .= 'Username: ';
$user_log_detail .= $log_username;
$user_log_detail .= "\n";
$user_log_detail .= 'Email: ';
$user_log_detail .= $log_user_email;
$user_log_detail .= "\n";
$user_log_detail .= 'Seller Id: ';
$user_log_detail .= $log_user_seller_id;
$user_log_detail .= "\n";
$user_log_detail .= 'Marketplace Id: ';
$user_log_detail .= $log_user_marketplace_id;
$user_log_detail .= "\n";
$user_log_detail .= 'Shop Id: ';
$user_log_detail .= $log_user_shop_id;
$user_log_detail .= "\n";
$user_log_detail .= 'Account setup datetime: ';
$user_log_detail .= $log_user_setup_date;
$user_log_detail .= "\n******************************************************************\n\n\n\n\n";
$user_log_file    = 'ced-user.txt';
ced_amazon_log_data( $user_log_detail, $user_log_file );
// Save user data in log file end
?>


<style type="text/css">
		.ced-progress li {
			width: 33% !important;
		}
	</style>
	<div class="woocommerce-progress-form-wrapper">
		<h2 style="text-align: left;">Amazon Integration Onboarding</h2>
		<ol class="wc-progress-steps ced-progress">
			<li class="done">Global Options</li>
			<li class="done">General Settings</li>
			<li class="active">Done!</li>
		</ol>
		<div class="wc-progress-form-content woocommerce-importer">
			<header style="text-align: center;">
			  <?php
				$amazon_icon =  CED_AMAZON_URL . 'admin/images/success.jpg';
				
				?>
				<img style="width: 15%;" src="<?php echo esc_url( $amazon_icon ); ?>" alt="">
				<p><strong>Onboarding successfully completed!</strong></p>
			</header>
			<div class="wc-actions">
				<?php 
				wp_nonce_field( 'ced_amazon_configuration_page_nonce', 'ced_amazon_configuration_nonce' ); 
				if ( ! empty( $payload ) && is_array( $payload ) ) { 
					?>
						<a class="components-button is-primary" style="float: right;" data-attr='4' id="ced_amazon_continue_wizard_button" href="<?php esc_attr_e( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=overview&user_id=' . $user_id . '&seller_id=' . $seller_id  ) ); ?>" >Go to Overview</a>
					<?php } else { ?>
							<button class="config_button components-button is-primary" style="float: right;" name="back_to_login" > Back To Login </button>
					<?php } ?>
				
			</div>
		</div>
	</div>
