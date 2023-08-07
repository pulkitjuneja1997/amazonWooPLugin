<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

?>


<style type="text/css">
		table tbody tr td {
			border-bottom: 1px solid #eee;
		}

		.widefat td {
			vertical-align: middle;
			padding: 16px 10px;
		}

		table {
			border: 1px solid #eee;
		}

		/* span.ced-circle {
			
			height: 15px;
			width: 15px;
			position: absolute;
			left: 21px;
			background: #1ED14B;
			border-radius: 50%;
			border: 1px solid #75757580;
			top: 17px;
		}

		.ced-connected-button-wrap a {
			background: #EFF9F1;
			padding: 13px 30px;
			color: #1E1E1E;
			position: relative;
			content: '';
			border-radius: 2px;
			padding-left: 50px;
			font-size: 16px;
			
		} */

		span.ced-circle {
			/* padding: 4px; */
			height: 10px;
			width: 10px;
			position: absolute;
			left: 5px;
			background: #1ED14B;
			border-radius: 50%;
			border: 1px solid #75757580;
			top: 17px;
		}
		.ced-connected-button-wrap a {
			background: #EFF9F1;
			padding: 16px 7px;
			color: #1E1E1E;
			position: relative;
			content: '';
			border-radius: 2px;
			padding-left: 22px;
			font-size: 12px;
			/* align-items: center; */
		}
	</style>

	<div class="wrap woocommerce">
		<div class="woocommerce-progress-form-wrapper">
			<header style="text-align: left;">
				<h2>Welcome to CedCommerce Integration</h2>
				<p>Accelerate your sales by connecting to different marketplaces by CedCommerce. You can connect each
					marketplace from below or by clicking on marketplace tab above.</p>
			</header>
			<div class="wc-progress-form-content woocommerce-importer">
				<header>
					<h2>Connect Integration</h2>
				</header>
				<table class="wp-list-table widefat fixed striped table-view-list posts">
					<tbody id="the-list">

					<?php   

						/** 
						*
						* Filter to get array of active marketplaces
						*
						* @since  1.0.0
						*/
						$activeMarketplaces = apply_filters( 'ced_add_marketplace_menus_array', array() );
						foreach ( $activeMarketplaces as $navigation ) { 
							?>

							<tr id="post-319" style="background: #fff; border-bottom: 1px solid #c3c4c7;"
								class="iedit author-self level-0 post-319 type-product status-publish hentry" style="">
								<td style="width: 6%;" class="thumb column-thumb" data-colname="Image">
									<a href="http://cedcommerce.local/wp-admin/post.php?post=319&amp;action=edit">

									<?php
										$icon = CED_AMAZON_URL . 'admin/images/' . esc_attr($navigation['menu_link']) . 'Icon.png';
									?>
										<img width="150" height="150" src="<?php echo esc_url( $icon ); ?>" class="woocommerce-placeholder wp-post-image"
											alt="Placeholder" decoding="async" loading="lazy"
											sizes="(max-width: 150px) 100vw, 150px">
									</a>
								</td>
								<td style="width: 60%;" class="name column-name has-row-actions column-primary"
									data-colname="Name"><strong>
										<span style="font-size: 16px; color: #1E1E1E;"><?php echo esc_html__($navigation['name'], 'ced-amazon'); ?></span>
										<br>
										<?php
										if( 'amazon' == $navigation['menu_link'] ){
											$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
											if( !empty($ced_amazon_sellernext_shop_ids) ){ ?>

											<a class="row-title"
											href="http://cedcommerce.local/wp-admin/post.php?post=319&amp;action=edit"><?php echo esc_html__( count($ced_amazon_sellernext_shop_ids) ,'ced-amazon'); ?> account
											connected</a>



											<?php

											}

										}
										?>
										
									</strong>
								</td>
								<td class="sku column-sku" data-colname="SKU">
									<a href="#">View Guide</a>
								</td>
								
									<?php
									if ($navigation['is_installed'] && empty($ced_amazon_sellernext_shop_ids) ) {
										$url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon' ;
										?>
										<td class="is_in_stock column-is_in_stock" data-colname="Stock">
										<a href="<?php echo esc_url($url); ?>"class="components-button is-secondary">Connect</a> </td>
										<?php
									}elseif( $navigation['is_installed'] && !empty($ced_amazon_sellernext_shop_ids) ){ ?>
										<td class="ced-connected-button-wrap"><a class="ced-connected-link"><span class="ced-circle"></span>Connected</a> </td>
									<?php } else {
										?>
										<td class="is_in_stock column-is_in_stock" data-colname="Stock">
										<a target="_blank" href="<?php echo esc_url($navigation['page_url']); ?>" class="components-button is-secondary">Add plugin</a> </td>
									<?php
									}
									?>
									
								
							</tr>

							

						<?php } ?>	


					</tbody>
				</table>
			</div>
		</div>
	</div>



	<!-- <script type="text/javascript">
		jQuery(function () {
			jQuery('.woocommerce-importer-done-view-errors').on('click', function () {
				jQuery('.wc-importer-error-log').slideToggle();
				return false;
			});
		});
	</script> -->