<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_AMAZON_DIRPATH . 'admin/partials/amazonRegions.php';
if( file_exists( $file ) ){
    require_once $file;
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
			table{
				border: 1px solid #eee;
			}
			span.ced-circle {
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
			}
			.ced-pending-button-wrap span.ced-circle {
				height: 10px;
				width: 10px;
				position: absolute;
				left: 5px;
				background: #72AEE6;
				border-radius: 50%;
				border: 1px solid #75757580;
				top: 17px;
			}
			.ced-pending-button-wrap a {
				background: #EFF4F9;
				padding: 16px 7px;
				color: #1E1E1E;
				position: relative;
				content: '';
				border-radius: 2px;
				padding-left: 22px;
				font-size: 12px;
			}
			.ced-account-head div, .ced-account-body div {
				width: 50%;
				padding: 10px;
			}
			.ced-acount-body-label p {
				margin: 0.5em 0 !important;
				font-size: 14px !important;
			}
			.ced-account-head, .ced-account-body {
				display: flex;
				align-items: center;
			}
			.ced-account-label p {
				font-weight: 700;
				color: #1E1E1E !important;
				margin: 0.5em 0 !important;
				font-size: 14px !important;
			}

			.wc-importer-error-log{
				background: #fff;
			}

			.wc-importer-error-log:hover{
				background: #fff;
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

											<a class="row-title woocommerce-importer-done-view-errors" href="" ><?php echo esc_html__( count($ced_amazon_sellernext_shop_ids) ,'ced-amazon'); ?> account
											connected <span class="dashicons dashicons-arrow-down-alt2"></span></a>  

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
										// $url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon' ;
										$url = ced_get_navigation_url( $navigation['menu_link']);
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

							<?php
								if( 'amazon' == $navigation['menu_link'] ){
									$ced_amazon_sellernext_shop_ids = get_option( 'ced_amazon_sellernext_shop_ids', array() );
									if( !empty($ced_amazon_sellernext_shop_ids) ){ ?>

									<tr class="wc-importer-error-log" style="display:none;">
										<td colspan="4">
											<section class="wc-importer-error-log" style="display:none;">
												<div class="ced-account-connected-form">
													<div class="ced-account-head">
														<div class="ced-account-label">
															<p>Account Details</p>
														</div>
														<div class="ced-account-label">
															<p>Status</p>
														</div> 
													</div>

													<?php
														$sellernextShopIds     = get_option( 'ced_amazon_sellernext_shop_ids', array() );
														if( !empty( $sellernextShopIds ) ){
															foreach( $ced_amazon_sellernext_shop_ids as $sellernextId => $sellernextData ){ 

																$current_marketplace_id  = isset( $sellernextData['marketplace_id'] ) ? $sellernextData['marketplace_id'] : '';
																$current_marketplace_name = isset( $ced_amazon_regions_info[$current_marketplace_id] ) && isset( $ced_amazon_regions_info[$current_marketplace_id]['country_name'] ) ? $ced_amazon_regions_info[$current_marketplace_id]['country_name'] : '';
                                                                    

																?>
																
																	<?php

																	if( 3 < $sellernextData['ced_amz_current_step'] ){ 
																		$url = get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=overview&user_id='. $sellernextId . '&seller_id=' . $sellernextData['ced_mp_seller_key'];
					
																		?>
																		<!-- <a href="<?php echo esc_url($url); ?>" >  -->
																			<div class="ced-account-body">
																				<div class="ced-acount-body-label">
																				<p><?php echo $current_marketplace_name; ?></p>
																				</div>
																				<div class="ced-connected-button-wrap">
																					<a class="ced-connected-link"><span class="ced-circle"></span>Onboarding Complete</a>
																				</div>
																			</div>
																		<!-- </a> -->

																		<?php

																	} else{

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
                                                                        <a href="<?php echo esc_url($url); ?>" >
																			<div class="ced-account-body">
																				<div class="ced-acount-body-label">
																					<p><?php echo $current_marketplace_name; ?></p>
																				</div>
																				<div class="ced-pending-button-wrap">
																					<a class="ced-pending-link"><span class="ced-circle"></span>Onboarding Pending</a>
																				</div>
																			</div>
																		</a>

																		<?php

																	}

                                                                    ?>
																	
																
																<?php
															}

														}

                                                    ?>

									
												</div>
											</section>
										</td>
									</tr>
									<?php

									}

								}
							?>

						<?php } ?>	


					</tbody>
				</table>
			</div>
		</div>
	</div>



	<script type="text/javascript">
		jQuery(function () {
			jQuery('.woocommerce-importer-done-view-errors').on('click', function () {
				jQuery('.wc-importer-error-log').slideToggle();
				return false;
			});
		});
	</script>