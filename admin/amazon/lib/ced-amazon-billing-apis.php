<?php

class Billing_Apis {


	public function ced_amazon_subscription( $params ) { 

		// $plan_type   = isset( $params['plan_type'] ) ? $params['plan_type'] : '';
		$plan_id     = isset( $params['plan_id'] ) ? $params['plan_id'] : '';
		$site_domain = isset( $params['domain'] ) ? $params['domain'] : '';
		$period      = isset( $params['period'] ) ? $params['period'] : 'month';
		$trial       = isset( $params['trial'] ) ? $params['trial'] : false;
		
		$contract_id = isset( $params['contract_id'] ) ? $params['contract_id'] : false;

		$planData = array(
			'planData' => array(
				// 'plan_type' => $plan_type,
				'plan_id'   => $plan_id
			),
			'period' => $period,
			'site_domain' => $site_domain,
			'trial' => $trial,
			'contract_id'       => $contract_id
		);

		// print_r($planData); die;
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://amazon-php-apis.vercel.app/getAmazonPlan',
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => http_build_query($planData),
		));

		$amazonPlanResponse = curl_exec($curl);
		curl_close($curl);

		//print_r($amazonPlanResponse); die('vercel response');

		if ( is_wp_error( $amazonPlanResponse ) ) {
			print_r( array( 'status' =>  false, 'message' => 'Failed to fetch plans. Please try again later.' ) );
			die;
		} else {
			$response         = json_decode( $amazonPlanResponse, true );
			$confirmation_url = isset( $response['confirmation_url'] ) ? $response['confirmation_url'] : '';

			if ( !empty($confirmation_url) ) {
				return array( 'status' =>  true, 'confirmation_url' => $response['confirmation_url'] ) ;
				
			} else {
				return  array( 'status' =>  false, 'message' => 'Failed during checkout process. Please try again later or contact support.' );
				
			}
		}

	}

	public function fethcAllAmazonPlans() {

		$curl = curl_init();

		curl_setopt_array($curl, array(
		CURLOPT_URL => 'https://amazon-php-apis.vercel.app/fetchAllAmazonPlans',
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => '',
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 0,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		// CURLOPT_CUSTOMREQUEST => '',
		// CURLOPT_POSTFIELDS => http_build_query($planData),
		));

		$amazonPlanResponse = curl_exec($curl);
		curl_close($curl);

		if ( is_wp_error( $amazonPlanResponse ) ) {
			return array( 'status' =>  false, 'message' => 'Failed to fetch plans. Please contact support.' );
		} else {
			$plans = json_decode( $amazonPlanResponse, true );
			return $plans;
		}

	}

	public function getAmazonPlanById( $id ) {

		if ( empty($id) ) {
			print_r( array( 'status' =>  false, 'message' => 'Failed to fetch your current plans details. Please try again later or contact support.' ) );
			die;
		}


		$data = array(
			'id' => $id 
		);
		$curl = curl_init();

		$url = 'https://amazon-php-apis.vercel.app/getAmazonPlanById';
		$url = $url . '?' . http_build_query($data);
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

			CURLOPT_POSTFIELDS => $id,
		));

		$currentPlanResponse = curl_exec($curl);
		curl_close($curl);

		if ( is_wp_error( $currentPlanResponse ) ) {
			print_r( array( 'status' =>  false, 'message' => 'Failed to fetch your current plans details. Please try again later or contact support.' ) );
			die;
		} else {
			$response = json_decode( $currentPlanResponse, true);
			return $response;

		}

	}
	
	
	public function cancelPlan( $params ) {

		$curl = curl_init();

		$url = 'https://amazon-php-apis.vercel.app/cancelPlan';
		$url = $url . '?' . http_build_query($params);
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,

			CURLOPT_POSTFIELDS => $id,
		));

		$currentPlanResponse = curl_exec($curl);
		curl_close($curl);
		
		// print_r( $currentPlanResponse );
		// die;

		if ( is_wp_error( $currentPlanResponse ) ) {
			print_r( array( 'status' =>  false, 'message' => 'Failed to fetch your current plans details. Please try again later or contact support.' ) );
			die;
		} else {
			$response = json_decode( $currentPlanResponse, true);
			return $response;

		}

	}

}




