<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}


$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : false;


$sellernextShopIds = get_option( 'ced_amazon_sellernext_shop_ids', array() );

if ( empty( $seller_id ) ) {
	$seller_id = isset( $sellernextShopIds[$user_id] ) && isset( $sellernextShopIds[$user_id]['ced_mp_seller_key'] ) ? $sellernextShopIds[$user_id]['ced_mp_seller_key'] : '';
}

$amz_data_validated = get_option( 'ced_amzon_configuration_validated', array() );

// Check account participation
$seller_participation = false; 
$participate_accounts = isset( $sellernextShopIds[$user_id] ) && isset( $sellernextShopIds[$user_id]['marketplaces_participation'] ) ? $sellernextShopIds[$user_id]['marketplaces_participation'] : '';

if ( is_array($participate_accounts) && $participate_accounts[$seller_id] ) {
	$seller_participation = true;
}

if ( isset( $amz_data_validated[ $seller_id ] ) && ! empty( $amz_data_validated[ $seller_id ] ) && is_array( $amz_data_validated[ $seller_id ] ) ) {
	$shop_id = $amz_data_validated[ $seller_id ]['seller_next_shop_id'];
}

if ( isset( $_GET['section'] ) ) {
	$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';
}

?>
<div class="ced_amazon_loader">
	<img src="<?php echo esc_attr( CED_AMAZON_URL ) . 'admin/images/loading.gif'; ?>" width="50px" height="50px" class="ced_amazon_loading_img"  >
</div>

<div class="ced-menu-container">
	<ul class="subsubsub">
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=overview&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
					if ( 'overview' == $section ) {
						echo 'current';}
					?>">Overview</a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=orders-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
		if ( 'orders-view' == $section ) {
			echo 'current';}
		?>"><?php esc_attr_e( 'Orders', 'amazon-integration-for-woocommerce' ); ?></a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=profiles-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
					if ( 'profiles-view' == $section || 'add-new-template' == $section ) {
						echo 'current';}
					?>"><?php esc_attr_e( 'Templates', 'amazon-integration-for-woocommerce' ); ?></a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=products-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
					if ( 'products-view' == $section ) {
						echo 'current';}
					?>"><?php esc_attr_e( 'Products', 'amazon-integration-for-woocommerce' ); ?></a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=feeds-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
					if ( 'feeds-view' == $section ) {
						echo 'current';}
					?>"><?php esc_attr_e( 'Feeds', 'amazon-integration-for-woocommerce' ); ?></a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=settings&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
					if ( 'settings' == $section ) {
						echo 'current';}
					?>"><?php esc_attr_e( 'Settings', 'amazon-integration-for-woocommerce' ); ?></a>|</li>
	    <li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=amazon-options&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
					if ( 'amazon-options' == $section ) {
						echo 'current';}
					?>"><?php esc_attr_e( 'Options', 'amazon-integration-for-woocommerce' ); ?></a> |</li>
		<li><a href="<?php echo esc_attr( admin_url( 'admin.php?page=sales_channel&channel=amazon&section=plans-view&user_id=' . $user_id . '&seller_id=' . $seller_id ) ); ?>" class="<?php
					if ( 'plans-view' == $section ) {
						echo 'current';}
					?>"><?php esc_attr_e( 'Subscriptions', 'amazon-integration-for-woocommerce' ); ?></a> </li>
		
	</ul>

	<div class="ced-right">
		    <?php $ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() ); 

			if( !empty( $ced_amazon_sellernext_shop_ids ) ){ ?>
				<select style="min-width: 160px;" id="media-attachment-filters" name="ced_amazon_change_acc" class="attachment-filters ced_amazon_change_acc">
				<?php
				foreach( $ced_amazon_sellernext_shop_ids as $sellernextId => $sellernextData ){ 

                    if( 3 < $sellernextData['ced_amz_current_step'] ){ 
						$url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=overview&user_id='. $sellernextId . '&seller_id=' . $sellernextData['ced_mp_seller_key'];
						?>
					   
					   <option value="all" data-href="<?php echo esc_url($url); ?>" ><?php echo $sellernextId; ?></option>

					<?php } else{ 

						$current_step = $sellernextData['ced_amz_current_step'];
						if ( empty( $current_step ) ) {
							$urlKey = 'section=setup-amazon';
						} elseif ( 1 == $current_step ) {
							$urlKey = 'section=setup-amazon&part=amazon-options';
						} elseif ( 2 == $current_step ) {
							$urlKey = 'section=setup-amazon&part=settings';
						} elseif ( 3 == $current_step ) {
							$urlKey = 'section=setup-amazon&part=configuration';
						} else {
							//$part = 'section=settings&user_id=' . $user_id;
							$part = 'section=overview';
						}
						$url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon'. $urlKey . '&user_id='. $sellernextId . '&seller_id=' . $sellernextData['ced_mp_seller_key'];
						?>
					    <option value="all" data-href="<?php echo esc_url($url); ?>" ><?php echo $sellernextId; ?></option>
					<?php }
   
				}  ?>

				    <option value="image" data-href="<?php echo esc_url( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=setup-amazon&add_new_account=1' ) ; ?>" >+ Add New Account</option>
		        </select> 
			   <?php
                   
			}

			?>	
	</div>
</div>

<div class="success-admin-notices is-dismissible"></div>

<?php
if ( ! $seller_participation ) {
	?>
			<div class="notice notice-error is-dismissable">
				 <p>Something went wrong with seller's participation, please check your Amazon seller account!</p>
			</div>
	<?php
}
	
?>





<style type="text/css">
			.ced-right{
				float: right;
			}
			.ced-menu-container{
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
</style>

