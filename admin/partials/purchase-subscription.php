
<?php
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';

if ( 'plans-view' == $section ) { 

	$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
	if ( file_exists( $file ) ) {
	  require_once $file;
	}
	
}	


?>


<body>
	<div class="ced-amazon-pricing-table-wrapper">
		<div class="ced-amazon-pricing-table-container">
			<div class="ced-amazon-pricing-table-holder">
				
			<?php 
			if ( 'plans-view' !== $section ) { 
				?>
					<div class="section_padding_130">
						<div class="row justify-content-center">
							<div class="col-12 col-sm-8 col-lg-6">
								<!-- Section Heading-->
								<div class="section_heading text-center wow fadeInUp" data-wow-delay="0.2s"
									style="visibility: visible; animation-delay: 0.2s; animation-name: fadeInUp;">
									<h3 class="text-nowrap"><span>Plans for every Amazon Seller. Get started for free.</h3>
									<p class="fs-6">Join thousands of satisfied and happy Amazon Sellers who use our plugin to
										automatically and effortlessly sync their Amazon and WooCommerce store.</p>
									<div class="line"></div>
								</div>
							</div>
						</div>
					</div>
	
				   <?php
			}
			?>
				
				
				<div class="ced-amazon-pricing-table-wrap">
					<div class="ced-amazon-pricing-table-row">
						
						  <?php

							$amzonBilling = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-billing-apis.php';
		
							if ( file_exists( $amzonBilling ) ) {
								require_once $amzonBilling;
								$amzonBilling = new Billing_Apis();
							}  

						   $plans = $amzonBilling->fethcAllAmazonPlans();
						   // $plans = [];
	
							if ( 'plans-view' == $section ) {
								$contract_id = get_option( 'ced_amazon_contract_id', '');
								// $contract_id = 'd987a3c8-6a45-4cb8-8776-701364ceac42';
								$currentPlan = $amzonBilling->getAmazonPlanById( $contract_id );
								//echo json_encode($currentPlan); die;
								if ( isset( $currentPlan['status'] ) && $currentPlan['status'] ) {
	
									$responseBody = $currentPlan['data'];
									
									//echo '<pre>';
									//print_r($responseBody); echo '</pre>';
									
									$subscriptionStatus = isset($responseBody['status']) ? $responseBody['status'] : '';
	
									if ( 'active' == $subscriptionStatus ) {
										$subscriptionVerified = 1;
									}
	
								} else { 
									?>
	
									   <div class="ced-amazon-bootstrap-wrapper" >
										  <div class="jumbotron ced_subscription_warning" >
											  <h1 class="display-4">Hello, User!</h1>
											  <p class="lead">At this moment we are unable to load your current plan details, please Refresh the page or contact support.</p>
											  <hr class="my-4">
	
											  <p class="lead">
												 <a class="btn btn-primary btn-lg" href="#" role="button" onclick="history.back()" >Go Back</a>
											  </p>
										  </div>
									   </div>
	
									   <?php
								}
							}

							//print_r($plans); echo 'plansss';
							//print_r($responseBody); die('oppppppppppp');
	
							if ( ! empty( $plans ) && 0 < count($plans) && 'null' !== $plans ) {
	
								//$colorClass = array( 'starter' => 'secondary', 'growth' => 'warning', 'advance' => 'success', 'professional' => 'primary', 'enterprise' => 'danger' );
								$arrayOfArrays = isset( $responseBody['billing_intents'] ) ? $responseBody['billing_intents'] : array();

								if ( !empty( $arrayOfArrays ) ) {
									 
									foreach ( $arrayOfArrays as $planKey => $planArray ) {
										if ( 'completed' == $planArray['status'] ) {
											$subscribedPlan = $responseBody['billing_intents'][$planKey]['payload'];
											break;
										}
									}
						
								// 	 $responseBody['billing_intents'] = $arrayOfArrays;
									 
								// 	 $billingIntentsLength = count( $responseBody['billing_intents'] ) - 1;
								

								}
									 
								foreach ( $plans as $key => $plan ) {
									 
									?>
						
									<div class="ced-amazon-pricing-table-coloum-wrap">
										<div class="ced-amazon-pricing-header">
											
											 <?php if ( 'plans-view' == $section && $plan['name'] == $subscribedPlan['name'] ) { ?>
													   <div class="ced-current-price">
														  <span class="ced-current-price-new">New</span>
														</div>
											 <?php } ?>
											
											<div class="ced-amazon-pricing-header-wrap">
												<div class="ced-amazon-plan-info" > 
													<h3><?php print_r($plan['name']); ?></h3> 
																   <?php
													
																	if ( 'plans-view' == $section  && $plan['name'] == $subscribedPlan['name']  && 'canceled' !== $subscriptionStatus  ) { 
																		?>
													   
														<h5 class="ced_amazon_next_billing_date"> Next Billing: <b><?php print_r(substr($responseBody['next_payment_date'], 0, 10) ); ?></b> </h5>
														  
																	<?php 
																	} elseif ( 'plans-view' == $section  && $plan['name'] == $subscribedPlan['name']  && 'canceled' == $subscriptionStatus  ) { 
																		?>
													   
															  <h5 class="ced_amazon_end_billing_date"> Valid Till: <b><?php print_r( substr($responseBody['end_date'], 0, 10) ); ?></b> </h5>
														 
																   <?php } ?>
														  
												</div>
											
												<p>Everything you need to launch an nline store</p>
											</div>
											<hr>
										</div>
										<div class="ced-amazon-pricing-table-price-container">
											<div class="ced-amazon-pricing-table-price-name-wrapper">
												<div class="ced-amazon-price-text">
													<div class="ced-amazon-price-sub-cont" > 
														<input type="radio" name="interval" class="ced_ebay_plan_interval" value="month">
														<h4><span><?php print_r($plan['price']['month']); ?></span>/month</h4>
													</div>
													
													 <?php
														$perMonth = $plan['price']['year'] / 12;
														$percent  = ( $plan['price']['month'] - $perMonth ) / $perMonth * 100;
														?>
													 
													 <div class="ced-amazon-price-sub-cont" >  
														 <input type="radio" name="interval" class="ced_ebay_plan_interval" value="year">
														 <p><span>$<?php print_r($perMonth); ?></span>/month billed annualy <span class="ced-amazon-save-higilight">SAVE <?php print_r( ceil($percent) ) ; ?>%</span></p>
													</div>
												</div>
												<hr>
											</div>
										</div>
										
										<div class="ced-amazon-pricing-table-content-wrap-listing">
											<div class="ced-amazon-pricing-table-list-holder">
												<ul>
													<li><?php print_r($plan['product_limit']); ?> Listings</li>
													<li><?php print_r($plan['orders_limit']); ?> Orders</li>
													<li>Free 24 * 7 support</li>
													<li>Multiple Amazon Accounts</li>
													<li>Beautiful premium theme</li>
													<li>Automated site backup</li>
												</ul>
											</div>
										</div>
										<div class="ced-amazon-pricing-button-subscribe">
											<div class="ced-amazon-pricing-buttin-content">
													 <?php
														if ( 'plans-view' == $section  && $plan['name'] == $subscribedPlan['name']  && 'canceled' !== $subscriptionStatus  ) { 
															?>
													   
														   <button class="ced_plan_cancel_button" data-plan_id="<?php echo esc_attr($key); ?>" data-contract_id = "<?php echo esc_attr($contract_id); ?>" > CANCEL PLAN</button>
													  
													 <?php 
														} elseif ( 'plans-view' == $section  && $plan['name'] == $subscribedPlan['name']  && 'canceled' == $subscriptionStatus  ) { 
															?>
													   
															 
														<?php } else { ?>
														   <button class="ced_plan_selection_button" data-plan_id="<?php echo esc_attr($key); ?>"  data-contract_id = "<?php echo esc_attr($contract_id); ?>" data_trial=false >SUBSCRIBE PLAN</button>
														<?php
														}
													   
														?>
												   
											</div>
										</div>
									</div>
									
									
									<?php
								} 
								 
							}
								 
							?>
								
									
								
					</div>
					
					<div class="ced-amazon-pricing-table-row">
						
						
					</div>
				</div>
			</div>
		</div>
	</div>
