<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class CedCommerce_Amazon_Rest_API_Endpoints extends WP_REST_Controller {
	private $api_namespace;
	private $api_version;
	private $required_capability;

	private $amazon_config_instance;

	public function __construct() {
		$this->api_namespace       = 'ced-amazon-woo/';
		$this->api_version         = 'v1';
		$this->required_capability = 'read';  // Minimum capability to use the endpoint
		$this->ced_amazon_init_rest_endpoints();

		// error_reporting(E_ALL);
		// ini_set('display_errors', 1);
	}

	// Register our REST Server
	public function ced_amazon_init_rest_endpoints() {
		add_action( 'rest_api_init', array( $this, 'ced_amazon_register_routes' ) );
	}
	
	public function ced_amazon_register_routes() {
		$namespace = $this->api_namespace . $this->api_version;

		register_rest_route(
			$namespace,
			'/amazon/report-id',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_amazon_get_report_id' ),
					'permission_callback' => array( $this, 'ced_amazon_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/amazon/report-data',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_amazon_get_report_data' ),
					'permission_callback' => array( $this, 'ced_amazon_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/amazon/order',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_amazon_get_single_order_data' ),
					'permission_callback' => array( $this, 'ced_amazon_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/amazon/orders',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_amazon_get_orders_data' ),
					'permission_callback' => array( $this, 'ced_amazon_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/amazon/order-items',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_amazon_get_order_items_data' ),
					'permission_callback' => array( $this, 'ced_amazon_permission_check' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/amazon/get-wc-logs',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'ced_amazon_get_wc_logs' ),
					'permission_callback' => array( $this, 'ced_amazon_permission_check' ),
				),
			)
		);
	}


	// Check User Is Authorized or Not
	public function ced_amazon_permission_check( $request ) {
		$headers = getallheaders();

		// Get username and password from the submitted headers.
		if ( array_key_exists( 'Authorization', $headers ) || array_key_exists( 'authorization', $headers ) ) {

			// Don't authenticate twice
			if ( ! empty( $user ) ) {
				return true;
			}

			// Check that we're trying to authenticate
			if ( ! isset( $_SERVER['PHP_AUTH_USER'] ) ) {
				return true;
			}

			$username = ! empty( $_SERVER['PHP_AUTH_USER'] ) ? sanitize_text_field( $_SERVER['PHP_AUTH_USER'] ) : '';
			$password = ! empty( $_SERVER['PHP_AUTH_PW'] ) ? sanitize_text_field( $_SERVER['PHP_AUTH_PW'] ) : '';

			/**
			 * In multi-site, wp_authenticate_spam_check filter is run on authentication. This filter calls
			 * get_currentuserinfo which in turn calls the determine_current_user filter. This leads to infinite
			 * recursion and a stack overflow unless the current function is removed from the determine_current_user
			 * filter during authentication.
			 */
			remove_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

			$user = wp_authenticate( $username, $password );

			add_filter( 'determine_current_user', 'json_basic_auth_handler', 20 );

			if ( is_wp_error( $user ) ) {
				return new WP_Error( 'incorrect_password', 'The password you entered for the username ' . $username . ' is incorrect.', array( 'status' => 401 /* Unauthorized */ ) );
			}

			return true;
		} else {
			return new WP_Error( 'invalid-method', 'You must specify a valid username and password.', array( 'status' => 400 /* Bad Request */ ) );
		}
	}


	public function ced_amazon_get_report_id( WP_REST_Request $request ) {

		// $json_params = $request->get_json_params();
		// if ( ! is_array( $json_params ) || empty( $json_params ) || empty( $json_params['seller_id'] ) ) {
		// 	return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		// }

		// $report_type    = ! empty( $json_params['report_type'] ) ? $json_params['report_type'] : 'GET_MERCHANT_LISTINGS_ALL_DATA';

		$seller_id   = $request->get_param( 'seller_id' );
		$report_type = $request->get_param( 'report_type' );

		if ( empty( $seller_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data seller id is missing. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		$report_type = ! empty( $report_type ) ? $report_type : 'GET_MERCHANT_LISTINGS_ALL_DATA';

		$mplocation_arr = explode('|', $seller_id);
		$mplocation     = isset($mplocation_arr[1]) ? $mplocation_arr[0] : '';

		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		$location_for_seller  = $seller_id;

		if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
			$shop_data = $saved_amazon_details[ $location_for_seller ];
		}

		$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
		$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
		$merchant_id    = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

		if ( empty( $refresh_token ) || empty( $marketplace_id ) ) {
			return new WP_Error( 'invalid-data', 'The supplied API details are invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		// Request report id
		$report_request_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/create_report';
		$report_request_body     = array(
			'token'          => $refresh_token,
			'report_type'    => $report_type,
			'marketplace_id' => $marketplace_id,
			'contract_id'      => get_option('ced_amazon_contract_id', '')
		);
		$report_request_body     = wp_json_encode( $report_request_body );

		$report_request_options = array(
			'body'    => $report_request_body,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => 200,
		);

		$report_request_reponse = wp_remote_post( $report_request_endpoint, $report_request_options );
		if ( is_wp_error( $report_request_reponse ) ) {
			//return new WP_Error( 'invalid-data', $report_request_reponse, array( 'status' => 400 /* Bad Request */ ) );
			return $report_request_reponse;
		}

		$report_request_data = json_decode( $report_request_reponse['body'], true );
		if ( isset($report_request_data['success']) && 'false' == $report_request_data['success'] ) {
			return new WP_Error( 'invalid-data', $report_request_data, array( 'status' => 400 /* Bad Request */ ) );
		}

		$report_id_request = array();
		if ( isset( $report_request_data['report_id'] ) && ! empty( $report_request_data['report_id'] ) ) {
			$report_id_request['report_id']   = $report_request_data['report_id'];
			$report_id_request['report_type'] = $report_type;
		}

		return $report_id_request;
	}


	public function ced_amazon_get_report_data( WP_REST_Request $request ) {

		$seller_id   = $request->get_param( 'seller_id' );
		$report_id   = $request->get_param( 'report_id' );
		$report_type = $request->get_param( 'report_type' );
		if ( empty( $seller_id ) || empty( $report_id ) || empty( $report_type ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		$mplocation_arr = explode('|', $seller_id);
		$mplocation     = isset($mplocation_arr[1]) ? $mplocation_arr[0] : '';

		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		$location_for_seller  = $seller_id;

		if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
			$shop_data = $saved_amazon_details[ $location_for_seller ];
		}

		$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
		$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
		$merchant_id    = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

		if ( empty( $refresh_token ) || empty( $marketplace_id ) ) {
			return new WP_Error( 'invalid-data', 'The supplied API details are invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		// Get report data using report id
		$report_data_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/get_report_using_id';
		$report_data_body     = array(
			'token'          => $refresh_token,
			'marketplace_id' => $marketplace_id,
			'report_type'    => $report_type,
			'report_id'      => $report_id,
			'contract_id'      => get_option('ced_amazon_contract_id', '')

		);
		$report_data_body = wp_json_encode( $report_data_body );

		$report_data_options = array(
			'body'    => $report_data_body,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => 200,
		);

		$report_data_reponse = wp_remote_post( $report_data_endpoint, $report_data_options );
		if ( is_wp_error( $report_data_reponse ) ) {
			return $report_data_reponse;
		}

		$report_data = json_decode( $report_data_reponse['body'], true );
		if ( isset($report_data['success']) && 'false' == $report_data['success'] ) {

			return new WP_Error( 'invalid-data', $report_data, array( 'status' => 400 /* Bad Request */ ) );
		}
		
		return $report_data;
	}


	public function ced_amazon_get_single_order_data( WP_REST_Request $request ) {
		$seller_id = $request->get_param( 'seller_id' );
		$order_id  = $request->get_param( 'order_id' );
		if ( empty( $seller_id ) || empty( $order_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$mplocation_arr       = explode('|', $seller_id);
		$mplocation           = isset($mplocation_arr[1]) ? $mplocation_arr[0] : '';
		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		$location_for_seller  = $seller_id;

		if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
			$shop_data = $saved_amazon_details[ $location_for_seller ];
		}

		$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
		$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
		$merchant_id    = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

		if ( empty( $refresh_token ) || empty( $marketplace_id ) ) {
			return new WP_Error( 'invalid-data', 'The supplied API details are invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		
		// Get specific order using amazon order id via SP-API endpoint
		$order_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/get_order';
		$order_body     = [
			'marketplace_id'  => $marketplace_id,
			'token'           => $refresh_token,
			'amazon_order_id' => $order_id,
			'contract_id'      => get_option('ced_amazon_contract_id', '')
		];
		$order_body     = wp_json_encode( $order_body );
		 
		$order_options          = [
			'body'        => $order_body,
			'headers'     => [
				'Content-Type' => 'application/json',
			],
			'timeout'     => 200,
		];
		$specific_order_reponse = wp_remote_post( $order_endpoint, $order_options );
		if ( is_wp_error($specific_order_reponse)) {
			return $specific_order_reponse;
		}
		$specific_order_reponse = json_decode($specific_order_reponse['body'], true);

		return $specific_order_reponse;
	}


	public function ced_amazon_get_orders_data( WP_REST_Request $request ) {
		$seller_id = $request->get_param( 'seller_id' );
		if ( empty( $seller_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		$time_limit = '-24 hours';
		$next_token = null;

		$mplocation_arr       = explode('|', $seller_id);
		$mplocation           = isset($mplocation_arr[1]) ? $mplocation_arr[0] : '';
		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		$location_for_seller  = $seller_id;

		if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
			$shop_data = $saved_amazon_details[ $location_for_seller ];
		}

		$refresh_token    = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$region           = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
		$marketplace_id   = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
		$amz_order_status = array('Unshipped', 'PartiallyShipped', 'Shipped');
		$time_limit       = gmdate('Y-m-d\Th:i:s\Z', strtotime($time_limit));
		if ( empty( $refresh_token ) || empty( $marketplace_id ) ) {
			return new WP_Error( 'invalid-data', 'The supplied API details are invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		$marketplace_ids = array($marketplace_id);

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
			return $order_reponse_main;
		}

		$order_reponse = json_decode( $order_reponse_main['body'], true );
		if ( isset($order_reponse['success']) && 'false' == $order_reponse['success'] ) {
			return new WP_Error( 'invalid-data', $order_reponse, array( 'status' => 400 /* Bad Request */ ) );
		}

		return $order_reponse;
	}


	public function ced_amazon_get_order_items_data( WP_REST_Request $request ) {
		$seller_id = $request->get_param( 'seller_id' );
		$order_id  = $request->get_param( 'order_id' );
		if ( empty( $seller_id ) || empty( $order_id ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}
		
		$mplocation_arr       = explode('|', $seller_id);
		$mplocation           = isset($mplocation_arr[1]) ? $mplocation_arr[0] : '';
		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		$location_for_seller  = $seller_id;

		if ( isset( $saved_amazon_details[ $location_for_seller ] ) && ! empty( $saved_amazon_details[ $location_for_seller ] ) && is_array( $saved_amazon_details[ $location_for_seller ] ) ) {
			$shop_data = $saved_amazon_details[ $location_for_seller ];
		}

		$refresh_token  = isset( $shop_data['amazon_refresh_token'] ) ? $shop_data['amazon_refresh_token'] : '';
		$region         = isset( $shop_data['marketplace_region'] ) ? $shop_data['marketplace_region'] : '';
		$marketplace_id = isset( $shop_data['marketplace_id'] ) ? $shop_data['marketplace_id'] : '';
		$merchant_id    = isset( $shop_data['merchant_id'] ) ? $shop_data['merchant_id'] : '';

		if ( empty( $refresh_token ) || empty( $marketplace_id ) ) {
			return new WP_Error( 'invalid-data', 'The supplied API details are invalid. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		// Get Order items
		$order_item_endpoint = 'https://lo9bsyugeh.execute-api.ap-southeast-1.amazonaws.com/webapi/amazon/get_order_items';
		$order_item_body     = array(
			'marketplace_id' => $marketplace_id,
			'token'          => $refresh_token,
			'order_id'       => $order_id,
			'contract_id'      => get_option('ced_amazon_contract_id', '')
		);
		$order_item_body     = wp_json_encode( $order_item_body );
		$order_item_options  = array(
			'body'    => $order_item_body,
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'timeout' => 200,
		);
		$order_item_reponse  = wp_remote_post( $order_item_endpoint, $order_item_options );
		if ( is_wp_error( $order_item_reponse ) ) {
			return $order_item_reponse;
		}
		$order_item_reponse = json_decode( $order_item_reponse['body'], true );
		return $order_item_reponse;

	}


	public function ced_amazon_get_wc_logs( WP_REST_Request $request ) {
		$keyword     = $request->get_param( 'keyword' );
		$date_suffix = ! empty( $request->get_param( 'date' ) ) ? $request->get_param( 'date' ) : gmdate( 'Y-m-d', time() );
		if ( empty( $keyword ) ) {
			return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check and try again!', array( 'status' => 400 /* Bad Request */ ) );
		}

		if ( class_exists( 'WC_Log_Handler_File' ) ) {
			if ( ! function_exists( 'wp_hash' ) ) {
				return new WP_Error( 'function-not-found', 'Function not found', array( 'status' => 400 /* Bad Request */ ) );
			}
			$log_files     = WC_Log_Handler_File::get_log_files();
			$hash_suffix   = wp_hash( $keyword );
			$log_file_name = sanitize_file_name( implode( '-', array( $keyword, $date_suffix, $hash_suffix ) ) . '.log' );
			if ( in_array( $log_file_name, $log_files ) ) {
				if ( defined( 'WC_LOG_DIR' ) ) {
					$log_file_path = trailingslashit( WC_LOG_DIR ) . $log_file_name;
					if ( file_exists( $log_file_path ) ) {
						header( 'Content-Description: File Transfer' );
						header( 'Content-Type: application/octet-stream' );
						header( 'Content-Disposition: attachment; filename="' . basename( $log_file_path ) . '"' );
						header( 'Expires: 0' );
						header( 'Cache-Control: must-revalidate' );
						header( 'Pragma: public' );
						header( 'Content-Length: ' . filesize( $log_file_path ) );
						readfile( $log_file_path );
						exit;
					} else {
						return new WP_Error( 'log-file-not-found', 'Log file not found.', array( 'status' => 400 /* Bad Request */ ) );

					}
				} else {
					return new WP_Error( 'invalid-constant', 'Invalid constant', array( 'status' => 400 /* Bad Request */ ) );

				}
			} else {
				return new WP_Error( 'invalid-data', 'The request data is invalid or missing fields. Please check date and keyword and try again!', array( 'status' => 400 /* Bad Request */ ) );

			}
		} else {
			return new WP_Error( 'invalid-class', 'Unable to load the log files.', array( 'status' => 400 /* Bad Request */ ) );
		}
	}

}

$ced_amazon_rest_api_endpoints = new CedCommerce_Amazon_Rest_API_Endpoints();
