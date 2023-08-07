<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$ced_amazon_validated_account = get_option( 'ced_amzon_configuration_validated', array() );
if ( is_array($ced_amazon_validated_account) && 5 <= count($ced_amazon_validated_account) ) {
	wp_redirect( get_admin_url() . 'admin.php?page=ced_amazon' );
	exit;
}
$part      = isset( $_GET['part'] ) ? sanitize_text_field( $_GET['part'] ) : false;
$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : false;
$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : false;
if ( empty($user_id ) ) {
	$user_id = $current_amaz_shop_id;
}
$add_new_account                = isset( $_GET['add_new_account'] ) ? sanitize_text_field( $_GET['add_new_account'] ) : false;
$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
if ( !empty( $ced_amazon_sellernext_shop_ids ) && is_array( $ced_amazon_sellernext_shop_ids ) ) {
	$connect_to_amazon['will_connect']  = 'none';
	$connect_to_amazon['did_connected'] = 'block';
} else {
	$connect_to_amazon['will_connect']  = 'block';
	$connect_to_amazon['did_connected'] = 'none';
}
if ( $add_new_account ) {
	$connect_to_amazon['will_connect']  = 'block';
	$connect_to_amazon['did_connected'] = 'none';
}



// $connection_setup           = '';
// $integration_settings_setup = '';
// $amazon_options_setup       = '';
// $general_settings_setup     = '';
// if ( empty( $part ) || 'ced-amazon-login' == $part ) {
// 	$connection_setup = 'active';
// } elseif ( 'amazon-options' == $part ) {
// 	$amazon_options_setup = 'active';
// } elseif ( 'settings' == $part ) {
// 	$general_settings_setup = 'active';
// } elseif ( 'configuration' == $part ) {
// 	$integration_settings_setup = 'active';
// }

$file = CED_AMAZON_DIRPATH . 'admin/partials/amazonRegions.php';
if( file_exists( $file ) ){
    require_once $file;
}


