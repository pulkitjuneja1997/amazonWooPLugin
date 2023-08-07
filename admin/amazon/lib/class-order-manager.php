<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Amazon order manager file.
 *
 * @since      1.0.0
 *
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/admin/amazon/lib
 */

if ( ! class_exists( 'Ced_Umb_Amazon_Order_Manager' ) ) :

	/**
	 * Order related functionalities.
	 *
	 * @since      1.0.0
	 * @package    Amazon_Integration_For_Woocommerce
	 * @subpackage Amazon_Integration_For_Woocommerce/admin/amazon/lib
	 * @link       http://www.cedcommerce.com/
	 */
	class Ced_Umb_Amazon_Order_Manager {

		/**
		 * The Instace of Ced_Umb_Amazon_Feed_Manager.
		 *
		 * @since    1.0.0
		 * @var      $_instance   The Instance of Ced_Umb_Amazon_Order_Manager class.
		 */
		private static $_instance;

		/**
		 * Ced_Umb_Amazon_Feed_Manager Instance.
		 *
		 * Ensures only one instance of Ced_Umb_Amazon_Order_Manager is loaded or can be loaded.
		 *
		 * @since 1.0.0
		 * @static
		 * @return Ced_Umb_Amazon_Order_Manager instance.
		 * @link  http://www.cedcommerce.com/
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Constructor.
		 *
		 * @name __construct()
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public function __construct() {
			// process order.
		}


		/**
		 * Enqueue scripts.
		 *
		 * @name enqueue_scripts()
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public function enqueue_scripts() {

		}

		/**
		 * Get order info.
		 *
		 * @name get_marketplace_info()
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public function get_marketplace_info( $order_id = '' ) {

			if ( ! is_null( $order_id ) ) {
				$order = wc_get_order( $order_id );
				if ( is_wp_error( $order ) ) {
					return false;
				} elseif ( '' == $order ) {
					return false;
				} else {

					$order_from  = get_post_meta( $order_id, '_umb_marketplace', true );
					$marketplace = strtolower( $order_from );
					return $marketplace;
				}
			}
		}

		/**
		 * Meta boxes for managing the orders at woo order page.
		 *
		 * @name add_meta_boxes()
		 * @since 1.0.
		 * @link  http://www.cedcommerce.com/
		 */
		public function add_meta_boxes() {
			global $post;

			$post_type   = get_post_type( $post );
			$order_types = wc_get_order_types();

			if ( in_array( $post_type, $order_types ) ) {
				add_meta_box( 'ced-amazon-order-manager', __( 'Manage Amazon Orders', 'ced-amazon' ) . wc_help_tip( __( 'Please send shipping confirmation or order cancellation request.', 'ced-amazon' ) ), array( $this, 'ced_amazon_order_manager_box' ) );
			}
		}

		/**
		 * Order meta box at woo order page.
		 *
		 * @name order_manager_box()
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public function ced_amazon_order_manager_box() {
			global $post;
			$order_id = isset( $post->ID ) ? intval( $post->ID ) : '';
			if ( ! is_null( $order_id ) ) {
				$order = wc_get_order( $order_id );
				if ( ! is_wp_error( $order ) && '' !== $order ) {

					$order_from  = get_post_meta( $order_id, '_umb_marketplace', true );
					$marketplace = strtolower( $order_from );

					$template_path = CED_AMAZON_DIRPATH . 'admin/helper/order_template.php';

					if ( file_exists( $template_path ) ) {
						require_once $template_path;
					}
				}
			}
		}


		/**
		 * This function save order details in custom table
		 *
		 * @name save_order_listing
		 * @since 1.0.0
		 */
		public function save_order_listing( $orderMeta = array() ) {
			
		}


		/**
		 * This function to fetch order from amazon seller panel
		 *
		 * @name fetchOrders
		 * @since 1.0.0
		 */
		public function fetchOrders( $mplocation = '', $cron = true, $amazon_order_id = '', $seller_mp_key = '' ) {


			// Log file name
			$log_date = gmdate( 'Y-m-d' );
			$log_time = strtotime( gmdate( 'H:i:s' ) );
			//$log_name = "order_api_" . $log_date . '_' . $log_time .".txt";
			$log_name = 'order_api_' . $log_date . '.txt';

			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Mplocation or seller id is missing while order sync! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'order' );
				return;
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_mp_key;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			if ( empty( $shop_data ) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Seller API is missing. \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'order' );
				return;
			}

			$time_limit = get_option( 'ced_umb_amazon_order_sync_time_limit' );
			if ( empty( $time_limit ) || $time_limit <= 0 ) {
				$time_limit = '-24 hours';
			} else {
				$time_limit = "-$time_limit hours";
			}

			$refresh_token    = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region           = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id   = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$amz_order_status = array( 'Unshipped', 'PartiallyShipped', 'Shipped' );

			$time_limit = gmdate( 'Y-m-d\Th:i:s\Z', strtotime( $time_limit ) );

			// $time_limit = $this->generateTime($time_limit); //ISO 8601 format i.e. 2021-07-15T12:24:02

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $time_limit ) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Refresh token/marketplace id/time limit are missing. \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'order' );
				return;
			}

			//Check if order next token is save in option
			$next_token = get_option( 'ced_amazon_fetch_order_next_token_' . $mplocation );
			if ( empty($next_token) ) {
				$next_token = null;
			}

			$marketplace_ids = array( $marketplace_id );

			$directorypath = plugin_dir_path( __FILE__ );

			try {

				// ini_set('max_execution_time', 600);
				// ini_set('memory_limit',-1);

				set_time_limit( 600 );
				wp_raise_memory_limit( -1 );

				// Order list
				$order_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/orders';
				$order_body     = array(
					'marketplace_id'     => $marketplace_ids,
					'token'              => $refresh_token,
					'last_updated_after' => $time_limit,
					'order_statuses'     => array( 'Unshipped', 'PartiallyShipped', 'Shipped' ),
					'next_token'         => $next_token,
					'contract_id'      => get_option('ced_amazon_contract_id', '')
				);
				$order_body     = wp_json_encode( $order_body );

				$order_options = array(
					'body'    => $order_body,
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'timeout' => 200,
				);

				$order_reponse_main = wp_remote_post( $order_endpoint, $order_options );
				if ( is_wp_error( $order_reponse_main ) ) {
					// Save error in log
					ced_amazon_log_data( $order_reponse_main, $log_name, 'order' );
					return;
				}

				$order_reponse = json_decode( $order_reponse_main['body'], true );
				if ( isset($order_reponse['success']) && 'false' == $order_reponse['success'] ) {
					// Save error in log
					ced_amazon_log_data( $order_reponse_main, $log_name, 'order' );
					return;
				}

				$orderlists = $order_reponse['payload']['Orders'];

				//Save next token for order fetch (when order response are more than 100)
				if ( isset($order_reponse['payload']['NextToken']) ) {
					$order_next_token = $order_reponse['payload']['NextToken'];
				} else {
					$order_next_token = '';
				}
				update_option( 'ced_amazon_fetch_order_next_token_' . $mplocation, $order_next_token );
				
				$counter = 1;

				if ( isset( $orderlists ) && ! empty( $orderlists ) ) {

					foreach ( $orderlists as $orderlist ) {

						$amazon_order_detail = $orderlist;

						// Fetch only 5 orders via manually fetch request
						if ( ! $cron ) {
							if ( 5 < $counter ) {
								return;
							}
							$exist_order_id = $this->is_umb_order_exists( $amazon_order_detail['AmazonOrderId'] );
							if ( $exist_order_id ) {
								continue;
							}
						}

						//$amazon_order_detail['BuyerEmail'] = isset( $amazon_order_detail['BuyerInfo']['BuyerEmail'] ) ? $amazon_order_detail['BuyerInfo']['BuyerEmail'] : '';
					
						$site_url = str_replace( array( 'http://', 'https://'), array( '', '' ), get_site_url( ) );
						if ( ! empty( $site_url ) && ! empty( $amazon_order_detail['AmazonOrderId']) ) {
							
							$amazon_order_detail['BuyerEmail'] = $amazon_order_detail['AmazonOrderId'] . '@' . $site_url;
						}
						
						$amazon_order_detail['BuyerName'] = isset( $amazon_order_detail['BuyerInfo']['BuyerName'] ) ? $amazon_order_detail['BuyerInfo']['BuyerName'] : '';

						$amazonorderid                = isset( $amazon_order_detail['AmazonOrderId'] ) ? $amazon_order_detail['AmazonOrderId'] : '';
						$sellerOrderId                = isset( $amazon_order_detail['SellerOrderId'] ) ? $amazon_order_detail['SellerOrderId'] : '';
						$marketplaceId                = isset( $amazon_order_detail['MarketplaceId'] ) ? $amazon_order_detail['MarketplaceId'] : '';
						$fulfillmentChannel           = isset( $amazon_order_detail['FulfillmentChannel'] ) ? $amazon_order_detail['FulfillmentChannel'] : '';
						$salesChannel                 = isset( $amazon_order_detail['SalesChannel'] ) ? $amazon_order_detail['SalesChannel'] : '';
						$shipServiceLevel             = isset( $amazon_order_detail['ShipServiceLevel'] ) ? $amazon_order_detail['ShipServiceLevel'] : '';
						$paymentExecutionDetail       = isset( $amazon_order_detail['PaymentExecutionDetail'] ) ? $amazon_order_detail['PaymentExecutionDetail'] : '';
						$orderChannel                 = isset( $amazon_order_detail['OrderChannel'] ) ? $amazon_order_detail['OrderChannel'] : '';
						$shipmentServiceLevelCategory = isset( $amazon_order_detail['ShipmentServiceLevelCategory'] ) ? $amazon_order_detail['ShipmentServiceLevelCategory'] : '';

						$ShipToFirstName = isset( $amazon_order_detail['ShippingAddress']['Name'] ) ? $amazon_order_detail['ShippingAddress']['Name'] : '';
						/*explode first name and last name*/
						@list( $shipping_firstname, $shipping_lastname ) = explode( ' ', $ShipToFirstName, 2 );
						$ShipToFirstName                                 = sanitize_user( $shipping_firstname, true );
						$ShipToLastName                                  = sanitize_user( $shipping_lastname, true );
						$first_buyername                                 = sanitize_user( $shipping_firstname, true );
						$last_buyername                                  = sanitize_user( $shipping_lastname, true );
						$ShipToAddress1                                  = isset( $amazon_order_detail['ShippingAddress']['AddressLine1'] ) ? $amazon_order_detail['ShippingAddress']['AddressLine1'] : '';
						$ShipToAddress2                                  = isset( $amazon_order_detail['ShippingAddress']['AddressLine2'] ) ? $amazon_order_detail['ShippingAddress']['AddressLine2'] : '';
						if ( ! empty( $ShipToAddress2 ) && empty( $ShipToAddress1 ) ) {
							$ShipToAddress1 = $ShipToAddress2;
							$ShipToAddress2 = '';
						}
						$ShipToAddress3          = isset( $amazon_order_detail['ShippingAddress']['AddressLine3'] ) ? $amazon_order_detail['ShippingAddress']['AddressLine3'] : '';
						$ShipToCityName          = isset( $amazon_order_detail['ShippingAddress']['City'] ) ? $amazon_order_detail['ShippingAddress']['City'] : '';
						$ShipToCountyName        = isset( $amazon_order_detail['ShippingAddress']['County'] ) ? $amazon_order_detail['ShippingAddress']['County'] : '';
						$ShipToDistrictName      = isset( $amazon_order_detail['ShippingAddress']['District'] ) ? $amazon_order_detail['ShippingAddress']['District'] : '';
						$ShipToStateOrRegionName = isset( $amazon_order_detail['ShippingAddress']['StateOrRegion'] ) ? $amazon_order_detail['ShippingAddress']['StateOrRegion'] : '';
						$ShipToZipCode           = isset( $amazon_order_detail['ShippingAddress']['PostalCode'] ) ? $amazon_order_detail['ShippingAddress']['PostalCode'] : '';
						$ShipToCountry           = isset( $amazon_order_detail['ShippingAddress']['CountryCode'] ) ? $amazon_order_detail['ShippingAddress']['CountryCode'] : '';
						$ShipToPhone             = isset( $amazon_order_detail['ShippingAddress']['Phone'] ) ? $amazon_order_detail['ShippingAddress']['Phone'] : '';

						$ShippingAddress = array(
							'first_name' => $ShipToFirstName,
							'last_name'  => $ShipToLastName,
							'address_1'  => $ShipToAddress1,
							'address_2'  => $ShipToAddress2,
							'city'       => $ShipToCityName,
							'county'     => $ShipToCountyName,
							'district'   => $ShipToDistrictName,
							'state'      => $ShipToStateOrRegionName,
							'postcode'   => $ShipToZipCode,
							'country'    => $ShipToCountry,
							'phone'      => $ShipToPhone,
						);

						$buyeremail = isset( $amazon_order_detail['BuyerEmail'] ) ? $amazon_order_detail['BuyerEmail'] : '';
						$buyername  = isset( $amazon_order_detail['BuyerName'] ) ? $amazon_order_detail['BuyerName'] : '';

						$BillingAddress = array(
							'first_name' => $first_buyername,
							'last_name'  => $last_buyername,
							'email'      => $buyeremail,
							'address_1'  => $ShipToAddress1,
							'address_2'  => $ShipToAddress2,
							'city'       => $ShipToCityName,
							'county'     => $ShipToCountyName,
							'district'   => $ShipToDistrictName,
							'state'      => $ShipToStateOrRegionName,
							'postcode'   => $ShipToZipCode,
							'country'    => $ShipToCountry,
							'phone'      => $ShipToPhone,
						);

						$userdata['first_name'] = $buyername;
						$userdata['user_login'] = $buyername;
						$userdata['user_email'] = $buyeremail;
						$userdata['first_name'] = $buyername;
						$userdata['last_name']  = '';
						$userdata['role']       = 'amazonusers';

						// $user_id = email_exists( $userdata['user_email'] );
						// Create user
						/*if ( ! $user_id && false == email_exists( $userdata['user_email'] ) ) {
						$userdata['user_pass'] = wp_generate_password( $length = 12, $include_standard_special_chars = false );
						$user_id = wp_insert_user( $userdata );
						}*/

						// Get Order items
						$order_item_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/get_order_items';
						$order_item_body     = array(
							'marketplace_id' => $marketplace_id,
							'token'          => $refresh_token,
							'order_id'       => $amazonorderid,
							'contract_id'      => get_option('ced_amazon_contract_id', '')
						);
						$order_item_body     = wp_json_encode( $order_item_body );

						$order_item_options = array(
							'body'    => $order_item_body,
							'headers' => array(
								'Content-Type' => 'application/json',
							),
							'timeout' => 200,
						);

						$order_item_reponse = wp_remote_post( $order_item_endpoint, $order_item_options );
						if ( is_wp_error( $order_item_reponse ) ) {
							// Save error in log
							ced_amazon_log_data( $order_item_reponse, $log_name, 'order' );
							return;
						}
						$order_item_reponse = json_decode( $order_item_reponse['body'], true );
						$amzitemlistitems   = $order_item_reponse['payload']['OrderItems'];
						// echo "<pre>"; print_r($amzitemlistitems); die(">>>AAA");

						$orderlineitems = array();

						if ( isset( $amzitemlistitems ) && ! empty( $amzitemlistitems ) ) {
							$shipping_price     = 0;
							$shipping_tax       = 0;
							$promotion_discount = 0;
							foreach ( $amzitemlistitems as $linenu => $amzitemlistitem ) {
								$sku = $amzitemlistitem['SellerSKU'];

								if ( $sku ) {
									$metaKey  = '_sku';
									$metaKey2 = 'item_sku';
									$args     = array(
										'post_type'      => array( 'product', 'product_variation' ),
										//'post_status'    => 'any',
										'posts_per_page' => -1,
										'meta_query'     => array(
											'relation' => 'OR',
											array(
												'key'     => $metaKey,
												'value'   => trim( $sku ),
												'compare' => '=',
											),
											array(
												'key'     => $metaKey2,
												'value'   => trim( $sku ),
												'compare' => '=',
											),
										),
										'fields'         => 'ids',
									);
									$ID = get_posts( $args );
								}

								$product_id = isset( $ID[0] ) ? $ID[0] : '';

								$product = wc_get_product( $product_id );
								if ( ! is_object( $product ) ) {
									// Save error in log
									$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
									$log_message .= "SKU: $sku does not exist in woo. \n\n\n";
									ced_amazon_log_data( $log_message, $log_name, 'order' );
									continue;
								}
								$product_sku = $product->get_sku();
								$product_qty = $amzitemlistitem['QuantityOrdered'];
								if ( $product_qty > 1 ) {

									$product_price = $amzitemlistitem['ItemPrice']['Amount'] / $product_qty;
								} else {
									$product_price = $amzitemlistitem['ItemPrice']['Amount'];

								}

								if ( isset( $amzitemlistitem['ShippingPrice'] ) && ! empty( $amzitemlistitem['ShippingPrice']['Amount'] ) ) {
									$shipping_price += $amzitemlistitem['ShippingPrice']['Amount'];
								}

								if ( isset( $amzitemlistitem['ShippingTax'] ) && ! empty( $amzitemlistitem['ShippingTax']['Amount'] ) ) {
									$shipping_tax += $amzitemlistitem['ShippingTax']['Amount'];
								}

								if ( isset( $amzitemlistitem['ItemTax'] ) && $amzitemlistitem['ItemTax']['Amount'] > 0 ) {
									$product_tax = $amzitemlistitem['ItemTax']['Amount'];
								} else {
									$product_tax = 0;
								}

								if ( isset( $amzitemlistitem['PromotionDiscount'] ) && $amzitemlistitem['PromotionDiscount']['Amount'] > 0 ) {
									$promotion_discount += $amzitemlistitem['PromotionDiscount']['Amount'];
								}

								$item = array(
									'OrderedQty' => $product_qty,
									'CancelQty'  => '',
									'UnitPrice'  => $product_price,
									'UnitTax'    => $product_tax,
									'ID'         => $product_id,
									'Sku'        => $sku,
								);

								$orderlineitems[] = $item;

							}
						}

						if ( empty($orderlineitems) ) {
							// Save error in log
							$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
							$log_message .= "Amazon Order $amazonorderid SKU does not exist in woo. \n\n\n";
							ced_amazon_log_data( $log_message, $log_name, 'order' );
							continue;
						}

						$shippingservice = $amazon_order_detail['ShipmentServiceLevelCategory'];

						$OrderNumber    = isset( $amazon_order_detail['AmazonOrderId'] ) ? $amazon_order_detail['AmazonOrderId'] : '';
						$OrderItemsInfo = array(
							'OrderNumber'    => $OrderNumber,
							'ItemsArray'     => $orderlineitems,
							'tax'            => 0,
							'ShippingAmount' => $shipping_price,
							'ShippingTax'    => $shipping_tax,
							'ShipService'    => $shippingservice,
							'DiscountAmount' => $promotion_discount,
						);

						$address = array(
							'shipping' => $ShippingAddress,
							'billing'  => $BillingAddress,
						);

						$merchantOrderId = $OrderNumber;

						$purchaseOrderId = isset( $orderDetail['purchaseOrderId'] ) ? $orderDetail['purchaseOrderId'] : '';
						$amazonOrderMeta = array(
							'amazon_order_id'   => $merchantOrderId,
							'order_detail'      => $amazon_order_detail,
							'order_item_detail' => $amzitemlistitems,
							'order_items'       => $orderlineitems,
							'OrderItemsInfo'    => $OrderItemsInfo,
						);

						$buyeremail = isset( $amazon_order_detail['BuyerEmail'] ) ? $amazon_order_detail['BuyerEmail'] : '';
						$buyername  = isset( $amazon_order_detail['BuyerName'] ) ? $amazon_order_detail['BuyerName'] : '';

						$paymentmethod = isset( $amazon_order_detail['PaymentMethod'] ) ? $amazon_order_detail['PaymentMethod'] : '';

						$amazonorderid                = isset( $amazon_order_detail['AmazonOrderId'] ) ? $amazon_order_detail['AmazonOrderId'] : '';
						$sellerOrderId                = isset( $amazon_order_detail['SellerOrderId'] ) ? $amazon_order_detail['SellerOrderId'] : '';
						$marketplaceId                = isset( $amazon_order_detail['MarketplaceId'] ) ? $amazon_order_detail['MarketplaceId'] : '';
						$fulfillmentChannel           = isset( $amazon_order_detail['FulfillmentChannel'] ) ? $amazon_order_detail['FulfillmentChannel'] : '';
						$salesChannel                 = isset( $amazon_order_detail['SalesChannel'] ) ? $amazon_order_detail['SalesChannel'] : '';
						$shipServiceLevel             = isset( $amazon_order_detail['ShipServiceLevel'] ) ? $amazon_order_detail['ShipServiceLevel'] : '';
						$paymentExecutionDetail       = isset( $amazon_order_detail['PaymentExecutionDetail'] ) ? $amazon_order_detail['PaymentExecutionDetail'] : array();
						$orderChannel                 = isset( $amazon_order_detail['OrderChannel'] ) ? $amazon_order_detail['OrderChannel'] : '';
						$shipmentServiceLevelCategory = isset( $amazon_order_detail['ShipmentServiceLevelCategory'] ) ? $amazon_order_detail['ShipmentServiceLevelCategory'] : '';

						$amazon_order_array = array(
							'amazon_orderid'           => $amazonorderid,
							'amazon_sellerid'          => $sellerOrderId,
							'purchasedate'             => $amazon_order_detail['PurchaseDate'],
							'lastupdatedate'           => $amazon_order_detail['LastUpdateDate'],
							'marketplace_id'           => $marketplaceId,
							'order_channel'            => $orderChannel,
							'sales_channel'            => $salesChannel,
							'fulfillment_channel'      => $fulfillmentChannel,
							'payment_execution_detail' => json_encode( $paymentExecutionDetail ),
							'order_status_history'     => json_encode( array( $amazon_order_detail['OrderStatus'] => $amazon_order_detail ) ),
							'order_shipped_details'    => json_encode( $OrderItemsInfo ),
							'status'                   => $amazon_order_detail['OrderStatus'],
							'buyername'                => $buyername,
							'buyeremail'               => $buyeremail,
							'shippingservice'          => $shipServiceLevel,
							'paymentmethod'            => $paymentmethod,
							'order_data'               => json_encode( $amazon_order_detail ),
							'order_history'            => json_encode( array( $amazon_order_detail['OrderStatus'] => $amazon_order_detail ) ),
							'order_pre_data'           => json_encode( $amazonOrderMeta ),
							'items'                    => json_encode( $amzitemlistitems ),
							'shipping'                 => json_encode( $ShippingAddress ),
							'billing'                  => json_encode( $BillingAddress ),
							'total'                    => json_encode(
								array(
									'currency' => $amazon_order_detail['OrderTotal']['CurrencyCode'],
									'total'    => $amazon_order_detail['OrderTotal']['Amount'],
								)
							),
						);

						$OrderNumber = isset( $amazon_order_detail['AmazonOrderId'] ) ? $amazon_order_detail['AmazonOrderId'] : '';

						$amazonOrderMetaToSave = array(
							'amazon_order_id' => $OrderNumber,
							'order_detail'    => $amazon_order_array,
						);

						// $this->save_order_listing($amazonOrderMetaToSave );
						// continue;

						$order_id = $this->create_order( $address, $OrderItemsInfo, 'Amazon', $amazonOrderMeta, $mplocation, $seller_mp_key );

						update_post_meta( $order_id, 'ced_amazon_order_countory_code', $mplocation );
						update_post_meta( $order_id, 'ced_umb_order_sales_channel', $salesChannel );
						update_post_meta( $order_id, 'ced_umb_amazon_fulfillment_channel', $fulfillmentChannel );
						$order_status        = 'wc-processing';
						$amazon_order_status = 'Created';

						if ( 'Unshipped' == $amazon_order_detail['OrderStatus'] ) {
							$order_status        = 'wc-processing';
							$amazon_order_status = 'Created';
						}
						if ( 'Shipped' == $amazon_order_detail['OrderStatus'] ) {
							$order_status        = 'wc-completed';
							$amazon_order_status = 'Shipped';
						}
						update_post_meta( $order_id, '_amazon_umb_order_status', $amazon_order_status );

						if ( $order_id ) {
							$order = new WC_Order( $order_id );

							// Create/update user
							/*
							if (!empty($amazon_order_detail['BuyerEmail']) ) {
							   // echo "<pre>"; print_r($buyeremail.'buyeremail'); print_r($ShippingAddress); print_r($BillingAddress);
							$user_id = $this->addAmazonCustomer( $buyeremail, $buyername, $ShippingAddress, $BillingAddress );
							update_post_meta( $order_id, '_customer_user', $user_id );
							}*/

							$order_get_status = new WC_Order( $order_id );
							$order_get_status = $order_get_status->get_status();
							if ( 'trash' == $order_get_status || 'draft' == $order_get_status ) {
								continue;
							}

							$order = wc_get_order( $order_id );
							if ( ! $order ) {
								continue;
							}

							if ( in_array( $order->get_status(), array( 'completed', 'cancelled', 'refunded', 'failed' ) ) ) {
								continue;
							}

							if ( ! in_array( $order->get_status(), array( 'pending', 'processing', 'on-hold', 'completed' ) ) ) {
								continue;
							}

							// Order status
							if ( 'Unshipped' == $amazon_order_detail['OrderStatus'] ) {
								$current_order_status = 'processing';
							} elseif ( 'Shipped' == $amazon_order_detail['OrderStatus'] ) {
								$current_order_status = 'completed';
							} else {
								$current_order_status = 'processing';
							}
							$_amazon_umb_order_status = get_post_meta( $order_id, '_amazon_umb_order_status', true );

							if ( $order->get_status() != $current_order_status ) {

								$order->update_status( $current_order_status );
							}
							update_post_meta( $order_id, '_amazon_umb_order_status', $amazon_order_status );
							update_post_meta( $order_id, 'umb_amazon_shippied_data', $amazonOrderMeta );

							$counter++;
						}

					}
					if ( ! $cron ) {
						return true;
					}

				} else {

					// Save error in log
					$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
					$log_message .= "No orders found. \n\n\n";
					ced_amazon_log_data( $log_message, $log_name, 'order' );

					if ( ! $cron ) {
						return false;
					}
				}
				
			} catch ( Exception $e ) {
				echo 'Exception when calling order endpoint: ', esc_attr( $e->getMessage() ), PHP_EOL;
				// Save error in log
				ced_amazon_log_data( $e->getMessage(), $log_name, 'order' );
			}

		}



		/**
		 * Create order into woo.
		 *
		 * @name create_order()
		 * @since 1.0.0
		 * @param array() $address
		 * @param array() $OrderItemsInfo
		 * @since string $frameworkName
		 * @param array() $orderMeta
		 * @link  http://www.cedcommerce.com/
		 */
		public function create_order( $address = array(), $OrderItemsInfo = array(), $frameworkName = 'UMB', $orderMeta = array(), $mplocation = '', $seller_mp_key = '' ) {

			// ini_set('max_execution_time', 600);
			// ini_set('memory_limit',-1);

			set_time_limit( 600 );
			wp_raise_memory_limit( -1 );

			// Log file name
			$log_date = gmdate( 'Y-m-d' );
			$log_time = strtotime( gmdate( 'H:i:s' ) );
			//$log_name = "order_api_" . $log_date . '_' . $log_time .".txt";
			$log_name = 'order_api_' . $log_date . '.txt';

			global $ced_umb_helper_amaz;

			$order_id           = '';
			$order_created      = false;
			$tax_amount         = 0;
			$total_items_amount = 0;
			$woo_tax            = false;

			if ( count( $OrderItemsInfo ) ) {

				$OrderNumber = isset( $OrderItemsInfo['OrderNumber'] ) ? $OrderItemsInfo['OrderNumber'] : 0;
				$order_id    = $this->is_umb_order_exists( $OrderNumber );

				if ( $order_id ) {
					update_post_meta( $order_id, 'ced_amazon_order_countory_code', $mplocation );
					update_post_meta( $order_id, 'ced_amazon_order_seller_id', $seller_mp_key );
					return $order_id;
				}

				if ( count( $OrderItemsInfo ) ) {
					$ItemsArray = isset( $OrderItemsInfo['ItemsArray'] ) ? $OrderItemsInfo['ItemsArray'] : array();
					if ( is_array( $ItemsArray ) ) {
						$productIdsToUpdate = array();
						foreach ( $ItemsArray as $ItemInfo ) {
							$ProID         = isset( $ItemInfo['ID'] ) ? intval( $ItemInfo['ID'] ) : 0;
							$Sku           = isset( $ItemInfo['Sku'] ) ? $ItemInfo['Sku'] : '';
							$MfrPartNumber = isset( $ItemInfo['MfrPartNumber'] ) ? $ItemInfo['MfrPartNumber'] : '';
							$Upc           = isset( $ItemInfo['UPCCode'] ) ? $ItemInfo['UPCCode'] : '';
							$Asin          = isset( $ItemInfo['ASIN'] ) ? $ItemInfo['ASIN'] : '';

							$params = array( '_sku' => $Sku );
							if ( ! $ProID ) {
								$ProID = $ced_umb_helper_amaz->umb_get_product_by( $params );
							}
							if ( ! $ProID ) {
								$ProID = $Sku;
							}
							
							$Qty                  = isset( $ItemInfo['OrderedQty'] ) ? intval( $ItemInfo['OrderedQty'] ) : 0;
							$UnitPrice            = isset( $ItemInfo['UnitPrice'] ) ? floatval( $ItemInfo['UnitPrice'] ) : 0;
							$UnitTax              = isset( $ItemInfo['UnitTax'] ) ? floatval( $ItemInfo['UnitTax'] ) : 0;
							$ExtendUnitPrice      = isset( $ItemInfo['ExtendUnitPrice'] ) ? floatval( $ItemInfo['ExtendUnitPrice'] ) : 0;
							$ExtendShippingCharge = isset( $ItemInfo['ExtendShippingCharge'] ) ? floatval( $ItemInfo['ExtendShippingCharge'] ) : 0;

							$_product = wc_get_product( $ProID );

							$productIdsToUpdate[] = $ProID;
							if ( is_wp_error( $_product ) ) {
								continue;
							} elseif ( is_null( $_product ) ) {
								continue;
							} elseif ( ! $_product ) {
								continue;
							} else {
								if ( ! $order_created ) {
									$order_data = array(
										/**
										 * Function to get woocommerce order by status 
										 * 
										 * @param 'function'
										 * @param  integer 'limit'
										 * @return 'count'
										 * @since  1.0.0
										 */
										'status'        => apply_filters( 'woocommerce_default_order_status', 'pending' ),
										'customer_note' => __( 'Order from ', 'ced-amazon' ) . $frameworkName,
										'created_via'   => $frameworkName,
									);

									/* ORDER CREATED IN WOOCOMMERCE */
									$order = wc_create_order( $order_data );
									
									
									if ( is_plugin_active( 'woocommerce-sequential-order-numbers-pro/woocommerce-sequential-order-numbers-pro.php' ) ) {
										if ( function_exists( 'wc_seq_order_number_pro' ) && method_exists( 'wc_seq_order_number_pro', 'set_sequential_order_number' ) ) {
											wc_seq_order_number_pro()->set_sequential_order_number( $order->get_id(), get_post( $order->get_id() ) );
										}
									}

									/* ORDER CREATED IN WOOCOMMERCE */

									if ( is_wp_error( $order ) ) {
										// Save error in log
										$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
										$log_message .= "There is an error while create order. \n\n\n";
										ced_amazon_log_data( $log_message, $log_name, 'order' );
										continue;
									} elseif ( false === $order ) {
										continue;
									} else {
										if ( WC()->version < '3.0.0' ) {
											$order_id = $order->id;
										} else {
											$order_id = $order->get_id();
										}
										$order_created = true;
									}
								}

								if ( $UnitTax > 0 ) {
									$tax_amount         += $UnitTax;
									$total_items_amount += ( $UnitPrice * $Qty );
								}

								$_product->set_price( $UnitPrice );
								$item_id = $order->add_product( $_product, $Qty );

								// Add line item tax if woo tax is false
								if ( ! empty( $item_id ) && 0 < $UnitTax && ! $woo_tax ) {
									$tax_arr_data             = array();
									$tax_arr_data['total']    = array( '1' => $UnitTax );
									$tax_arr_data['subtotal'] = array( '1' => $UnitTax );
									wc_update_order_item_meta( $item_id, '_line_subtotal_tax', $UnitTax );
									wc_update_order_item_meta( $item_id, '_line_tax', $UnitTax );
									wc_update_order_item_meta( $item_id, '_line_tax_data', $tax_arr_data );
								}

								$BillingAddress = isset($address['billing']) ? $address['billing'] : '';
								if ( is_array($BillingAddress) ) {
									$order->set_address($BillingAddress, 'billing');
								}

								$ShippingAddress = isset($address['shipping']) ? $address['shipping'] : '';
								if ( is_array($ShippingAddress) ) {
									$order->set_address($ShippingAddress, 'shipping');
								}

								$order->calculate_totals( $woo_tax );

								// Reduce quantity from product level amazon field
								$stock_quantity = get_post_meta( $ProID, 'quantity', true );
								if ( isset( $stock_quantity ) && is_numeric( $stock_quantity ) ) {
									if ( $stock_quantity > 0 ) {
										$update_stock_quantity = $stock_quantity - $Qty;
										update_post_meta( $ProID, 'quantity', $update_stock_quantity );
									}
								}

							}
						}
					}

					if ( ! $order_created ) {
						// Save error in log
						$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
						$log_message .= "Order not created, please check!! \n\n\n";
						ced_amazon_log_data( $log_message, $log_name, 'order' );
						return false;
					}

					if ( isset( $order ) && ! empty( $order ) ) {
						$order->save();
						wc_reduce_stock_levels( $order_id );
					}

					update_post_meta( $order_id, '_umb_order_id', $OrderNumber );

					$seller_id                = $seller_mp_key;
					$inventory_sync_frequency = get_option( 'ced_amazon_inventory_scheduler_job_' . $seller_id );

					// Update relevant woo product quantity on Amazon
					if ( ! empty( $productIdsToUpdate ) && ( ! empty( $inventory_sync_frequency ) && '1' != $inventory_sync_frequency ) ) {
						/**
						 * Function to update product inventory
						 * 
						 * @param 'function'
						 * @param  'array'
						 * @param  'string'
						 * @param  'string'
						 * @return ''
						 * @since  1.0.0
						 */
						do_action( 'ced_amazon_order_product_inventory_update', $productIdsToUpdate, $mplocation, $seller_id );
					}

					$OrderItemAmount = isset( $OrderItemsInfo['OrderItemAmount'] ) ? $OrderItemsInfo['OrderItemAmount'] : 0;
					$ShippingAmount  = isset( $OrderItemsInfo['ShippingAmount'] ) ? $OrderItemsInfo['ShippingAmount'] : 0;
					$ShippingTax     = isset( $OrderItemsInfo['ShippingTax'] ) ? $OrderItemsInfo['ShippingTax'] : 0;
					$DiscountAmount  = isset( $OrderItemsInfo['DiscountAmount'] ) ? $OrderItemsInfo['DiscountAmount'] : 0;
					$RefundAmount    = isset( $OrderItemsInfo['RefundAmount'] ) ? $OrderItemsInfo['RefundAmount'] : 0;
					$ShipService     = isset( $OrderItemsInfo['ShipService'] ) ? $OrderItemsInfo['ShipService'] : '';

					// Add amazon tax if woo tax is false
					if ( $tax_amount > 0 && ! $woo_tax ) {
						$tax_percent = ( $tax_amount * 100 ) / $total_items_amount;
						$tax_percent = round( $tax_percent, 2 );

						$tax_item = new WC_Order_Item_Tax();
						$tax_item->set_label( 'Tax' );
						$tax_item->set_name( 'AMAZON-TAX' );
						$tax_item->set_rate_id( 1 );
						$tax_item->set_rate_percent( $tax_percent );
						$tax_item->set_shipping_tax_total( $ShippingTax );
						$tax_item->set_tax_total( $tax_amount );
						$order->add_item( $tax_item );

						$total_tax_amount = $tax_amount + $ShippingTax;
						$order->set_cart_tax( $total_tax_amount );
					}


					// Add shipping
					$shipping_item = new WC_Order_Item_Shipping();
					$shipping_item->set_method_title( $ShipService );
					$shipping_item->set_method_id( 'amazon-shipping' );
					$shipping_item->set_total( $ShippingAmount );
					// Add Amazon shipping tax if woo tax is false
					if ( ! $woo_tax ) {
						$ship_tax_arr_data          = array();
						$ship_tax_arr_data['total'] = array('1' => $ShippingTax);
						$shipping_item->set_taxes( $ship_tax_arr_data );
					}
					$order->add_item( $shipping_item );

					$order->set_payment_method( 'check' );
					$order->set_total( $DiscountAmount, 'cart_discount' );
					$order->calculate_totals($woo_tax);

					update_post_meta( $order_id, '_umb_order_id', $OrderNumber );
					update_post_meta( $order_id, '_is_amazon_order', 1 );
					update_post_meta( $order_id, '_amazon_umb_order_status', 1 );
					update_post_meta( $order_id, '_umb_marketplace', $frameworkName );
					update_post_meta( $order_id, 'ced_amazon_order_countory_code', $mplocation );
					update_post_meta( $order_id, 'ced_amazon_order_seller_id', $seller_mp_key );
					
					if ( count( $orderMeta ) ) {
						foreach ( $orderMeta as $oKey => $oValue ) {
							update_post_meta( $order_id, $oKey, $oValue );
						}
					}

					$order->save();

					// Save error in log
					$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
					$log_message .= "Amazon Order $OrderNumber has been created with woo order id $order_id. \n\n\n";
					ced_amazon_log_data( $log_message, $log_name, 'order' );
				}

				return $order_id;
			}
			return false;
		}

		/**
		 * Check if order already imported or not.
		 *
		 * @name is_umb_order_exists()
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 * @return integer
		 */
		public function is_umb_order_exists( $order_number = 0 ) {
			global $wpdb;
			if ( $order_number ) {
				$order_id = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_umb_order_id' AND meta_value=%s LIMIT 1", $order_number ), 'ARRAY_A' );
				if ( $order_id ) {
					return $order_id;
				}
			}
			return false;
		}


		/**
		 * Get product id by.
		 *
		 * @name get_product_id_by()
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 * @return integer
		 */
		public function get_product_id_by( $params = array() ) {
			global $wpdb;

			$where = '';
			if ( count( $params ) ) {
				$Flag = false;
				foreach ( $params as $meta_key => $meta_value ) {
					if ( ! empty( $meta_value ) && ! empty( $meta_key ) ) {
						if ( ! $Flag ) {
							$where .= 'meta_key="' . sanitize_key( $meta_key ) . '" AND meta_value="' . $meta_value . '"';
							$Flag   = true;
						} else {
							$where .= ' OR meta_key="' . sanitize_key( $meta_key ) . '" AND meta_value="' . $meta_value . '"';
						}
					}
				}
				if ( $Flag ) {
					$sql_val    = 1;
					$product_id = $wpdb->get_results( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE  %s LIMIT = %d", $where, $sql_val ), 'ARRAY_A' );
					if ( $product_id ) {
						return $product_id;
					}
				}
			}
			return false;
		}

		/**
		 * Add shipping charge
		 *
		 * @name addShippingCharge()
		 * @param object  $order
		 * @param array() $ShipParams
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public static function addShippingCharge( $order, $ShipParams = array() ) {

			$ShipName = isset( $ShipParams['ShipService'] ) ? esc_attr( $ShipParams['ShipService'] ) : 'UMB Default Shipping';
			$ShipCost = isset( $ShipParams['ShippingCost'] ) ? floatval( $ShipParams['ShippingCost'] ) : 0;
			$ShipTax  = isset( $ShipParams['ShippingTax'] ) ? floatval( $ShipParams['ShippingTax'] ) : 0;

			if ( WC()->version < '3.0.0' ) {
				$item_id = wc_add_order_item(
					$order->id,
					array(
						'order_item_name' => $ShipName,
						'order_item_type' => 'shipping',
					)
				);
			} else {
				$item_id = wc_add_order_item(
					$order->get_id(),
					array(
						'order_item_name' => $ShipName,
						'order_item_type' => 'shipping',
					)
				);
			}

			if ( ! $item_id ) {
				return false;
			}

			wc_add_order_item_meta( $item_id, 'method_id', $ShipName );
			wc_add_order_item_meta( $item_id, 'cost', wc_format_decimal( $ShipCost ) );

			if ( WC()->version < '3.0.0' ) {
				// Update total
				$order->set_total( $order->order_shipping + wc_format_decimal( $ShipCost ), 'shipping' );
			} else {
				$order_id       = $order->get_id();
				$order_shipping = get_post_meta( $order_id, '_order_shipping', true );
				$order->set_shipping_total( $order_shipping + wc_format_decimal( $ShipCost ) );
				$order->save();
			}

			return $item_id;

		}


		/**
		 * Add Amazon Customere using Buyer Email
		 *
		 * @name addAmazonCustomer()
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public function addAmazonCustomer( $amazon_buyer_email, $amazon_buyer_name, $shipping_address, $billing_address ) {

			$buyeremail = $amazon_buyer_email;
			$buyername  = $amazon_buyer_name;

			global $wpdb;
			if ( email_exists( $buyeremail ) == $user_id  ) {
				return $user_id;
			}

			@list( $shipping_firstname, $shipping_lastname ) = explode( ' ', $buyername, 2 );
			$user_firstname                                  = sanitize_user( $shipping_firstname, true );
			$user_lastname                                   = sanitize_user( $shipping_lastname, true );
			$user_fullname                                   = sanitize_user( $buyername, true );

			// Create random password
			$random_password = wp_generate_password( 18, false );

			// Create wp_user
			$wp_user = array(
				'user_login' => $amazon_buyer_email,
				'user_email' => $amazon_buyer_email,
				'first_name' => $user_firstname,
				'last_name'  => $user_lastname,

				'user_pass'  => $random_password,
				'role'       => 'amazonusers',
			);

			$user_id = wp_insert_user( $wp_user );

			if ( is_wp_error( $user_id ) ) {
				return false;

			} else {

				// Add user meta
				update_user_meta( $user_id, '_amazon_user_email', $amazon_buyer_email );
				update_user_meta( $user_id, 'billing_email', $amazon_buyer_email );
				update_user_meta( $user_id, 'paying_customer', 1 );
				update_user_meta( $user_id, 'billing_phone', stripslashes( $billing_address['phone'] ) );

				// Amazon user billing address Details
				update_user_meta( $user_id, 'billing_first_name', $user_firstname );
				update_user_meta( $user_id, 'billing_last_name', $user_lastname );

				update_user_meta( $user_id, 'billing_address_1', stripslashes( $billing_address['address_1'] ) );
				update_user_meta( $user_id, 'billing_address_2', stripslashes( $billing_address['address_2'] ) );
				update_user_meta( $user_id, 'billing_city', stripslashes( $billing_address['city'] ) );
				update_user_meta( $user_id, 'billing_postcode', stripslashes( $billing_address['postcode'] ) );
				update_user_meta( $user_id, 'billing_country', stripslashes( $billing_address['country'] ) );
				update_user_meta( $user_id, 'billing_state', stripslashes( stripslashes( $billing_address['state'] ) ) );

				// Amazon user shipping details
				update_user_meta( $user_id, 'shipping_first_name', $user_firstname );
				update_user_meta( $user_id, 'shipping_last_name', $user_lastname );

				update_user_meta( $user_id, 'shipping_address_1', stripslashes( $shipping_address['address_1'] ) );
				update_user_meta( $user_id, 'shipping_address_2', stripslashes( $shipping_address['address_2'] ) );
				update_user_meta( $user_id, 'shipping_city', stripslashes( $shipping_address['city'] ) );
				update_user_meta( $user_id, 'shipping_postcode', stripslashes( $shipping_address['postcode'] ) );
				update_user_meta( $user_id, 'shipping_country', stripslashes( $shipping_address['country'] ) );
				update_user_meta( $user_id, 'shipping_state', stripslashes( stripslashes( $shipping_address['state'] ) ) );
			}

			return $user_id;
		}


	}
	
endif;
