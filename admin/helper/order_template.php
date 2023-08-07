<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

global $post;


$order_id                = isset( $post->ID ) ? intval( $post->ID ) : '';
$feedstatus              = get_post_meta( $order_id, '_umb_order_feed_status', true );
$umb_amazon_order_status = get_post_meta( $order_id, '_amazon_umb_order_status', true );
$amazon_shipped_details  = get_post_meta( $order_id, 'umb_amazon_shippied_data', true );

if ( ( isset( $amazon_shipped_details ) && ! empty( $amazon_shipped_details ) ) || ( isset( $umb_amazon_order_status ) && ! empty( $umb_amazon_order_status ) ) ) {
	$merchant_order_id = get_post_meta( $order_id, 'amazon_order_id', true );
	$order_detail      = get_post_meta( $order_id, 'order_detail', true );
	$order_items       = get_post_meta( $order_id, 'order_items', true );
	$order_details     = get_post_meta( $order_id, 'order_item_detail', true );
	$number_items      = 0;

	// Get order status


	$umb_amazon_order_status = get_post_meta( $order_id, '_amazon_umb_order_status', true );

	if ( empty( $umb_amazon_order_status ) ) {
		$umb_amazon_order_status = __( 'Created', 'ced-amazon' );
	}
	?>
	
	<div id="umb_amazon_order_settings" class="panel woocommerce_options_panel">
		<div class="ced_amazon_loader">
			<img src="<?php echo esc_attr( CED_AMAZON_URL ) . 'admin/images/loading.gif'; ?>" width="50px" height="50px" class="ced_amazon_loading_img" style="display: none;" >
		</div>
	
		<div class="options_group">
		 <p class="form-field">
			  <h3><center>
			  <?php
				esc_attr_e( 'AMAZON ORDER STATUS : ', 'ced-amazon' );
				echo esc_attr( strtoupper( $umb_amazon_order_status ) );
				?>
				</center></h3>
			 </p>
		 </div>
	<div class="options_group umb_amazon_options"> 
	<?php
	if ( $feedstatus ) {
		$feeddetails = get_post_meta( $order_id, '_umb_order_feed_details', true );
		?>
		  <p class="form-field">
			<b><?php echo esc_attr_e( 'Order', 'ced-amazon' ) . esc_attr( $feeddetails['request'] ) . esc_attr_e( 'request is under process', 'ced-amazon' ); ?></b>
			<input type="button" class="button primary " value="Check Status" data-order_id = "<?php echo esc_attr( $order_id ); ?>" data-feed_id = "<?php echo esc_attr( $feeddetails['id'] ); ?>" data-feed_req = "<?php echo esc_attr( $feeddetails['request'] ); ?>"  id="umb_amazon_checkfeedstatus"/>
	   </p>
		<?php
	} else {
		if ( 'Cancelled' == $umb_amazon_order_status ) {
			?>
				 <h1 style="text-align:center;"><?php esc_attr_e( 'ORDER CANCELLED ', 'ced-amazon' ); ?></h1>
			<?php
		}
		$umb_amazon_order_status = 'Acknowledged';

		// $umb_amazon_order_status = "Shipped"
		if ( 'Created' == $umb_amazon_order_status ) {
			?>
		  <p class="form-field">
			<label><?php esc_attr_e( 'Select Order Action:', 'ced-amazon' ); ?></label>
			<input type="button" class="button primary " value="<?php esc_attr_e( 'Acknowledge Order', 'ced-amazon' ); ?>" data-order_id = "<?php echo esc_attr( $order_id ); ?>" id="umb_amazon_ack_action"/>
			<input type="button" class="button primary " value="<?php esc_attr_e( 'Cancel Order', 'ced-amazon' ); ?>" data-order_id = "<?php echo esc_attr( $order_id ); ?>" id="umb_amazon_cancel_action"/>
		</p>
			<?php
		} elseif ( 'Acknowledged' == $umb_amazon_order_status ) {
			?>
			  <input type="hidden" id="amazon_orderid" value="<?php echo esc_attr( $order_detail['AmazonOrderId'] ); ?>" readonly>
			<input type="hidden" id="woocommerce_orderid" value="<?php echo esc_attr( $order_id ); ?>">
			  <h2 class="title"><?php esc_attr_e( 'Shipment Information', 'ced-amazon' ); ?> -
			  <!-- <input type="button" class="button primary " value="<?php esc_attr_e( 'Cancel Order', 'ced-amazon' ); ?>" data-order_id = "<?php echo esc_attr( $order_id ); ?>" id="umb_walmart_cancel_action" style="float: right; margin-bottom: 7px;"> --></h2>
			
			  <!-- Ship Complete Order -->
			
			  <div id="ced_umb_amazon_complete_order_shipping">
					 <table class="wp-list-table widefat fixed striped">
					<tbody>
						  <tr>
							   <td><b><?php esc_attr_e( 'Reference Order Id on Amazon.com', 'ced-amazon' ); ?></b></td>
							   <td><?php echo esc_attr( $order_detail['AmazonOrderId'] ); ?></td>
						   </tr>
						<tr>
							   <td><b><?php esc_attr_e( 'Order Placed on Amazon.com', 'ced-amazon' ); ?></b></td>
							   <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['PurchaseDate'] ) ) ); ?></td>
						</tr>
						 <tr>
							<td><b><?php esc_attr_e( 'Shipping carrier used', 'ced-amazon' ); ?></b></td>
							<td>
								<select id="umb_amazon_carrier_order">
									<option value="USPS"><?php esc_attr_e( 'USPS', 'ced-amazon' ); ?></option>
									<option value="UPS"><?php esc_attr_e( 'UPS', 'ced-amazon' ); ?></option>
									<option value="UPSMI"><?php esc_attr_e( 'UPSMI', 'ced-amazon' ); ?></option>
									<option value="FedEx"><?php esc_attr_e( 'FedEx', 'ced-amazon' ); ?></option>
									<option value="DHL"><?php esc_attr_e( 'DHL', 'ced-amazon' ); ?></option>
									<option value="Fastway"><?php esc_attr_e( 'Fastway', 'ced-amazon' ); ?></option>
									<option value="GLS"><?php esc_attr_e( 'GLS', 'ced-amazon' ); ?></option>
									<option value="GO!"><?php esc_attr_e( 'GO!', 'ced-amazon' ); ?></option>
									<option value="Hermes Logistik Gruppe"><?php esc_attr_e( 'Hermes Logistik Gruppe', 'ced-amazon' ); ?></option>
									<option value="Royal Mail"><?php esc_attr_e( 'Royal Mail', 'ced-amazon' ); ?></option>
									<option value="Parcelforce"><?php esc_attr_e( 'Parcelforce', 'ced-amazon' ); ?></option>
									<option value="City Link"><?php esc_attr_e( 'City Link', 'ced-amazon' ); ?></option>
									<option value="TNT"><?php esc_attr_e( 'TNT', 'ced-amazon' ); ?></option>
									<option value="Target"><?php esc_attr_e( 'Target', 'ced-amazon' ); ?></option>
									<option value="SagawaExpress"><?php esc_attr_e( 'SagawaExpress', 'ced-amazon' ); ?></option>
									<option value="NipponExpress"><?php esc_attr_e( 'NipponExpress', 'ced-amazon' ); ?></option>
									<option value="YamatoTransport"><?php esc_attr_e( 'YamatoTransport', 'ced-amazon' ); ?></option>
									<option value="DHL Global Mail"><?php esc_attr_e( 'DHL Global Mail', 'ced-amazon' ); ?></option>
									<option value="UPS Mail Innovations"><?php esc_attr_e( 'UPS Mail Innovations', 'ced-amazon' ); ?></option>
									<option value="FedEx SmartPost"><?php esc_attr_e( 'FedEx SmartPost', 'ced-amazon' ); ?></option>
									<option value="OSM"><?php esc_attr_e( 'OSM', 'ced-amazon' ); ?></option>
									<option value="OnTrac"><?php esc_attr_e( 'OnTrac', 'ced-amazon' ); ?></option>
									<option value="Streamlite"><?php esc_attr_e( 'Streamlite', 'ced-amazon' ); ?></option>
									<option value="Newgistics"><?php esc_attr_e( 'Newgistics', 'ced-amazon' ); ?></option>
									<option value="Canada Post"><?php esc_attr_e( 'Canada Post', 'ced-amazon' ); ?></option>
									<option value="Blue Package"><?php esc_attr_e( 'Blue Package', 'ced-amazon' ); ?></option>
									<option value="Chronopost"><?php esc_attr_e( 'Chronopost', 'ced-amazon' ); ?></option>
									<option value="Deutsche Post"><?php esc_attr_e( 'Deutsche Post', 'ced-amazon' ); ?></option>
									<option value="DPD"><?php esc_attr_e( 'DPD', 'ced-amazon' ); ?></option>
									<option value="La Poste"><?php esc_attr_e( 'La Poste', 'ced-amazon' ); ?></option>
									<option value="Poste Italiane"><?php esc_attr_e( 'Poste Italiane', 'ced-amazon' ); ?></option>
									<option value="SDA"><?php esc_attr_e( 'SDA', 'ced-amazon' ); ?></option>
									<option value="Smartmail"><?php esc_attr_e( 'Smartmail', 'ced-amazon' ); ?></option>
									<option value="FEDEX_JP"><?php esc_attr_e( 'FEDEX_JP', 'ced-amazon' ); ?></option>
									<option value="JPesc_attr_eXPRESS"><?php esc_attr_e( 'JPesc_attr_eXPRESS', 'ced-amazon' ); ?></option>
									<option value="NITTSU"><?php esc_attr_e( 'NITTSU', 'ced-amazon' ); ?></option>
									<option value="SAGAWA"><?php esc_attr_e( 'SAGAWA', 'ced-amazon' ); ?></option>
									<option value="YAMATO"><?php esc_attr_e( 'YAMATO', 'ced-amazon' ); ?></option>
									<option value="BlueDart"><?php esc_attr_e( 'BlueDart', 'ced-amazon' ); ?></option>
									<option value="AFL/Fedex"><?php esc_attr_e( 'AFL/Fedex', 'ced-amazon' ); ?></option>
									<option value="Aramex"><?php esc_attr_e( 'Aramex', 'ced-amazon' ); ?></option>
									<option value="India Post"><?php esc_attr_e( 'India Post', 'ced-amazon' ); ?></option>
									<option value="Australia Post"><?php esc_attr_e( 'Australia Post', 'ced-amazon' ); ?></option>
									<option value="Professional"><?php esc_attr_e( 'Professional', 'ced-amazon' ); ?></option>
									<option value="DTDC"><?php esc_attr_e( 'DTDC', 'ced-amazon' ); ?></option>
									<option value="Overnite Express"><?php esc_attr_e( 'Overnite Express', 'ced-amazon' ); ?></option>
									<option value="First Flight"><?php esc_attr_e( 'First Flight', 'ced-amazon' ); ?></option>
									<option value="Delhivery"><?php esc_attr_e( 'Delhivery', 'ced-amazon' ); ?></option>
									<option value="Lasership"><?php esc_attr_e( 'Lasership', 'ced-amazon' ); ?></option>
									<option value="Yodel"><?php esc_attr_e( 'Yodel', 'ced-amazon' ); ?></option>
									<option value="Other"><?php esc_attr_e( 'Other', 'ced-amazon' ); ?></option>
								</select>
								<input type="text" id="umb_amazon_other_carrier" name="umb_amazon_other_carrier" value="" style="margin-top: 5px; width: 70%; display: none;">
							</td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Shipping Type', 'ced-amazon' ); ?></b></td>
							<td>
								<select id="umb_amazon_methodCode_order">
									<option value="Standard"><?php esc_attr_e( 'Standard', 'ced-amazon' ); ?></option>
									<option value="Express"><?php esc_attr_e( 'Express', 'ced-amazon' ); ?></option>
									<option value="OneDay"><?php esc_attr_e( 'OneDay', 'ced-amazon' ); ?></option>
									<option value="Freight"><?php esc_attr_e( 'Freight', 'ced-amazon' ); ?></option>
									<option value="WhiteGlove"><?php esc_attr_e( 'WhiteGlove', 'ced-amazon' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Tracking Number', 'ced-amazon' ); ?></b></td>
							<td><input type="text" id="umb_amazon_tracking_order" value=""></td>
						</tr>
						<tr>
							<td><b><?php esc_attr_e( 'Fulfillment Date', 'ced-amazon' ); ?></b></td>
							<td><input class=" input-text required-entry"  type="text" id="umb_amazon_ship_date_order" name="ship_date"/></td>
						</tr>
					</tbody>
				</table>	
			 </div>
			 
			 <input data-items="<?php echo esc_attr( $number_items ); ?>" type="button" class="button" id="ced_amzon_shipment_submit" value="<?php esc_attr_e( 'Submit Shipment', 'ced-amazon' ); ?>">
			<?php
		} elseif ( 'Shipped' == $umb_amazon_order_status ) {
			$amazon_postshipped_data = get_post_meta( $order_id, 'ced_amzon_shipped_data', true );
			$amazon_shipped_details  = get_post_meta( $order_id, 'umb_amazon_shippied_data', true );

			$amazon_shipping_carrier = isset( $amazon_postshipped_data[0]['carrier'] ) ? $amazon_postshipped_data[0]['carrier'] : '';
			$amazon_shipping_type    = isset( $amazon_postshipped_data[0]['methodCode'] ) ? $amazon_postshipped_data[0]['methodCode'] : '';
			$amazon_tracking_no      = isset( $amazon_postshipped_data[0]['tracking'] ) ? $amazon_postshipped_data[0]['tracking'] : '';
			$amazon_ship_date        = isset( $amazon_postshipped_data[0]['ship_todate'] ) ? $amazon_postshipped_data[0]['ship_todate'] : '';
			?>
				<input type="hidden" id="amazon_orderid" value="<?php echo esc_attr( $amazon_shipped_details['AmazonOrderId'] ); ?>" readonly>
			  <input type="hidden" id="woocommerce_orderid" value="<?php echo esc_attr( $amazon_postshipped_data['order'] ); ?>">
				<h2 class="title"><?php esc_attr_e( 'Shipment Information' ); ?></h2>
				<table class="wp-list-table widefat fixed striped">
				 <tbody>
					   <tr>
							 <td><b><?php esc_attr_e( 'Reference Order Id on Amazon.com', 'ced-amazon' ); ?></b></td>
							 <td><?php echo esc_attr( $order_detail['AmazonOrderId'] ); ?></td>
						 </tr>
					  <tr>
							 <td><b><?php esc_attr_e( 'Order Placed on Amazon.com', 'ced-amazon' ); ?></b></td>
							 <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $order_detail['PurchaseDate'] ) ) ); ?></td>
					  </tr>
					  <tr>
							 <td><b><?php esc_attr_e( 'Shipping carrier used', 'ced-amazon' ); ?></b></td>
							 <td>
							<?php echo esc_attr( $amazon_shipping_carrier ); ?>
						  </td>
					  </tr>
					  <tr>
						  <td><b><?php esc_attr_e( 'Shipping Type', 'ced-amazon' ); ?></b></td>
						  <td>
						   
							   <?php echo esc_attr( $amazon_shipping_type ); ?>
						 </td>
					  </tr>
					  <tr>
						  <td><b><?php esc_attr_e( 'Tracking Number', 'ced-amazon' ); ?></b></td>
						  <td><?php echo esc_attr( $amazon_tracking_no ); ?></td>
					  </tr>
				  
					  <tr>
						  <td><b><?php esc_attr_e( 'Ship To Date', 'ced-amazon' ); ?></td>
						  <td><?php echo esc_attr( gmdate( 'l, F jS Y \a\t g:ia', strtotime( $amazon_ship_date ) ) ); ?></td>
					  </tr>
				  </tbody>
			  </table>	
			<?php
		}
	}
	?>
	</div>    
</div>    
	<?php
}
?>
