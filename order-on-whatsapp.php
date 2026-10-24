<?php
/**
 * Plugin Name:       Order on WhatsApp for WooCommerce
 * Plugin URI:        https://wahyunur.runs-on.dev/
 * Description:       Lets shoppers send an order for a single product or their whole cart to your WhatsApp number with a pre-filled message.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Wahyu Nur Agustusanto
 * Author URI:        https://wahyunur.runs-on.dev/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       order-on-whatsapp
 * WC requires at least: 8.0
 *
 * @package OrderOnWhatsApp
 */

defined( 'ABSPATH' ) || exit;

define( 'OOW_VERSION', '1.0.0' );
define( 'OOW_FILE', __FILE__ );
define( 'OOW_PATH', plugin_dir_path( __FILE__ ) );
define( 'OOW_URL', plugin_dir_url( __FILE__ ) );

require_once OOW_PATH . 'includes/class-oow-settings.php';
require_once OOW_PATH . 'includes/class-oow-frontend.php';

/**
 * Tell WooCommerce this plugin works with HPOS and the Cart/Checkout blocks.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', OOW_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', OOW_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		OOW_Settings::init();
		OOW_Frontend::init();
	}
);
