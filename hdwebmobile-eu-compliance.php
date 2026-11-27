<?php

/**
 * Plugin Name: HDWebmobile EU Compliance
 * Plugin URI: https://hdwebmobile.com/plugins/hdwebmobile-eu-compliance/
 * Description: Show the lowest 30-day price on discounted products, product safety (GPSR) details and a legal guarantee notice for EU shoppers.
 * Version: 1.0.0
 * Author: htrxuan - Han Tran
 * Author URI: https://hdwebmobile.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hdwebmobile-eu-compliance
 * Domain Path: /languages
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Requires at least: 6.9
 */

namespace htrxuan\hdeu;

if (!defined('ABSPATH')) {
    exit;
}

define('HDEU_VERSION', '1.0.0');
define('HDEU_PLUGIN_FILE', __FILE__);
define('HDEU_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HDEU_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-activator.php';

register_activation_hook(__FILE__, array(HDEU_Activator::class, 'activate'));
register_deactivation_hook(__FILE__, array(HDEU_Activator::class, 'deactivate'));

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', HDEU_PLUGIN_FILE, true);
    }
});

add_action('plugins_loaded', function () {
    require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-core.php';
    HDEU_Core::get_instance();
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $donate_link = '<a href="https://paypal.me/htrxuan/20" target="_blank" rel="noopener noreferrer">' . esc_html__('Donate', 'hdwebmobile-eu-compliance') . '</a>';
    array_unshift($links, $donate_link);
    return $links;
});
