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

require_once CED_AMAZON_DIRPATH . 'admin/amazon/lib/class-feed-manager.php';

$notices = array();

if ( isset( $_POST['ced_amazon_product_bulk_action_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_bulk_action_nonce'] ), 'ced_amazon_product_bulk_action_page_nonce' ) ) {

	if ( isset( $_POST['doaction'] ) ) {

		$marketplace = 'amazon';
		
		$seller_id = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		
		if ( !empty($seller_id) ) {
			$mplocation_arr = explode('|', $seller_id);
			$mplocation     = isset($mplocation_arr[0]) ? $mplocation_arr[0] : '';
		}
		$product_action = isset( $_POST['action'] ) ? sanitize_text_field( $_POST['action'] ) : -1;

		$sanitized_array = filter_input_array( INPUT_POST, FILTER_SANITIZE_STRING );
		$proIds          = isset( $sanitized_array['amazon_product_ids'] ) ? $sanitized_array['amazon_product_ids'] : array();


		$allset = true;


		if ( empty( $product_action ) || -1 == $product_action ) {
			$allset    = false;
			$message   = __( 'Please select the bulk actions to perform action!', 'ced-amazon' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}
		
		if ( empty( $seller_id ) || '' == $seller_id ) {
			$allset    = false;
			$message   = __( 'Seller id  is missing to perform action!', 'ced-amazon' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( empty( $mplocation ) || '' == $mplocation ) {
			$allset    = false;
			$message   = __( 'Seller location is missing to perform action!', 'ced-amazon' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( empty( $marketplace ) || -1 == $marketplace ) {
			$allset    = false;
			$message   = __( 'Any marketplace is not activated!', 'ced-amazon' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( ! is_array( $proIds ) ) {

			$allset    = false;
			$message   = __( 'Please select products to perform bulk action!', 'ced-amazon' );
			$classes   = 'error is-dismissable';
			$notices[] = array(
				'message' => $message,
				'classes' => $classes,
			);
		}

		if ( $allset ) {

			if ( class_exists( 'Ced_Umb_Amazon_Feed_Manager' ) ) {
				$feed_manager = Ced_Umb_Amazon_Feed_Manager::get_instance();
				$notice       = $feed_manager->process_feed_request( $product_action, $marketplace, $proIds, $mplocation, $seller_id );

				$notice_array = json_decode( $notice, true );
				if ( is_array( $notice_array ) ) {
					$message   = isset( $notice_array['message'] ) ? $notice_array['message'] : '';
					$classes   = isset( $notice_array['classes'] ) ? $notice_array['classes'] : 'error is-dismissable';
					$notices[] = array(
						'message' => $message,
						'classes' => $classes,
					);
				} else {
					$message   = __( 'Unexpected error encountered, please try again!', 'ced-amazon' );
					$classes   = 'error is-dismissable';
					$notices[] = array(
						'message' => $message,
						'classes' => $classes,
					);
				}
			}
		}
	}
}

if ( count( $notices ) ) {
	foreach ( $notices as $notice_array ) {
		$message = isset( $notice_array['message'] ) ? esc_html( $notice_array['message'] ) : '';
		$classes = isset( $notice_array['classes'] ) ? esc_attr( $notice_array['classes'] ) : 'error is-dismissable';
		if ( ! empty( $message ) ) {?>
			<div class="<?php echo esc_attr( $classes ); ?>">
				 <p><?php echo esc_attr( $message ); ?></p>
			</div>
			<?php
		}
	}
	unset( $notices );
}


class AmazonListProducts extends WP_List_Table {

	/**
	 *
	 * Function to construct
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'ced-amazon-product', 'amazon-integration-for-woocommerce' ), // singular name of the listed records
				'plural'   => __( 'ced-amazon-products', 'amazon-integration-for-woocommerce' ), // plural name of the listed records
				'ajax'     => true, // does this table support ajax?
			)
		);

	}

	/**
	 *
	 * Function for preparing data to be displayed
	 */

	public function prepare_items() {

		global $wpdb;

		/**
		 * Function to list order based on per page
		 * 
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since  1.0.0
		 */
		$per_page  = apply_filters( 'ced_amazon_products_per_page', 10 );
		$post_type = 'product';
		$columns   = $this->get_columns();
		$hidden    = array();
		$sortable  = $this->get_sortable_columns();

		// Column headers
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$current_page = $this->get_pagenum();
		if ( 1 < $current_page ) {
			$offset = $per_page * ( $current_page - 1 );
		} else {
			$offset = 0;
		}

		$this->items = self::ced_amazon_get_product_details( $per_page, $current_page, $post_type );
		$count       = self::get_count( $per_page, $current_page );
		//$count       = count( $this->items );

		// Set the pagination
		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => ceil( $count / $per_page ),
			)
		);

		if ( $this->current_action() ) {
			$this->process_bulk_action();
		}
		$this->renderHTML();
	}

	/**
	 *
	 * Function for get product data
	 */
	public function ced_amazon_get_product_details( $per_page = '', $page_number = '', $post_type = '' ) {
		$filterFile = CED_AMAZON_DIRPATH . 'admin/partials/products-filters.php';
		if ( file_exists( $filterFile ) ) {
			require_once $filterFile;
		}

		$instanceOf_FilterClass = new FilterClass();

		$args = $this->GetFilteredData( $per_page, $page_number );

		global $wpdb;
		if ( isset( $args['search_by_sku'] ) ) {
			$search_sku = $args['search_by_sku'];
			$product_id = $wpdb->get_var( $wpdb->prepare( "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key`='_sku' AND `meta_value`=%s LIMIT 1", $search_sku ) );
		}

		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) || isset( $args['s'] ) ) {
			$args = $args;
		} elseif ( ! isset( $args['prodID'] ) && ! isset( $args['search_by_sku'] ) ) {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => $per_page,
				'paged'          => $page_number,
			);
		} elseif ( isset( $args['search_by_sku'] ) ) {
			$args = array(
				'post_type'           => 'product',
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'meta_key'            => '_sku',
				'meta_value'          => $args['search_by_sku'],
				'meta_compare'        => 'LIKE',
			);
		}

		if ( isset( $args['prodID'] ) ) {
			$prod           = new stdClass();
			$prod->ID       = $args['prodID'];
			$product_data[] = $prod;
		} else {
			$loop         = new WP_Query( $args );
			$product_data = $loop->posts;
			if ( empty($product_data) && isset($product_id) && !empty($product_id) ) {
				$parentId     = wp_get_post_parent_id($product_id);
				$args         = array(
					'post_type'           => 'product',
					'post_status'         => 'publish',
					'ignore_sticky_posts' => 1,
					'post__in'            => array($parentId),
				);
				$loop         = new WP_Query( $args );
				$product_data = $loop->posts;
			}
		}

		$woo_categories = get_terms( 'product_cat', array( 'hide_empty' => false ) );
		$woo_products   = array();
		foreach ( $product_data as $key => $value ) {
			$get_product_data = wc_get_product( $value->ID );
			$get_product_data = $get_product_data->get_data();
			if ( ! empty( $get_product_data['category_ids'] ) ) {
				rsort( $get_product_data['category_ids'] );
			}
			$woo_products[ $key ]['category_id']  = isset( $get_product_data['category_ids'] ) ? $get_product_data['category_ids'] : '';
			$woo_products[ $key ]['id']           = $value->ID;
			$woo_products[ $key ]['name']         = $get_product_data['name'];
			$woo_products[ $key ]['stock']        = $get_product_data['stock_quantity'];
			$woo_products[ $key ]['stock_status'] = $get_product_data['stock_status'];
			$woo_products[ $key ]['sku']          = $get_product_data['sku'];
			$woo_products[ $key ]['price']        = $get_product_data['price'];
			$Image_url_id                         = $get_product_data['image_id'];
			$woo_products[ $key ]['image']        = wp_get_attachment_url( $Image_url_id );
			foreach ( $woo_categories as $key1 => $value1 ) {
				if ( isset( $get_product_data['category_ids'] ) ) {
					foreach ( $get_product_data['category_ids'] as $key2 => $prodCat ) {
						if ( $value1->term_id == $prodCat ) {
							$woo_products[ $key ]['category'][] = $value1->name;
						}
					}
				}
			}
		}

		if ( isset( $_POST['ced_amazon_product_filter_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_product_filter_nonce'] ), 'ced_amazon_product_filter_page_nonce' ) ) {
			if ( isset( $_POST['filter_button'] ) ) {
				$woo_products = $instanceOf_FilterClass->ced_amazon_filters_on_products();
			}
		}

		if ( isset( $_POST['ced_amazon_filter_product_nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['ced_amazon_filter_product_nonce'] ), 'ced_amazon_filter_product_action_nonce' ) ) {
			if ( isset( $_POST['ced_amazon_filter_product_button'] ) ) {
				$woo_products = $instanceOf_FilterClass->ced_amazon_product_search_box();
			}
		}

		return $woo_products;

	}

	/**
	 *
	 * Text displayed when no data is available
	 */
	public function no_items() {
		esc_html_e( 'No Products To Show.', 'amazon-integration-for-woocommerce' );
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

	/*
	 * Render the bulk edit checkbox
	 *
	 */
	public function column_cb( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return sprintf(
			'<input type="checkbox" name="amazon_product_ids[]" class="amazon_products_id" value="%s" /></div></div>',
			$item['id']
		);
	}

	/**
	 *
	 * Function for name column
	 */
	public function column_name( $item ) {
		$actions       = array();
		$user_id       = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$url           = get_edit_post_link( $item['id'], '' );
		$actions['id'] = 'ID:' . __( $item['id'] );
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		echo '<b><a class="ced_amazon_prod_name" href="' . esc_attr( $url ) . '" target="_blank">' . esc_attr( $item['name'] ) . '</a></b><br>';
		echo '</div></div>';
		//$actions['modify'] = '<span class="sidebarCollapse" data-prod-id="' . __( $item['id'] ) . '" > ' . __( 'Modify', 'amazon-integration-for-woocommerce' ) . '</span>';

		//Feed actions as per product meta
		$seller_id     = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_id_val = str_replace('|', '_', $seller_id);
		$product_feeds = get_post_meta( $item['id'], 'ced_amazon_feed_actions_' . $seller_id_val, true );
		if ( is_array($product_feeds) && !empty($product_feeds) ) {
			$actions['feed-actions'] = '<span class="product-feed-actions" data-prod-id="' . __( $item['id'] ) . '" > ' . __( 'View feed actions', 'amazon-integration-for-woocommerce' ) . '</span>';
		}

		return $this->row_actions( $actions );
	}

	/**
	 *
	 * Function for profile column
	 */
	public function column_profile( $item ) {

		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		$seller_id = isset( $_GET['seller_id' ] ) ? sanitize_text_field( $_GET['seller_id' ] ) : '';
		$user_id   = isset( $_GET['user_id' ] ) ? sanitize_text_field( $_GET['user_id' ] ) : '';

		global $wpdb;
		$amazon_profiles = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_profiles WHERE `seller_id` = %s", $seller_id ), 'ARRAY_A' );
		$amazon_profiles = isset( $amazon_profiles ) ? $amazon_profiles : array();

		$terms = wp_get_post_terms( $item['id'], 'product_cat',
			array(
				'order'   => '',
				'orderby' => '',
			)
		);

		$terms   = json_decode( json_encode( $terms ), true );
		$cat_ids = array();
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$cat_ids[] = $term['term_id'];
			}
		}

		$profile = '';
		if ( ! empty( $amazon_profiles ) ) {
			foreach ( $amazon_profiles as $key => $value ) {
				$woo_categories = json_decode( $value['wocoommerce_category'], true );
				$woo_categories = isset($woo_categories) ? $woo_categories : array();
				$match_woo_cat  = array_intersect( $woo_categories, $cat_ids );
				if ( is_array( $match_woo_cat ) && ! empty( $match_woo_cat ) ) {
					$template_id = $value['id'];
					$profile     = $value['primary_category'] . ' --> ' . $value['secondary_category'];
				}

				$template_type = isset( $value['template_type'] ) ? $value['template_type'] : '';
			}

			if ( empty( $profile ) ) {
				echo '<div class="ced_amazon_category_not_mapped" > No Profile Assigned </div>';
			} else {   
				echo '<a target="_blank" href="' . esc_attr( get_admin_url() ) . 'admin.php?page=sales_channel&channel=amazon&section=add-new-template&template_id=' . esc_attr( $template_id ) . '&template_type=' . esc_attr( $template_type ) . '&user_id=' . esc_attr( $user_id ) . '&seller_id=' . esc_attr( $seller_id ) . '">' . esc_attr( $profile ) . '</a>' ;
			}
		} else {
			echo 'No Profile Assigned';
		} 

	}
	
	
	
	/**
	 *
	 * Function for stock column
	 */
	public function column_stock( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		if ( 'instock' == $item['stock_status'] ) {
			if ( 0 == $item['stock'] || '0' == $item['stock'] ) {
				return '<b class="stock_alert_instock" >' . esc_attr( 'In Stock', 'amazon-integration-for-woocommerce' ) . '</b>';
			} else {
				return '<b class="stock_alert_instock">In Stock(' . $item['stock'] . ')</b>';
			}
		} else {
			return '<b class="stock_alert_outofstock" >' . esc_attr( 'Out of Stock', 'amazon-integration-for-woocommerce' ) . '</b>';
		}

		echo '</div></div>';

	}
	/**
	 *
	 * Function for category column
	 */
	public function column_category( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		if ( isset( $item['category'] ) ) {
			$allCategories = '';
			foreach ( $item['category'] as $key => $prodCat ) {
				$allCategories .= '<b>-->' . $prodCat . '</b><br>';
			}
			return $allCategories;
		}

		echo '</div></div>';

	}

	/**
	 *
	 * Function for price column
	 */
	public function column_price( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		$currencySymbol = get_woocommerce_currency_symbol();
		return $currencySymbol . '&nbsp<b class="success_upload_on_amazon">' . $item['price'] . '</b>';
		echo '</div></div>';
	}

	/**
	 *
	 * Function for product type column
	 */
	public function column_type( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';

		$product      = wc_get_product( $item['id'] );
		$product_type = $product->get_type();
		return '<b>' . $product_type . '</b>';
		echo '</div></div>';
	}

	/**
	 *
	 * Function for sku column
	 */
	public function column_sku( $item ) {
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return '<b>' . $item['sku'] . '</b>';
		echo '</div></div>';
	}

	/**
	 *
	 * Function for image column
	 */
	public function column_image( $item ) {
		$item_image = ( $item['image'] ) ? $item['image'] : wc_placeholder_img_src('woocommerce_thumbnail');
		echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
		return '<img height="50" width="50" src="' . $item_image . '">';
		echo '</div></div>';
	}

	/**
	 *
	 * Function for status column
	 */
	public function column_status( $item ) {
		$actions             = array();
		$user_id             = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id           = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_loc_arr      = explode( '|', $seller_id );
		$mp_location         = isset( $seller_loc_arr['1'] ) ? $seller_loc_arr['0'] : '';
		$listing_id          = get_post_meta( $item['id'], 'ced_amazon_product_asin_' . $mp_location, true );
		$amazon_catalog_asin = get_post_meta( $item['id'], 'ced_amazon_catalog_asin_' . $mp_location, true );
		if ( ! empty( get_post_meta( $item['id'], 'ced_amazon_alt_prod_description_' . $item['id'] . '_' . $user_id, true ) ) || ! empty( get_post_meta( $item['id'], 'ced_amazon_alt_prod_title_' . $item['id'] . '_' . $user_id, true ) ) ) {
			echo '<button class="px-3 py-1 mr-3 text-white font-semibold bg-blue-500 rounded">Modified</button><br>';

		}
		if ( ! empty( get_post_meta( $item['id'], '_ced_amazon_relist_item_id_' . $user_id, true ) ) ) {
			echo '<button class="px-3 py-1 mr-3 text-white font-semibold bg-blue-500 rounded">Re-Listed</button><br>';
		}

		$saved_amazon_details = get_option( 'ced_amzon_configuration_validated', false );
		if ( isset( $saved_amazon_details[ $seller_id ]['marketplace_url'] ) && ! empty( $saved_amazon_details[ $seller_id ]['marketplace_url'] ) ) {
			// $amazon_url = $saved_amazon_details[$seller_id]['marketplace_url'];
			$view_url_production = $saved_amazon_details[ $seller_id ]['marketplace_url'] . 'dp/' . $listing_id;
			$catalog_asin_url    = $saved_amazon_details[ $seller_id ]['marketplace_url'] . 'dp/' . $amazon_catalog_asin;
		} else {
			$view_url_production = 'https://www.amazon.com/dp/' . $listing_id;
			$catalog_asin_url    = 'https://www.amazon.com/dp/' . $amazon_catalog_asin;
		}

		if ( isset( $listing_id ) && ! empty( $listing_id ) ) {
			$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';

			$view_url_sandbox  = 'https://sandbox.amazon.com/itm/' . $listing_id;
			$mode_of_operation = get_option( 'ced_amazon_mode_of_operation', '' );
			if ( 'sandbox' == $mode_of_operation ) {
				echo '<div class="admin-custom-action-button-outer">';
				echo '<div class="admin-custom-action-show-button-outer">';
				echo '<a target="_blank" href="' . esc_attr( $view_url_sandbox ) . '" type="button" style="background:#5850ec !important;" class="button btn-normal-tt"><span>View on amazon</span></a>';
				echo '</div></div>';
			} elseif ( 'production' == $mode_of_operation ) {
				echo '<div class="admin-custom-action-button-outer">';
				echo '<div class="admin-custom-action-show-button-outer">';
				echo '<a target="_blank" href="' . esc_attr( $view_url_production ) . '" type="button" style="background:#5850ec !important;" class="button btn-normal-tt"><span>View on amazon</span></a>';
				echo '</div></div>';
			} else {
					echo '<div class="admin-custom-action-button-outer">';
					echo '<div class="admin-custom-action-show-button-outer">';
					echo '<a target="_blank" href="' . esc_attr( $view_url_production ) . '" type="button" style="background:#5850ec !important;" class="button btn-normal-tt"><span>View on amazon</span></a>';
				if ( isset( $amazon_catalog_asin ) && ! empty( $amazon_catalog_asin ) ) {
					echo '<br><a target="_blank" href="' . esc_attr( $catalog_asin_url ) . '" type="button" style="background:#198754 !important; margin-top: 5px;" class="button btn-normal-tt"><span>View ASIN</span></a>';
				}
					echo '</div></div>';
			}
		} else {
			echo '<div class="admin-custom-action-button-outer">';
			echo '<div class="admin-custom-action-show-button-outer">';
			echo '<button type="button" class="button btn-normal-tt"><span>Not Uploaded</span></button>';
			if ( isset( $amazon_catalog_asin ) && ! empty( $amazon_catalog_asin ) ) {
				echo '<a target="_blank" href="' . esc_attr( $catalog_asin_url ) . '" type="button" style="background:#198754 !important; margin-top: 5px;" class="button btn-normal-tt"><span>View ASIN</span></a>';
			}
			echo '</div></div>';
		}
		return $this->row_actions( $actions );

	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'       => '<input type="checkbox" />',
			'image'    => __( 'Image', 'amazon-integration-for-woocommerce' ),
			'name'     => __( 'Name', 'amazon-integration-for-woocommerce' ),
			// 'report'   => __('Report' , 'amazon-integration-for-woocommerce'),
			'type'     => __( 'Type', 'amazon-integration-for-woocommerce' ),
			'price'    => __( 'Price', 'amazon-integration-for-woocommerce' ),
			'profile'  => __( 'Profile Assigned', 'amazon-integration-for-woocommerce' ),
			'sku'      => __( 'Sku', 'amazon-integration-for-woocommerce' ),
			'stock'    => __( 'Stock', 'amazon-integration-for-woocommerce' ),
			'category' => __( 'Woo Category', 'amazon-integration-for-woocommerce' ),
			'status'   => __( 'Status', 'amazon-integration-for-woocommerce' ),

		);
		/**
		 * Function to list order based on per page
		 * 
		 * @param 'function'
		 * @param  integer 'limit'
		 * @return 'count'
		 * @since 1.0.0
		 */
		$columns = apply_filters( 'ced_amazon_alter_product_table_columns', $columns );
		return $columns;
	}

	/**
	 *
	 * Function to count number of responses in result
	 */
	public function get_count( $per_page, $page_number ) {
		$args = $this->GetFilteredData( $per_page, $page_number );
		if ( ! empty( $args ) && isset( $args['tax_query'] ) || isset( $args['meta_query'] ) || isset( $args['s'] ) ) {
			$args = $args;
		} else {
			$args = array( 'post_type' => 'product' );
		}
		$loop         = new WP_Query( $args );
		$product_data = $loop->posts;
		$product_data = $loop->found_posts;

		return $product_data;
	}

	/**
	 *
	 * Function for GetFilteredData
	 */

	public function GetFilteredData( $per_page, $page_number ) {
		$args           = array();
		$user_id        = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		$seller_id      = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_loc_arr = explode( '|', $seller_id );
		$mp_location    = isset( $seller_loc_arr['1'] ) ? $seller_loc_arr['0'] : '';
		if ( ! empty( $_REQUEST['searchType'] ) && ! empty( $_REQUEST['searchQuery'] ) && ! empty( $_REQUEST['searchCriteria'] ) ) {
			$search_criteria = isset( $_GET['searchCriteria'] ) ? sanitize_text_field( $_GET['searchCriteria'] ) : '';
			$search_type     = isset( $_GET['searchType'] ) ? sanitize_text_field( $_GET['searchType'] ) : '';
			$search_query    = isset( $_GET['searchQuery'] ) ? sanitize_text_field( $_GET['searchQuery'] ) : '';
			if ( 'productId' == $search_type ) {
				$args['prodID'] = $search_query;
			}
			if ( 'product_name' == $search_criteria && 'productCustomSearch' == $search_type ) {
				$args['s'] = $search_query;
			}
			if ( 'product_sku' == $search_criteria && 'productCustomSearch' == $search_type ) {
				$args['search_by_sku'] = $search_query;
			}
		}

		if ( ( isset( $_GET['status_sorting'] ) || isset( $_GET['pro_cat_sorting'] ) || isset( $_GET['pro_type_sorting'] ) || isset( $_GET['searchBy'] ) || isset( $_GET['pro_profile_sorting'] ) || isset( $_GET['prodID'] ) ) && empty( $_REQUEST['searchType'] ) ) {
			if ( isset( $_REQUEST['pro_cat_sorting'] ) && ! empty( $_REQUEST['pro_cat_sorting'] ) ) {
				$pro_cat_sorting = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';
				if ( '' != $pro_cat_sorting ) {
					$selected_cat          = array( $pro_cat_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_cat';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_cat;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( isset( $_REQUEST['pro_type_sorting'] ) && ! empty( $_REQUEST['pro_type_sorting'] ) ) {
				$pro_type_sorting = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( $_GET['pro_type_sorting'] ) : '';
				if ( '' != $pro_type_sorting ) {
					$selected_type         = array( $pro_type_sorting );
					$tax_query             = array();
					$tax_queries           = array();
					$tax_query['taxonomy'] = 'product_type';
					$tax_query['field']    = 'id';
					$tax_query['terms']    = $selected_type;
					$args['tax_query'][]   = $tax_query;
				}
			}

			if ( isset( $_REQUEST['status_sorting'] ) && ! empty( $_REQUEST['status_sorting'] ) ) {
				$status_sorting = isset( $_GET['status_sorting'] ) ? sanitize_text_field( $_GET['status_sorting'] ) : '';
				if ( '' != $status_sorting ) {
					$meta_query = array();
					if ( 'Uploaded' == $status_sorting ) {

						$meta_query[] = array(
							'key'     => 'ced_amazon_product_asin_' . $mp_location,
							'compare' => 'EXISTS',
						);
					} elseif ( 'NotUploaded' == $status_sorting ) {
						$meta_query[] = array(
							'key'     => 'ced_amazon_product_asin_' . $mp_location,
							'compare' => 'NOT EXISTS',
						);
					} elseif ( 'CatalogASIN' == $status_sorting ) {
						$meta_query[] = array(
							'key'     => 'ced_amazon_catalog_asin_' . $mp_location,
							'compare' => 'EXISTS',
							//'value'   => '',
							//'compare' => '!='
						);
					}
					$args['meta_query'] = $meta_query;
				}
			}

			if ( isset( $_REQUEST['pro_stock_sorting'] ) && ! empty( $_REQUEST['pro_stock_sorting'] ) ) {
				$sort_by_stock = isset( $_GET['pro_stock_sorting'] ) ? sanitize_text_field( $_GET['pro_stock_sorting'] ) : '';
				if ( '' != $sort_by_stock ) {
					$meta_query = array();
					if ( 'instock' == $sort_by_stock ) {
						if ( 'Uploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'instock',
									'compare' => '=',
								),

							);

						} elseif ( 'NotUploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'NOT EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'instock',
									'compare' => '=',
								),

							);

						} elseif ( 'CatalogASIN' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_catalog_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'instock',
									'compare' => '=',
								),

							);

						} else {
							$args['meta_query'][] = array(
								'key'   => '_stock_status',
								'value' => 'instock',
							);
						}
					} elseif ( 'outofstock' == $sort_by_stock ) {
						if ( 'Uploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),
								array(
									'key'     => '_stock_status',
									'value'   => 'outofstock',
									'compare' => '=',
								),

							);

						} elseif ( 'NotUploaded' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_product_asin_' . $mp_location,
									'compare' => 'NOT EXISTS',
								),

								array(
									'key'     => '_stock_status',
									'value'   => 'outofstock',
									'compare' => '=',
								),

							);

						} elseif ( 'CatalogASIN' == $_REQUEST['status_sorting'] ) {
							$args['meta_query'] = array(
								'relation' => 'AND',
								array(
									'key'     => 'ced_amazon_catalog_asin_' . $mp_location,
									'compare' => 'EXISTS',
								),

								array(
									'key'     => '_stock_status',
									'value'   => 'outofstock',
									'compare' => '=',
								),

							);

						} else {
							$args['meta_query'][] = array(
								'key'   => '_stock_status',
								'value' => 'outofstock',
							);
						}
					}
				}
			}
			if ( ! empty( $_REQUEST['searchBy'] ) ) {
				$search_by = isset( $_GET['searchBy'] ) ? sanitize_text_field( wp_unslash( $_GET['searchBy'] ) ) : '';
				if ( ! empty( $search_by ) ) {
					$args['s'] = $search_by;
				}
			}

			if ( ! empty( $_REQUEST['prodID'] ) ) {
				$prodID = isset( $_GET['prodID'] ) ? sanitize_text_field( wp_unslash( $_GET['prodID'] ) ) : '';
				if ( ! empty( $prodID ) ) {
					$args['prodID'] = $prodID;
				}
			}
		}

		$args['post_type']      = 'product';
		$args['posts_per_page'] = $per_page;
		$args['paged']          = $page_number;

		return $args;

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
			echo '<select name="action' . esc_attr( $two ) . '" class="ced_amazon_select_amazon_product_action">';
			echo '<option value="-1">' . esc_attr( 'Bulk Actions' ) . "</option>\n";

			foreach ( $this->_actions as $name => $title ) {
				$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . "</option>\n";
			}

			echo "</select>\n";

			wp_nonce_field( 'ced_amazon_product_bulk_action_page_nonce', 'ced_amazon_product_bulk_action_nonce' );
			submit_button( __( 'Apply' ), 'action', 'doaction', false, array( 'id' => 'ced_amazon_bulk_operation' ) );
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
			'upload_product'   => __( 'Upload', 'amazon-integration-for-woocommerce' ),
			'relist_product'   => __( 'Relist Product', 'amazon-integration-for-woocommerce' ),
			'update_inventory' => __( 'Update Inventory', 'amazon-integration-for-woocommerce' ),
			'update_price'     => __( 'Update Price', 'amazon-integration-for-woocommerce' ),
			'update_images'    => __( 'Update Images', 'amazon-integration-for-woocommerce' ),
			'delete_product'   => __( 'Delete Listing', 'amazon-integration-for-woocommerce' ),
			'look_up'		   => __('Look up on amazon', 'amazon-integration-for-woocommerce')
		);
		return $actions;
	}

	/**
	 *
	 * Function for rendering html
	 */
	public function renderHTML() {
		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';
		?>
		<div class="justify-center my-8 select-none flex">

		</div>

		<div class="ced-amazon-v2-header">
			<div class="ced-amazon-v2-logo">
				
			</div>
			<div class="ced-amazon-v2-header-content">
				<div class="ced-amazon-v2-title">
					<h1>Products Management</h1>
				</div>

				<div class="ced-amazon-v2-actions">
					<div class="admin-custom-action-button-outer button-wrap">
						<div class="admin-custom-action-show-button-outer">
							<?php
							if ( function_exists( 'as_get_scheduled_actions' ) ) {
								$scheduled_bulk_upload_actions = as_get_scheduled_actions(
									array(
										'group'  => 'ced_amazon_bulk_upload_' . $user_id,
										'status' => ActionScheduler_Store::STATUS_PENDING,
									),
									'ARRAY_A'
								);
							}
							if ( ! empty( $scheduled_bulk_upload_actions ) ) {
								?>
								<!-- <button style="background:red;" data-action="turn_off" style="margin-left:5px;" id="ced_amazon_toggle_bulk_upload_btn" type="button" class="button btn-normal-tt">
									<span>Turn Off Bulk Products Upload</span>
								</button> -->
								<?php
							} else {
								?>
								<!-- <button  style="margin-left:5px;" data-action="turn_on" id="ced_amazon_toggle_bulk_upload_btn" type="button" class="button btn-normal-sbc">
									<span>Turn On Bulk Products Upload</span>
								</button> -->
								<?php
							}
							?>
						</div>

						<div class="admin-custom-action-show-button-outer">
							<?php
							if ( function_exists( 'as_has_scheduled_action' ) ) {

								if ( as_has_scheduled_action( 'ced_amazon_inventory_scheduler_job_' . $user_id ) ) {
									?>
									<!-- <button  data-action="turn_off" type="button" id="ced_amazon_toggle_bulk_inventory_btn" style="background:#c62019 !important;" class="button btn-normal-tt">
										<span>Turn Off Bulk Inventory Sync</span>
									</button> -->
									<?php
								} else {
									?>
									<!-- <button  data-action="turn_on" type="button" id="ced_amazon_toggle_bulk_inventory_btn" class="button btn-normal-sbc">
										<span>Turn On Bulk Inventory Sync</span>
									</button> -->
									<?php
								}
							}
							?>

						</div>
						<div class="admin-custom-action-show-button-outer">
							<button style="background:#5850ec !important;"  type="button" class="button btn-normal-tt">
								<span><a style="all:unset;" href="https://docs.cedcommerce.com/woocommerce/amazon-integration-woocommerce/?section=add-a-new-profile-6" target="_blank">
								Documentation</a></span>
							</button>

						</div>

					</div>
				</div>
			</div>
		</div>

		<!-- Render feed actions via ajax in modal -->
		<div class="ced-amazon-bootstrap-wrapper">
			<div class="modal fade in" id="feedActionsModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
					<div class="modal-content" style="margin-top: 5%; /*width: 236%; margin-left: 30%;*/">
						<div class="modal-header">
							<h5 class="modal-title" id="exampleModalLabel">Feed actions</h5>
							<button type="button" class="close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">
								<span aria-hidden="true">&times;</span>
							</button>
						</div>
						<div class="modal-body">
							<div class="product-feed-response-modal"></div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close">Close</button>
						</div>
					</div>
				</div>
			</div> 
		</div>

		<section class="woocommerce-inbox-message plain">
			<div class="woocommerce-inbox-message__wrapper">
				<div class="woocommerce-inbox-message__content">
					<h2 class="woocommerce-inbox-message__title">We've upgraded the support!</h2>
					<div class="woocommerce-inbox-message__text">
						<h4>If you're facing a specific problem or want to get the most out of the amazon Integration as an amazon seller, we're here to help.
						To live chat with our seller expert, please click on the chat icon in the bottom right.</h4>
					</div>
				</div>
			</div>
		</section>
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php
					$status_actions = array(
						'Uploaded'    => __( 'Uploaded', 'amazon-integration-for-woocommerce' ),
						'NotUploaded' => __( 'Not Uploaded', 'amazon-integration-for-woocommerce' ),
						'CatalogASIN' => __( 'Amazon ASIN', 'amazon-integration-for-woocommerce' ),
					);

					$product_types = get_terms( 'product_type', array( 'hide_empty' => false ) );
					$temp_array    = array();
					foreach ( $product_types as $key => $value ) {
						if ( 'simple' == $value->name || 'variable' == $value->name ) {
							$temp_array_type[ $value->term_id ] = ucfirst( $value->name );
						}
					}
					$product_types      = $temp_array_type;
					$product_categories = $this->ced_amazon_get_taxonomy_hierarchy( 'product_cat', 0, 0 );
					$temp_array         = array();

					$profiles_array = array();

					$assigned_profiles              = $profiles_array;
					$previous_selected_status       = isset( $_GET['status_sorting'] ) ? sanitize_text_field( $_GET['status_sorting'] ) : '';
					$previous_selected_cat          = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';
					$previous_selected_type         = isset( $_GET['pro_type_sorting'] ) ? sanitize_text_field( $_GET['pro_type_sorting'] ) : '';
					$previous_selected_stock_status = isset( $_GET['pro_stock_sorting'] ) ? sanitize_text_field( $_GET['pro_stock_sorting'] ) : '';
					echo '<div class="ced_amazon_wrap">';
					echo '<form method="post" action="">';
					echo '<div class="ced_amazon_top_wrapper">';
					echo '<select name="status_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Product Status', 'amazon-integration-for-woocommerce' ) . '</option>';
					foreach ( $status_actions as $name => $title ) {
						$selectedStatus = ( $previous_selected_status == $name ) ? 'selected="selected"' : '';
						$class          = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedStatus ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}

					echo '</select>';
					$previous_selected_cat = isset( $_GET['pro_cat_sorting'] ) ? sanitize_text_field( $_GET['pro_cat_sorting'] ) : '';

					$dropdown_cat_args = array(
						'name'            => 'pro_cat_sorting',
						'show_count'      => 1,
						'hierarchical'    => 1,
						'depth'           => 10,
						'taxonomy'        => 'product_cat',
						'class'           => 'select_boxes_product_page',
						'selected'        => $previous_selected_cat,
						'show_option_all' => 'Product Category',

					);
					wp_dropdown_categories( $dropdown_cat_args );
					echo '<select name="pro_type_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Product Type', 'amazon-integration-for-woocommerce' ) . '</option>';
					foreach ( $product_types as $name => $title ) {
						$selectedType = ( $previous_selected_type == $name ) ? 'selected="selected"' : '';
						$class        = 'edit' === $name ? ' class="hide-if-no-js"' : '';
						echo '<option ' . esc_attr( $selectedType ) . ' value="' . esc_attr( $name ) . '"' . esc_attr( $class ) . '>' . esc_attr( $title ) . '</option>';
					}
					echo '</select>';

					echo '<select name="pro_stock_sorting" class="select_boxes_product_page">';
					echo '<option value="">' . esc_attr( 'Stock Status', 'amazon-integration-for-woocommerce' ) . '</option>';
					echo '<option ' . esc_attr( ( 'instock' == $previous_selected_stock_status ) ? 'selected="selected"' : '' ) . ' value="instock">In Stock</option>';
					echo '<option ' . esc_attr( ( 'outofstock' == $previous_selected_stock_status ) ? 'selected="selected"' : '' ) . ' value="outofstock">Out Of Stock</option>';
					echo '</select>';
					wp_nonce_field( 'ced_amazon_product_filter_page_nonce', 'ced_amazon_product_filter_nonce' );
					submit_button( __( 'Filter', 'amazon-integration-for-woocommerce' ), 'action', 'filter_button', false, array() );
					?>

					<?php
						echo '</div>';
						echo '</form>';
						echo '</div>';
					?>
					  <div class="test2"></div>

					<form method="post">
					</div>
				</div>
				<?php
				$this->display();
				?>
			</form>
		</div>
	</div>
	<div class="clear"></div>
