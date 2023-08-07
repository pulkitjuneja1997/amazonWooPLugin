<?php


require_once '../../../../../../wp-blog-header.php';

$contract_id = ! empty( $_GET['contract_id'] ) ? sanitize_text_field( $_GET['contract_id'] ) : '';
$redirection = ! empty( $_GET['redirect'] ) ? sanitize_text_field( $_GET['redirect'] ) : '';

if ( ! empty( $contract_id ) ) {

	$channel   = ! empty( $_GET['channel'] ) ? sanitize_text_field( $_GET['channel'] ) : '';
	$url       = 'https://sandbox.woocommerce.com/wp-json/wccom/billing/1.0/subscriptions/' . $contract_id;
	$headers[] = 'Authorization: Basic MjdmOTAzOTktMmY1MS00MDY3LWE2NDEtNDQxMjRmMWFmZGJhOkN4eUIhRkJwNE1TZ1M5Xip3YWNVQk9jNUQxQENaYUFSJUNZRnZlNWVCXmIlJjV1Y092KCNLWUhHVHZEdDJ3QzlqWHJaTzJUZWZeeEApaHVrSlFxeSl4eTZDR0hMbXlIIUExYVdLcGVvRjlaaHUlS2dxQ281RUgkaEE0TkEpZDJO';

	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 5,
			'redirection' => 5,
			'sslverify'   => 0,
			'headers'     => $headers,
		)
	);

	$redirect_url = $redirection . '/wp-admin/admin.php?page=ced_amazon&success=yes&contract_id=' . $contract_id;
	header( 'Location: ' . $redirect_url );

} else {
	$redirect_url = $redirection . '/wp-admin/admin.php?page=ced_amazon&success=yes&contract_id=';
	header( 'Location: ' . $redirect_url );
}



