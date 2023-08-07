(function ($) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

	var ajaxUrl               = ced_amazon_admin_obj.ajax_url;
	var ajaxNonce             = ced_amazon_admin_obj.ajax_nonce;
	var user_id               = ced_amazon_admin_obj.user_id;
	var siteUrl               = ced_amazon_admin_obj.site_url;
	var amazon_loader_overlay = '<div class="ced_amazon_overlay"><div class="ced_amazon_overlay__inner"><div class="ced_amazon_overlay__content"><div class="ced_amazon_page-loader-indicator ced_amazon_overlay_loader"><svg class="ced_amazon_overlay_spinner" width="65px" height="65px" viewBox="0 0 66 66" xmlns="http://www.w3.org/2000/svg"><circle class="path" fill="none" stroke-width="6" stroke-linecap="round" cx="33" cy="33" r="30"></circle></svg></div><div class="ced_amazon_page-loader-info"><p class="ced_amazon_page-loader-info-text" id="ced_amazon_progress_text">Loading...</p><p class="ced_amazon_page-loader-info-text" style="font-size:19px;" id="ced_amazon_countdown_timer"></p></div></div></div></div>';

	const queryString = window.location.search;
	var urlParams     = new URLSearchParams(queryString);

	console.log(ajaxUrl);


	jQuery(document).on(
		'change',
		'.ced-amazon-filter-products-criteria',
		function () {

			var productFilterCriteria = jQuery(document).find('.ced-amazon-filter-products-criteria option:selected').val();
			if (productFilterCriteria == 'product_name') {
				var productFilterCriteriaPlaceholder = 'Filter by Product Name';
			} else if (productFilterCriteria == 'amazon_listing_id') {
				var productFilterCriteriaPlaceholder = 'Filter by amazon Item ID';
			} else if (productFilterCriteria == 'product_sku') {
				var productFilterCriteriaPlaceholder = 'Filter by Product SKU';
			}

			jQuery('.ced-amazon-filter-products').selectWoo(
				{
					allowClear: true,
					placeholder: productFilterCriteriaPlaceholder,
					dropdownPosition: 'below',
					dropdownAutoWidth: true,
					language: {
						inputTooShort: function (args) {

							return "Please enter 3 or more words.";
						},
						noResults: function () {
							return "Not Found.";
						},
						searching: function () {
							return "Searching...";
						}
					},
					minimumInputLength: 3,
					ajax: {
						url: ajaxUrl,
						delay: 250,
						data: function (term) {
							return {
								search_term: term.term,
								ajax_nonce: ajaxNonce,
								action: 'ced_amazon_filter_products',
								filter_criteria: productFilterCriteria,
								user_id: user_id
							};
						},
						processResults: function (data) {
							return {
								results: $.map(
									data,
									function (obj) {
										if (obj.id != 0) {
											return { id: obj.post_id, text: obj.post_title };
										}
									}
								)
							};

						}
					}
				}
			);
		}
	);

	$(document).ready(
		function (e) {

			jQuery('.ced-amazon-filter-products').selectWoo(
				{
					allowClear: true,
					placeholder: "Filter by Product Name",
					dropdownPosition: 'below',
					dropdownAutoWidth: true,
					language: {
						inputTooShort: function (args) {

							return "Please enter 3 or more words.";
						},
						noResults: function () {
							return "Not Found.";
						},
						searching: function () {
							return "Searching...";
						}
					},
					minimumInputLength: 3,
					ajax: {
						url: ajaxUrl,
						delay: 250,
						data: function (term) {
							return {
								search_term: term.term,
								ajax_nonce: ajaxNonce,
								action: 'ced_amazon_filter_products',
								filter_criteria: 'product_name',
								user_id: user_id
							};
						},
						processResults: function (data) {
							return {
								results: $.map(
									data,
									function (obj) {
										if (obj.id != 0) {
											return { id: obj.post_id, text: obj.post_title };
										}
									}
								)
							};

						}
					}
				}
			);


			$(document).find('.ced_amazon_loader').css('display', 'none');

			remove_custom_notice();
			let section = urlParams.get('section');

			if (section == 'add-new-template') {
				checkSellerNextCategoryApi();
			}

		}
	);


	function remove_custom_notice(reload = 'no') {

		if ($('#ced_amazon_custom_notice').hasClass('ced_amazon_notice')) {
			setTimeout(() => {
				$('.ced_amazon_notice').remove();
				if (reload == 'yes') {
					window.location.reload();
				}
			}, 3000)
		}
	}

	$(document).on(
		'change',
		'.ced_amazon_select_category ',
		function (e) {

			var level         = $(this).data('level');
			var category_data = {
				'primary_category': $('#ced_amazon_primary_category_selection').val()
			}

			let template_id = urlParams.get('template_id') ? urlParams.get('template_id') : '';
			let id          = $(this).attr('id');

			if (id == "ced_amazon_primary_category_selection") {
				$('#ced_amazon_secondary_category_selection').parents('tr').remove();
				$('#ced_amazon_browse_nodes_selection').parents('tr').remove();
				$('.categoryAttributes').remove();
			}

			if (id == "ced_amazon_secondary_category_selection") {
				$('#ced_amazon_browse_nodes_selection').parents('tr').remove();
				$('.categoryAttributes').remove();

				category_data['secondary_category'] = $('#ced_amazon_secondary_category_selection').val();
			}

			if (id == "ced_amazon_browse_nodes_selection") {
				$('.categoryAttributes').remove();
				category_data['secondary_category'] = $('#ced_amazon_secondary_category_selection').val();
				category_data['browse_nodes']       = $('#ced_amazon_browse_nodes_selection').val();
			}

			jQuery( '#wpbody-content' ).append( amazon_loader_overlay );
			let categoryResponse = ced_amazon_fetch_next_level_category( level, category_data , template_id,'no' );
			categoryResponse.then( response => {
				jQuery( '#wpbody-content .ced_amazon_overlay' ).remove();
				handleCategoryResponse( response )
			})

		}
	);


	async function ced_amazon_fetch_next_level_category( level, category_data, template_id, display_saved_values ){
		 
		let user_id   = urlParams.get('user_id');
		let seller_id = urlParams.get('seller_id'); 

		let categoryResponse;

		await	$.ajax({
			url : ajaxUrl,
			data : {
				ajax_nonce : ajaxNonce,
				action : 'ced_amazon_fetch_next_level_category',
				level : level,
				category_data: category_data,
				template_id : template_id,
				user_id: user_id,
				seller_id: seller_id,
				display_saved_values: display_saved_values
			},
			type : 'POST',
			success: function(response){
				categoryResponse = response;
			}
			});

		return categoryResponse;
	}


	$(document).on(
		'click',
		'.ced_amazon_add_account_button',
		function () {

			var nameValue         = jQuery("#ced_amazon_select_marketplace_region").find("option:selected").val();
			var endPt             = jQuery("#ced_amazon_select_marketplace_region").find("option:selected").attr("end-pt");
			var marketplaceId     = jQuery("#ced_amazon_select_marketplace_region").find("option:selected").attr("mp-id");
			var shopName          = jQuery("#ced_amazon_select_marketplace_region").find("option:selected").attr("shop-name");
			var sellerEmail       = jQuery(".ced_amazon_seller_email").val();
			var countryName       = jQuery("#ced_amazon_select_marketplace_region").find("option:selected").attr("country-name");
			var marketplaceUrl    = jQuery("#ced_amazon_select_marketplace_region").find("option:selected").attr("mp-url");
			var marketplaceRegion = jQuery("#ced_amazon_select_marketplace_region").find("option:selected").parents('optgroup').data('attr');

			
			if (sellerEmail == '') {
				
				jQuery('.ced_wizard_content').append('<div id="message" class="error inline ced-notification-error"><p>Please enter email ID.</p></div>');
				setTimeout(() => {
					jQuery('#message').remove();
				}, 5000)

				return;
			}

			if ( marketplaceId == '' || marketplaceId == undefined || marketplaceId == null ) {
				jQuery('.ced_wizard_content').append('<div id="message" class="error inline ced-notification-error"><p>Please select valid marketplace region.</p></div>');
				setTimeout(() => {
					jQuery('#message').remove();
				}, 5000)

				return;
			}

			// jQuery('#wpbody-content').append(amazon_loader_overlay);
			// jQuery('#progress_text').html('Please wait while we connect your Amazon Account');

			let params = {

				marketplace_id: marketplaceId,
				marketplace_region: marketplaceRegion,
				name_value: nameValue,
				end_pt: endPt,
				shop_name: shopName,
				country_name: countryName,
				marketplace_url: marketplaceUrl,

			}

			sessionStorage.setItem("amazonAccountParams", params );
	
			jQuery.ajax(
				{
					type: 'POST',
					url: ajaxUrl,
					data: {
						ajax_nonce: ajaxNonce,
						marketplace_id: marketplaceId,
						seller_email: sellerEmail,
						// marketplace_region: marketplaceRegion,
						action: 'ced_amazon_create_sellernext_user',
					},
					success: function (response) {

						// jQuery( '.ced_amazon_overlay' ).remove();

						if (response.status == 'success') {
							jQuery('#progress_text').html(response.message);
							jQuery.ajax(
								{
									type: 'POST',
									url: ajaxUrl,
									data: {
										ajax_nonce: ajaxNonce,
										marketplace_id: marketplaceId,
										refresh_token: response.refresh_token,
										marketplace_region: marketplaceRegion,
										name_value: nameValue,
										end_pt: endPt,
										shop_name: shopName,
										country_name: countryName,
										marketplace_url: marketplaceUrl,
										action: 'ced_amazon_sellernext_get_access_token_and_redirect',

									},
									success: function (response) {
										if (response.status == 'success') {
											jQuery('.ced_amazon_overlay').remove();

											window.location.replace(response.redirect_url);
										}
									}
								}
							)
						}
						// jQuery('#progress_text').html(response.message);
						if (response.status == 'failed' || response.status == '') {
							jQuery('.ced_amazon_overlay').remove();
							jQuery('#wpbody-content').prepend('<div class="notice notice-error ced_amazon_notice" id="ced_amazon_custom_notice">' + response.message + '.</div>')
							remove_custom_notice();
						}

					}
				}
			)

		}
	);


	function cedAmazonConnectAccount( params ){

		jQuery.ajax(
			{
				type: 'POST',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					marketplace_id: params.marketplaceId,
					seller_email: params.sellerEmail,
					// marketplace_region: marketplaceRegion,
					action: 'ced_amazon_create_sellernext_user',
				},
				success: function (response) {

					// jQuery( '.ced_amazon_overlay' ).remove();

					if (response.status == 'success') {
						jQuery('#progress_text').html(response.message);
						jQuery.ajax(
							{
								type: 'POST',
								url: ajaxUrl,
								data: {
									ajax_nonce: ajaxNonce,
									marketplace_id: params.marketplaceId,
									refresh_token: response.refresh_token,
									marketplace_region: params.marketplaceRegion,
									name_value: params.nameValue,
									end_pt: params.endPt,
									shop_name: params.shopName,
									country_name: params.countryName,
									marketplace_url: params.marketplaceUrl,
									action: 'ced_amazon_sellernext_get_access_token_and_redirect',

								},
								success: function (response) {
									if (response.status == 'success') {
										jQuery('.ced_amazon_overlay').remove();
										window.location.replace(response.redirect_url);
									}
								}
							}
						)
					}
					// jQuery('#progress_text').html(response.message);
					if (response.status == 'failed' || response.status == '') {
						jQuery('.ced_amazon_overlay').remove();
						jQuery('#wpbody-content').prepend('<div class="notice notice-error ced_amazon_notice" id="ced_amazon_custom_notice">' + response.message + '.</div>')
						remove_custom_notice();
					}

				}
			}
		)

	}


	// Set configuration path while select added account (Arun)
	$(document).on(
		'change',
		'#ced_amazon_select_active_marketplaces_region',
		function (e) {

			let nameValue = jQuery("#ced_amazon_select_active_marketplaces_region").find("option:selected").val();
			// let marketplaceId     = jQuery("#ced_amazon_select_active_marketplaces_region").find("option:selected").attr("mp-id");
			// let shopName          = jQuery("#ced_amazon_select_active_marketplaces_region").find("option:selected").attr("shop-name");
			// let countryName       = jQuery("#ced_amazon_select_active_marketplaces_region").find("option:selected").attr("country-name");
			let sellerId = jQuery("#ced_amazon_select_active_marketplaces_region").find("option:selected").attr("seller-id");
			// let marketplaceRegion = jQuery("#ced_amazon_select_active_marketplaces_region").find("option:selected").parents('optgroup').data('attr');


			var sellernextShopId   = jQuery("#ced_amazon_select_active_marketplaces_region").find("option:selected").attr('sellernext-shop-id');
			var currentMarketplace = jQuery("#ced_amazon_select_current_marketplace").attr("current-url");

			if (nameValue == '') {
				jQuery('#wpbody-content .ced-amazon-bootstrap-wrapper').prepend('<div class="notice notice-error ced_amazon_notice" id="ced_amazon_custom_notice">Please select your amazon marketplace.</div>');
				remove_custom_notice();
				jQuery('a#ced_amazon_select_current_marketplace').hide();
				jQuery('#ced_amazon_disconnect_account_btn').hide();
				jQuery("#ced_amazon_select_current_marketplace").attr("href", "#");
				jQuery("#ced_amazon_disconnect_account_btn").attr("data-attr", "");
				return;
			}

			if (nameValue !== '') {
				
				jQuery('#ced_amazon_select_current_marketplace').show();
				jQuery('#ced_amazon_disconnect_account_btn').show();
			}

			let currentURL = currentMarketplace;

			// Set href for current marketplace

			jQuery('#wpbody-content').append(amazon_loader_overlay);
			jQuery.ajax(
				{
					type: 'POST',
					url: ajaxUrl,
					data: {
						ajax_nonce: ajaxNonce,
						action: 'ced_amazon_update_current_url',
						user_id: sellernextShopId,

					},
					success: function (response) {
						
						jQuery('.ced_amazon_overlay').remove();

						response    = JSON.parse(response);
						currentURL += '&' + response.part;
						currentURL += '&user_id=' + sellernextShopId;

						if (notNullAndEmpty(response.seller_id)) {

							currentURL = currentURL + "&seller_id=" + response.seller_id;
							jQuery("#ced_amazon_select_current_marketplace").attr("href", currentURL);

						}

						jQuery("#ced_amazon_select_current_marketplace").attr("href", currentURL);
						jQuery("#ced_amazon_select_current_marketplace").attr("sellernext-shop-id", sellernextShopId);

						//set seller id for disconnect button
						jQuery("#ced_amazon_disconnect_account_btn").attr("sellernext-shop-id", sellernextShopId);
						jQuery("#ced_amazon_disconnect_account_btn").attr("seller-id", sellerId);


					}
				}
			)


		}
	);


	function notNullAndEmpty(variable) {

		if (variable == null || variable == "" || variable == 0 || variable == 'null') {
			return false;
		}

		return true;
	}

	$(document).on(
		'click',
		'#ced_amazon_fetch_orders',
		function (event) {
			event.preventDefault();
			var store_id  = $(this).attr('data-id');
			var seller_id = urlParams.get('seller_id');

			$('.ced_amazon_loader').show();
			$.ajax(
				{
					url: ajaxUrl,
					data: {
						ajax_nonce: ajaxNonce,
						action: 'ced_amazon_get_orders',
						userid: store_id,
						seller_id: seller_id
					},
					type: 'POST',
					success: function (response) {
						$('.ced_amazon_loader').hide();
						var response  = jQuery.parseJSON(response);
						var response1 = jQuery.trim(response.message);
						if (response1 == "Shop is Not Active") {
							var notice = "";
							notice    += "<div class='notice notice-error'><p>Currently Shop is not Active . Please activate your Shop in order to fetch orders.</p></div>";
							$(".success-admin-notices").append(notice);
							return;
						} else if (response.status == 'success') {
							var notice = "";
							notice    += "<div class='notice notice-success'><p>Orders fetch requested Successfully. Please reload the page to see your orders.</p></div>";
							$(document).find(".success-admin-notices").append(notice);
						} else if (response.status == 'No Results') {
							var notice = "";
							notice    += "<div class='notice notice-error'><p>We can\'t see your orders in API response.</p></div>";
							$(document).find(".success-admin-notices").append(notice);
						} else if (response.status == 'error') {
							var notice = "";
							notice    += "<div class='notice notice-error'><p>Something went wrong, please try again!</p></div>";
							$(document).find(".success-admin-notices").append(notice);
						}

					}
				}
			);
		}
	);

	$(document).on('click', '#ced_amazon_continue_wizard_button', function (e) {

		let currentStep = $(this).data('attr');
		let user_id     = urlParams.get('user_id');

		jQuery.ajax(
			{
				type: 'POST',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					action: 'ced_amazon_update_current_step',
					current_step: currentStep,
					user_id: user_id
				},
				success: function (response) {
				}
			}
		)

	}
	)

	$(document).on(
		'click',
		'.ced_amazon_suggestion',
		function (e) {
			$(this).fadeOut('slow');
		}
	)

	jQuery(document).on(
		'click',
		'#ced_amazon_disconnect_account_btn',
		function (e) {

			let sellernextShopId = $(this).attr('sellernext-shop-id');
			let seller_id        = $(this).attr('seller-id');
			e.preventDefault();
			//var seller_id = urlParams.get('seller_id');
			
			customSwal({
				text: "Are you sure you want to disconnect your account? Disconnecting your account will stop all the automation. Your configuration will not be deleted. <a id='ced_amazon_verf_disconnect_account_btn' sellernext-shop-id='" + sellernextShopId + "' seller_id = '"+seller_id + "' >Disconnect Account</a>",
				icon: "warning",
				buttons: true,
				dangerMode: true,
			}, () => {return; }, 25000);
		}
	);

	jQuery(document).on(
		'click',
		'#ced_amazon_verf_disconnect_account_btn',
		function (e) {

			let sellernextShopId = $(this).attr('sellernext-shop-id');
			let seller_id        = $(this).attr('seller_id');
			e.preventDefault();
			//var seller_id = urlParams.get('seller_id');
			// jQuery('.ced_amazon_loader').show();
			jQuery('#wpbody-content').append(amazon_loader_overlay);
			jQuery.ajax(
				{
					type: 'post',
					url: ajaxUrl,
					data: {
						seller_id: seller_id,
						sellernextShopId: sellernextShopId,
						ajax_nonce: ajaxNonce,
						action: 'ced_amazon_remove_account_from_integration'
					},
					success: function (response) {
						// jQuery('.ced_amazon_loader').hide();
						jQuery('#wpbody-content .ced_amazon_overlay').remove();

						customSwal(
							{
								title: response.title,
								text: response.message,
								icon: response.status,
							},
							() => {
								window.location.reload();
							}
						)
					}
				}
			)
			
		}
	);

	jQuery(document).on(
		'focus',
		'.ced-form-field',
		function () {
			jQuery(this).closest('.input-outline').addClass('active')
		}
	).on(
		'blur',
		'.ced-form-field',
		function () {
			if (jQuery(this).val() == '') {
				jQuery(this).closest('.input-outline').removeClass('active')
			}
		}
	)


	jQuery(document).on(
		"click",
		".ced_amazon_add_rows_button",
		function (e) {

			e.preventDefault();
			let custom_field     = $(this).parents('tr').children('td').eq(1).find('select');
			let custom_field_val = custom_field.val();
			let id               = $(this).attr('id');
			let fileUrl          = $('.ced_amazon_profile_url').val(); 

			console.log($('.ced_amazon_template_type').val());
	
			id = escapeBrackets(id);
			
			let primary_cat   = $('#ced_amazon_primary_category_selection').val();
			let secondary_cat = $('#ced_amazon_secondary_category_selection').val();

			if ('' == custom_field_val) {
				$(this).parents('tr').before('<tr class="ced_amazon_custom_field_error" id="empty_field_error"><td colspan="3">Please enter a custom field name</td></tr>')

				setTimeout(
					function () {
						$('#empty_field_error').remove();
					},
					3000
				);
				return;
			}
			jQuery('#wpbody-content').append(amazon_loader_overlay);

			jQuery.ajax({
				type: 'post',
				url: ajaxUrl,
				data: {
					userid: user_id,
					ajax_nonce: ajaxNonce,
					custom_field: JSON.parse(custom_field_val),
					primary_cat: primary_cat,
					secondary_cat: secondary_cat,
					template_type: $('.ced_amazon_template_type').val(),
					fileUrl: fileUrl,
					dataType: "html",
					action: 'ced_amazon_add_custom_profile_rows'
				},
				success: function (response) {

					response = JSON.parse(response);

					jQuery('#wpbody-content .ced_amazon_overlay').remove();
					$('#' + id).parents('tr').before(response.data);
					$('#optionalFields option:selected').remove();
					custom_field.val('');
					$("#optionalFields").val(null).trigger("change");
					$('.custom_category_attributes_select').selectWoo();
					$( '.custom_category_attributes_select2' ).selectWoo();

					createTooltip();

				}
			})

		}
	);

	jQuery(document).on(
		"click",
		".profile-del",
		function (e) {

			let id      = $(this).parents('span').attr('id');
			let profile = $(this).parents('span').attr('class');

			jQuery('#wpbody-content').append(amazon_loader_overlay);

			jQuery.ajax(
				{
					type: 'post',
					url: ajaxUrl,
					data: {
						userid: user_id,
						ajax_nonce: ajaxNonce,
						id: id,
						profile: profile,
						action: 'ced_amazon_del_profile'
					},
					success: function (response) {
						jQuery('#wpbody-content .ced_amazon_overlay').remove();
						response = JSON.parse(response);

						customSwal(
							{
								title: 'Woocommerce Category',
								text: response.message,
								icon: response.status,
							},
							() => { window.location.reload(); }
						);

					}
				}
			)

		}
	)

	jQuery(document).on(
		"click",
		"#update_template",
		function (e) {

			e.preventDefault();

			let primary_cat   = $('#ced_amazon_primary_category_selection').val();
			let secondary_cat = $('#ced_amazon_secondary_category_selection').val();
			let browse_nodes  = $('#ced_amazon_browse_nodes_selection').val();

			let user_id   = urlParams.get('user_id');
			let seller_id = urlParams.get('seller_id');

			jQuery('#wpbody-content').append(amazon_loader_overlay);

			jQuery.ajax(
				{
					type: 'post',
					url: ajaxUrl,
					data: {
						ajax_nonce: ajaxNonce,
						primary_cat: primary_cat,
						secondary_cat: secondary_cat,
						browse_nodes: browse_nodes,
						user_id: user_id,
						seller_id: seller_id,
						action: 'ced_amazon_update_template'
					},
					success: function (response) {
						response = JSON.parse(response);
						jQuery('#wpbody-content .ced_amazon_overlay').remove();
						// swal(
						// 	{
						// 		title: 'Update',
						// 		text: response.message,
						// 		icon: response.status,
						// 	}
						// ).then(() => { window.location.reload(); });

						customSwal({
							title: 'Update',
							text: response.message,
							icon: response.status,
						}, () => { window.location.reload(); } )

					}
				}
			)

		}
	);

	function checkSellerNextCategoryApi(){

		jQuery.ajax(
			{
				type:'post',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					user_id: urlParams.get('user_id'),
					action: 'ced_amazon_checkSellerNextCategoryApi'
				},
				success: function(response){
					
					let template_id = urlParams.get( 'template_id' );
					if (  response.success == 1 ) {
						if ( template_id !== '' && template_id !== null ) {
							CategoryApiLoop( template_id );
							
						} else {
							jQuery( '#wpbody-content .ced_amazon_overlay' ).remove();
						}
					} else {

						customSwal({
							title: 'Product Category',
							text: 'We are facing some issue while loading data. Please try after sometime. ',
							icon: 'error',
						}, () => {   window.history.back() } );

					}

				}
			}
		)
	}


	async function CategoryApiLoop( template_id ){

		// let level_array = [1,2,3];  
		// for(let i = 1; i <= level_array.length; i++){
		
		let template_type = urlParams.get('template_type');

		let level_array = [1, 2, 3];
		for (let i = 1; i <= level_array.length; i++) {
			if ( template_type == 'amazonTemplate' && i <= 2 ) {
				continue;
			}

			var category_data = { 'primary_category' : $( '#ced_amazon_primary_category_selection' ).val() } ;
				
			category_data['secondary_category'] = $( '#ced_amazon_secondary_category_selection' ).val();

			jQuery( '#wpbody-content' ).append( amazon_loader_overlay );
			// let html = '<b>' + i + i + '</b>'; 
			// jQuery("#ced_amazon_progress_text").html(html);
	
			let categoryResponse = await ced_amazon_fetch_next_level_category( i, category_data, template_id, 'yes' );
			jQuery( '#wpbody-content .ced_amazon_overlay' ).remove();
			await handleCategoryResponse( categoryResponse )
				
		}
		
	}
	
	
	function handleCategoryResponse( response ){

		if ( response.success ) {

			$( '.profile_section tbody #amazon_category_reference' ).before( response.data );
			$( '.custom_category_attributes_select' ).selectWoo();
			$( '.custom_category_attributes_select2' ).selectWoo();
			$( '.ced_amazon_select_category' ).selectWoo();
			$( '#optionalFields' ).selectWoo();

		} else {
			
			customSwal({
				title: 'Fetch Category',
				text:  response.message,
				icon:  'error',

			},() => {   window.history.back() } );
		}

		createTooltip();

	}
	

	jQuery(document).on( 'click', '.ced_plan_selection_button', function (e) {

		e.preventDefault();
		//jQuery('#wpbody-content').append(amazon_loader_overlay);

		var data_trial = jQuery(this).attr('data_trial');
		var plan_type  = jQuery(this).parents('.card').attr('data-plan_name');
		var plan_id    = jQuery(this).attr('data-plan_id');
		var interval   = $(this).parents('.ced-amazon-pricing-table-coloum-wrap').find('input[name="interval"]:checked').val();
		
		var contract_id = jQuery(this).attr( 'data-contract_id' );

		if ( interval == undefined || interval == null || interval.length == 0 ) {

	
			customSwal({
				title: 'Plan Interval',
				text: 'Please select Plan Interval',
				icon: 'error',

			}, () => {return; });
			jQuery('#wpbody-content .ced_amazon_overlay').remove();

			return;
		}

		jQuery.ajax({
			type: 'post',
			url: ajaxUrl,
			data: {
				ajax_nonce: ajaxNonce,
				plan_type: plan_type,
				plan_id: plan_id,
				period: interval,
				data_trial: data_trial,
				contract_id: contract_id,
				action: 'ced_amazon_pricing_plan_selection'
			},

			success: function (response) {

				let parsed_response = JSON.parse(response);
				
				if ( parsed_response.status ) {
					let confirmation_url = parsed_response.confirmation_url;
					window.location.href = confirmation_url;
				} else {
					
					customSwal({
						title: 'Plan Error',
						text: 'Currently we are unable to process your request. Please try again.',
						icon: 'error',
					}, () => {return; })

				}
			}
		});

	});


	jQuery(document).on('click', '#amazon_seller_verification', function (e) {

		e.preventDefault();
		jQuery('#wpbody-content').append(amazon_loader_overlay);

		let user_id = $(this).attr('dta-amz-shop-id');

		jQuery.ajax(
			{
				type: 'post',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					user_id: user_id,
					action: 'ced_amazon_seller_verification'
				},
				success: function (response) {

					jQuery('#wpbody-content .ced_amazon_overlay').remove();

					if (response.success) {

						if (response.data.status) {
							customSwal({
								title: 'Seller Verification Successful',
								text: 'Seller has been verified successful',
								icon: 'success',
							},() => {

								let url = window.location.href;
				
								url = removeParam("data", url);
								url = removeParam( 'app_code', url );
								url = removeParam( 'success', url );
								url = removeParam( 'marketplace', url );
								url = removeParam( 'state', url );

								console.log(url);
								//return;
								//window.location.replace(url + '&user_id=' + response.data.data.user_id + '&seller_id=' + response.data.data.ced_mp_name + '|' + response.data.data.seller_id);
							
								window.location.replace(url + '&part=wizard-options&user_id=' + response.data.data.user_id + '&seller_id=' + response.data.data.ced_mp_name + '|' + response.data.data.seller_id )
							
							
							
							})

						} else {
							customSwal({
								title: 'Seller Verification Failed',
								text: 'Unable to verify you. Please try again',
								icon: 'error',
							}, () => {return; })
						}
					} else {
						customSwal({
							title: 'Seller Verification Failed',
							text: 'Unable to verify you. Please try again',
							icon: 'error',
						}, () => {return; })
					}
				}
			}
		);

	}
	);

	function removeParam(key, sourceURL) {
		var rtn         = sourceURL.split("?")[0],
			param,
			params_arr  = [],
			queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
		if (queryString !== "") {
			params_arr = queryString.split("&");
			for (var i = params_arr.length - 1; i >= 0; i -= 1) {
				param = params_arr[i].split("=")[0];
				if (param === key) {
					params_arr.splice(i, 1);
				}
			}
			if (params_arr.length) {
				rtn = rtn + "?" + params_arr.join("&");
			}
		}
		return rtn;
	}


	$(document).on('click', '.add-new-template-btn', function (e) {

		let woo_used_categories = $(this).attr('data-woo-used-cat');
		let woo_all_categories  = $(this).attr('data-woo-all-cat');

		woo_used_categories = JSON.parse(woo_used_categories);
		woo_all_categories  = JSON.parse(woo_all_categories);

		if (woo_all_categories.sort().join(',') === woo_used_categories.sort().join(',')) {

			customSwal({
				title: 'Woocommerce Category',
				text: 'All existing woocommerce category are already mapped. Please create new woocommerce category or remove some woocommerce category from existed mapped profiles.',
				icon: 'error',
			}, () => {return; } )
		} else {
			if ('URLSearchParams' in window) {
				var searchParams = new URLSearchParams(queryString);
				searchParams.set("section", "add-new-template");
				window.location.search = searchParams.toString();

			}
		}
	})

	$(document).on('click', '#ced_amazon_reset_product_page', function (e) {

		e.preventDefault();
		var searchParams = new URLSearchParams(queryString);
		searchParams.delete('searchType');
		searchParams.delete('searchQuery');
		searchParams.delete('searchCriteria');

		window.location.search = searchParams.toString();

	})


	$(document).on(
		'click',
		'.ced_amazon_add_missing_fields',
		function (e) {

			e.preventDefault();
			e.stopPropagation();

			let title = $('.ced_amazon_add_missing_field_title').val();
			let slug  = $('.ced_amazon_add_missing_field_slug').val();
			title     = title.trim();

			let existing_custom_item_aspects_json;

			let existing_custom_item_aspects_string = $('.ced_amazon_add_missing_fields_heading').attr('data-attr');

			existing_custom_item_aspects_string = existing_custom_item_aspects_string.replaceAll("+", " ");
			if (existing_custom_item_aspects_string !== '') {

				existing_custom_item_aspects_json = JSON.parse(existing_custom_item_aspects_string);

				if (existing_custom_item_aspects_json.hasOwnProperty(slug) || Object.values(existing_custom_item_aspects_json).indexOf(title) > -1) {
					let html = '<tr class="ced_amazon_add_missing_field_error" ><td colspan="3">Please enter another custom title or slug. Same custom title or slug has already been used.</td></tr>'
					$('.ced_amazon_add_missing_field_row').before(html);

					setTimeout(() => {
						$('.ced_amazon_add_missing_field_error').remove();
					}, 3000)
					return;
				}

			}

			if (title.length <= 0 || slug.length <= 0) {
				let html = '<tr class="ced_amazon_add_missing_field_error" ><td colspan="3">Please enter additional field title and slug.</td></tr>'
				$('.ced_amazon_add_missing_field_row').before(html);

				setTimeout(() => {
					$('.ced_amazon_add_missing_field_error').remove();
				}, 3000)

			} else {

				if (existing_custom_item_aspects_string == '') {
					existing_custom_item_aspects_json       = {};
					existing_custom_item_aspects_json[slug] = title;
				} else {
					existing_custom_item_aspects_json       = JSON.parse(existing_custom_item_aspects_string);
					existing_custom_item_aspects_json[slug] = title;
				}

				$('.ced_amazon_add_missing_fields_heading').attr('data-attr', JSON.stringify(existing_custom_item_aspects_json));

				let primary_cat   = $('#ced_amazon_primary_category_selection').val();
				let secondary_cat = $('#ced_amazon_secondary_category_selection').val();

				jQuery('#wpbody-content').append(amazon_loader_overlay);

				jQuery.ajax(
					{
						type: 'post',
						url: ajaxUrl,
						dataType: 'html',
						data: {
							user_id: user_id,
							ajax_nonce: ajaxNonce,
							title: title,
							slug: slug,
							primary_cat: primary_cat,
							secondary_cat: secondary_cat,
							action: 'ced_amazon_add_missing_field_row'
						},
						success: function (response) {

							response = JSON.parse(response);
							jQuery('#wpbody-content .ced_amazon_overlay').remove();
							$('.ced_amazon_add_missing_fields_heading').after(response.data);
							$('.ced_amazon_add_missing_field_title').val('');
							$('.ced_amazon_add_missing_field_slug').val('');
							$('.custom_category_attributes_select').selectWoo();
							remove_custom_notice();

						}
					}
				)

			}

		}
	)

	$(document).on('click', '.ced_amazon_remove_custom_row', function (e) {
		e.preventDefault();
		$(this).parents('tr').remove();
	})



	$(document).on('change', '.ced_amazon_change_acc', function (e) {

		let href = $('select[name="ced_amazon_change_acc"] :selected').attr('data-href');
		window.location.href = href;
		
	})

	$(document).ready( function(){

		let page    = urlParams.get('page');
		let section = urlParams.get('section') ? urlParams.get('section') : '';
		
		if ( page == 'ced_amazon' && section !== '' && section !== 'setup-amazon' ) {
			jQuery("#wpbody-content").addClass("ced-amz-not-setup");
		}

		createTooltip();
	})


	function createTooltip(){
		let allDescription = document.querySelectorAll('.fa-info-circle');
		if ( allDescription.length > 0 ) {

			[...allDescription].map(e =>{
				
				tippy( e, {
					content: $(e).attr('data-tooltip-content'),
				});
			})

		
		}
	}


	// View feed response in feed table via ajax using modal
	jQuery(document).on('click', '.feed-response', function (e) {

		jQuery('#wpbody-content').append(amazon_loader_overlay);
		jQuery( '.feed-response-modal' ).html( '' );

		let feed_id   = $(this).attr("data-attr");
		let seller_id = urlParams.get('seller_id') ? urlParams.get('seller_id') : '';
		if ( seller_id == '' ) {
			console.log('Seller Id is missing!');
			return false;
		}

		jQuery.ajax(
			{
				type: 'post',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					feed_id: feed_id,
					seller_id: seller_id,
					action: 'ced_amazon_view_feed_response'
				},
				success: function (response) {
					jQuery('#wpbody-content .ced_amazon_overlay').remove();
					
					jQuery( '.feed-response-modal' ).append( response.data );
					jQuery( '#feedViewModal' ).modal( 'show' );
				}
			}
		);

	});


	// Load amazon order details with all relevant amazon data 
	jQuery(document).on('click', '.amazon-order-detail', function (e) {

		jQuery('#wpbody-content').append(amazon_loader_overlay);
		jQuery( '.amazon-order-modal' ).html( '' );

		let order_id  = $(this).attr("data-order-id");
		let seller_id = urlParams.get('seller_id') ? urlParams.get('seller_id') : '';
		let user_id   = urlParams.get('user_id') ? urlParams.get('user_id') : '';
		if ( seller_id == '' ) {
			
			jQuery( '.amazon-order-modal' ).html( '<b>Seller id is missing from url!</b>' );
			jQuery('#wpbody-content .ced_amazon_overlay').remove();
			return false;
		}
		if ( user_id == '' ) {
			
			jQuery( '.amazon-order-modal' ).html( '<b>User id is missing from url!</b>' );
			jQuery('#wpbody-content .ced_amazon_overlay').remove();
			return false;
		}

		jQuery.ajax(
			{
				type: 'post',
				url: ajaxUrl,
				data: {
					ajax_nonce: ajaxNonce,
					order_id: order_id,
					seller_id: seller_id,
					user_id: user_id,
					action: 'ced_amazon_order_detail_amazon_data'
				},
				success: function (response) {
					jQuery('#wpbody-content .ced_amazon_overlay').remove();
					
					jQuery( '.amazon-order-modal' ).append( response.data );
					jQuery( '#orderDetailModal' ).modal( 'show' );
				}
			}
		);

	});

	$(document).ready(function () {
		createTooltip();
		let section = urlParams.get('section') 
		if ( section == 'profiles-view') {
			let allDescription = document.querySelectorAll('.ced_amazon_upload_image_button');
			if (allDescription.length > 0) {

				[...allDescription].map(e => {

					tippy(e, {
						content: $(e).attr('data-tooltip-content'),
						allowHTML : true,
						placement: "bottom"
					});
				})


			} 
		}
		
		if ( section == 'products-view') {
			let allDescription = document.querySelectorAll('.ced_amazon_feed_tooltip');
			if (allDescription.length > 0) {

				[...allDescription].map(e => {

					tippy(e, {
						content: $(e).attr('data-tooltip-content'),
						allowHTML : true,
						placement: "top"
					});
				})


			} 
		}
		
	})


	var custom_uploader;

	$(document).on('click', '.ced_amazon_upload_image_button', function (e) {
		e.preventDefault();

		let woo_used_categories = $(this).attr('data-woo-used-cat');
		let woo_all_categories  = $(this).attr('data-woo-all-cat');

		woo_used_categories = JSON.parse(woo_used_categories);
		woo_all_categories  = JSON.parse(woo_all_categories);


		if (woo_all_categories.sort().join(',') === woo_used_categories.sort().join(',')) {
			
			customSwal({
				title: 'Woocommerce Category',
				text: 'All existing woocommerce category are already mapped. Please create new woocommerce category or remove some woocommerce category from existed mapped profiles.',
				icon: 'error',
			}, () => { return; } )
			return;

		} else { 

			var $upload_button = $(this);

			// Extend the wp.media object
			custom_uploader = wp.media.frames.file_frame = wp.media({
				title: 'Choose File',
				button: {
					text: 'Choose File'
				},
				multiple: false
			});

			//When a file is selected, grab the URL and set it as the text field's value
			custom_uploader.on('select', function () {
				var attachment = custom_uploader.state().get('selection').first().toJSON();

				let obj, fileName, fileUrl;
				if (attachment.hasOwnProperty('filename') && attachment.filename.length > 0) {

					fileName            = attachment.filename;
					fileUrl             = attachment.url;
					const filenameArray = fileName.split(".");
					let ext             = filenameArray[filenameArray.length - 1];

					if (ext == 'xls' || ext == 'xlsm') {
						obj = { status: true, title: 'File Uploaded', text: 'Product template has been uploaded.', icon: 'success' };
					} else {
						obj = { status: false, title: 'File Uploaded', text: 'Product template upload has been failed. Invalid file extension or type.', icon: 'error' };
					}

				} else {
					obj = { status: false, title: 'Select File', text: 'Please select a file to upload.', icon: 'error' };

				}

				// swal(obj);
				customSwal(obj, () => {return; });

				if (obj.status) {
					jQuery( '#wpbody-content' ).append( amazon_loader_overlay );

					jQuery.ajax({
						type: 'post',
						url: ajaxUrl,
						dataType: 'html',
						data: {
							user_id: user_id,
							seller_id: urlParams.get('seller_id'),
							ajax_nonce: ajaxNonce,
							fileUrl: fileUrl,
							fileName: fileName,
							action: 'ced_amazon_prepare_template'
						},
						success: function (response) {

							response = JSON.parse(response);
							jQuery('#wpbody-content .ced_amazon_overlay').remove();
							$('.update_template_body').append(response.data);
							$('.ced_amazon_add_missing_field_title').val('');
							$('.ced_amazon_add_missing_field_slug').val('');
							$('.custom_category_attributes_select').selectWoo({ "width": "400" });
							$('#optionalFields').selectWoo();
							remove_custom_notice();
							$('#TemplateModal').show();
							//$( '#TemplateModal' ).animate({display: "block"}, 'slow' ,'swing' );
							createTooltip();

						},
						error: function (error) {
							jQuery('#wpbody-content .ced_amazon_overlay').remove();
						}
					}
					)
				}

			});

			//Open the uploader dialog
			custom_uploader.open();

		}	

	});


	jQuery(document).on( 'click', '.close_template_modal', function(e){
		
		$("#TemplateModal").fadeOut("slow", () =>{
			$( '#TemplateModal' ).find('tbody').find('.categoryAttributes').remove();
		});

	})
	
	
	function escapeBrackets(str) {
		// Use regular expression to find and escape brackets
		return str.replace(/[(){}\[\]]/g, '\\$&');
	}
	
	
	
	jQuery(document).on( 'click', '.ced_plan_cancel_button', function (e) {

		e.preventDefault();
		jQuery('#wpbody-content').append(amazon_loader_overlay);

		var contract_id = jQuery(this).attr('data-contract_id');

		if ( contract_id == undefined || contract_id == null || contract_id.length == 0 ) {

			customSwal({
				title: 'Contract ID',
				text: 'Unable to get contract ID',
				icon: 'error',

			}, () => {return; });
			jQuery('#wpbody-content .ced_amazon_overlay').remove();

			return;
		}

		jQuery.ajax({
			type: 'post',
			url: ajaxUrl,
			data: {
				ajax_nonce: ajaxNonce,
				contract_id: contract_id,
				action: 'ced_amazon_cancel_plan'
			},

			success: function (response) {

				let parsed_response = JSON.parse(response);
				
				if ( parsed_response.status ) {
					let confirmation_url = parsed_response.confirmation_url;
					
					window.location.href = confirmation_url;
				} else {
					
					customSwal({
						title: 'Plan Error',
						text: 'Currently we are unable to process your request. Please try again.',
						icon: 'error',
					}, () => {return; })

				}
			}
		});

	});


	function customSwal(swalObj = {}, callback , time = 5000 ){

		window.scrollTo(0, 0);
		var notice = "";

		let title = swalObj.title ? swalObj.title : '';
		let text  = swalObj.text  ? swalObj.text : '';
		
		if ( swalObj.icon == "success") {
			
			notice += "<div class='notice notice-success'><p> <b>"+ title + "</b>. "+ text +" </p></div>";
			$(".success-admin-notices").prepend(notice);
			
		} else if ( swalObj.icon == 'error') {
			
			notice += "<div class='notice notice-error'><p> <b>"+ title + "</b>. "+ text +" </p></div>";
			$(".success-admin-notices").prepend(notice);
		} else if ( swalObj.icon == 'warning') {
			
			notice += "<div class='notice notice-warning'><p> <b>"+ title + "</b>. "+ text +" </p></div>";
			$(".success-admin-notices").prepend(notice);
			
		} 

		if ( $(".success-admin-notices").length == 0 ) {
			$("#wpbody-content").prepend(notice);
		}

		setTimeout( () => {
			$("#wpbody-content").find('.notice').remove();
			callback();
		}, time)


	}	


	
	jQuery(document).on( 'click', '.ced_amazon_faq_collection_question', function (e) {

		var elementsWithAttribute = $(".ced_amazon_faq_collection_answer").filter(function() {
			return $(this).attr("style") === "display: block;";
		});
		  
		elementsWithAttribute.slideToggle(500);
		$(this).next().slideToggle(500);
	})


	

})(jQuery);