</div>
		<?php
	}

	public function extra_tablenav( $which ) {
		global $wpdb, $testiURL, $tablename, $tablet;
		$move_on_url = '&product-filter=';
		if ( 'top' == $which ) {
			ob_start();
			?>

		<div class="alignleft actions bulkactions" style="padding-right:0px !important;">

			<select name="product-filter" class="ced-amazon-filter-products" style="min-width:293px;">
			</select>


		</div>
		<div class="alignleft actions bulkactions">

			<select name="product-filter-criteria" class="ced-amazon-filter-products-criteria">
				<option value="product_name">Name</option>
				<option value="product_sku">SKU</option>
				<!-- <option value="amazon_asin">Amazon ASIN</option> -->
			</select>


		</div>
			<?php
			wp_nonce_field( 'ced_amazon_filter_product_action_nonce', 'ced_amazon_filter_product_nonce' );
			submit_button( __( 'Search', 'amazon-integration-for-woocommerce' ), 'action', 'ced_amazon_filter_product_button', false, array() );
			submit_button( __( 'Reset', 'amazon-integration-for-woocommerce' ), '', 'ced_amazon_reset_product_page', false, array() );
		
			ob_flush();
		}
	}
	
	
	public function column_report( $item) {
		$seller_id     = isset( $_GET['seller_id'] ) ? sanitize_text_field( $_GET['seller_id'] ) : '';
		$seller_id_val = str_replace('|', '_', $seller_id);
		$product_feeds = get_post_meta( $item['id'], 'ced_amazon_feed_actions_' . $seller_id_val, true );
		
		if ( is_array($product_feeds) && !empty($product_feeds) ) {
			
		global $wpdb;
		$tableName        = $wpdb->prefix . 'ced_amazon_feeds';
		$feed_request_ids = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}ced_amazon_feeds WHERE `feed_id` = %d", $product_feeds['POST_FLAT_FILE_LISTINGS_DATA'] ), 'ARRAY_A' );
		$feed_request_id  = $feed_request_ids[0];
		$main_id          = $feed_request_id['id'];
		$feed_type        = $feed_request_id['feed_action'];
		$location_id      = $feed_request_id['feed_location'];
		$marketplace      = 'amazon_spapi';
		
		$response        = $feed_request_id['response'];
		$response_format = false;
		$response        = json_decode( $response, true );
			if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
				$response        = $response;
				$response_format = true;
	
			} else {

				$feed_manager = Ced_Umb_Amazon_Feed_Manager::get_instance();
				$response     = $feed_manager->getFeedItemsStatusSpApi( $product_feeds['POST_FLAT_FILE_LISTINGS_DATA'], $feed_type, $location_id, $marketplace , $seller_id );
				// print_r($response);
	
				if ( isset( $response['status'] ) && 'DONE' == $response['status'] ) {
					$response_format = true;
				}
				$response_data = json_encode( $response );
				$wpdb->update( $tableName, array( 'response' => $response_data ), array( 'id' => $main_id ) );
			}

		// echo "<pre>";
		// print_r($feed_request_ids);echo "</pre>";
			if ('POST_FLAT_FILE_LISTINGS_DATA' == $feed_type ) {
				if ( $response_format ) {
					if ( isset($main_id) && !isset($response['body']) ) {
						echo 'record under process';

					} elseif (isset($response['body']) ) {
						$tab_response_data = explode( "\n", $response['body'] );
						foreach ( $tab_response_data as $tabKey => $tabValue ) {
							// echo $tabKey;
							$line_data = explode( "\t", $tabValue );
							if ( 'Feed Processing Summary' == $line_data[0] || 'Feed Processing Summary:' == $line_data[0] ) {
								continue;
							} elseif ( empty( $line_data[0] ) || '' == $line_data[0] ) {
								continue;
							} elseif ( 'original-record-number' == $line_data[0] ) {
								continue;
							} else {
								if ('Error' == $line_data[3]) {
									if ('99001' == $line_data[2]) {
										?>
								<span> Failure  
									<i class=" ced_amazon_feed_tooltip fa fa-info-circle" data-tooltip-content ="Some of the required values are missing. Please check the feed" > </i>
								</span>
							
									<?php	
									} elseif ('90057' == $line_data[2]) {
										?>
								<span> Failure 
									<i class=" ced_amazon_feed_tooltip fa fa-info-circle" data-tooltip-content ="Some of the required values are invalid. Please check the feed" > </i >
								</span>
									<?php	
									} else {
										?>
								  <span> Failure
									<i class=" ced_amazon_feed_tooltip fa fa-info-circle" data-tooltip-content="There are some major errors. Please check the feed"></i>
									</span> </td>
								</div>
										<?php	
									}
											
									break;
								}
							}
						}
					}
				} else {
					if ( isset($main_id) && !isset($response['body']) ) {
						echo 'record under process';

					}
				}
			}
	
		}
		// echo "hello";
	}

	public function column_listing_ad_rate( $item ) {
		$dataInGlobalSettings = get_option( 'ced_amazon_global_settings', false );

		$user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : '';

		$listing_id = get_post_meta( $item['id'], '_ced_amazon_listing_id_' . $user_id, true );
		if ( isset( $listing_id ) && ! empty( $listing_id ) ) {
			$promoted_listing_data = get_post_meta( $item['id'], '_ced_amazon_promoted_listings_ad_data_' . $user_id, true );
			if ( empty( $promoted_listing_data['adId'] ) ) {
				echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
				echo '<input type="button"
			class="button button-primary create-ad-button"
			data-tab-id="tab1"
			data-product-id="' . esc_attr( $item['id'] ) . '"
			data-listing-id="' . esc_attr( $listing_id ) . '" value="Create Ad">';
				echo '</div></div>';
			} else {
				echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
				echo '<input type="button" class="button button-primary update-ad-button" data-tab-id="tab2" id="ced_amazon_update_listing_ad_rate" data-listing-ad-id= "' . esc_attr( $promoted_listing_data['adId'] ) . '" data-campaign-id="' . esc_attr( $promoted_listing_data['campaignId'] ) . '" data-listing-id="' . esc_attr( $listing_id ) . '"  value="Update Ad">';
				echo '<input type="button"
			id="ced_amazon_delete_campaign_ad_button"
			class="button button-primary create-ad-button"
			data-tab-id="tab1"
			data-listing-ad-id= "' . esc_attr( $promoted_listing_data['adId'] ) . '"
			data-campaign-id="' . esc_attr( $promoted_listing_data['campaignId'] ) . '"
			style="margin-top:10px;"
			value="Delete Ad">';
				echo '</div></div>';
			}
		} else {
			echo '<div class="admin-custom-action-button-outer"><div class="admin-custom-action-show-button-outer">';
			echo '<p style="color:red;">Please upload the product first.</p>';
			echo '</div></div>';
		}

	}

}

$ced_amazon_products_obj = new AmazonListProducts();
$ced_amazon_products_obj->prepare_items();

?>



<style>
	 .ced-amazon-v2-admin-menu{
	   margin-right: 20px !important;
   }
   
   .ced-amazon-v2-header{
		   margin: 10px 20px 20px 2px !important;
   }
</style>
