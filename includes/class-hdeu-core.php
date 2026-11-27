<?php

namespace htrxuan\hdeu;

if (!defined('ABSPATH')) {
    exit;
}

final class HDEU_Core
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    private function __clone()
    {
    }

    private function includes()
    {
        require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-settings.php';
        require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-price-history.php';
        require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-product.php';
        require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-admin.php';
    }

    private function init_hooks()
    {
        add_action('admin_notices', array($this, 'render_missing_woocommerce_notice'));

        if (!class_exists('WooCommerce')) {
            return;
        }

        // HDEU_Admin owns the hdwebmobile_hub_tabs registration used by the shared hub
        // page, so it must load unconditionally (not only when is_admin()).
        HDEU_Admin::get_instance();
        HDEU_Price_History::maybe_install();
        HDEU_Product::get_instance();
    }

    public function render_missing_woocommerce_notice()
    {
        $screen = get_current_screen();
        if (!$screen || 'plugins' !== $screen->id) {
            return;
        }

        if (!get_transient('hdeu_wc_missing_notice')) {
            return;
        }
        delete_transient('hdeu_wc_missing_notice');
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <?php esc_html_e('HDWebmobile EU Compliance requires WooCommerce to be installed and active. The plugin has been deactivated.', 'hdwebmobile-eu-compliance'); ?>
            </p>
        </div>
        <?php
    }
}