// if ( ( '' == $part || 'ced-amazon-login' == $part ) ) { ?>

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
	</style>

	<div class="woocommerce-progress-form-wrapper" style="display: <?php echo esc_attr( $connect_to_amazon['will_connect'] ); ?>" >
		<h2 style="text-align: left;">Amazon Integration</h2>
		<div class="wc-progress-form-content woocommerce-importer">
			<header>
				<h2>Connect Marketplace</h2>
				<p class="ced_wizard_content" >To get started, connect your Amazon account by Selecting your Amazon Account Region clicking the button. This is only a one time process and all the data is processed and stored on your website.</p>
				<div class="form-field form-required term-name-wrap ced-label-wrap">
					<label for="woocommerce_email_from_address">Seller Email Address</label>
					<input name="tag-name" id="tag-name" class="ced_amazon_seller_email" type="text" value="" placeholder="Enter Address" size="40" aria-required="true" aria-describedby="name-description">
				</div>
				<div class="form-field form-required term-name-wrap ced-label-wrap">
					<label for="tag-name">Amazon Store Region</label>

					<?php

						$ced_amazon_marketplace_data = array(
							array( 
								'region' => 'North America',
								'region_value'  => 'NA',
								'value' => 'CA',
								'country-name' => 'Canada',
								'shop-name' => 'us_ca',
								'end-pt' => 'https://mws.amazonservices.ca/',
								'mp-id' => 'A2EUQ1WTGCTBG2',
								'mp-url' => 'https://www.amazon.ca/'
							),
							array( 
								'region' => 'North America',
								'region_value'  => 'NA',
								'value' => 'MX',
								'country-name' => 'Mexico',
								'shop-name' => 'us_mx',
								'end-pt' => 'https://mws.amazonservices.com.mx/',
								'mp-id' => 'A1AM78C64UM0Y8',
								'mp-url' => 'https://www.amazon.com.mx/' 
							),
							array( 
								'region' => 'North America',
								'region_value'  => 'NA',
								'value' => 'US',
								'country-name' => 'United States',
								'shop-name' => 'us',
								'end-pt' => 'https://mws.amazonservices.com/',
								'mp-id' => 'ATVPDKIKX0DER',
								'mp-url' => 'https://www.amazon.com/'
							),
							array( 
								'region' => 'North America',
								'region_value'  => 'NA',
								'value' => 'BR',
								'country-name' => 'Brazil',
								'shop-name' => 'br',
								'end-pt' => 'https://mws.amazonservices.com/',
								'mp-id' => 'A2Q3Y263D00KWC',
								'mp-url' => 'https://www.amazon.com.br/'
								
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'IN',
								'country-name' => 'India',
								'shop-name' => 'in',
								'end-pt' => 'https://mws.amazonservices.in/',
								'mp-id' => 'A21TJRUUN4KGV',
								'mp-url' => 'https://www.amazon.in/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'DE',
								'country-name' => 'Germany',
								'shop-name' => 'uk_de',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id'  => 'A1PA6795UKMFR9',
								'mp-url' => 'https://www.amazon.de/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value'  => 'ES',
								'country-name' => 'Spain',
								'shop-name' => 'uk_es',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id'  => 'A1RKKUPIHCS9HS',
								'mp-url' => 'https://www.amazon.es/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'FR',
								'country-name' => 'France',
								'shop-name' => 'uk_fr',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'A13V1IB3VIYZZH',
								'mp-url' => 'https://www.amazon.fr/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'GB',
								'country-name' => 'United Kingdom',
								'shop-name' => 'uk',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'A1F83G8C2ARO7P',
								'mp-url' => 'https://www.amazon.co.uk/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'IT',
								'country-name' => 'Italy',
								'shop-name' => 'uk_it',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'APJ6JRA9NG5V4',
								'mp-url' => 'https://www.amazon.it/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'NL',
								'country-name' => 'Netherlands',
								'shop-name' => 'nl',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'A1805IZSGTT6HS',
								'mp-url' => 'https://www.amazon.nl/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'BE',
								'country-name' => 'Belgium',
								'shop-name' => 'be',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'AMEN7PMS3EDWL',
								'mp-url' => 'https://www.amazon.com.be/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'SE',
								'country-name' => 'Sweden',
								'shop-name' => 'se',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'A2NODRKZP88ZB9',
								'mp-url' => 'https://www.amazon.se/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'PL',
								'country-name' => 'Poland',
								'shop-name' => 'uk_pl',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'A1C3SOZRARQ6R3',
								'mp-url' => 'https://www.amazon.pl/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'EG',
								'country-name' => 'Egypt',
								'shop-name' => 'eg',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'ARBP9OOSHTCHU',
								'mp-url' => 'https://www.amazon.eg/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'TR',
								'country-name' => 'Turkey',
								'shop-name' => 'tr', 
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'A33AVAJ2PDY3EV',
								'mp-url' => 'https://www.amazon.com.tr/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'SA',
								'country-name' => 'Saudi Arabia',
								'shop-name' => 'sa',
								'end-pt' => 'https://mws-eu.amazonservices.com/',
								'mp-id' => 'A17E79C6D8DWNP',
								'mp-url' => 'https://www.amazon.sa/'
							),
							array(
								'region' => 'Europe',
								'region_value'  => 'EU',
								'value' => 'AE',
								'country-name' => 'United Arab Emirates',
								'shop-name' => 'uae',
								'end-pt' => 'https://mws.amazonservices.ae/',
								'mp-id' => 'A2VIGQ35RCS4UG',
								'mp-url' => 'https://www.amazon.ae/'
							),
							array(
								'region' => 'Far East region',
								'region_value'  => 'FE',
								'value' => 'AU',
								'country-name' => 'Australia',
								'shop-name' => 'au',
								'end-pt' => 'https://mws.amazonservices.com.au/',
								'mp-id' => 'A39IBJ37TRP1C6',
								'mp-url' => 'https://www.amazon.com.au/'
							),
							array(
								'region' => 'Far East region',
								'region_value'  => 'FE',
								'value' => 'SG',
								'country-name' => 'Singapore',
								'shop-name' => 'sg',
								'end-pt' => 'https://mws-fe.amazonservices.com/',
								'mp-id' => 'A19VAU5U5O7RUS',
								'mp-url' => 'https://www.amazon.sg/'
							),
							array(
								'region' => 'Far East region',
								'region_value'  => 'FE',
								'value' => 'JP',
								'country-name' => 'Japan',
								'shop-name' => 'jp',
								'end-pt' => 'https://mws.amazonservices.jp/',
								'mp-id' => 'A1VC38T7YXB528',
								'mp-url' => 'https://www.amazon.co.jp/'
							),
							


						);

						$saved_amazon_details = get_option('ced_amzon_configuration_validated', array() );
						$saved_amazon_details = array_values($saved_amazon_details);

						$keyToGet          = 'country_name';
						$connectedAccounts = array_column($saved_amazon_details, $keyToGet);
				
						?>
					<!-- <input name="tag-name" id="tag-name" type="text" value="" size="40" placeholder="Select region" aria-required="true" aria-describedby="name-description"> -->
					<select name="ced_amazon_select_marketplace_region" id="ced_amazon_select_marketplace_region" style="width: 100%;" >
											
						<?php
							
							$na_marketplaces = '';
							$eu_marketplaces = '';
							$fe_marketplaces = ''; 

							$ced_amazon_regions = [ 'NA' => 'North America', 'EU' => 'Europe', 'FE' => 'Far East region' ];
						foreach ( $ced_amazon_marketplace_data as $marketplace_id => $marketplace_data ) {
								
							if ( ! in_array( $marketplace_data['country-name'], $connectedAccounts ) ) {
								$option =  '<option value="' . $marketplace_data['value'] . '" country-name="' . $marketplace_data['country-name'] . '" shop-name="' . $marketplace_data['shop-name'] . '" end-pt="' . $marketplace_data['end-pt'] . '" mp-id="' . $marketplace_data['mp-id'] . '" mp-url="' . $marketplace_data['mp-url'] . '">' . $marketplace_data['country-name'] . '</option>';
								if ( 'NA' == $marketplace_data['region_value'] ) {
									$na_marketplaces .= $option;
								}
								if ( 'EU' == $marketplace_data['region_value'] ) {
									$eu_marketplaces .= $option;
								}
								if ( 'FE' == $marketplace_data['region_value'] ) {
									$fe_marketplaces .= $option;
								}
							}
								
						}

							$na_region_html = '<option>-- Select -- </option><optgroup data-attr="NA" label="North America">' . $na_marketplaces . '</optgroup>';
							$eu_region_html = '<optgroup data-attr="EU" label="Europe">' . $eu_marketplaces . '</optgroup>';
							$fe_region_html = '<optgroup data-attr="FE" label="Far East region">' . $fe_marketplaces . '</optgroup>';
							
							print_r($na_region_html);
							print_r($eu_region_html);
							print_r($fe_region_html);
								
						?>
					</select>
				</div>
			</header>
			<div class="wc-actions">
				<button style="float: right;" type="button" class="components-button is-primary ced_amazon_add_account_button">Connect Amazon</button>
			</div>
		</div>
	</div>


	<div class="woocommerce-progress-form-wrapper" style="display: <?php echo esc_attr( $connect_to_amazon['did_connected'] ); ?>" >
		<h2 style="text-align: left;">Amazon Integration Onboarding</h2>
		<div class="wc-progress-form-content">
			<header>
				<h2>Connect Marketplace</h2>
				<p>To get started, connect your Amazon account by Selecting your Amazon Account Region clicking the
					button. This is only a one time process and all the data is processed and stored on your website.
				</p>

				<div id="message" class="updated inline ced-notification-notice">
					<p><strong>🎉 Awesome, your Amazon account is now connected!</strong></p>

					<?php
                        $sellernextShopIds     = get_option( 'ced_amazon_sellernext_shop_ids', array() );
						$sellernextShopIdsKeys = array_keys($sellernextShopIds);
						$latestShopID          = $sellernextShopIdsKeys[ count($sellernextShopIdsKeys) - 1 ];

						$current_marketplace_id = isset( $sellernextShopIds[$latestShopID] ) && isset( $sellernextShopIds[$latestShopID]['marketplace_id'] ) ? $sellernextShopIds[$latestShopID]['marketplace_id'] : '';
						$current_marketplace_name = isset( $ced_amazon_regions_info[$current_marketplace_id] ) && isset( $ced_amazon_regions_info[$current_marketplace_id]['country_name'] ) ? $ced_amazon_regions_info[$current_marketplace_id]['country_name'] : '';
                        
				   ?>
					<div class="ced-account-detail-wrapper">
						<div class="ced-account-details-holder"> 
							<p>Account details:</p>
							<p>Name: Sellernxt<br>Store Region: <?php echo $current_marketplace_name; ?></p>
						</div>
					</div>
					<?php
						$url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon';

					?>
					<p class="ced-link">
						<!-- Not the account you wanted to connect? 
						<a href="<?php echo esc_url($url); ?> ">Reconnect here</a> -->
					</p>
				</div>
			</header>
			<div class="wc-actions">
				<button style="float: right;" type="button" class="components-button is-primary" id="amazon_seller_verification" dta-amz-shop-id = "<?php echo esc_attr( $user_id ); ?>" >Verify and
					continue</button>
			</div>
		</div>
	</div>


	<style type="text/css">
			.ced-notification-notice p {
				margin: 0.5em 0 !important;
				font-size: 13px !important;
			}

			.ced-notification-notice {
				background: #EFF9F1 !important;
			}

			.ced-account-detail-wrapper {
				border: 1px solid #4AB866;
				border-radius: 2px;
				padding: 12px 8px 12px 8px;
			}

			.ced-account-details-holder {
				display: flex;
			}

			.ced-account-details-holder p:first-child {
				font-weight: 500;
				padding-right: 15px;
			}

			.ced-account-details-holder p {
				color: #1E1E1E !important;
				margin: 0 !important;
			}

			.ced-link a {
				text-decoration: none;
			}

	</style>


	<?php

// } elseif ( 'integration_settings' == $part ) {
// 	require_once CED_AMAZON_DIRPATH . 'admin/partials/setup-wizard-settings-view.php';
// }

?>


<!-- <script type="text/javascript">
		jQuery(function () {
			jQuery('.woocommerce-importer-done-view-errors').on('click', function () {
				jQuery('.wc-importer-error-log').slideToggle();
				return false;
			});
		});
</script> -->