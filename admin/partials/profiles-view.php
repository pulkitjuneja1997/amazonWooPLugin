<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

$file = CED_AMAZON_DIRPATH . 'admin/partials/header.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Ced_Amazon_Profile_Table extends WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Amazon Template', 'amazon-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'Amazon Templates', 'amazon-integration-for-woocommerce' ), // plural name of the listed records
				'ajax'     => false, // does this table support ajax?
			)
		);
	}
	 /**
	  *
	  * Function for preparing profile data to be displayed column
	  */
	public function prepare_items() {

		global $wpdb;

		/**
		 * Function to get listing per page
		 * 
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page = apply_filters( 'ced_amazon_profile_list_per_page', 10 );
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::ced_amazon_get_profiles( $per_page, $current_page );

		$count = self::get_count();

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( ! $this->current_action() ) {
			$this->items = self::ced_amazon_get_profiles( $per_page, $current_page );
			$this->renderHTML();
		} else {
			$this->process_bulk_action();
		}
	}
	 /**
	  *
	  * Function for status column
	  */
	public function ced_amazon_get_profiles( $per_page = 1, $page_number = 1 ) {

		global $wpdb;
		$offset    = ( $page_number - 1 ) * $per_page;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s ORDER BY `id` DESC LIMIT %d OFFSET %d", $seller_id, $per_page, $offset ), 'ARRAY_A' );
		return $result;
	}

	 /*
	 *
	 * Function to count number of responses in result
	 *
	 */
	public function get_count() {

		global $wpdb;
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		$amazon_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s", $seller_id ), 'ARRAY_A' );
		if ( ! empty( $amazon_profiles ) ) {
			return count( $amazon_profiles );
		} else {
			return 0;
		}

	}

	/*
	*
	* Text displayed when no customer data is available
	*
	*/

	public function no_items() {
		esc_attr_e( 'No Templates Created.', 'amazon-integration-for-woocommerce' );
	}

	 /**
	  * Render the bulk edit checkbox
	  *
	  * @param array $item
	  *
	  * @return string
	  */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="amazon_profile_ids[]" value="%s" class="amazon_profile_ids"/>',
			$item['id']
		);
	}


	 /**
	  * Function for name column
	  *
	  * @param array $item an array of DB data
	  *
	  * @return string
	  */
	public function column_profile_name( $item ) {
		echo '<strong>' . esc_attr( $item['profile_name'] ) . '</strong>';
	}

	 /**
	  *
	  * Function for profile status column
	  */
	public function column_profile_status( $item ) {
		if ( isset( $item['profile_status'] ) && ! empty( $item['profile_status'] ) ) {

			if ( 'inactive' == $item['profile_status'] ) {
				return 'InActive';
			} else {
				return 'Active';
			}
		} else {
			return 'Active';
		}
	}

	 /**
	  *
	  * Function for category column
	  */
	public function column_woo_categories( $item ) {

		$woo_categories = json_decode( $item['wocoommerce_category'], true );

		if ( ! empty( $woo_categories ) ) {
			foreach ( $woo_categories as $key => $value ) {
				$term = get_term_by( 'id', $value, 'product_cat' );
				if ( isset( $term ) && ! empty( $term ) ) {
					echo '<span class="' . esc_attr( $item['id'] ) . '" id="' . esc_attr( $term->term_id ) . '">' . esc_attr( $term->name ) . ' <i class="fa fa-times profile-del"></i></span>';
					if ( $key + 1 < count( $woo_categories ) ) {
						echo '<br>';
					}
				}
			}
		} else {
			echo '-';
		}
	}

	 /**
	  *  Associative array of columns
	  *
	  * @return array
	  */
	public function get_columns() {
		$columns = array(
			'cb'              => '<input type="checkbox" />',
			'profile_name'    => __( 'Template Name', 'amazon-integration-for-woocommerce' ),
			'profile_status'  => __( 'Template Status', 'amazon-integration-for-woocommerce' ),
			'woo_categories'  => __( 'Mapped WooCommerce Categories', 'amazon-integration-for-woocommerce' ),
			'profile_actions' => __( 'Template Actions', 'amazon-integration-for-woocommerce' ),
		);

		/**
		 * Function to alter profile table columns
		 * 
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_amazon_alter_profiles_table_columns', $columns );
		return $columns;
	}


	 /**
	  * Columns to make sortable.
	  *
	  * @return array
	  */
	public function get_sortable_columns() {
		$sortable_columns = array();
		return $sortable_columns;
	}

	/**
	 *
	 * Render bulk actions
	 */

	protected function bulk_actions( $which = '' ) {
		if ( 'top' == $which ) :
			if ( is_null( $this->_actions ) ) {
				$this->_actions = $this->get_bulk_actions();
				/**
				 * Filters the list table Bulk Actions drop-down.
				 *
				 * The dynamic portion of the hook name, `$this->screen->id`, refers
				 * to the ID of the current screen, usually a string.
				 *
				 * This filter can currently only be used to remove bulk actions.
				 *
				 * @since 3.5.0
				 *
				 * @param array $actions An array of the available bulk actions.
				 */
				$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
				$two            = '';
			} else {
				$two = '2';
			}

			if ( empty( $this->_actions ) ) {
				return;
			}

			echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_attr( 'Select bulk action' ) . '</label>';
			echo '<select name="action' . esc_attr( $two ) . '" class="bulk-action-selector ">';
			echo '<option value="-1">' . esc_attr( 'Bulk Actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => 'ced_amazon_profile_bulk_operation' ) );
			echo "\n";
		endif;
	}
	
	 /**
	  * Returns an associative array containing the bulk action
	  *
	  * @return array
	  */
	public function get_bulk_actions() {
		$actions = array(
			'bulk-delete' => __( 'Delete', 'amazon-integration-for-woocommerce' ),
		);
		return $actions;
	}

	 /**
	  * Function to get changes in html
	  */
	public function renderHTML() {

		if ( isset( $_POST['ced_amazon_profile_edit'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_profile_edit'] ), 'ced_amazon_profile_edit_page_nonce' ) ) {
			if ( isset( $_POST['add_meta_keys'] ) || isset( $_POST['ced_amazon_profile_save_button'] ) ) {
		
				$sanitized_array     = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
				$amazon_profile_data = isset( $sanitized_array['ced_amazon_profile_data'] ) ? ( $sanitized_array['ced_amazon_profile_data'] ) : array();
		
				$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : ''; 
				
				$profileDetails = array(
					'profile_name'         => $amazon_profile_data['profile_name'],
					'primary_category'     => isset( $amazon_profile_data['primary_category'] ) ? $amazon_profile_data['primary_category'] : '',
					'secondary_category'   => isset( $amazon_profile_data['secondary_category'] ) ?  $amazon_profile_data['secondary_category'] : '',
					'browse_nodes'         => isset( $amazon_profile_data['browse_nodes'] ) ? $amazon_profile_data['browse_nodes'] : '',
					'wocoommerce_category' => isset( $amazon_profile_data['wocoommerce_category'] ) ? $amazon_profile_data['wocoommerce_category'] : '',
					'template_type' => isset( $amazon_profile_data['template_type'] ) ? $amazon_profile_data['template_type'] : '',
					'file_url'      => isset( $amazon_profile_data['file_url'] ) ? $amazon_profile_data['file_url'] : '',
				);
		
				$profileDetails['category_attributes_structure'] = json_encode( $amazon_profile_data['ref_attribute_list'] );
		
				unset( $amazon_profile_data['profile_name'] );
				unset( $amazon_profile_data['primary_category'] );
				unset( $amazon_profile_data['secondary_category'] );
				unset( $amazon_profile_data['browse_nodes'] );
				unset( $amazon_profile_data['ref_attribute_list'] );
				unset( $amazon_profile_data['wocoommerce_category'] );
				unset( $amazon_profile_data['template_type'] );
				unset( $amazon_profile_data['file_url'] );
		
				$profileDetails['category_attributes_data'] = json_encode( $amazon_profile_data );
		
				/* -------------------------------------------------------- Saving Missing Fields Starts ----------------------------------------------------- */
		
				//die('opppppppp');
		
				$ced_amzon_configuration_validated = get_option( 'ced_amzon_configuration_validated', array() );

				$userData    = $ced_amzon_configuration_validated[ $seller_id ];
				$userCountry = $userData['ced_mp_name'];
		
				global $wpdb;
				$tableName = $wpdb->prefix . 'ced_amazon_profiles';

				$wpdb->insert(
					$tableName,
					array(
						'profile_name'                  => $profileDetails['profile_name'],
						'primary_category'              => $profileDetails['primary_category'],
						'secondary_category'            => $profileDetails['secondary_category'],
						'category_attributes_response'  => '',
						'wocoommerce_category'          => json_encode( $profileDetails['wocoommerce_category'] ),
						'category_attributes_structure' => $profileDetails['category_attributes_structure'],
						'browse_nodes'                  => $profileDetails['browse_nodes'],
						'category_attributes_data'      => $profileDetails['category_attributes_data'],
						'seller_id'                     => $seller_id,
						'file_url'                      => $profileDetails['file_url'],
						'template_type'                 => $profileDetails['template_type']
					),
					array( '%s' )
				);
		
				$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
				$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
				$seller_id = str_replace( '|', '%7C', $seller_id );
				wp_redirect( get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=profiles-view&user_id=' . $user_id . '&seller_id=' . $seller_id );
		
			}
		}

		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		global $wpdb;
		$tableName            = $wpdb->prefix . 'ced_amazon_profiles';
		$amazon_profiles      = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s ", $seller_id ), 'ARRAY_A' );
		$amazon_wooCategories = array();

		if ( ! empty( $amazon_profiles ) ) {
			foreach ( $amazon_profiles as $amazon_profile ) {

				$wooCatIds = json_decode( $amazon_profile['wocoommerce_category'], true );
				if ( !empty( $wooCatIds ) ) {
					foreach ( $wooCatIds as $wooCatId ) {
						$amazon_wooCategories[] = $wooCatId;
					}
				}
				
			}
		}

		$woo_store_categories = ced_amazon_get_categories_hierarchical(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		?>
		<div class="ced-amazon-v2-header">
			 <div class="ced-amazon-v2-logo">
				
			 </div>
			 <div class="ced-amazon-v2-header-content">
				 <div class="ced-amazon-v2-title">
					 <h1>Amazon Templates</h1>
				 </div>
				 <div class="ced-amazon-v2-actions">
					<?php

						global $wpdb;
						$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
						$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

						$wooUsedCategoriesArray = array();
						$wooUsedCategories      = $wpdb->get_results( $wpdb->prepare( "SELECT `wocoommerce_category` FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s" , $seller_id ), 'ARRAY_A' );
						
					if ( !empty( $wooUsedCategories ) ) {
						foreach ( $wooUsedCategories as $wooUsedCategory ) {
							$decoded_woo_categories =  json_decode( $wooUsedCategory['wocoommerce_category'], true); 
							if ( !empty( $decoded_woo_categories ) ) {
								foreach ( $decoded_woo_categories as $decoded_woo_category ) {

									settype($decoded_woo_category, 'integer');
									$wooUsedCategoriesArray[] = $decoded_woo_category;
								}
							}
								
						}
					}

						$wooUsedCategoriesArray = array_unique($wooUsedCategoriesArray);

						$allWooCategories = array();
						$orderby          = 'id';
						$order            = 'asc';
						$hide_empty       = false ;
						$cat_args         = array(
							'orderby'    => $orderby,
							'order'      => $order,
							'hide_empty' => $hide_empty,
						);
						$categories       = get_terms( 'product_cat', $cat_args ) ; 

						if ( !empty( $categories  ) ) { 
							foreach ($categories as $category ) {
								$cat                = json_decode( json_encode($category), true );
								$allWooCategories[] = $cat['term_id'];
							}

						}


						?>
						<!-- <input type="button" class="ced_amazon_upload_image_button ced-amazon-v2-btn" value="Upload Missing Fields"> -->
					<?php
					if ( !empty($seller_id) ) {
						?>
						<button class="ced-amazon-v2-btn add-new-template-btn" data-woo-used-cat="<?php print_r( htmlspecialchars( json_encode( $wooUsedCategoriesArray )) ); ?>" data-woo-all-cat = "<?php print_r( htmlspecialchars( json_encode( $allWooCategories )) ) ; ?>"> Add New Template </button>
					   
						<button type="button" class="ced_amazon_upload_image_button ced-amazon-v2-btn" data-woo-used-cat="<?php echo esc_attr( htmlspecialchars( json_encode( $wooUsedCategoriesArray ) ) ); ?>" data-woo-all-cat = "<?php echo esc_attr( htmlspecialchars( json_encode( $allWooCategories ) ) ); ?>" data-tooltip-content="Here you can upload your own XLSM feed template, generated on Seller Central.
						 <p> </p>
						 <b> To generate your own feed templates, log in to Seller Central, visit Inventory / Add Products via Upload and open the 'Download an Inventory file' tab.</b>"> 
							Upload Template </button> 
					<?php
					}
					?>
					<a class="ced-amazon-v2-btn" href="https://docs.cedcommerce.com/woocommerce/amazon-integration-woocommerce/?section=manage-profiles-8" target="_blank">
					 Documentation</a>
				</div>

			</div>
		</div>


		<div class="ced-amazon-bootstrap-wrapper">
			<div class="container" >
		
				<div class="modal" id="TemplateModal" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="false">
					<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document" style="width: 200%;" >
						<div class="modal-content" style="margin-top: 1%; width: 42%; margin-left: 7%;" >
							<div class="modal-header">
								<h5 class="modal-title" id="exampleModalLabel">Add Template</h5>
								<button type="button" class="close_template_modal" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" style="font-size: 28px !important;" >
								   <span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body">
								
								<form action="" method="post">
	
									<div class="ced_amazon_profile_details_wrapper">
										<div class="ced_amazon_profile_details_fields">
											
											<table class="update_template_body">
												<thead>
													<?php

												  
													// $configuration_validated_array = get_option( 'ced_amzon_configuration_validated', array() );

													// $sub_app_id = 0;
													// if( ! empty( $configuration_validated_array ) && isset( $configuration_validated_array[$seller_id] ) ){
													// 	$sub_app_id = isset( $configuration_validated_array[$seller_id]['sub_app_id'] ) ? $configuration_validated_array[$seller_id]['sub_app_id'] : 0; 
													// }

													// $amzonCurlRequest = CED_AMAZON_DIRPATH . 'admin/amazon/lib/ced-amazon-curl-request.php';
													// $shopId           = get_option( 'ced_amazon_sellernext_shop_id', true );
													// if ( file_exists( $amzonCurlRequest ) ) {
													// 	require_once $amzonCurlRequest;
													// 	$amzonCurlRequestInstance = new Ced_Amazon_Curl_Request();
													// 	$amazonCategoryList       = $amzonCurlRequestInstance->ced_amazon_get_category( 'webapi/rest/v1/category/?shop_id=' . $shopId . '&sAppId=' . $sub_app_id );

													// }

													?>
												</thead>
												<tbody>
													<tr>
														<th colspan="3" class="" style="text-align:left;margin:0;">
															<label style="font-size: 1.25rem;color: #6574cd;">Profile Details</label>
														</th>
													</tr>
													<tr>
														<td>
															<label for="" class="">Profile Name
																<span class="ced_amazon_wal_required">[Required]</span>
															</label>
														</td>
														<td>
															<input id="ced_amazon_profile_name" value="" type="text" name="ced_amazon_profile_data[profile_name]" required="">
														</td>
													</tr>

													<tr class="" id="amazon_category_reference"> </tr>

													<tr> 
														<th colspan="3" class="profileSectionHeading" >
															<label style="font-size: 1.25rem;color: #6574cd;" ><?php esc_attr_e( 'Woocommerce Category', 'amazon-integration-for-woocommerce' ); ?></label>
														</th>
													</tr>

													<tr> 
														<td>
															<label for="id_label_single">
																Select WooCommerce categories to map
															</label>	

														</td>

														<td>  
															<select style="width:90%;" class="select2 wooCategories" name="ced_amazon_profile_data[wocoommerce_category][]"  multiple="multiple" required>
																		<option>--Select--</option>

																		<?php
																		
																			ced_amazon_nestdiv( $woo_store_categories, array(), 0, $amazon_wooCategories );

																		?>
															</select>
														</td>		
													</tr>	

												</tbody>
											</table>
											
										</div>
									</div>
									
								

									<div class="modal-footer">
										<?php wp_nonce_field( 'ced_amazon_profile_edit_page_nonce', 'ced_amazon_profile_edit' ); ?>
										<button type="button" class="btn btn-secondary close_template_modal" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Close</button>
										<button class="ced-amazon-v2-btn save_profile_button" name="ced_amazon_profile_save_button" ><?php esc_attr_e( 'Save Profile Data', 'amazon-integration-for-woocommerce' ); ?></button>
			
									</div>
								</form>		
							</div>
						</div>
					</div>
				</div>   

			</div>
		</div>

		

		<?php
		if ( ! session_id() ) {
			session_start();
		}

		?>
		 <div id="post-body" class="metabox-holder columns-2">
			 <div id="">
				 <div class="meta-box-sortables ui-sortable">
					 <form method="post">
					   <?php
						wp_nonce_field( 'amazon_profile_view', 'amazon_profile_view_actions' );
						$this->display();
						?>
					 </form>
				 </div>
			 </div>
			 <div class="clear"></div>
		 </div>
						<?php
	}
	 /**
	  *
	  * Function for getting current status
	  */
	public function current_action() {
		if ( isset( $_GET['panel'] ) ) {
			$action = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
			return $action;
		} elseif ( isset( $_POST['action'] ) ) {
			if ( ! isset( $_POST['amazon_profile_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_profile_view_actions'] ) ), 'amazon_profile_view' ) ) {
				return;
			}
			$action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : '';
			return $action;
		}
	}

	public function column_profile_actions( $item ) {

		$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';

		echo '<a class="ced-amazon-v2-btn profile-edit" target="_blank" href="' . esc_attr( get_admin_url() ) . 'admin.php?page=sales_channel&channel=amazon&section=add-new-template&template_id=' . esc_attr( $item['id'] ) . '&template_type=' . esc_attr( $item['template_type'] ) . '&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) . '">Edit</a>';
	}

	 /**
	  *
	  * Function for processing bulk actions
	  */
	public function process_bulk_action() {
		$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );

		if ( ! session_id() ) {
			session_start();
		}
		wp_nonce_field( 'ced_amazon_profiles_view_page_nonce', 'ced_amazon_profiles_view_nonce' );
		if ( 'bulk-delete' === $this->current_action() || ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) ) {
			if ( ! isset( $_POST['amazon_profile_view_actions'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['amazon_profile_view_actions'] ) ), 'amazon_profile_view' ) ) {
				return;
			}
			$profileIds = isset( $sanitized_array['amazon_profile_ids'] ) ? $sanitized_array['amazon_profile_ids'] : array();
			
			if ( is_array( $profileIds ) && ! empty( $profileIds ) ) {

				global $wpdb;
				$user_id   = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : '';
				$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( wp_unslash( $_GET['seller_id'] ) ) : '';

				foreach ( $profileIds as $index => $pid ) {

					$product_ids_assigned = get_option( 'ced_amazon_product_ids_in_profile_' . $pid, array() );
					foreach ( $product_ids_assigned as $index => $ppid ) {
						delete_post_meta( $ppid, 'ced_amazon_profile_assigned' . $user_id );
					}

					$term_id = $wpdb->get_results( $wpdb->prepare( "SELECT `wocoommerce_category` FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` = %s ", $pid ), 'ARRAY_A' );
					$term_id = json_decode( $term_id[0]['wocoommerce_category'], true );
					foreach ( $term_id as $key => $value ) {
						delete_term_meta( $value, 'ced_amazon_profile_created_' . $user_id );
						delete_term_meta( $value, 'ced_amazon_profile_id_' . $user_id );
						delete_term_meta( $value, 'ced_amazon_mapped_category_' . $user_id );
					}
				}
				foreach ( $profileIds as $id ) {
					$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ced_amazon_profiles WHERE `id` IN (%s)", $id ) );
				}

				header( 'Location: ' . get_admin_url() . 'admin.php?page=sales_channel&channel=amazon&section=profiles-view&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) );
				exit();
			}
		} elseif ( isset( $_GET['panel'] ) && 'edit' == $_GET['panel'] ) {

			$file = CED_AMAZON_DIRPATH . 'admin/partials/profile-edit-view.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}
}

	$ced_amazon_profile_obj = new Ced_amazon_Profile_Table();
	$ced_amazon_profile_obj->prepare_items();


?>

<style>

@media (min-width: 576px){

	.ced-amazon-bootstrap-wrapper .modal-dialog-scrollable {
		height: calc(100% - 3.5rem) !important;
		max-width: 200%;
	}
}

.modal-body{
	overflow-x: hidden;
}

.modal {
  z-index: 1050 !important;
}

table input, select{
	line-height: 2 !important;
}

#TemplateModal{
	background: #0a0909ba;
}

.close_template_modal{
	background: transparent;
	border: none;
   
}

</style>

<script>
	
	jQuery(document).ready(function() {
		jQuery('.ced_amazon_select_category').selectWoo();

		jQuery(".wooCategories").selectWoo({
			dropdownPosition: 'below',
			dropdownAutoWidth : true,
			allowClear: true,
			width: 'resolve'
		});
	});


</script>

<style>
	 .ced-amazon-v2-admin-menu{
	   margin-right: 20px !important;
   }
   
   .ced-amazon-v2-header{
		   margin: 10px 20px 20px 2px !important;
   }
</style>