</body>



<style type="text/css">

ul li {
	list-style-type: disc;
}


.ced-amazon-pricing-table-row {
	display: flex;
	justify-content: space-around;
}
.ced-amazon-pricing-table-coloum-wrap {
	background: #ece9ff;
	padding: 20px 16px;
	width: 19%;
	overflow: hidden;
	border-radius: 10px;
}
.ced-amazon-pricing-header-wrap h3 {
	color: #674399;
}
.ced-amazon-pricing-header-wrap p {
	color: #606060;
}
.ced-amazon-pricing-table-coloum-wrap hr {
	color: #ded4ef;
}
.ced-amazon-price-text h4 {
	font-size: 24px;
	margin: 0;
	color: #333333;
}
.ced-amazon-price-text p span {
	color: #333333;
	font-size: 16px;
	font-weight: 900;
}
.ced-amazon-save-higilight {
	background: #000000;
	color: #fff !important;
	padding: 3px 7px;
	border-radius: 47px;
	display: inline-block;
	font-size: 11px !important;
}
.ced-amazon-price-text p {
	color: #606060;
}
.ced-amazon-pricing-table-list-holder ul li {
	color: #606060;
	margin-left: 16px;
	line-height: ;
}
.ced-pricing-table-purchase-button-holder a {
	text-decoration: none;
	background: #674399;
	width: 100%;
	display: block;
	padding: 12px;
	text-align: center;
	border-radius: 7px;
	color: #fff;
	transition: 0.3s all;
	font-weight: bold;
	border: 1px solid #674399;
}
.ced-pricing-table-purchase-button-holder a:hover {
	background: transparent;
	color: #674399;
}
.ced-amazon-pricing-buttin-content button {
	text-decoration: none;
	background: #674399d4;
	color: #fff;
	display: block;
	width: 100%;
	padding: 12px;
	text-align: center;
	font-weight: bold;
	border-radius: 7px;
	border: 1px solid #674399d4;
	transition: 0.3s all;
}
.ced-amazon-pricing-buttin-content button:hover {
	background: transparent;
	color: #674399d4;
}
	
