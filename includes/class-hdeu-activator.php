<?php

namespace htrxuan\hdeu;

if (!defined('ABSPATH')) {
    exit;
}

class HDEU_Activator
{

    public static function activate()
    {
        if (!self::is_woocommerce_active()) {
            deactivate_plugins(plugin_basename(HDEU_PLUGIN_FILE));
            set_transient('hdeu_wc_missing_notice', true, 30);
            return;
        }

        require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-price-history.php';
        HDEU_Price_History::install();
        if (!wp_next_scheduled('hdeu_prune_history')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'hdeu_prune_history');
        }
    }

    public static function deactivate()
    {
        wp_clear_scheduled_hook('hdeu_prune_history');
    }

    public static function is_woocommerce_active()
    {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return is_plugin_active('woocommerce/woocommerce.php') || class_exists('WooCommerce');
    }
}
