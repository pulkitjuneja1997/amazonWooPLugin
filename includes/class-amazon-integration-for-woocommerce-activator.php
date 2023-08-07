<?php

/**
 * Fired during plugin activation
 *
 * @link       care@cedcommerce.com
 * @since      1.0.0
 *
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Amazon_Integration_For_Woocommerce
 * @subpackage Amazon_Integration_For_Woocommerce/includes
 */
class Amazon_Integration_For_Woocommerce_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		global $wpdb;
		$tableName = $wpdb->prefix . 'ced_amazon_profiles';

		$create_profile_table =
			"CREATE TABLE $tableName (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			profile_name VARCHAR(255) NOT NULL,
			primary_category VARCHAR(255) NOT NULL,
			secondary_category VARCHAR(255) NOT NULL,
			browse_nodes DOUBLE NOT NULL,  
			category_attributes_response LONGTEXT DEFAULT NULL,
			wocoommerce_category TEXT(255) DEFAULT NULL,
			category_attributes_structure LONGTEXT DEFAULT NULL,
			category_attributes_data LONGTEXT DEFAULT NULL,
			template_type TEXT(255) DEFAULT NULL,
			file_url LONGTEXT DEFAULT NULL,
			seller_id VARCHAR(255) DEFAULT NULL,
			PRIMARY KEY (id) );";

		if ( is_file( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$a = dbDelta( $create_profile_table );

		global $wpdb;
		$feedTableName = $wpdb->prefix . 'ced_amazon_feeds';

		$create_feed_table =
			"CREATE TABLE $feedTableName (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			feed_id DOUBLE NOT NULL,
			feed_action VARCHAR(255) NOT NULL,
			feed_location VARCHAR(255) NOT NULL,
			feed_date_time DATETIME NOT NULL, 
			sku JSON DEFAULT NULL,
			response TEXT DEFAULT NULL, 
			PRIMARY KEY (id) );";

		if ( is_file( ABSPATH . 'wp-admin/includes/upgrade.php' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}
		$B = dbDelta( $create_feed_table );

	}

}