.ced-current-price {
  position: relative;
}
.ced-current-price-new:before {
	content: "";
	width: 0;
	height: 0;
	border-style: solid;
	transform: rotate(45deg);
	border-width: 37px 37px 0 30px;
	border-color: red transparent transparent transparent;
	position: absolute;
	right: -45px;
	bottom: -7px;
}
.ced-current-price-new:after {
	content: "";
	width: 0;
	height: 0;
	border-style: solid;
	transform: rotate(-45deg);
	border-width: 22px 22px 0 20px;
	border-color: red transparent transparent transparent;
	position: absolute;
	left: -27px;
	bottom: -2px;
}
/* .ced-current-price-new {
	background: red;
	color: white;
	padding: 6px 36px;
	text-transform: uppercase; 
	position: absolute;
	top: -29px;
	left: -52px;
	z-index: 2;
	transform: rotate(-45deg);
} */

.ced-amazon-price-sub-cont h4, p{
	display: contents;
}

.ced-amazon-price-sub-cont{
	padding: 8px 0px;
		
}

input[type="radio"]{
   border: 1px solid #674399;    
}

.ced-amazon-plan-info {
	display: flex;
	justify-content: space-between;
	align-items: center;
}
h5.btn.btn-outline-secondary .ced_amazon_end_billing_date, .ced_amazon_next_billing_date  {
	background: #7e5faa;
	color: #fff;
	padding: 0px 4px;
	border-radius: 11px;
}
.ced-current-price-new {
	background: red;
	color: white;
	padding: 6px 36px;
	/* text-transform: uppercase; */
	position: absolute;
	top: -14px;
	left: -51px;
	z-index: 2;
	transform: rotate(-45deg);
}

.ced-amazon-pricing-table-holder{
	padding-top: 50px;
}

</style>



<script>

// jQuery(document).ready(
// 	function (e) {

// 		jQuery.ajax({
// 			type: 'post',
// 			url: 'http://localhost:10219/wp-admin/admin-ajax.php',
// 			data: {
// 				// ajax_nonce: ajaxNonce,
// 				// plan_type: plan_type,
// 				plan_id: 'starter',
// 				period: 'year',
// 				data_trial: false,
// 				contract_id: '',
// 				action: 'ced_amazon_pricing_plan_selection'
// 			},

// 			success: function (response) {

// 				let parsed_response = JSON.parse(response);
				
// 				if ( parsed_response.status ) {
// 					let confirmation_url = parsed_response.confirmation_url;
// 					window.location.href = confirmation_url;
// 				} else {
					
// 					customSwal({
// 						title: 'Plan Error',
// 						text: 'Currently we are unable to process your request. Please try again.',
// 						icon: 'error',
// 					}, () => {return; })

// 				}
// 			}
// 		});


// })

</script>
