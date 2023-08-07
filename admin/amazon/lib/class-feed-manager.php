<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Amazon feed manager file.
 *
 * This file is used to perform all the relevant feed actions on Amazon.
 * Also used to get the relevant feed submission response.
 *
 * @since       1.0.0 
 * @package     Amazon_Integration_For_Woocommerce
 * @subpackage  Amazon_Integration_For_Woocommerce/admin/amazon/lib
 * @link        http://www.cedcommerce.com/
 */

if ( ! class_exists( 'Ced_Umb_Amazon_Feed_Manager' ) ) :

	/**
	 * Woo-marketplace feed submission functionality.
	 *
	 * Upload/update products, inventory, price, image, shipment from
	 * WooCommerce to Amazon.
	 *
	 * @since      1.0.0
	 * @package    Amazon_Integration_For_Woocommerce
	 * @subpackage Amazon_Integration_For_Woocommerce/admin/amazon/lib
	 */
	class Ced_Umb_Amazon_Feed_Manager {

		/**
		 * The Instace of Ced_Umb_Amazon_Feed_Manager.
		 *
		 * @since    1.0.0
		 * @var      $_instance   The Instance of Ced_Umb_Amazon_Feed_Manager class.
		 */
		private static $_instance;
		public $amazon_xml_lib;
		public $product_upload_notice;
		public $feed_xml_notice;
		public $amazon_xml_lib_custom;

		/**
		 * Ced_Umb_Amazon_Feed_Manager Instance.
		 *
		 * Ensures only one instance of Ced_Umb_Amazon_Feed_Manager is loaded or can be loaded.
		 *
		 * @name get_instance()
		 * @since 1.0.0
		 * @static
		 * @return Ced_Umb_Amazon_Feed_Manager instance.
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
		 * Registering actions and hooks for amazon.
		 *
		 * @link  http://www.cedcommerce.com/
		 * @since 1.0.0
		 */
		public function __construct() {

			require_once 'class-amazon-xml-lib.php';
			$this->amazon_xml_lib = new Ced_Amzon_XML_Lib();

			require_once 'class-amazon-xml-lib-custom.php';
			$this->amazon_xml_lib_custom = new Ced_Amzon_XML_Lib_Custom();

			add_action( 'ced_amazon_order_product_inventory_update', array( $this, 'ced_amazon_order_product_inventory_update_on_marketplace' ), 25, 3 );

			// Amazon order shipment ajax
			add_action( 'wp_ajax_umb_amazon_shipment_order', array( $this, 'umb_amazon_shipment_order' ) );
			add_action( 'wp_ajax_nopriv_umb_amazon_shipment_order', array( $this, 'umb_amazon_shipment_order' ) );
			add_action( 'wp_ajax_umb_amazon_check_feed_status', array( $this, 'umb_amazon_check_feed_status' ) );
			add_action( 'wp_ajax_nopriv_umb_amazon_check_feed_status', array( $this, 'umb_amazon_check_feed_status' ) );
		}

		/**
		 * Handle product management actions.
		 *
		 * Handling all product management actions i.e. upload products, update inventory, price and images.
		 *
		 * @name process_feed_request()
		 * @link  http://www.cedcommerce.com/
		 * @since 1.0.0
		 * @return bool true|false.
		 */
		public function process_feed_request( $action = '', $marketplace = '', $proIds = array(), $mplocation = '', $seller_mp_key = '' ) {

			//print_r($seller_mp_key); die('>>');

			if ( empty( $action ) || empty( $marketplace ) || ! is_array( $proIds ) || empty( $mplocation ) || empty($seller_mp_key) ) {
				$message = esc_attr_e( 'Either action/marketplace/products/mplocation/seller_id are missing for performing the the action, please try again!', 'ced-amazon' );
				$classes = 'error is-dismissable';
				$error   = array(
					'message' => $message,
					'classes' => $classes,
				);
				return json_encode( $error );
			} else {

				switch ( $action ) {
					case 'upload_product':
						return $this->upload_products_details( $proIds, $action, $mplocation, $seller_mp_key );
					break;

					case 'update_product':
						return $this->upload_products_details( $proIds, $action, $mplocation, $seller_mp_key );
					break;

					case 'relist_product':
						return $this->upload_products_details( $proIds, $action, $mplocation, $seller_mp_key );
					break;

					case 'update_price':
						return $this->upload_products_details( $proIds, $action, $mplocation, $seller_mp_key );
					break;

					case 'update_inventory':
						return $this->upload_products_details( $proIds, $action, $mplocation, $seller_mp_key );
					break;

					case 'update_images':
						return $this->upload_products_details( $proIds, $action, $mplocation, $seller_mp_key );
					break;

					case 'delete_product':
						return $this->upload_products_details( $proIds, $action, $mplocation, $seller_mp_key );
					break;
					
					case 'look_up':
						return $this->upload_products_details($proIds, $action, $mplocation, $seller_mp_key);
					break;

					default:
						return;
					break;
				}
			}
		}

		/**
		 * Upload selected products on selected marketplace.
		 *
		 * @since 1.0.0
		 * @param string $marketplace
		 * @param array  $proIds
		 * @return json string
		 * @link  http://www.cedcommerce.com/
		 */
		public function upload_products_details( $proIds = '', $action = '', $mplocation = '', $seller_mp_key = '' ) {
			$marketplace = 'amazon';

			if ( ! is_array( $proIds ) || empty($proIds) || empty($action) || empty($mplocation) || empty($seller_mp_key) ) {
				$message = esc_attr_e( 'Either products/action/mplocation/Seller_id are missing while performing the action, please try again!', 'ced-amazon' );
				$classes = 'error is-dismissable';
				$error   = array(
					'message' => $message,
					'classes' => $classes,
				);
				return json_encode( $error );
			}

			if ( 'upload_product' == $action || 'update_product' == $action || 'delete_product' == $action ) {
				if ( is_array( $proIds ) && ! empty( $proIds ) ) {
					$final_pro_ids = array();
					foreach ( $proIds as $key => $pro_id ) {
						$product = wc_get_product( $pro_id );
						if ( ! is_object( $product ) ) {
							continue;
						}
						$final_pro_ids[ $pro_id ] = $pro_id;
						$product_type             = $product->get_type();
						if ( 'variable' == $product_type ) {
							$children_ids = $product->get_children();
							foreach ( $children_ids as $key => $child_id ) {
								$final_pro_ids[ $child_id ] = $child_id;
							}
						}
					}
					if ( isset( $final_pro_ids ) && ! empty( $final_pro_ids ) && is_array( $final_pro_ids ) ) {
						$proIds = array_values( $final_pro_ids );
					}
				}
			}

			if ( is_array( $proIds ) && ! empty( $proIds ) ) {

				switch ( $action ) {
					case 'upload_product':
						return $this->uploadProductIds( $proIds, '', $mplocation );
					break;

					case 'update_product':
						return $this->uploadProductIds( $proIds, '', $mplocation );
					break;

					case 'relist_product':
						return $this->ced_amazon_relist_product( $proIds, $mplocation, $seller_mp_key );
					break;

					case 'update_inventory':
						return $this->ced_amazon_manual_inventory_update( $proIds, $mplocation, $seller_mp_key );
					break;

					case 'update_price':
						return $this->ced_amazon_bulk_price_update( $proIds, $mplocation, $seller_mp_key );
					break;

					case 'update_images':
						return $this->ced_amazon_bulk_image_update( $proIds, $mplocation, $seller_mp_key );
					break;

					case 'delete_product':
						return $this->ced_amazon_delete_product( $proIds, $mplocation, $seller_mp_key );
					break;
					case 'look_up':
						return $this->ced_amazon_look_up($proIds, $mplocation, $seller_mp_key);
					break;	

					default:
						// return $this->upload($proIds);
						return;
					break;
				}
			}

			$message = esc_attr_e( 'An unexpected error occured, please try again!', 'ced-amazon' );
			$classes = 'error is-dismissable';
			$error   = array(
				'message' => $message,
				'classes' => $classes,
			);
			return json_encode( $error );
		}


		/**
		 * Upload Product on amazon
		 *
		 * @param unknown $proIds
		 * @param string  $isWriteXML
		 * @return Ambigous <multitype:, boolean, string>
		 */
		public function uploadProductIds( $proIds = array(), $profileID = '', $mplocation = '', $template_name = '', $template_content = '', $product_ids_for_feed = array() ) {


			set_time_limit( 600 );
			wp_raise_memory_limit( -1 );
			
			ignore_user_abort( true );
			
			$seller_mp_key = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

			if ( empty($mplocation) ) {
				$notice['message'] = 'Marketplace location is missing!!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			if ( empty($seller_mp_key) ) {
				$notice['message'] = 'Seller id is missing!!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			if ( isset( $proIds ) && ! empty( $proIds ) ) {

				try {
					$directorypath     = plugin_dir_path( __FILE__ );
					$allDetails        = $this->makeProductXMLFileToSendOnAmazon( $proIds, $profileID, $mplocation );
					$notice['message'] = 'Products prepared Successfully!!';
					$notice['classes'] = 'notice notice-success';

					$allDetails = json_decode( $allDetails, true );
					if ( isset( $allDetails['profile_with_pro_ids'] ) && ! empty( $allDetails['profile_with_pro_ids'] ) && is_array( $allDetails['profile_with_pro_ids'] ) ) {

						foreach ( $allDetails['profile_with_pro_ids'] as $profileId => $product_ids ) {
							 $this->exportCsvToUploadUsingPorIds( $product_ids, $mplocation );
						}

						return json_encode( $this->product_upload_notice );

					} else {
						$notice['message'] = 'Product has not assigned with any profile!!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}
				} catch ( Exception $e ) {
					echo 'Exception : ', esc_attr( $e->getMessage() ), PHP_EOL;
				}
				$notice['message'] = 'Something went wrong, please check profile mapping!!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );

			}

			if ( '' != $template_content ) {
				$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
				
				$grtopt_data = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
				if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
					$shop_data = $saved_amazon_details[ $grtopt_data ];
				}

				$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
				$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
				$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
				$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

				if ( empty( $refresh_token ) || empty( $seller_id ) || empty( $marketplace_id ) ) {
					$notice['message']           = 'Seller credentials are missing, please check!';
					$notice['classes']           = 'notice notice-error is-dismissable';
					$this->product_upload_notice = $notice;
					return;
				}

				// Check file exist or not
				$file_path  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$file_path .= $template_name;
				$tmp_path   = ABSPATH . 'wp-content/uploads/ced-amazon/' . $template_name;
				if ( ! file_exists( $tmp_path ) ) {
					$notice['message']           = 'Product upload file not prepared, please check!';
					$notice['classes']           = 'notice notice-error is-dismissable';
					$this->product_upload_notice = $notice;
					return;
				}
				//return;

				try {

					// create feed for product upload
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'POST_FLAT_FILE_LISTINGS_DATA',
						'seller_id'      => $seller_id,
						//'file_url'       => $file_path,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $template_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						$notice['message']           = 'Something went wrong with product feed submission, please try again later!';
						$notice['classes']           = 'notice notice-error is-dismissable';
						$this->product_upload_notice = $notice;
						return;
					}

					$productuploadreponse = json_decode( $feed_reponse['body'], true );
					if ( isset($productuploadreponse['success']) && 'false' == $productuploadreponse['success'] ) {
						$notice['message']           = $productuploadreponse['body'];
						$notice['classes']           = 'notice notice-error is-dismissable';
						$this->product_upload_notice = $notice;
						return;
					}

					if ( isset( $productuploadreponse['feed_id'] ) && ! empty( $productuploadreponse['feed_id'] ) ) {
						$feedId    = $productuploadreponse['feed_id'];
						$feed_type = 'POST_FLAT_FILE_LISTINGS_DATA';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save feed action with respect to each product
						if ( is_array($product_ids_for_feed) && !empty($product_ids_for_feed) ) {
							foreach ( $product_ids_for_feed as $product_key => $product_id ) {
								$seller_id_val       = str_replace('|', '_', $grtopt_data);
								$product_feed_action = get_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, true );
								$current_feed_action = array('POST_FLAT_FILE_LISTINGS_DATA' => $feedId);
								if (is_array($product_feed_action) && !empty($product_feed_action)) {
									$product_feed_action = array_replace( $product_feed_action, $current_feed_action );
								} else {
									$product_feed_action = $current_feed_action;
								}
								update_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, $product_feed_action );
							}
						}

						$notice['message']           = 'Product upload feed has processed and submitted.';
						$notice['classes']           = 'notice notice-success is-dismissable';
						$this->product_upload_notice = $notice;
						return;

					} else {
						$notice['message']           = 'Something went wrong with feed submission, please check product feed URL!';
						$notice['classes']           = 'notice notice-error is-dismissable';
						$this->product_upload_notice = $notice;
						return;
					}

				} catch ( Exception $e ) {
					echo 'Exception when calling product upload feed end point: ', esc_attr( $e->getMessage() ), PHP_EOL;
					$notice['message']           = 'Exception when calling product upload feed end point!';
					$notice['classes']           = 'notice notice-error is-dismissable';
					$this->product_upload_notice = $notice;
					return;
				}

			} else {
				$notice['message']           = 'No products found to upload from manage products section!';
				$notice['classes']           = 'notice notice-error is-dismissable';
				$this->product_upload_notice = $notice;
				return;
			}
		}


		/**
		 * Relist product with exist Amazon catalog ASIN
		 *
		 * @name ced_amazon_relist_product
		 * @since 1.0.0
		 */
		public function ced_amazon_relist_product( $product_ids = array(), $mplocation = '', $seller_mp_key = '' ) {

			
			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				$notice['message'] = 'Marketplace location or seller id is missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_mp_key;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $seller_id ) || empty( $mplocation ) ) {
				$notice['message'] = 'Seller credentials are missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$products = $product_ids;
			} else {
				$notice['message'] = 'No products found to publish on Amazon!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$isWriteXML           = true;
			$product_publish_time = strtotime( gmdate('Y-m-d\TH:i:s') );
			//$xmlFileName          = 'product_publish_' . $product_publish_time . '.xml';
			$xmlFileName    = 'product-relist-' . $mplocation . '.xml';
			$relist_content = $this->create_product_relist_data_xml_file( $products, $isWriteXML, $mplocation, $xmlFileName );// Create product data xml file

			// $directorypath = plugin_dir_path(__FILE__);
			$directorypath = CED_AMAZON_DIRPATH . 'marketplaces/amazon';
			$xsdfile       = "$directorypath/upload/xsds/amzn-envelope.xsd";
			if ( $this->validateXML( $xsdfile, $xmlFileName ) ) {
				$XMLfilePath  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$XMLfilePath .= $xmlFileName;

				try {
					// Product relist feed API call using SP-API endpoint
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'POST_PRODUCT_DATA',
						'seller_id'      => $seller_id,
						//'file_url'       => $XMLfilePath,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $relist_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						$notice['message'] = 'Something went wrong with product relist feed submission, please try again later!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					$product_relist_response = json_decode( $feed_reponse['body'], true );
					if ( isset($product_relist_response['success']) && 'false' == $product_relist_response['success'] ) {
						$notice['message'] = $product_relist_response['body'];
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					if ( isset( $product_relist_response['feed_id'] ) && ! empty( $product_relist_response['feed_id'] ) ) {
						$feedId    = $product_relist_response['feed_id'];
						$feed_type = 'POST_PRODUCT_DATA';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save feed action with respect to each product
						foreach ( $products as $pkey => $product_id ) {
							$seller_id_val       = str_replace('|', '_', $grtopt_data);
							$product_feed_action = get_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, true );
							$current_feed_action = array('POST_PRODUCT_DATA' => $feedId);
							if (is_array($product_feed_action) && !empty($product_feed_action)) {
								$product_feed_action = array_replace( $product_feed_action, $current_feed_action );
							} else {
								$product_feed_action = $current_feed_action;
							}
							update_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, $product_feed_action );
						}

						$notice['message'] = 'Product relist feed has been processed and submitted.';
						$notice['classes'] = 'notice notice-success is-dismissable';
						return json_encode( $notice );

					} else {
						$notice['message'] = 'Something went wrong with feed submission, please check product relist feed URL!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

				} catch ( Exception $e ) {
					echo 'Exception when calling product relist feed API end point: ', esc_attr( $e->getMessage() ), PHP_EOL;
					$notice['message'] = 'Exception when calling product relist feed API end point!';
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
				}
			} else {
					$feed_xml_error    = $this->feed_xml_notice;
					$notice['message'] = 'Product relist data validation failed! ' . $feed_xml_error;
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
			}
			$notice['message'] = 'Something went wrong with feed submission!';
			$notice['classes'] = 'notice notice-error is-dismissable';
			return json_encode( $notice );
		}


		/**
		 * Update bulk products inventory on Amazon
		 *
		 * @name ced_amazon_bulk_inventory_update
		 * @since 1.0.0
		 */
		public function ced_amazon_bulk_inventory_update( $product_ids = array(), $mplocation = '', $seller_mp_key = '' ) {
			

			set_time_limit( 600 );
			wp_raise_memory_limit( -1 );
			ignore_user_abort( true );

			// Log file name
			$log_date = gmdate( 'Y-m-d' );
			$log_time = strtotime( gmdate( 'H:i:s' ) );
			//$log_name = "inventory_api_" . $log_date . '_' . $log_time .".txt";
			$log_name = 'inventory_api_' . $log_date . '.txt';

			
			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Marketplace location or seller id is missing while bulk inventory, please check! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_mp_key;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $seller_id ) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Seller credentials are missing! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$products = $product_ids;
			} else {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "No products found to update inventory on Amazon! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}

			$isWriteXML        = true;
			$xmlFileName       = 'bulk-inventory-' . $mplocation . '.xml';
			$inventory_content = $this->create_inventory_xml_data_file( $products, $isWriteXML, $mplocation, $xmlFileName, $seller_mp_key );// create inventory xml file
			// $directorypath = plugin_dir_path(__FILE__);
			$directorypath = CED_AMAZON_DIRPATH . 'marketplaces/amazon';
			$xsdfile       = "$directorypath/upload/xsds/amzn-envelope.xsd";
			if ( $this->validateXML( $xsdfile, $xmlFileName ) ) {
				$XMLfilePath  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$XMLfilePath .= $xmlFileName;
				//return;
				try {
					// Inventory update feed API call using SP-API endpoint
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'POST_INVENTORY_AVAILABILITY_DATA',
						'seller_id'      => $seller_id,
						//'file_url'       => $XMLfilePath,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $inventory_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						// Save error in log
						ced_amazon_log_data( $feed_reponse, $log_name, 'feed' );
						return;
					}

					$inventoryuploadreponse = json_decode( $feed_reponse['body'], true );
					if ( isset($inventoryuploadreponse['success']) && 'false' == $inventoryuploadreponse['success'] ) {
						// Save error in log
						ced_amazon_log_data( $feed_reponse, $log_name, 'feed' );
						return;
					}

					if ( isset( $inventoryuploadreponse['feed_id'] ) && ! empty( $inventoryuploadreponse['feed_id'] ) ) {
						$feedId    = $inventoryuploadreponse['feed_id'];
						$feed_type = 'POST_INVENTORY_AVAILABILITY_DATA';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save error in log
						$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
						$log_message .= "Bulk inventory feed has processed and submitted. \n\n\n";
						ced_amazon_log_data( $log_message, $log_name, 'feed' );
						return;

					} else {
						// Save error in log
						$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
						$log_message .= "Something went wrong with feed submission, please check inventory feed URL! \n\n\n";
						ced_amazon_log_data( $log_message, $log_name, 'feed' );
						return;
					}

				} catch ( Exception $e ) {
					echo 'Exception when calling inventory feed API end point: ', esc_attr( $e->getMessage() ), PHP_EOL;
					// Save error in log
					ced_amazon_log_data( $e->getMessage(), $log_name, 'feed' );
					return;
				}
			} else {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Inventory data validation failed! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}
			
		}


		/**
		 * Update products inventory on Amazon manually
		 *
		 * @name ced_amazon_manual_inventory_update
		 * @since 1.0.0
		 */
		public function ced_amazon_manual_inventory_update( $product_ids = array(), $mplocation = '', $seller_mp_key = '' ) {

			
			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				$notice['message'] = 'Marketplace location or seller id is missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_mp_key;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $seller_id ) ) {
				$notice['message'] = 'Seller credentials are missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$products = $product_ids;
			} else {
				$notice['message'] = 'No products found to update inventory on Amazon!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$isWriteXML        = true;
			$xmlFileName       = 'inventory-' . $mplocation . '.xml';
			$inventory_content = $this->create_inventory_xml_data_file( $products, $isWriteXML, $mplocation, $xmlFileName, $seller_mp_key );// create inventory xml file
			// $directorypath = plugin_dir_path(__FILE__);
			$directorypath = CED_AMAZON_DIRPATH . 'marketplaces/amazon';
			$xsdfile       = "$directorypath/upload/xsds/amzn-envelope.xsd";
			if ( $this->validateXML( $xsdfile, $xmlFileName ) ) {
				$XMLfilePath  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$XMLfilePath .= $xmlFileName;

				try {
					// Inventory update feed API call using SP-API endpoint
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'POST_INVENTORY_AVAILABILITY_DATA',
						'seller_id'      => $seller_id,
						//'file_url'       => $XMLfilePath,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $inventory_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						$notice['message'] = 'Something went wrong with inventory feed submission, please try again later!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					$inventoryuploadreponse = json_decode( $feed_reponse['body'], true );
					if ( isset($inventoryuploadreponse['success']) && 'false' == $inventoryuploadreponse['success'] ) {
						$notice['message'] = $inventoryuploadreponse['body'];
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					if ( isset( $inventoryuploadreponse['feed_id'] ) && ! empty( $inventoryuploadreponse['feed_id'] ) ) {
						$feedId    = $inventoryuploadreponse['feed_id'];
						$feed_type = 'POST_INVENTORY_AVAILABILITY_DATA';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save feed action with respect to each product
						foreach ( $products as $pkey => $product_id ) {
							$seller_id_val       = str_replace('|', '_', $grtopt_data);
							$product_feed_action = get_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, true );
							$current_feed_action = array('POST_INVENTORY_AVAILABILITY_DATA' => $feedId);
							if (is_array($product_feed_action) && !empty($product_feed_action)) {
								$product_feed_action = array_replace( $product_feed_action, $current_feed_action );
							} else {
								$product_feed_action = $current_feed_action;
							}
							update_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, $product_feed_action );
						}

						$notice['message'] = 'Product inventory feed has processed and submitted.';
						$notice['classes'] = 'notice notice-success is-dismissable';
						return json_encode( $notice );

					} else {
						$notice['message'] = 'Something went wrong with feed submission, please check inventory feed URL!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

				} catch ( Exception $e ) {
					echo 'Exception when calling inventory feed API end point: ', esc_attr( $e->getMessage() ), PHP_EOL;
					$notice['message'] = 'Exception when calling inventory feed API end point!';
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
				}
			} else {
				
					$notice['message'] = 'Inventory data validation failed!';
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
			}
			$notice['message'] = 'Something went wrong with feed submission!';
			$notice['classes'] = 'notice notice-error is-dismissable';
			return json_encode( $notice );
		}


		/**
		 * Update product price on Amazon
		 *
		 * @name ced_amazon_bulk_price_update
		 * @since 1.0.0
		 */
		public function ced_amazon_bulk_price_update( $product_ids = array(), $mplocation = '', $seller_mp_key = '' ) {
			

			set_time_limit( 600 );
			wp_raise_memory_limit( -1 );

			ignore_user_abort( true );


			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				$notice['message'] = 'Marketplace location or seller id is missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_mp_key;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $seller_id ) ) {
				$notice['message'] = 'Seller credentials are missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$products = $product_ids;
			} else {
				$notice['message'] = 'No products found to update price on Amazon!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$isWriteXML    = true;
			$xmlFileName   = 'price-' . $mplocation . '.xml';
			$price_content = $this->makePriceXMLFileToSendOnAmazon( $products, $isWriteXML, $mplocation, $xmlFileName, $seller_mp_key );// create Price xml file
			// $directorypath = plugin_dir_path(__FILE__);
			$directorypath = CED_AMAZON_DIRPATH . 'marketplaces/amazon';
			$xsdfile       = "$directorypath/upload/xsds/amzn-envelope.xsd";

			if ( $this->validateXML( $xsdfile, $xmlFileName ) ) {
				$XMLfilePath  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$XMLfilePath .= $xmlFileName;

				try {
					// Price update feed API call using SP-API endpoint
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'POST_PRODUCT_PRICING_DATA',
						'seller_id'      => $seller_id,
						//'file_url'       => $XMLfilePath,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $price_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						$notice['message'] = 'Something went wrong with price feed submission, please try again later!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					$priceuploadreponse = json_decode( $feed_reponse['body'], true );
					if ( isset($priceuploadreponse['success']) && 'false' == $priceuploadreponse['success'] ) {
						$notice['message'] = $priceuploadreponse['body'];
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					if ( isset( $priceuploadreponse['feed_id'] ) && ! empty( $priceuploadreponse['feed_id'] ) ) {
						$feedId    = $priceuploadreponse['feed_id'];
						$feed_type = 'POST_PRODUCT_PRICING_DATA';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save feed action with respect to each product
						foreach ( $products as $pkey => $product_id ) {
							$seller_id_val       = str_replace('|', '_', $grtopt_data);
							$product_feed_action = get_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, true );
							$current_feed_action = array('POST_PRODUCT_PRICING_DATA' => $feedId);
							if (is_array($product_feed_action) && !empty($product_feed_action)) {
								$product_feed_action = array_replace( $product_feed_action, $current_feed_action );
							} else {
								$product_feed_action = $current_feed_action;
							}
							update_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, $product_feed_action );
						}

						$notice['message'] = 'Product price feed has processed and submitted.';
						$notice['classes'] = 'notice notice-success is-dismissable';
						return json_encode( $notice );

					} else {
						$notice['message'] = 'Something went wrong with feed submission, please check price feed URL!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

				} catch ( Exception $e ) {
					echo 'Exception when calling price update feed api endpint: ', esc_attr( $e->getMessage() ), PHP_EOL;
					$notice['message'] = 'Exception when calling price update feed api endpint!';
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
				}
			} else {
				$feed_xml_error    = $this->feed_xml_notice;
				$notice['message'] = 'Price data validation failed!! ' . $feed_xml_error;
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}
			$notice['message'] = 'Something went wrong with feed submission!!';
			$notice['classes'] = 'notice notice-error is-dismissable';
			return json_encode( $notice );

		}


		/**
		 * Update product image on Amazon
		 *
		 * @name ced_amazon_bulk_image_update
		 * @since 1.0.0
		 */
		public function ced_amazon_bulk_image_update( $product_ids = array(), $mplocation = '', $seller_mp_key = '' ) {
			

			set_time_limit( 600 );
			wp_raise_memory_limit( -1 );

			ignore_user_abort( true );

			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				$notice['message'] = 'Marketplace location or seller id is missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_mp_key;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			if ( empty( $shop_data ) ) {
				$notice['message'] = 'Seller credentials are missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $seller_id ) ) {
				$notice['message'] = 'Seller credentials are missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$products = $product_ids;
			} else {
				$notice['message'] = 'No products found to update image on Amazon!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$isWriteXML    = true;
			$xmlFileName   = 'image-' . $mplocation . '.xml';
			$image_content = $this->makeImageXMLFileToSendOnAmazon( $products, $isWriteXML, $mplocation, $xmlFileName, $seller_mp_key );
			$directorypath = CED_AMAZON_DIRPATH . 'marketplaces/amazon';
			$xsdfile       = "$directorypath/upload/xsds/amzn-envelope.xsd";
			if ( $this->validateXML( $xsdfile, $xmlFileName ) ) {
				$XMLfilePath  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$XMLfilePath .= $xmlFileName;

				try {
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'POST_PRODUCT_IMAGE_DATA',
						'seller_id'      => $seller_id,
						//'file_url'       => $XMLfilePath,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $image_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						$notice['message'] = 'Something went wrong with image feed submission, please try again later!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					$imageuploadreponse = json_decode( $feed_reponse['body'], true );
					if ( isset($imageuploadreponse['success']) && 'false' == $imageuploadreponse['success'] ) {
						$notice['message'] = $imageuploadreponse['body'];
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					if ( isset( $imageuploadreponse['feed_id'] ) && ! empty( $imageuploadreponse['feed_id'] ) ) {
						$feedId    = $imageuploadreponse['feed_id'];
						$feed_type = 'POST_PRODUCT_IMAGE_DATA';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save feed action with respect to each product
						foreach ( $products as $pkey => $product_id ) {
							$seller_id_val       = str_replace('|', '_', $grtopt_data);
							$product_feed_action = get_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, true );
							$current_feed_action = array('POST_PRODUCT_IMAGE_DATA' => $feedId);
							if (is_array($product_feed_action) && !empty($product_feed_action)) {
								$product_feed_action = array_replace( $product_feed_action, $current_feed_action );
							} else {
								$product_feed_action = $current_feed_action;
							}
							update_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, $product_feed_action );
						}

						$notice['message'] = 'Product image feed has processed and submitted.';
						$notice['classes'] = 'notice notice-success is-dismissable';
						return json_encode( $notice );

					} else {
						$notice['message'] = 'Something went wrong with feed submission, please check image feed URL!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

				} catch ( Exception $e ) {
					echo 'Exception when calling image update feed api endpint: ', esc_attr( $e->getMessage() ), PHP_EOL;
					$notice['message'] = 'Exception when calling image update feed api endpint!';
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
				}
			} else {
				$feed_xml_error    = $this->feed_xml_notice;
				$notice['message'] = 'Image data validation failed!! ' . $feed_xml_error;
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}
			$notice['message'] = 'Something went wrong with feed submission!!';
			$notice['classes'] = 'notice notice-error is-dismissable';
			return json_encode( $notice );

		}



		/**
		 * Delete product from Amazon
		 *
		 * @name ced_amazon_delete_product
		 * @since 1.0.0
		 */
		public function ced_amazon_delete_product( $product_ids = array(), $mplocation = '', $seller_mp_key = '' ) {
			
			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				$notice['message'] = 'Marketplace location or seller id is missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_mp_key;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $seller_id ) ) {
				$notice['message'] = 'Seller credentials are missing, please check!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$products = $product_ids;
			} else {
				$notice['message'] = 'No products found to update inventory on Amazon!';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

			$isWriteJSON    = true;
			$jsonFileName   = 'product-delete-' . $mplocation . '.json';
			$delete_content = $this->create_product_delete_data_json_file( $products, $isWriteJSON, $mplocation, $jsonFileName, $seller_id ); //Product delete data json file
			
			if ( false !== $delete_content ) {
				$JSONfilePath  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$JSONfilePath .= $jsonFileName;

				try {
					// Product delete feed API call using SP-API endpoint
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'JSON_LISTINGS_FEED',
						'seller_id'      => $seller_id,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $delete_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						$notice['message'] = 'Something went wrong with product delete feed submission, please try again later!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					$product_delete_response = json_decode( $feed_reponse['body'], true );
					if ( isset($product_delete_response['success']) && 'false' == $product_delete_response['success'] ) {
						$notice['message'] = $product_delete_response['body'];
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					if ( isset( $product_delete_response['feed_id'] ) && ! empty( $product_delete_response['feed_id'] ) ) {
						$feedId    = $product_delete_response['feed_id'];
						$feed_type = 'JSON_LISTINGS_FEED';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save feed action with respect to each product
						foreach ( $products as $pkey => $product_id ) {
							$seller_id_val       = str_replace('|', '_', $grtopt_data);
							$product_feed_action = get_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, true );
							$current_feed_action = array('JSON_LISTINGS_FEED' => $feedId);
							if (is_array($product_feed_action) && !empty($product_feed_action)) {
								$product_feed_action = array_replace( $product_feed_action, $current_feed_action );
							} else {
								$product_feed_action = $current_feed_action;
							}
							update_post_meta( $product_id, 'ced_amazon_feed_actions_' . $seller_id_val, $product_feed_action );
						}

						$notice['message'] = 'Product delete feed has been processed and submitted.';
						$notice['classes'] = 'notice notice-success is-dismissable';
						return json_encode( $notice );

					} else {
						$notice['message'] = 'Something went wrong with feed submission, please check product delete feed URL/content!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

				} catch ( Exception $e ) {
					echo 'Exception when calling product delete feed API end point: ', esc_attr( $e->getMessage() ), PHP_EOL;
					$notice['message'] = 'Exception when calling product delete feed API end point!';
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
				}
			} else {
					$notice['message'] = 'Product delete data validation failed!';
					$notice['classes'] = 'notice notice-error is-dismissable';
					return json_encode( $notice );
			}
			$notice['message'] = 'Something went wrong with delete feed submission!';
			$notice['classes'] = 'notice notice-error is-dismissable';
			return json_encode( $notice );
		}


		/**
		 * Make product xml file send on amaozn
		 *
		 * @param unknown $proIds
		 * @param string  $isWriteXML
		 * @param string  $xmlFileName
		 */
		public function makeProductXMLFileToSendOnAmazon( $proIds = array(), $profileID = '', $getopt_data = '' ) {

			if ( empty($getopt_data) ) {
				$notice['message']           = 'Marketplace location is missing, please check!';
				$notice['classes']           = 'notice notice-error is-dismissable';
				$this->product_upload_notice = $notice;
				return;
			}

			$template_type = '';
			$seller_id     = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

			$finalAllProfileType              = array();
			$finalAllLoaderProductIds         = array();
			$finalAllProfileTypeWithProductId = array();

			if ( isset( $proIds['0'] ) ) {
				foreach ( $proIds as $pro_key => $product_id ) {

					// my new code start: get profile name based on product woo category
					global $wpdb;
					$tableName = $wpdb->prefix . 'ced_amazon_profiles';
					// $amazon_profiles    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles " ), 'ARRAY_A' );
					//$amazon_profiles = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles ", 'ARRAY_A' );
					$amazon_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s", $seller_id ), 'ARRAY_A' );
					$amazon_profiles = isset( $amazon_profiles ) ? $amazon_profiles : array();

					$terms      = get_the_terms( $product_id, 'product_cat' );
					$term_array = array();
					if ( $terms && ! is_wp_error( $terms ) ) {
						foreach ( $terms as $term ) {
							$term_array[] = $term->term_id;
						}
					}
					if ( ! empty( $amazon_profiles ) ) {
						foreach ( $amazon_profiles as $profileKey => $profileValues ) {
							$profile_woo_cat = json_decode( $profileValues['wocoommerce_category'], true );
							$match_woo_cat   = array_intersect( $profile_woo_cat, $term_array );
							if ( is_array( $match_woo_cat ) && ! empty( $match_woo_cat ) ) {
								$profileID     = $profileValues['id'];
								$template_type = $profileValues['template_type'];
							}
						}
					}
					update_post_meta( $product_id, 'ced_umb_amazon_profile_' . $getopt_data, $profileID );
					update_post_meta( $product_id, 'ced_amazon_profile_template_type_' . $getopt_data, $template_type );
					// my new code end.

					update_post_meta( $product_id, 'ced_umb_amazon_profile_updated_' . $getopt_data, 'yes' );
					delete_post_meta( $product_id, 'ced_prepared_for_template_temp' );

					$profileIDPerProduct = '';

					if ( '' == $profileID || '0' == $profileID ) {

						$final_profile_id = get_post_meta( $product_id, 'ced_umb_amazon_profile_' . $getopt_data, true );
						
						// $final_profile_id=(int)$final_profile_id;

						if ( '' == $final_profile_id || empty( $final_profile_id ) || 0 == $final_profile_id ) {
							$finalAllLoaderProductIds[ $product_id ] = $product_id;

							continue;
						} else {
							$profileIDPerProduct = $final_profile_id;
						}
					} else {
						$profileIDPerProduct = $profileID;
					}
					$finalAllProfileType[ $profileIDPerProduct ]                             = $profileIDPerProduct;
					$finalAllProfileTypeWithProductId[ $profileIDPerProduct ][ $product_id ] = $product_id;

					if ( '' != $profileIDPerProduct && '0' != $profileIDPerProduct ) {

						if ( isset($template_type) && 'amazonTemplate' == $template_type ) {
							$this->amazon_xml_lib_custom->fetchAssignedProfileDataOfProductCustom( $product_id, $getopt_data, $profileIDPerProduct );
							$amazonxmlarrayresponse = $this->amazon_xml_lib_custom->prepareAllProductTypeDataCustom( $product_id, $getopt_data, $profileIDPerProduct );
						} else {
							$this->amazon_xml_lib->fetchAssignedProfileDataOfProduct( $product_id, $getopt_data, $profileIDPerProduct );
							$amazonxmlarrayresponse = $this->amazon_xml_lib->prepareAllProductTypeData( $product_id, $getopt_data, $profileIDPerProduct );
						}

					} else {
						$finalAllLoaderProductIds[ $product_id ] = $product_id;
					}
				}
			}
			return json_encode(
				array(
					'invntory_loder_ids'   => array_unique( $finalAllLoaderProductIds ),
					'profile_ids'          => $finalAllProfileType,
					'profile_with_pro_ids' => $finalAllProfileTypeWithProductId,
				)
			);
		}


		/**
		 * Function to prepare final tab seprated file to get upload on Amazon upload product ids and countory wise!!
		 *
		 * @param array  $proIds
		 * @param string $isWriteXML
		 * @param string $xmlFileName
		 */
		public function exportCsvToUploadUsingPorIds( $proIds = '', $mplocation = '' ) {
			ob_start();

			ignore_user_abort( true );
			set_time_limit( 0 );
			// ini_set('max_execution_time', 600);
			// ini_set('memory_limit',-1);

			set_time_limit( 600 );
			wp_raise_memory_limit( -1 );

			ignore_user_abort( true );

			if ( ( isset( $proIds ) && ! empty( $proIds ) ) ) {

				$posts = array_values( $proIds );

				if ( empty( $posts ) ) {
					$notice['message'] = 'No products ready to upload! Check the profile or missing data';
					$notice['classes'] = 'notice notice-error is-dismissable';

					return json_encode( $notice );
					die( 'no posts to export' );
				}

				$getopt_data = $mplocation;

				$profileID = '';

				if ( isset( $posts['0'] ) ) {

					foreach ( $posts as $pro_key => $product_id ) {

						$profileIDPerProduct = '';

						if ( '' == $profileID || '0' == $profileID ) {

							$final_profile_id = get_post_meta( $product_id, 'ced_umb_amazon_profile_' . $getopt_data, true );
							$final_profile_id = (int) $final_profile_id;

							$template_type = get_post_meta( $product_id, 'ced_amazon_profile_template_type_' . $getopt_data, true );

							if ( '' == $final_profile_id || empty( $final_profile_id ) || 0 == $final_profile_id ) {
								continue;
							} else {
								$profileIDPerProduct = $final_profile_id;
							}
						} else {
							$profileIDPerProduct = $profileID;
						}
						if ( is_array( $posts ) && ! empty( $posts ) && ! empty( $profileIDPerProduct ) && isset( $posts[0] ) ) {

							if ( isset($template_type) && 'amazonTemplate' == $template_type ) {
								$this->amazon_xml_lib_custom->fetchAssignedProfileDataOfProductCustom( $posts[0], $getopt_data, $profileIDPerProduct );
								$template_fields_details = $this->amazon_xml_lib_custom->template_details;
							} else {
								$this->amazon_xml_lib->fetchAssignedProfileDataOfProduct( $posts[0], $getopt_data, $profileIDPerProduct );
								$template_fields_details = $this->amazon_xml_lib->template_details;
							}
							
							$template_details_info   = json_decode( $template_fields_details['template_details_info'], true );
							$template_fields_details = json_decode( $template_fields_details['template_fields_details'], true );

							$product_data = '';
							if ( isset( $template_fields_details[3] ) ) {
								$template_fields_details_temp_saved = array_filter($template_fields_details[3]);
								$template_fields_details_temp_saved = array_flip( $template_fields_details_temp_saved );
								$template_fields_details_temp_saved = array_fill_keys( array_keys( $template_fields_details_temp_saved ), '' );
							}
						}
						break;
					}

					$template_content     = '';
					$template_name_export = 'amazon_product_feed';
					if ( isset( $template_details_info['template_name'] ) ) {
						// $template_name_export='amazon_product_feed_for_'. $template_details_info['template_name'];
						$template_name_export = 'pro_ids_' . $template_details_info['template_name'];
					}

					if ( isset( $template_fields_details[1] ) && ! empty( $template_fields_details[1] ) ) {
						$template_content .= implode( "\t", $template_fields_details[1] );
						$template_content .= "\r\n";
						$template_content .= implode( "\t", $template_fields_details[2] );
						$template_content .= "\r\n";
						$template_content .= implode( "\t", $template_fields_details[3] );
						$template_content .= "\r\n";

						$final_products_values = array();
						foreach ( $posts as $key => $value ) {
							$products_should_exluded_from_template = get_option( 'ced_amzon_upload_queue', true );
							if ( isset( $products_should_exluded_from_template[ $value ] ) ) {
								continue;
							}

							$template_fields_details_temp = array();
							$template_fields_details_temp = $template_fields_details_temp_saved;
							if ( ! isset( $template_fields_details[3] ) ) {
								break;
							}

							$product_data = get_post_meta( $value, 'ced_amazon_final_pro_det_' . $getopt_data, true );
							// update_post_meta($value,'ced_amazon_final_pro_det_'.$getopt_data,'');
							$final_products_values_final = '';
							if ( is_array( $product_data ) && ! empty( $product_data ) && is_array( $template_fields_details_temp ) && ! empty( $template_fields_details_temp ) ) {
								$final_products_values_final = array_replace( $template_fields_details_temp, $product_data );
								if ( '' != $final_products_values_final ) {
									$template_content .= implode( "\t", $final_products_values_final );
									$template_content .= "\r\n";

								}
							}
						}
					} else {
						$notice['message'] = 'No products ready to upload';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

					if ( isset( $template_details_info['template_name'] ) ) {
						$tempfilePathFinal  = '';
						$tempfilePath       = ABSPATH . 'wp-content/uploads/';
						$tempfilePath       = $tempfilePath . 'ced-amazon/pro_ids_';
						$tempfilePathFinal .= 'pro_ids_' . $template_details_info['template_name'] . '.txt';
						$this->writeXMLStringToFile( $template_content, $tempfilePathFinal );
						if ( '' != $template_content ) {

							// ini_set("max_execution_time", "-1");
							// ini_set("memory_limit", "-1");

							set_time_limit( -1 );
							wp_raise_memory_limit( -1 );

							ignore_user_abort( true );
							set_time_limit( 0 );
							$this->uploadProductIds( array(), '', $getopt_data, $tempfilePathFinal, $template_content, $posts );
						}
						$notice['message'] = 'Product feed is processed and submitted!!';
						$notice['classes'] = 'notice notice-success is-dismissable';
						return json_encode( $notice );
					}   exit();
				}
				$notice['message'] = 'Failed to perform the operation!!';
				$notice['classes'] = 'notice notice-success is-dismissable';
				return json_encode( $notice );
				exit();
			}
		}


		/**
		 * Ship Amazon Order
		 *
		 * @name umb_amazon_shipment_order
		 * @link  http://www.cedcommerce.com/
		 */
		public function umb_amazon_shipment_order() {

			$check_ajax = check_ajax_referer( 'ced-amazon-order-shipment', 'ajax_nonce' );
			if ( ! $check_ajax ) {
				return;
			}

			// Order shipment via SP-API
			$post_order  = isset( $_POST['order'] ) ? sanitize_text_field( $_POST['order'] ) : '';
			$mplocation  = get_post_meta( $post_order, 'ced_amazon_order_countory_code', true );
			$grtopt_data = get_post_meta( $post_order, 'ced_amazon_order_seller_id', true );
			if ( empty( $mplocation ) || empty( $grtopt_data ) ) {
				echo esc_attr_e( 'Mp_location or Seller_id is missing for this order!', 'ced-amazon' );
				die;
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			//$grtopt_data          = get_option( 'ced_umb_amazon_bulk_profile_loc_temp', true );
			
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';
			if ( empty( $refresh_token ) || empty( $seller_id ) || empty( $marketplace_id ) ) {
				echo esc_attr_e( 'Invalid or missing seller data', 'ced-amazon' );
				die;
			}

			$order_id           = isset( $_POST['order'] ) ? sanitize_text_field( $_POST['order'] ) : '';
			$amazon_order_id    = get_post_meta( $order_id, 'amazon_order_id', true );
			$amazon_carrier     = isset( $_POST['carrier'] ) ? sanitize_text_field( $_POST['carrier'] ) : '';
			$amazon_methodCode  = isset( $_POST['methodCode'] ) ? sanitize_text_field( $_POST['methodCode'] ) : '';
			$amazon_ship_todate = isset( $_POST['ship_todate'] ) ? sanitize_text_field( $_POST['ship_todate'] ) : '';
			$amazon_tracking    = isset( $_POST['tracking'] ) ? sanitize_text_field( $_POST['tracking'] ) : '';

			update_post_meta( $order_id, 'ced_amzon_shipped_data', $_POST );

			$order_items   = get_post_meta( $order_id, 'order_items', true );
			$order_details = get_post_meta( $order_id, 'order_item_detail', true );

			$offset_end = $this->getStandardOffsetUTC(); // get offset
			if ( empty( $offset_end ) || '' == trim( $offset_end ) ) {
				$offset = '.0000000-00:00';
			} else {
				$offset = '.0000000' . trim( $offset_end );
			}

			$amazon_ship_todate = strtotime( $amazon_ship_todate );

			$Ship_todate = gmdate( 'Y-m-d', $amazon_ship_todate ) . 'T' . gmdate( 'H:i:s', $amazon_ship_todate ) . $offset;

			$ordershipfulfildata['CarrierCode']           = $amazon_carrier;
			$ordershipfulfildata['ShippingMethod']        = $amazon_methodCode;
			$ordershipfulfildata['ShipperTrackingNumber'] = $amazon_tracking;
			$ordershiparray['AmazonOrderID']              = $amazon_order_id;
			$ordershiparray['FulfillmentDate']            = $Ship_todate;
			$ordershiparray['FulfillmentData']            = $ordershipfulfildata;
			// $ordershiparray['MerchantOrderID'] = $order_id;
			// $ordershiparray['MerchantFulfillmentID'] = $order_id;

			$order     = new WC_Order( $order_id );
			$itemarray = array();
			foreach ( $order_details as $key => $order_item ) {
				$amznitem                        = array();
				$amznitem['AmazonOrderItemCode'] = $order_item['OrderItemId'];
				$amznitem['Quantity']            = $order_item['QuantityOrdered'];
				$itemarray[]                     = $amznitem;
				unset( $order_details[ $key ] );
			}
			$ordershiparray['Item'] = $itemarray;

			$ordershipmainarray                = array();
			$ordershipmainarray['@attributes'] = array(
				'xmlns:xsi'                     => 'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd',
			);

			$ordershipmainarray['Header']['DocumentVersion']    = '1.01';
			$ordershipmainarray['Header']['MerchantIdentifier'] = 'M_SELLER_XXXXXX';
			$ordershipmainarray['MessageType']                  = 'OrderFulfillment';
			$ordershipmainarray['PurgeAndReplace']              = 'false';
			$ordershipmainarray['Message']['MessageID']         = 1;
			$ordershipmainarray['Message']['OperationType']     = 'Update';
			$ordershipmainarray['Message']['OrderFulfillment']  = $ordershiparray;

			$directorypath = plugin_dir_path( __FILE__ );
			if ( ! class_exists( 'Array2XML' ) ) {
				require_once CED_AMAZON_DIRPATH . 'marketplaces/amazon/lib/array2xml.php';
			}
			$xml       = Array2XML::createXML( 'AmazonEnvelope', $ordershipmainarray );
			$xmlString = $xml->saveXML();

			$xmlFileName = 'shipment-data-' . $mplocation . '.xml';
			$this->writeXMLStringToFile( $xmlString, $xmlFileName );

			$tmp_path = ABSPATH . 'wp-content/uploads/ced-amazon/' . $xmlFileName;
			if ( ! file_exists( $tmp_path ) ) {
				echo esc_attr_e( 'Shipment data file not prepared, please check!', 'ced-amazon' );
				die;
			}

			// Order shipment via SP-API
			$file_path     = get_site_url() . '/wp-content/uploads/ced-amazon/';
			$file_path    .= $xmlFileName;
			$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
			$feed_body     = array(
				'feed_action'    => 'POST_ORDER_FULFILLMENT_DATA',
				'seller_id'      => $seller_id,
				//'file_url'       => $file_path,
				'marketplace_id' => $marketplace_id,
				'token'          => $refresh_token,
				'feed_content'   => $xmlString,
				'contract_id'      => get_option('ced_amazon_contract_id', '')
			);
			$feed_body     = wp_json_encode( $feed_body );

			$feed_options = array(
				'body'    => $feed_body,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 200,
			);

			$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
			if ( is_wp_error( $feed_reponse ) ) {
				echo esc_attr_e( 'Something went wrong with shipment feed submission, please try again later!', 'ced-amazon' );
				die;
			}
			$ordershipmentreponse = json_decode( $feed_reponse['body'], true );
			if ( isset($ordershipmentreponse['success']) && 'false' == $ordershipmentreponse['success'] ) {
				echo esc_attr( $ordershipmentreponse['body'] );
				die;
			}

			if ( isset( $ordershipmentreponse['feed_id'] ) && ! empty( $ordershipmentreponse['feed_id'] ) ) {
				$feedId = $ordershipmentreponse['feed_id'];

				$feedrequest['request']  = 'Shipped';
				$feedrequest['id']       = $feedId;
				$feedrequest['response'] = false;

				update_post_meta( $order_id, '_umb_order_feed_status', true );
				update_post_meta( $order_id, '_umb_order_feed_details', $feedrequest );

				$this->insertFeedInfoToDatabase( $feedId, 'POST_ORDER_FULFILLMENT_DATA', $mplocation );  // save product feed
				update_post_meta( $order_id, '_amazon_umb_order_status', 'Shipped' );
				echo esc_attr_e( 'Shipment Request Submitted Successfully', 'ced-amazon' );
				die;

			} else {
				echo esc_attr_e( 'Something went wrong with feed submission, please check shipment feed URL/content!', 'ced-amazon' );
				die;
			}
		}


		/**
		 * Update products inventory on Amazon while order sync in woo
		 *
		 * @name ced_amazon_order_product_inventory_update_on_marketplace
		 * @since 1.0.0
		 */
		public function ced_amazon_order_product_inventory_update_on_marketplace( $product_ids = array(), $mplocation = '', $seller_mp_key = '' ) {

			// Log file name
			$log_date = gmdate( 'Y-m-d' );
			$log_time = strtotime( gmdate( 'H:i:s' ) );
			//$log_name = "order_inventory_api_" . $log_date . '_' . $log_time .".txt";
			$log_name = 'order_inventory_api_' . $log_date . '.txt';

			if ( empty( $product_ids ) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "No product ids found to update inventory while order sync! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}

			if ( empty( $mplocation ) || empty($seller_mp_key) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Mplocation or seller id is missing! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
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
				$log_message .= "Seller API are missing \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			$seller_id      = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $seller_id ) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Refresh token/marketplace id/seller id are missing \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}

			if ( is_array( $product_ids ) && ! empty( $product_ids ) ) {
				$products = array_unique( $product_ids );
			} else {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "No product ids found to update inventory while order sync! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}

			set_time_limit( -1 );
			ignore_user_abort( true );

			$isWriteXML = true;
			// create inventory xml file when order sync in woo
			$xmlFileName       = 'order-sync-inventory-' . $mplocation . '.xml';
			$inventory_content = $this->makeInventoryXMLFileAfterOrderSync( $products, $isWriteXML, $mplocation, $xmlFileName, $seller_mp_key );

			// $directorypath = plugin_dir_path(__FILE__);
			$directorypath = CED_AMAZON_DIRPATH . 'marketplaces/amazon';
			$xsdfile       = "$directorypath/upload/xsds/amzn-envelope.xsd";
			if ( $this->validateXML( $xsdfile, $xmlFileName ) ) {
				$XMLfilePath  = get_site_url() . '/wp-content/uploads/ced-amazon/';
				$XMLfilePath .= $xmlFileName;

				try {
					// Inventory update feed API call using SP-API endpoint
					$feed_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_feed';
					$feed_body     = array(
						'feed_action'    => 'POST_INVENTORY_AVAILABILITY_DATA',
						'seller_id'      => $seller_id,
						//'file_url'       => $XMLfilePath,
						'marketplace_id' => $marketplace_id,
						'token'          => $refresh_token,
						'feed_content'   => $inventory_content,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
					);
					$feed_body     = wp_json_encode( $feed_body );

					$feed_options = array(
						'body'    => $feed_body,
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'timeout' => 200,
					);

					$feed_reponse = wp_remote_post( $feed_endpoint, $feed_options );
					if ( is_wp_error( $feed_reponse ) ) {
						// Save error in log
						ced_amazon_log_data( $feed_reponse, $log_name, 'feed' );
						return;
					}

					$inventoryuploadreponse = json_decode( $feed_reponse['body'], true );

					if ( isset($inventoryuploadreponse['success']) && 'false' == $inventoryuploadreponse['success'] ) {
						// Save error in log
						ced_amazon_log_data( $feed_reponse, $log_name, 'feed' );
						return;
					}

					if ( isset( $inventoryuploadreponse['feed_id'] ) && ! empty( $inventoryuploadreponse['feed_id'] ) ) {
						$feedId    = $inventoryuploadreponse['feed_id'];
						$feed_type = 'POST_INVENTORY_AVAILABILITY_DATA';
						$this->insertFeedInfoToDatabase( $feedId, $feed_type, $mplocation );

						// Save error in log
						$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
						$log_message .= "Inventory feed has processed and submitted while order sync. \n\n\n";
						ced_amazon_log_data( $log_message, $log_name, 'feed' );
						return;

					} else {
						// Save error in log
						$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
						$log_message .= "Something went wrong with inventory feed id!! \n\n\n";
						ced_amazon_log_data( $log_message, $log_name, 'feed' );
						return;
					}
				} catch ( Exception $e ) {
					// echo 'Exception when calling inventory feed API end point: ', $e->getMessage(), PHP_EOL;
					// Save error in log
					ced_amazon_log_data( $e->getMessage(), $log_name, 'feed' );
					return;
				}

			} else {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Inventory data validation failed while order sync! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'feed' );
				return;
			}
		}


		/**
		* Create product relist data xml file
		*
		* @name create_product_relist_data_xml_file
		* @since 1.0.0
		*/
		public function create_product_relist_data_xml_file( $proIds, $isWriteXML = true, $mplocation = '', $xmlFileName = '' ) {

			if ( empty($mplocation) || empty($xmlFileName) ) {
				return false;
			}

			$directorypath                    = plugin_dir_path( __FILE__ );
			$amazonxmlsubarray                = array();
			$amazonxmlsubarray['@attributes'] = array(
				'xmlns:xsi'                     => 'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd'
				);
			
			$amazonxmlsubarray['Header']['DocumentVersion']    = '1.01';
			$amazonxmlsubarray['Header']['MerchantIdentifier'] = 'M_SELLER_XXXXXX';
			$amazonxmlsubarray['MessageType']                  = 'Product';
			$amazonxmlsubarray['PurgeAndReplace']              = 'false';
			$i =1;

			foreach ($proIds as $product_id) {
				$product = wc_get_product( $product_id );
				if (!is_object($product)) {
					continue;
				}
				$productType = $product->get_type();

				if ( 'simple' == $productType ) {
					$amazonxmlarray = array();
					$qty            = $product->get_stock_quantity();
					$sku            = $product->get_sku();
					$asin           = get_post_meta( $product_id, 'ced_amazon_catalog_asin_' . $mplocation, true );

					if (isset($sku) && !empty($sku) && !empty($asin)) {
						$amazonxmlarray['MessageID']                             = $i;
						$amazonxmlarray['OperationType']                         = 'Update';
						$amazonxmlarray['Product']['SKU']                        = $sku;
						$amazonxmlarray['Product']['StandardProductID']['Type']  = 'ASIN';
						$amazonxmlarray['Product']['StandardProductID']['Value'] = $asin;
						$amazonxmlsubarray['Message'][]                          = $amazonxmlarray;
						$i++;
					}

				} else {

					if ( 'variable' == $productType ) {
						$amazonxmlarray = array();
						$qty            = $product->get_stock_quantity();
						$sku            = $product->get_sku();
						$asin           = get_post_meta( $product_id, 'ced_amazon_catalog_asin_' . $mplocation, true );

						if ( !empty($sku) ) {
							$parent_sku = $sku;
						}
						if (isset($sku) && !empty($sku) && !empty($asin)) {
							$amazonxmlarray['MessageID']                             = $i;
							$amazonxmlarray['OperationType']                         = 'Update';
							$amazonxmlarray['Product']['SKU']                        = $sku;
							$amazonxmlarray['Product']['StandardProductID']['Type']  = 'ASIN';
							$amazonxmlarray['Product']['StandardProductID']['Value'] = $asin;
							$amazonxmlsubarray['Message'][]                          = $amazonxmlarray;
							$i++;
						}

						$all_available_var = $product->get_available_variations();

						foreach ($all_available_var as $var_key => $var_value) {
							$amazonxmlarray = array();
							$product        = wc_get_product( $var_value['variation_id'] );
							$qty            = $product->get_stock_quantity();
							$sku            = $product->get_sku();
							$asin           = get_post_meta( $var_value['variation_id'], 'ced_amazon_catalog_asin_' . $mplocation, true );

							if ( isset($parent_sku) && $sku == $parent_sku ) {
								$sku = '';
							}

							if (isset($sku) && !empty($sku) && !empty($asin)) {
								$amazonxmlarray['MessageID']                             = $i;
								$amazonxmlarray['OperationType']                         = 'Update';
								$amazonxmlarray['Product']['SKU']                        = $sku;
								$amazonxmlarray['Product']['StandardProductID']['Type']  = 'ASIN';
								$amazonxmlarray['Product']['StandardProductID']['Value'] = $asin;
								$amazonxmlsubarray['Message'][]                          = $amazonxmlarray;
								$i++;
							}
						}

					}
				}
			}

			require_once CED_AMAZON_DIRPATH . 'marketplaces/amazon/lib/array2xml.php';
			$xml       = Array2XML::createXML('AmazonEnvelope', $amazonxmlsubarray);
			$xmlString = $xml->saveXML();
			$this->writeXMLStringToFile( $xmlString, $xmlFileName );
			return $xmlString;
		}



		/**
		 * Create Inventory File
		 *
		 * @name create_inventory_xml_data_file
		 * @since 1.0.0
		 */
		public function create_inventory_xml_data_file( $proIds, $isWriteXML = true, $shop_location = '', $xmlFileName = '', $seller_mp_key = '' ) {

			if ( empty($xmlFileName) ) {
				return false;
			}

			// Get global settings data
			$seller_global_settings = array();
			$global_settings        = get_option( 'ced_amazon_global_settings' );
			//$seller_location        = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
			$seller_location = $seller_mp_key;
			if ( isset( $global_settings[ $seller_location ] ) && ! empty( $global_settings[ $seller_location ] ) ) {
				$seller_global_settings = $global_settings[ $seller_location ];
			}

			$directorypath                    = plugin_dir_path( __FILE__ );
			$amazonxmlsubarray                = array();
			$amazonxmlsubarray['@attributes'] = array(
				'xmlns:xsi'                     => 'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd',
			);

			$amazonxmlsubarray['Header']['DocumentVersion']    = '1.01';
			$amazonxmlsubarray['Header']['MerchantIdentifier'] = 'M_SELLER_XXXXXX';
			$amazonxmlsubarray['MessageType']                  = 'Inventory';
			$amazonxmlsubarray['PurgeAndReplace']              = 'false';
			$i = 1;

			foreach ( $proIds as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
					continue;
				}
				$productType = $product->get_type();
				if ( 'simple' == $productType ) {
					$amazonxmlarray = array();
					$qty            = $product->get_stock_quantity();
					$sku            = $product->get_sku();

					/** Stock quantity thershold */
					if ( isset( $seller_global_settings['ced_amazon_product_stock_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_stock_type'] ) && isset( $seller_global_settings['ced_amazon_listing_stock'] ) && ! empty( $seller_global_settings['ced_amazon_listing_stock'] ) ) {

						$max_quantity_threshold = $seller_global_settings['ced_amazon_listing_stock'];
						if ( isset( $qty ) && $qty > $max_quantity_threshold ) {
							$qty = $max_quantity_threshold;
						}
					}

					$productLevelAmazonSKU = get_post_meta( $product_id, 'item_sku', true );
					if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
						$sku = $productLevelAmazonSKU;
						// print_r($sku);
					}

					$productLevelAmazonQty = get_post_meta( $product_id, 'quantity', true );
					if ( isset( $productLevelAmazonQty ) && is_numeric( $productLevelAmazonQty ) ) {
						$qty = ( $productLevelAmazonQty >= 0 ) ? $productLevelAmazonQty : 0;
					}

					if ( isset( $qty ) && isset( $sku ) && ! empty( $sku ) ) {
						$quantity                                = ( $qty >= 0 ) ? $qty : 0;
						$amazonxmlarray['MessageID']             = $i;
						$amazonxmlarray['OperationType']         = 'Update';
						$amazonxmlarray['Inventory']['SKU']      = $sku;
						$amazonxmlarray['Inventory']['Quantity'] = $quantity;
						// $amazonxmlarray['Inventory']['FulfillmentLatency'] =2;
						$amazonxmlsubarray['Message'][] = $amazonxmlarray;
						$i++;
					}
				} else {

					if ( 'variable' == $productType ) {
						$amazonxmlarray = array();

						$qty = $product->get_stock_quantity();
						$sku = $product->get_sku();
						if ( ! empty( $sku ) ) {
							$parent_sku = $product->get_sku();
						}

						/** Stock quantity thershold */
						if ( isset( $seller_global_settings['ced_amazon_product_stock_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_stock_type'] ) && isset( $seller_global_settings['ced_amazon_listing_stock'] ) && ! empty( $seller_global_settings['ced_amazon_listing_stock'] ) ) {

							$max_quantity_threshold = $seller_global_settings['ced_amazon_listing_stock'];
							if ( isset( $qty ) && $qty > $max_quantity_threshold ) {
								$qty = $max_quantity_threshold;
							}
						}

						$productLevelAmazonSKU = get_post_meta( $product_id, 'item_sku', true );
						if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
							$sku        = $productLevelAmazonSKU;
							$parent_sku = $productLevelAmazonSKU;
						}

						$productLevelAmazonQty = get_post_meta( $product_id, 'quantity', true );
						if ( isset( $productLevelAmazonQty ) && is_numeric( $productLevelAmazonQty ) ) {
							$qty = ( $productLevelAmazonQty >= 0 ) ? $productLevelAmazonQty : 0;
						}

						// if ( isset( $qty ) && isset( $sku ) && ! empty( $sku ) ) {
						// 	$quantity                                = ( $qty >= 0 ) ? $qty : 0;
						// 	$amazonxmlarray['MessageID']             = $i;
						// 	$amazonxmlarray['OperationType']         = 'Update';
						// 	$amazonxmlarray['Inventory']['SKU']      = $sku;
						// 	$amazonxmlarray['Inventory']['Quantity'] = $quantity;
						// 	// $amazonxmlarray['Inventory']['FulfillmentLatency'] = 2;
						// 	$amazonxmlsubarray['Message'][] = $amazonxmlarray;
						// 	$i++;
						// }

						$all_available_var = $product->get_available_variations();

						foreach ( $all_available_var as $var_key => $var_value ) {
							$product = wc_get_product( $var_value['variation_id'] );
							$qty     = $product->get_stock_quantity();
							$sku     = $product->get_sku();

							/** Stock quantity thershold */
							if ( isset( $seller_global_settings['ced_amazon_product_stock_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_stock_type'] ) && isset( $seller_global_settings['ced_amazon_listing_stock'] ) && ! empty( $seller_global_settings['ced_amazon_listing_stock'] ) ) {

								$max_quantity_threshold = $seller_global_settings['ced_amazon_listing_stock'];
								if ( isset( $qty ) && $qty > $max_quantity_threshold ) {
									$qty = $max_quantity_threshold;
								}
							}

							$productLevelAmazonSKU = get_post_meta( $var_value['variation_id'], 'item_sku', true );
							if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
								$sku = $productLevelAmazonSKU;
							}

							if ( isset( $parent_sku ) && $sku == $parent_sku ) {
								$sku = '';
							}

							$productLevelAmazonQty = get_post_meta( $var_value['variation_id'], 'quantity', true );
							if ( isset( $productLevelAmazonQty ) && is_numeric( $productLevelAmazonQty ) ) {
								$qty = ( $productLevelAmazonQty >= 0 ) ? $productLevelAmazonQty : 0;
							}

							if ( isset( $qty ) && isset( $sku ) && ! empty( $sku ) ) {
								$quantity                                = ( $qty >= 0 ) ? $qty : 0;
								$amazonxmlarray['MessageID']             = $i;
								$amazonxmlarray['OperationType']         = 'Update';
								$amazonxmlarray['Inventory']['SKU']      = $sku;
								$amazonxmlarray['Inventory']['Quantity'] = $quantity;
								// $amazonxmlarray['Inventory']['FulfillmentLatency'] = 2;
								$amazonxmlsubarray['Message'][] = $amazonxmlarray;
								$i++;
							}
						}
					}
				}
			}

			require_once CED_AMAZON_DIRPATH . 'marketplaces/amazon/lib/array2xml.php';
			$xml = Array2XML::createXML( 'AmazonEnvelope', $amazonxmlsubarray );
			//$xmlFileName = 'inventory.xml';
			$xmlString = $xml->saveXML();
			$this->writeXMLStringToFile( $xmlString, $xmlFileName );
			return $xmlString;
		}


		/**
		 * Create Inventory File after order sync
		 *
		 * @name makeInventoryXMLFileAfterOrderSync
		 * @since 1.0.0
		 */
		public function makeInventoryXMLFileAfterOrderSync( $proIds, $isWriteXML = true, $shop_location = '', $xmlFileName = '', $seller_mp_key = '' ) {

			if ( empty($xmlFileName) ) {
				return false;
			}

			// Get global settings data
			$seller_global_settings = array();
			$global_settings        = get_option( 'ced_amazon_global_settings' );
			//$seller_location        = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
			$seller_location = $seller_mp_key;
			if ( isset( $global_settings[ $seller_location ] ) && ! empty( $global_settings[ $seller_location ] ) ) {
				$seller_global_settings = $global_settings[ $seller_location ];
			}

			$directorypath                    = plugin_dir_path( __FILE__ );
			$amazonxmlsubarray                = array();
			$amazonxmlsubarray['@attributes'] = array(
				'xmlns:xsi'                     => 'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd',
			);

			$amazonxmlsubarray['Header']['DocumentVersion']    = '1.01';
			$amazonxmlsubarray['Header']['MerchantIdentifier'] = 'M_SELLER_XXXXXX';
			$amazonxmlsubarray['MessageType']                  = 'Inventory';
			$amazonxmlsubarray['PurgeAndReplace']              = 'false';
			$i = 1;

			foreach ( $proIds as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( ! is_object( $product ) ) {
					continue;
				}
				$productType = $product->get_type();

				$amazonxmlarray = array();
				$qty            = $product->get_stock_quantity();
				$sku            = $product->get_sku();

				/** Stock quantity thershold */
				if ( isset( $seller_global_settings['ced_amazon_product_stock_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_stock_type'] ) && isset( $seller_global_settings['ced_amazon_listing_stock'] ) && ! empty( $seller_global_settings['ced_amazon_listing_stock'] ) ) {

					$max_quantity_threshold = $seller_global_settings['ced_amazon_listing_stock'];
					if ( isset( $qty ) && $qty > $max_quantity_threshold ) {
						$qty = $max_quantity_threshold;
					}
				}

				$productLevelAmazonSKU = get_post_meta( $product_id, 'item_sku', true );
				if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
					$sku = $productLevelAmazonSKU;
				}

				$productLevelAmazonQty = get_post_meta( $product_id, 'quantity', true );
				if ( isset( $productLevelAmazonQty ) && is_numeric( $productLevelAmazonQty ) ) {
					$qty = ( $productLevelAmazonQty >= 0 ) ? $productLevelAmazonQty : 0;
				}

				if ( isset( $qty ) && isset( $sku ) && ! empty( $sku ) ) {
					$quantity                                = ( $qty >= 0 ) ? $qty : 0;
					$amazonxmlarray['MessageID']             = $i;
					$amazonxmlarray['OperationType']         = 'Update';
					$amazonxmlarray['Inventory']['SKU']      = $sku;
					$amazonxmlarray['Inventory']['Quantity'] = $quantity;
					// $amazonxmlarray['Inventory']['FulfillmentLatency'] = 2;
					$amazonxmlsubarray['Message'][] = $amazonxmlarray;
					$i++;
				}
			}

			require_once CED_AMAZON_DIRPATH . 'marketplaces/amazon/lib/array2xml.php';
			$xml = Array2XML::createXML( 'AmazonEnvelope', $amazonxmlsubarray );
			//$xmlFileName = 'order-sync-inventory.xml';
			$xmlString = $xml->saveXML();
			$this->writeXMLStringToFile( $xmlString, $xmlFileName );
			return $xmlString;
		}


		/**
		 * Create Price File
		 *
		 * @name makePriceXMLFileToSendOnAmazon
		 * @since 1.0.0
		 */
		public function makePriceXMLFileToSendOnAmazon( $proIds, $isWriteXML = true, $mplocation = '', $xmlFileName = '', $seller_mp_key = '' ) {

			if ( empty($xmlFileName)  ) {
				return false;
			}

			// Get global settings data
			$seller_global_settings = array();
			$global_settings        = get_option( 'ced_amazon_global_settings' );
			//$seller_location        = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : get_option( 'ced_umb_amazon_bulk_profile_loc_temp' );
			$seller_location = $seller_mp_key;
			if ( isset( $global_settings[ $seller_location ] ) && ! empty( $global_settings[ $seller_location ] ) ) {
				$seller_global_settings = $global_settings[ $seller_location ];
			}

			$directorypath                    = plugin_dir_path( __FILE__ );
			$amazonxmlsubarray                = array();
			$amazonxmlsubarray['@attributes'] = array(
				'xmlns:xsi'                     => 'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd',
			);

			$amazonxmlsubarray['Header']['DocumentVersion']    = '1.01';
			$amazonxmlsubarray['Header']['MerchantIdentifier'] = 'M_SELLER_XXXXXX';
			$amazonxmlsubarray['MessageType']                  = 'Price';
			$amazonxmlsubarray['PurgeAndReplace']              = 'false';

			$i = 1;
			foreach ( $proIds as $product_id ) {
				if ( 1 ) {
					$product = wc_get_product( $product_id );
					if ( ! is_object( $product ) ) {
						continue;
					}
					$productType = $product->get_type();
					if ( 'simple' == $productType ) {
						$amazonxmlarray = array();
						$qty            = $product->get_stock_quantity();
						$sku            = $product->get_sku();
						$price_amz      = $product->get_price();

						if ( isset( $seller_global_settings['ced_amazon_product_markup_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup_type'] ) && isset( $seller_global_settings['ced_amazon_product_markup'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup'] ) ) {

							if ( 'Fixed_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
								$price_amz = (float) $price_amz + (float) $seller_global_settings['ced_amazon_product_markup'];
							} elseif ( 'Fixed_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
								$price_amz = (float) $price_amz - (float) $seller_global_settings['ced_amazon_product_markup'];
							} elseif ( 'Percentage_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
								$price_amz = (float) $price_amz + ( ( (float) $price_amz * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
							} elseif ( 'Percentage_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
								$price_amz = (float) $price_amz - ( ( (float) $price_amz * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
							}
						}

						$productLevelAmazonSKU = get_post_meta( $product_id, 'item_sku', true );
						if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
							$sku = $productLevelAmazonSKU;
						}

						$productLevelAmazonPrice = get_post_meta( $product_id, 'standard_price', true );
						if ( isset( $productLevelAmazonPrice ) && ! empty( $productLevelAmazonPrice ) ) {
							$price_amz = $productLevelAmazonPrice;
							// print_r($price_amz);
						}

						if ( isset( $price_amz ) && ! empty( $price_amz ) && isset( $sku ) && ! empty( $sku ) ) {
							$amazonxmlarray['MessageID']     = $i;
							$amazonxmlarray['OperationType'] = 'Update';
							$amazonxmlarray['Price']['SKU']  = $sku;
							$amazonxmlarray['Price']['StandardPrice']['@attributes']['currency'] = 'DEFAULT';
							$amazonxmlarray['Price']['StandardPrice']['@value']                  = round( $price_amz, 2 );
							$amazonxmlsubarray['Message'][]                                      = $amazonxmlarray;
							$i++;
						}
					} else {
						if ( 'variable' == $productType ) {
							$amazonxmlarray = array();
							$qty            = $product->get_stock_quantity();
							$sku            = $product->get_sku();
							if ( ! empty( $sku ) ) {
								$parent_sku = $product->get_sku();
							}
							$price_amz = $product->get_price();

							if ( isset( $seller_global_settings['ced_amazon_product_markup_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup_type'] ) && isset( $seller_global_settings['ced_amazon_product_markup'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup'] ) ) {

								if ( 'Fixed_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
									$price_amz = (float) $price_amz + (float) $seller_global_settings['ced_amazon_product_markup'];
								} elseif ( 'Fixed_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
									$price_amz = (float) $price_amz - (float) $seller_global_settings['ced_amazon_product_markup'];
								} elseif ( 'Percentage_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
									$price_amz = (float) $price_amz + ( ( (float) $price_amz * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
								} elseif ( 'Percentage_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
									$price_amz = (float) $price_amz - ( ( (float) $price_amz * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
								}
							}

							$productLevelAmazonSKU = get_post_meta( $product_id, 'item_sku', true );
							if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
								$sku        = $productLevelAmazonSKU;
								$parent_sku = $productLevelAmazonSKU;
							}

							$productLevelAmazonPrice = get_post_meta( $product_id, 'standard_price', true );
							if ( isset( $productLevelAmazonPrice ) && ! empty( $productLevelAmazonPrice ) ) {
								$price_amz = $productLevelAmazonPrice;
								// print_r($price_amz);
							}

							if ( isset( $price_amz ) && ! empty( $price_amz ) && isset( $sku ) && ! empty( $sku ) ) {
								$amazonxmlarray['MessageID']     = $i;
								$amazonxmlarray['OperationType'] = 'Update';
								$amazonxmlarray['Price']['SKU']  = $sku;
								$amazonxmlarray['Price']['StandardPrice']['@attributes']['currency'] = 'DEFAULT';
								$amazonxmlarray['Price']['StandardPrice']['@value']                  = round( $price_amz, 2 );
								$amazonxmlsubarray['Message'][]                                      = $amazonxmlarray;
								$i++;
							}
							$all_available_var = $product->get_available_variations();

							foreach ( $all_available_var as $var_key => $var_value ) {
								$product = wc_get_product( $var_value['variation_id'] );

								$qty = $product->get_stock_quantity();
								$sku = $product->get_sku();

								$productLevelAmazonSKU = get_post_meta( $var_value['variation_id'], 'item_sku', true );
								if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
									$sku = $productLevelAmazonSKU;
								}

								if ( isset( $parent_sku ) && $sku == $parent_sku ) {
									// $sku = $var_value['variation_id'];
									$sku = '';
								}
								$price_amz = $product->get_price();

								if ( isset( $seller_global_settings['ced_amazon_product_markup_type'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup_type'] ) && isset( $seller_global_settings['ced_amazon_product_markup'] ) && ! empty( $seller_global_settings['ced_amazon_product_markup'] ) ) {

									if ( 'Fixed_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
										$price_amz = (float) $price_amz + (float) $seller_global_settings['ced_amazon_product_markup'];
									} elseif ( 'Fixed_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
										$price_amz = (float) $price_amz - (float) $seller_global_settings['ced_amazon_product_markup'];
									} elseif ( 'Percentage_Increased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
										$price_amz = (float) $price_amz + ( ( (float) $price_amz * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
									} elseif ( 'Percentage_Decreased' == $seller_global_settings['ced_amazon_product_markup_type'] ) {
										$price_amz = (float) $price_amz - ( ( (float) $price_amz * (float) $seller_global_settings['ced_amazon_product_markup'] ) / 100 );
									}
								}

								$productLevelAmazonPrice = get_post_meta( $var_value['variation_id'], 'standard_price', true );
								if ( isset( $productLevelAmazonPrice ) && ! empty( $productLevelAmazonPrice ) ) {
									$price_amz = $productLevelAmazonPrice;
									// print_r($price_amz);
								}

								if ( isset( $price_amz ) && ! empty( $price_amz ) && isset( $sku ) && ! empty( $sku ) ) {
									$amazonxmlarray['MessageID']     = $i;
									$amazonxmlarray['OperationType'] = 'Update';
									$amazonxmlarray['Price']['SKU']  = $sku;
									$amazonxmlarray['Price']['StandardPrice']['@attributes']['currency'] = 'DEFAULT';
									$amazonxmlarray['Price']['StandardPrice']['@value']                  = round( $price_amz, 2 );
									$amazonxmlsubarray['Message'][]                                      = $amazonxmlarray;
									$i++;
								}
							}
						}
					}
				}
			}

			require_once CED_AMAZON_DIRPATH . 'marketplaces/amazon/lib/array2xml.php';
			$xml = Array2XML::createXML( 'AmazonEnvelope', $amazonxmlsubarray );
			//$xmlFileName = 'price.xml';
			$xmlString = $xml->saveXML();
			$this->writeXMLStringToFile( $xmlString, $xmlFileName );
			return $xmlString;
		}


		/**
		 * Create Image File
		 *
		 * @name makeImageXMLFileToSendOnAmazon
		 * @since 1.0.0
		 */
		public function makeImageXMLFileToSendOnAmazon( $proIds, $isWriteXML = true, $mplocation = '', $xmlFileName = '', $seller_mp_key = '' ) {

			if ( empty($xmlFileName) ) {
				return false;
			}

			$directorypath                    = plugin_dir_path( __FILE__ );
			$amazonxmlsubarray                = array();
			$amazonxmlsubarray['@attributes'] = array(
				'xmlns:xsi'                     => 'http://www.w3.org/2001/XMLSchema-instance',
				'xsi:noNamespaceSchemaLocation' => 'amzn-envelope.xsd',
			);

			$amazonxmlsubarray['Header']['DocumentVersion']    = '1.01';
			$amazonxmlsubarray['Header']['MerchantIdentifier'] = 'M_SELLER_XXXXXX';
			$amazonxmlsubarray['MessageType']                  = 'ProductImage';
			$amazonxmlsubarray['PurgeAndReplace']              = 'false';

			$i = 1;
			foreach ( $proIds as $product_id ) {
				if ( 1 ) {
					$product = wc_get_product( $product_id );
					if ( ! is_object( $product ) ) {
						continue;
					}
					$productType       = $product->get_type();
					$post_thumbnail_id = get_post_thumbnail_id( $product_id );
					$image             = wp_get_attachment_image_url( $post_thumbnail_id, 'full', false );
					$amazonxmlarray    = array();
					$sku               = $product->get_sku();

					$productLevelAmazonSKU = get_post_meta( $product_id, 'item_sku', true );
					if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
						$sku = $productLevelAmazonSKU;
					}

					if ( ! empty( $image ) ) {
						$attachment_url_modified = $this->amazon_xml_lib->modifyImageUrl( $image );
						$image                   = ! empty( $attachment_url_modified ) ? $attachment_url_modified : $image;
					}

					if ( isset( $image ) && ! empty( $image ) && isset( $sku ) && ! empty( $sku ) ) {
						$amazonxmlarray['MessageID']                     = $i;
						$amazonxmlarray['OperationType']                 = 'Update';
						$amazonxmlarray['ProductImage']['SKU']           = $sku;
						$amazonxmlarray['ProductImage']['ImageType']     = 'Main';
						$amazonxmlarray['ProductImage']['ImageLocation'] = $image;
						$amazonxmlsubarray['Message'][]                  = $amazonxmlarray;
						// $i = $i+1;
						++$i;
					}

					if ( '3.0.0' > WC()->version ) {
						$attachment_ids = $product->get_gallery_attachment_ids();
					} else {
						$attachment_ids = $product->get_gallery_image_ids();
					}
					if ( isset( $attachment_ids ) && ! empty( $attachment_ids ) && isset( $sku ) && ! empty( $sku ) ) {
						$j = 1;
						foreach ( $attachment_ids as $attachment_id ) {
							if ( 8 > $j ) {
								$alternateimage = wp_get_attachment_url( $attachment_id );
								if ( ! empty( $alternateimage ) ) {
									$attachment_url_modified = $this->amazon_xml_lib->modifyImageUrl( $alternateimage );
									$alternateimage          = ! empty( $attachment_url_modified ) ? $attachment_url_modified : $alternateimage;
								}
								$amazonxmlarray['MessageID']                     = $i;
								$amazonxmlarray['OperationType']                 = 'Update';
								$amazonxmlarray['ProductImage']['SKU']           = $sku;
								$amazonxmlarray['ProductImage']['ImageType']     = "PT$j";
								$amazonxmlarray['ProductImage']['ImageLocation'] = $alternateimage;
								$amazonxmlsubarray['Message'][]                  = $amazonxmlarray;
								// $i = $i+1;
								// $j = $j+1;
								++$i;
								++$j;
							}
						}
					}

					if ( 'variable' == $productType ) {
						$amazonxmlarray = array();
						$parent_product = $product;
						if ( '3.0.0' > WC()->version ) {
							$attachment_ids = $parent_product->get_gallery_attachment_ids();
						} else {
							$attachment_ids = $parent_product->get_gallery_image_ids();
						}

						if ( ! empty( $product->get_sku() ) ) {
							$parent_sku = $product->get_sku();
						}

						$productLevelAmazonPSKU = get_post_meta( $product_id, 'item_sku', true );
						if ( isset( $productLevelAmazonPSKU ) && ! empty( $productLevelAmazonPSKU ) ) {
							$parent_sku = $productLevelAmazonPSKU;
						}

						$all_available_var = $product->get_available_variations();
						foreach ( $all_available_var as $var_key => $var_value ) {
							$product    = wc_get_product( $var_value['variation_id'] );
							$product_id = $var_value['variation_id'];
							$sku        = $product->get_sku();

							$productLevelAmazonSKU = get_post_meta( $var_value['variation_id'], 'item_sku', true );
							if ( isset( $productLevelAmazonSKU ) && ! empty( $productLevelAmazonSKU ) ) {
								$sku = $productLevelAmazonSKU;
							}

							if ( isset( $parent_sku ) && $sku == $parent_sku ) {
								// $sku = $var_value['variation_id'];
								$sku = '';
							}

							// if( empty($sku) ){
							// 	$sku = $var_value['variation_id'];
							// }

							$post_thumbnail_id = get_post_thumbnail_id( $var_value['variation_id'] );
							$var_image         = '';
							$var_image         = wp_get_attachment_image_url( $post_thumbnail_id, 'full', false );
							if ( '' == $var_image ) {
								$var_image = $image;
							}

							if ( ! empty( $var_image ) ) {
								$attachment_url_modified = $this->amazon_xml_lib->modifyImageUrl( $var_image );
								$var_image               = ! empty( $attachment_url_modified ) ? $attachment_url_modified : $var_image;
							}

							if ( isset( $var_image ) && ! empty( $var_image ) && isset( $sku ) && ! empty( $sku ) ) {
								$amazonxmlarray['MessageID']                     = $i;
								$amazonxmlarray['OperationType']                 = 'Update';
								$amazonxmlarray['ProductImage']['SKU']           = $sku;
								$amazonxmlarray['ProductImage']['ImageType']     = 'Main';
								$amazonxmlarray['ProductImage']['ImageLocation'] = $var_image;
								$amazonxmlsubarray['Message'][]                  = $amazonxmlarray;
								// $i = $i+1;
								++$i;
							}

							if ( isset( $attachment_ids ) && ! empty( $attachment_ids ) && isset( $sku ) && ! empty( $sku ) ) {
								$j = 1;
								foreach ( $attachment_ids as $attachment_id ) {
									if ( 8 > $j ) {
										$alternateimage = wp_get_attachment_url( $attachment_id );
										if ( ! empty( $alternateimage ) ) {
											$attachment_url_modified = $this->amazon_xml_lib->modifyImageUrl( $alternateimage );
											$alternateimage          = ! empty( $attachment_url_modified ) ? $attachment_url_modified : $alternateimage;
										}
										$amazonxmlarray['MessageID']                     = $i;
										$amazonxmlarray['OperationType']                 = 'Update';
										$amazonxmlarray['ProductImage']['SKU']           = $sku;
										$amazonxmlarray['ProductImage']['ImageType']     = "PT$j";
										$amazonxmlarray['ProductImage']['ImageLocation'] = $alternateimage;
										$amazonxmlsubarray['Message'][]                  = $amazonxmlarray;
										// $i = $i+1;
										// $j = $j+1;
										++$i;
										++$j;
									}
								}
							}
						}
					}
				}
			}

			require_once CED_AMAZON_DIRPATH . 'marketplaces/amazon/lib/array2xml.php';
			$xml = Array2XML::createXML( 'AmazonEnvelope', $amazonxmlsubarray );
			//$xmlFileName = 'image.xml';
			$xmlString = $xml->saveXML();
			$this->writeXMLStringToFile( $xmlString, $xmlFileName );
			return $xmlString;
		}
		
		
		/**
		 * Check ASIN of product from Amazon
		 *
		 * @name ced_amazon_look_up
		 * @since 1.0.0
		 */
		public function ced_amazon_look_up( $proIds = array(), $mplocation = '', $seller_mp_key = '') {
		$seller_id = $seller_mp_key;
		// die("--");
		$directorypath = plugin_dir_path(__FILE__);

		// Log file name
		$log_date = gmdate( 'Y-m-d' );
		$log_time = strtotime( gmdate( 'H:i:s' ) );
		//$log_name = "catalog_api_" . $log_date . '_' . $log_time .".txt";
		$log_name = 'catalog_api_' . $log_date . '.txt';

		$saved_amazon_details = get_option('ced_amzon_configuration_validated', false);
		$location_for_seller  = $seller_id;
			if ( isset($saved_amazon_details[$location_for_seller]) && !empty($saved_amazon_details[$location_for_seller]) && is_array($saved_amazon_details[$location_for_seller]) ) {
				$shop_data = $saved_amazon_details[$location_for_seller];
			}

		$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
		$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';

			if ( empty($refresh_token) || empty($marketplace_id) || empty($mplocation) || empty($location_for_seller) ) {
				// Save error in log
				$log_message  = gmdate( 'Y-m-d\Th:i:s' ) . "\n";
				$log_message .= "Refresh_token/marketplace_id/mplocation/seller_id are missing while ASIN sync! \n\n\n";
				ced_amazon_log_data( $log_message, $log_name, 'catalog' );
				return;
			}

		// Get UPC/EAN mapping data from global settings
		$global_setting_data = get_option( 'ced_amazon_global_settings', false );
	
		$meta_key_map = isset( $global_setting_data[$location_for_seller]['ced_amazon_catalog_asin_sync_meta'] ) ? $global_setting_data[$location_for_seller]['ced_amazon_catalog_asin_sync_meta'] : '_sku';
		
			if (empty($meta_key_map)) {
				$meta_key_map = '_sku';
			}

		$products = $proIds;
			if ( isset($products) && !empty($products) ) {

				foreach ($products as $product_id) {

					$product   = wc_get_product($product_id);
					$parent_id = $product->get_parent_id();

					// Get UPC/EAN number from woo meta
					$upc_number = get_post_meta( $product_id, $meta_key_map, true );
				
					$upc_number_length = strlen($upc_number);
					// echo $upc_number_length; die();

					if ( !empty($upc_number) && is_numeric($upc_number) && ( 11==$upc_number_length || 12==$upc_number_length || 13==$upc_number_length || 14==$upc_number_length ) ) {
						// Request to get product data using UPC/EAN
						$search_catalog_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/search_catalog_items';
						$body                    = [
						'marketplace_id'  => $marketplace_id,
						'token'           => $refresh_token,
						'ean'             => $upc_number,
						'contract_id'      => get_option('ced_amazon_contract_id', '')
						];
						$body                    = wp_json_encode( $body );
						$options                 = [
						'body'        => $body,
						'headers'     => [
							'Content-Type' => 'application/json',
						],
						'timeout'     => 200,
						];

						$catalog_response_main = wp_remote_post( $search_catalog_endpoint, $options );
					
						if ( is_wp_error( $catalog_response_main ) ) {
							//$error = 'Something went wrong with ASIN sync, please try again later!';
							ced_amazon_log_data( $catalog_response_main, $log_name, 'catalog' );
							$notice['message'] = 'Something went wrong Look up on Amazon, please try again later!';
							$notice['classes'] = 'notice notice-error is-dismissable';
							return json_encode( $notice );
							continue;
						}
						$catalog_response = json_decode($catalog_response_main['body'], true);
						if ( isset($catalog_response['success']) && 'false' == $catalog_response['success'] ) {
							// Save error in log
							ced_amazon_log_data( $catalog_response_main, $log_name, 'catalog' );
							ced_amazon_log_data( $catalog_response_main, $log_name, 'catalog' );
							$notice['message'] = 'Something went wrong Look up on Amazon, please try again later!';
							$notice['classes'] = 'notice notice-error is-dismissable';
							return json_encode( $notice );
							continue;
						}
					
					
						if ( isset($catalog_response['payload']['Items'][0]) && is_array($catalog_response['payload']['Items'][0]) && !empty($catalog_response['payload']['Items'][0]) ) {
							// echo "<pre>";
							// print_r($catalog_response['payload']['Items'][0]); die("data");
							$child_asin = $catalog_response['payload']['Items'][0]['Identifiers']['MarketplaceASIN']['ASIN'];
							// echo $child_asin; die("child");
							$parent_asin = isset($catalog_response['payload']['Items'][0]['Relationships'][0]['Identifiers']['MarketplaceASIN']['ASIN'])?$catalog_response['payload']['Items'][0]['Relationships'][0]['Identifiers']['MarketplaceASIN']['ASIN']:'';

							if ( !empty($child_asin) ) {
								update_post_meta($product_id, 'ced_amazon_catalog_asin_' . $mplocation, $child_asin);
							}

							if ( 0 != $parent_id && !empty($parent_asin) ) {
								update_post_meta($parent_id, 'ced_amazon_catalog_asin_' . $mplocation, $parent_asin);
							}
							$notice['message'] = 'Product Look up has been processed.';
							$notice['classes'] = 'notice notice-success is-dismissable';
							return json_encode( $notice );

						} else {
							$notice['message'] = 'Product Look up not found on Amazon.';
							$notice['classes'] = 'notice notice-success is-dismissable';
							return json_encode( $notice );
						}
					
					} else {
						$notice['message'] = 'UPC/EAN invalid. Please check!';
						$notice['classes'] = 'notice notice-error is-dismissable';
						return json_encode( $notice );
					}

				}
			} else {
				$notice['message'] = 'Please select product for Look Up on Amazon';
				$notice['classes'] = 'notice notice-error is-dismissable';
				return json_encode( $notice );
			}

		}


		/**
		* Create json file for delete product
		*
		* @name create_product_delete_data_json_file
		* @since 1.0.0
		*/
		public function create_product_delete_data_json_file( $proIds, $isWriteJSON = true, $mplocation = '', $jsonFileName = '', $seller_id = '' ) {

			if ( empty($jsonFileName) || empty($seller_id) || empty($mplocation) ) {
				return false;
			}

			$json_format = false;
			$messages    = array();
			$counter     = 1;

			foreach ($proIds as $product_id) {

				$product = wc_get_product( $product_id );
				if (!is_object($product)) {
					continue;
				}

				//$productType = $product->get_type();
				$sku = $product->get_sku();

				if (isset($sku) && !empty($sku)) {

					update_post_meta( $product_id, 'ced_amazon_already_uploaded_' . $mplocation, 'no' );
					delete_post_meta( $product_id, 'ced_amazon_product_asin_' . $mplocation );
					
					$msg        = [
						'messageId'     => $counter,
						'sku'           => $sku,
						'operationType' => 'DELETE'
					];
					$messages[] = $msg;
					$counter++;
					$json_format = true;
				}

			}

			$header = [
				'sellerId'    => $seller_id,
				'version'     => '2.0',
				'issueLocale' => 'en_US'
			];

			$product_info = [
				'header'   => $header,
				'messages' => $messages
			];

			$json_data = wp_json_encode($product_info);
			$this->writeXMLStringToFile( $json_data, $jsonFileName );

			if ( $json_format ) {
				return $json_data;
			} else {
				return false;
			}

		}


		/**
		 * Check feed status of amazon order shipment.
		 *
		 * @name umb_amazon_check_feed_status
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public function umb_amazon_check_feed_status() {

			$check_ajax = check_ajax_referer( 'ced-amazon-order-shipment', 'ajax_nonce' );
			if ( ! $check_ajax ) {
				return;
			}

			$feedId     = isset( $_POST['feed_id'] ) ? sanitize_text_field( $_POST['feed_id'] ) : '';
			$order_id   = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$mplocation = get_post_meta( $order_id, 'ced_amazon_order_countory_code', true );
			$seller_id  = get_post_meta( $order_id, 'ced_amazon_order_seller_id', true );
			if ( empty( $feedId ) || empty( $mplocation ) || empty( $seller_id ) ) {
				echo esc_attr_e( 'Feed_id/Mp_location/Seller_id is missing!', 'ced-amazon' );
				die;
			}

			$feedresponse = $this->getFeedItemsStatusSpApi( $feedId, 'POST_ORDER_FULFILLMENT_DATA', $mplocation, '', $seller_id );
			if ( isset( $feedresponse['body'] ) ) {

				$finalxml    = simplexml_load_string( $feedresponse['body'], 'SimpleXMLElement', LIBXML_NOCDATA );
				$finalstring = json_encode( $finalxml );
				$finalresult = json_decode( $finalstring, true );
			}

			if ( isset( $finalresult['Message']['ProcessingReport']['StatusCode'] ) ) {
				if ( 'Complete' == $finalresult['Message']['ProcessingReport']['StatusCode'] ) {
					if ( 0 == $finalresult['Message']['ProcessingReport']['ProcessingSummary']['MessagesWithError'] ) {
						$feed_req = isset( $_POST['feed_req'] ) ? sanitize_text_field( $_POST['feed_req'] ) : '';
						update_post_meta( $order_id, '_amazon_umb_order_status', $feed_req );
						update_post_meta( $order_id, '_umb_order_feed_status', false );
						$feeddetails             = get_post_meta( $order_id, '_umb_order_feed_details', true );
						$feeddetails['response'] = true;
						update_post_meta( $order_id, '_umb_order_feed_details', $feeddetails );
						echo esc_attr( $feed_req ) . ' Feed is process successfully';
						die;
					} else {
						if ( isset( $finalresult['Message']['ProcessingReport']['Result'] ) ) {
							$errormessages = isset( $finalresult['Message']['ProcessingReport']['Result'][0] ) ? $finalresult['Message']['ProcessingReport']['Result'] : $finalresult['Message']['ProcessingReport'];

							foreach ( $errormessages as $errormessage ) {
								if ( isset( $errormessage['ResultDescription'] ) ) {
									echo esc_attr( $errormessage['ResultDescription'] );
									update_post_meta( $order_id, '_umb_order_feed_status', false );
								}
							}
						}
						die;
					}
				}
			}
			echo 'Request is under process.';
			die;
		}


		/**
		 * This function writes xml string to destination file.
		 *
		 * @name writeXMLStringToFile()
		 * @link  http://www.cedcommerce.com/
		 */
		public function writeXMLStringToFile( $xmlString, $fileName ) {
			$XMLfilePath = ABSPATH . 'wp-content/uploads/';
			if ( ! is_dir( $XMLfilePath ) ) {
				if ( ! mkdir( $XMLfilePath, 0755 ) ) {
					return false;
				}
			}
			$XMLfilePath = $XMLfilePath . 'ced-amazon/';
			if ( ! is_dir( $XMLfilePath ) ) {
				if ( ! mkdir( $XMLfilePath, 0755 ) ) {
					return false;
				}
			}

			if ( ! is_writable( $XMLfilePath ) ) {
				return false;
			}
			$XMLfilePath .= $fileName;
			$XMLfile      = fopen( $XMLfilePath, 'w' );
			fwrite( $XMLfile, $xmlString );
			fclose( $XMLfile );
		}


		/**
		 * Validate XML against xsd before sending to Amazon
		 *
		 * @name validateXML
		 * @since 1.0.0
		 */
		public function validateXML( $xsdfile, $xmlFileName, $showerror = true, $returnerror = false ) {
			$XMLfilePath  = ABSPATH . 'wp-content/uploads/ced-amazon/';
			$XMLfilePath .= $xmlFileName;
			$return       = true;

			// print_r($XMLfilePath);

			libxml_use_internal_errors( true );
			$feed                     = new DOMDocument();
			$feed->preserveWhitespace = false;
			$result                   = $feed->load( $XMLfilePath );

			if ( true === $result ) {
				if ( @( $feed->schemaValidate( $xsdfile ) ) ) {
					global $ced_umb_helper_amaz;
					// echo '<pre>'; print_r($showerror); die('jj');
					// if($showerror)
					// {
					// echo "Valid";
					// }
					$log_detail  = "\nmessage: Product XML ERRORS \n";
					$log_detail .= 'No Errors :: Valid XML' . "\n******************************************************************\n\n\n\n\n";
					// $ced_umb_helper_amaz->umb_write_logs("amazon-product-xml.log",$log_detail);
					$logFilePath = ABSPATH . 'wp-content/uploads/ced-amazon/logs/amazon-product-xml.log';
					// if ( ! $fp == fopen( $logFilePath, 'a' ) ) {
					$fp = fopen( $logFilePath, 'a' ); 
					if ( !$fp  ) {		
						return;
					}
					$fr = fwrite( $fp, $log_detail . "\n" );
					fclose( $fp );
				} else {
					$errorList = '';
					$return    = false;
					$errors    = libxml_get_errors();

					foreach ( $errors as $error ) {
						$errorList .= "---\n";
						$errorList .= $error->message . "\n";
						//$errorList .= '<br/>';
					}
					$this->feed_xml_notice = $errorList;
					// if ( $showerror ) {
					// 	//echo esc_attr( $errorList );
					// }
					if ( $returnerror ) {
						return esc_attr( $errorList );
					}
					global $ced_umb_helper_amaz;
					$log_detail  = "\nmessage: Product XML ERRORS \n";
					$log_detail .= $errorList . "\n******************************************************************\n\n\n\n\n";
					// $ced_umb_helper_amaz->umb_write_logs("amazon-product-xml.log",$log_detail);
					$logFilePath = ABSPATH . 'wp-content/uploads/ced-amazon/logs/amazon-product-xml.log';
					$fp          = fopen( $logFilePath, 'a' ); 
					if ( !$fp  ) {		
						return;
					}
					$fr = fwrite( $fp, $log_detail . "\n" );
					fclose( $fp );
				}
			} else {
				$return = false;
				$errors = "! Document is not valid:\n";
				if ( $showerror ) {
					echo esc_attr( $errors );
				}
				global $ced_umb_helper_amaz;
				$log_detail  = "\nmessage: Product XML ERRORS \n";
				$log_detail .= $errors . "\n******************************************************************\n\n\n\n\n";
				// $ced_umb_helper_amaz->umb_write_logs("amazon-product-xml.log",$log_detail);
				$logFilePath = ABSPATH . 'wp-content/uploads/ced-amazon/logs/amazon-product-xml.log';
				$fp          = fopen( $logFilePath, 'a' ); 
				if ( !$fp  ) {		
					return;
				}
				$fr = fwrite( $fp, $log_detail . "\n" );
				fclose( $fp );
			}
			// print_r($return);
			return $return;
		}



		/**
		 * Get feed item status for SP-API
		 *
		 * @name getFeedItemsStatusSpApi
		 * @since 1.0.0
		 * @link  http://www.cedcommerce.com/
		 */
		public function getFeedItemsStatusSpApi( $feedId = '', $feed_type = '', $location_id = '', $marketplace = '', $seller_id = '' ) {
			// if( "amazon_spapi" != $marketplace ){
			// return;
			// }

			$response = array();

			if ( empty($seller_id) ) {
				$response['body'] = 'Seller id is missing from URL!';
				return $response;
			}

			$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
			
			$grtopt_data = $seller_id;
			if ( isset( $saved_amazon_details[ $grtopt_data ] ) && ! empty( $saved_amazon_details[ $grtopt_data ] ) && is_array( $saved_amazon_details[ $grtopt_data ] ) ) {
				$shop_data = $saved_amazon_details[ $grtopt_data ];
			}

			$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
			$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
			$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
			if ( empty( $refresh_token ) || empty( $marketplace_id ) || empty( $feedId ) || empty( $feed_type ) ) {
				$response['body'] = 'Invalid seller info or feed type';
				return $response;
			}

			// Get feed response by feed id:
			$feed_response_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/get_feed_using_id';
			$feed_response_body     = array(
				'feed_id'        => $feedId,
				'marketplace_id' => $marketplace_id,
				'feed_action'    => $feed_type,
				'token'          => $refresh_token,
				'contract_id'      => get_option('ced_amazon_contract_id', '')
			);
			$feed_response_body     = wp_json_encode( $feed_response_body );

			$feed_response_options = array(
				'body'    => $feed_response_body,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 200,
			);

			$feed_response_data = wp_remote_post( $feed_response_endpoint, $feed_response_options );
			if ( is_wp_error( $feed_response_data ) ) {
				$response['body'] = 'Something went wrong, please try again later!!';
				return $response;
			}
			$response = json_decode( $feed_response_data['body'], true );

			return $response;

		}


		/**
		 * SAVE FEEDID
		 *
		 * @name insertFeedInfoToDatabase
		 * @since 1.0.0
		 */
		public function insertFeedInfoToDatabase( $feedId = '', $feed_for = '', $mplocation = '' ) {
			global $wpdb;
			$prefix    = $wpdb->prefix;
			$tableName = $prefix . 'ced_amazon_feeds';

			$response['body'] = '';
			$response         = json_encode( $response['body'] );
			$date_time        = gmdate( 'Y-m-d h:i:s' );

			$wpdb->insert(
				$tableName,
				array(
					'feed_id'        => $feedId,
					'feed_action'    => $feed_for,
					'feed_location'  => $mplocation,
					'feed_date_time' => $date_time,
					'response'       => $response,

				),
				array( '%s' )
			);

		}


		/**
		 * Get Time Zone
		 *
		 * @name getStandardOffsetUTC
		 * @link  http://www.cedcommerce.com/
		 */
		public function getStandardOffsetUTC() {
			$timezone = date_default_timezone_get();

			if ( 'UTC' == $timezone ) {
				return '';
			} else {
				$timezone    = new DateTimeZone( $timezone );
				$transitions = array_slice( $timezone->getTransitions(), -3, null, true );

				foreach ( array_reverse( $transitions, true ) as $transition ) {
					if ( 1 == $transition['isdst'] ) {
						continue;
					}
					return sprintf( 'UTC %+03d:%02u', $transition['offset'] / 3600, abs( $transition['offset'] ) % 3600 / 60 );
				}

				return false;
			}
		}


	}

endif;
