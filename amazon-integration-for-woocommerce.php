<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              care@cedcommerce.com
 * @since             1.0.5
 * @package           Amazon_Integration_For_Woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Amazon-integration-for-woocommerce
 * Plugin URI:        https://cedcommerce.com/
 * Description:       Configure once and sell your products to Amazon Marketplaces (up-to 2 marketplace) from WooCommerce easily.
 * Version:           1.0.5
 * Author:            Cedcommerce
 * Author URI:        care@cedcommerce.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       amazon-integration-for-woocommerce
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'AMAZON_INTEGRATION_FOR_WOOCOMMERCE_VERSION', '1.0.3' );


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-amazon-integration-for-woocommerce-activator.php
 */
function activate_amazon_integration_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-amazon-integration-for-woocommerce-activator.php';
	Amazon_Integration_For_Woocommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-amazon-integration-for-woocommerce-deactivator.php
 */
function deactivate_amazon_integration_for_woocommerce() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-amazon-integration-for-woocommerce-deactivator.php';
	Amazon_Integration_For_Woocommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_amazon_integration_for_woocommerce' );
register_deactivation_hook( __FILE__, 'deactivate_amazon_integration_for_woocommerce' );


/* DEFINE CONSTANTS */
define( 'CED_AMAZON_LOG_DIRECTORY', wp_upload_dir()['basedir'] . '/ced-amazon/logs' );
define( 'CED_AMAZON_VERSION', '1.0.3' );
define( 'CED_AMAZON_PREFIX', 'ced_AMAZON' );
define( 'CED_AMAZON_DIRPATH', plugin_dir_path( __FILE__ ) );
define( 'CED_AMAZON_URL', plugin_dir_url( __FILE__ ) );
define( 'CED_AMAZON_ABSPATH', untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-amazon-integration-for-woocommerce.php';


/**
* This file includes core functions to be used globally in plugin.
 *
* @link  http://www.cedcommerce.com/
*/
require_once plugin_dir_path( __FILE__ ) . 'includes/ced-amazon-core-functions.php';

/**
 * Check WooCommerce activation.
 */
if ( ced_amazon_check_woocommerce_active() ) {
	run_amazon_integration_for_woocommerce();
} else {
	add_action( 'admin_init', 'deactivate_ced_amazon_woo_missing' );
}

/**
 * Check WooCommmerce active or not.
 *
 * @name ced_amazon_check_woocommerce_active
 * @since 1.0.0
 * @return bool true|false 
 * @link  http://www.cedcommerce.com/
 */
function ced_amazon_check_woocommerce_active() {

	if ( function_exists('is_multisite') && is_multisite() ) {
		
		include_once  ABSPATH . 'wp-admin/includes/plugin.php' ;

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			return true;
		}
		return false;
	} else {
		/**
		 * Function to get list of active plugins
		 * 
		 * @param 'function'
		 * @return 'list'
		 * @since 1.0.0
		 */	
		$installedPlugins = apply_filters('active_plugins', get_option('active_plugins'));
		if ( in_array('woocommerce/woocommerce.php', $installedPlugins ) ) {

			return true;
		}
		return false;
	}
}

/**
 * This code runs when WooCommerce is not activated,
 * deativates the extension and displays the notice to admin.
 *
 * @name deactivate_ced_amazon_woo_missing
 * @since 1.0.0
 * @link  http://www.cedcommerce.com/
 */
function deactivate_ced_amazon_woo_missing() {

	deactivate_plugins( 'amazon-integration-for-woocommerce/amazon-integration-for-woocommerce.php' );
	add_action('admin_notices', 'ced_amazon_woo_missing_notice' );
	//add_action('network_admin_notices', 'ced_amazon_woo_missing_notice' );
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
}

/**
 * Callback function for sending notice if woocommerce is not activated.
 *
 * @name ced_amazon_woo_missing_notice
 * @since 1.0.0
 * @return string
 * @link  http://www.cedcommerce.com/
 */
function ced_amazon_woo_missing_notice() {

	$activate_url  = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php' );
	$activate_text = __( 'Activate WooCommerce', 'ced-amazon' );

	if ( current_user_can( 'install_plugins' ) ) {
		if ( is_wp_error( validate_plugin( 'woocommerce/woocommerce.php' ) ) ) {
			// WooCommerce is not installed.
			$activate_url  = wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
			$activate_text = __( 'Install WooCommerce', 'ced-amazon' );
		} else {
			// WooCommerce is installed, so it just needs to be enabled.
			$activate_url  = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php' ), 'activate-plugin_woocommerce/woocommerce.php' );
			$activate_text = __( 'Activate WooCommerce', 'ced-amazon' );
		}

		echo '<div class="error"><p>' . esc_html__('Amazon Integration for Woocommerce requires WooCommerce plugin to be installed and active. ', 'ced-amazon') . '
        <a href="' . esc_attr($activate_url) . '" id="activate-woocommerce" > Click here to ' . esc_html__( $activate_text, 'ced-amazon') . '</p></div>';
		
	}
	
}

/**
 * Callback function for sending notice while plugin activation error.
 *
 */
function ced_amazon_plugin_activation_error() {
	@trigger_error( esc_html_e( 'Amazon Integration for Woocommerce requires WooCommerce plugin to be installed and active.', 'cln' ), E_USER_ERROR );

}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_amazon_integration_for_woocommerce() {

	$plugin = new Amazon_Integration_For_Woocommerce();
	$plugin->run();

}
// run_amazon_integration_for_woocommerce();
