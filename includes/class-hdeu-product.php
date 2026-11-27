<?php

namespace htrxuan\hdeu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Product side: records price changes, prints the lowest-prior-price notice, adds the
 * "Product safety" tab and the legal guarantee line, and the per-product "EU compliance"
 * fields in the product data panel (plain fields only -- no nested form).
 *
 * Everything printed is escaped at output; texts are plain text with line breaks.
 */
final class HDEU_Product
{

    const META_MANUFACTURER   = '_hdeu_manufacturer';
    const META_RESPONSIBLE    = '_hdeu_responsible';
    const META_SAFETY         = '_hdeu_safety';
    const META_HIDE_GUARANTEE = '_hdeu_hide_guarantee';

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
        // Recording.
        add_action('woocommerce_after_product_object_save', array($this, 'record_price'));
        add_action('deleted_post', array($this, 'forget_deleted'), 10, 2);
        add_action('hdeu_prune_history', array(HDEU_Price_History::class, 'prune'));

        // Storefront.
        add_filter('woocommerce_get_price_html', array($this, 'append_lowest_price'), 20, 2);
        add_filter('woocommerce_available_variation', array($this, 'variation_lowest_price'), 20, 3);
        add_filter('woocommerce_product_tabs', array($this, 'add_tab'));
        add_action('woocommerce_after_add_to_cart_form', array($this, 'render_guarantee'));

        // Admin product fields.
        add_action('woocommerce_product_options_general_product_data', array($this, 'render_fields'));
        add_action('woocommerce_admin_process_product_object', array($this, 'save_fields'));
    }

    /* ---------- recording ---------- */

    public function record_price($product)
    {
        HDEU_Price_History::record($product);
    }

    public function forget_deleted($post_id, $post)
    {
        if ($post && in_array($post->post_type, array('product', 'product_variation'), true)) {
            HDEU_Price_History::delete_for_product($post_id);
        }
    }

    /* ---------- lowest prior price ---------- */

    /**
     * The notice text for one product, or '' when nothing should show.
     *
     * @param \WC_Product $product
     */
    public static function lowest_price_notice($product)
    {
        $s = HDEU_Settings::get();
        if (!$s['price_enabled'] || !$product instanceof \WC_Product || $product->is_type(array('variable', 'grouped')) || !$product->is_on_sale('edit')) {
            return '';
        }
        $lowest = HDEU_Price_History::lowest_prior_price($product, $s['price_days']);
        if (null === $lowest) {
            return '';
        }
        $price = wp_strip_all_tags(wc_price(wc_get_price_to_display($product, array('price' => $lowest))));
        $text  = strtr($s['price_label'], array('{price}' => $price, '{days}' => (string) $s['price_days']));
        return '<span class="hdeu-lowest-price" style="display:block;font-size:0.85em;opacity:0.8;">' . esc_html($text) . '</span>';
    }

    public function append_lowest_price($html, $product)
    {
        if (is_admin() && !wp_doing_ajax()) {
            return $html;
        }
        if ($product instanceof \WC_Product && $product->is_type('variation')) {
            return $html; // Added through the variation data below, once.
        }
        $s = HDEU_Settings::get();
        $on_own_page = is_product() && (int) get_queried_object_id() === (int) $product->get_id();
        if (!$on_own_page && !$s['price_in_lists']) {
            return $html;
        }
        return $html . self::lowest_price_notice($product);
    }

    public function variation_lowest_price($data, $product, $variation)
    {
        $notice = self::lowest_price_notice($variation);
        if ($notice && isset($data['price_html'])) {
            $data['price_html'] .= $notice;
        }
        return $data;
    }

    /* ---------- product safety tab + guarantee ---------- */

    /** Per-product values, falling back to the store-wide defaults. */
    public static function safety_info($product)
    {
        $s   = HDEU_Settings::get();
        $id  = $product->get_id();
        $out = array();
        foreach (array(
            'manufacturer' => array(self::META_MANUFACTURER, __('Manufacturer', 'hdwebmobile-eu-compliance')),
            'responsible'  => array(self::META_RESPONSIBLE, __('Responsible person in the EU', 'hdwebmobile-eu-compliance')),
            'safety'       => array(self::META_SAFETY, __('Safety information', 'hdwebmobile-eu-compliance')),
        ) as $key => $def) {
            $value = (string) get_post_meta($id, $def[0], true);
            if ('' === trim($value)) {
                $value = $s[$key];
            }
            if ('' !== trim($value)) {
                $out[$key] = array('label' => $def[1], 'value' => $value);
            }
        }
        return $out;
    }

    public static function guarantee_text($product)
    {
        $s = HDEU_Settings::get();
        if (!$s['guarantee_enabled'] || '' === trim($s['guarantee_text']) || 'yes' === get_post_meta($product->get_id(), self::META_HIDE_GUARANTEE, true)) {
            return '';
        }
        return $s['guarantee_text'];
    }

    public function add_tab($tabs)
    {
        global $product;
        $s = HDEU_Settings::get();
        if (!$s['safety_enabled'] || !$product instanceof \WC_Product || (!self::safety_info($product) && '' === self::guarantee_text($product))) {
            return $tabs;
        }
        $tabs['hdeu_safety'] = array(
            'title'    => $s['tab_title'],
            'priority' => 35,
            'callback' => array($this, 'render_tab'),
        );
        return $tabs;
    }

    public function render_tab()
    {
        global $product;
        if (!$product instanceof \WC_Product) {
            return;
        }
        echo '<h2>' . esc_html(HDEU_Settings::get()['tab_title']) . '</h2>';
        echo '<dl class="hdeu-safety">';
        foreach (self::safety_info($product) as $row) {
            echo '<dt><strong>' . esc_html($row['label']) . '</strong></dt>';
            echo '<dd style="margin:0 0 1em;">' . nl2br(esc_html($row['value'])) . '</dd>';
        }
        $guarantee = self::guarantee_text($product);
        if ('' !== $guarantee) {
            echo '<dt><strong>' . esc_html__('Legal guarantee', 'hdwebmobile-eu-compliance') . '</strong></dt>';
            echo '<dd style="margin:0 0 1em;">' . nl2br(esc_html($guarantee)) . '</dd>';
        }
        echo '</dl>';
    }

    public function render_guarantee()
    {
        global $product;
        if (!$product instanceof \WC_Product) {
            return;
        }
        $text = self::guarantee_text($product);
        if ('' !== $text) {
            echo '<p class="hdeu-guarantee" style="font-size:0.9em;margin-top:1em;">' . nl2br(esc_html($text)) . '</p>';
        }
    }

    /* ---------- admin product fields ---------- */

    public function render_fields()
    {
        global $post;
        echo '<div class="options_group hdeu-fields">';
        echo '<p class="form-field"><strong>' . esc_html__('EU compliance', 'hdwebmobile-eu-compliance') . '</strong> &mdash; ' . esc_html__('leave empty to use the store-wide defaults.', 'hdwebmobile-eu-compliance') . '</p>';
        woocommerce_wp_textarea_input(array(
            'id'    => self::META_MANUFACTURER,
            'label' => __('Manufacturer', 'hdwebmobile-eu-compliance'),
            'desc_tip' => true,
            'description' => __('Name, postal address and email or website.', 'hdwebmobile-eu-compliance'),
            'value' => get_post_meta($post->ID, self::META_MANUFACTURER, true),
        ));
        woocommerce_wp_textarea_input(array(
            'id'    => self::META_RESPONSIBLE,
            'label' => __('EU responsible person', 'hdwebmobile-eu-compliance'),
            'desc_tip' => true,
            'description' => __('Needed when the manufacturer is outside the EU.', 'hdwebmobile-eu-compliance'),
            'value' => get_post_meta($post->ID, self::META_RESPONSIBLE, true),
        ));
        woocommerce_wp_textarea_input(array(
            'id'    => self::META_SAFETY,
            'label' => __('Safety information', 'hdwebmobile-eu-compliance'),
            'desc_tip' => true,
            'description' => __('Warnings and safety instructions for this product.', 'hdwebmobile-eu-compliance'),
            'value' => get_post_meta($post->ID, self::META_SAFETY, true),
        ));
        woocommerce_wp_checkbox(array(
            'id'    => self::META_HIDE_GUARANTEE,
            'label' => __('Hide legal guarantee', 'hdwebmobile-eu-compliance'),
            'description' => __('Do not show the store-wide guarantee notice on this product.', 'hdwebmobile-eu-compliance'),
            'value' => get_post_meta($post->ID, self::META_HIDE_GUARANTEE, true),
        ));
        echo '</div>';
    }

    /**
     * Runs inside WooCommerce's own product save, after it has verified the
     * woocommerce_meta_nonce and the edit_post capability.
     *
     * @param \WC_Product $product
     */
    public function save_fields($product)
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified woocommerce_meta_nonce before firing woocommerce_admin_process_product_object.
        foreach (array(self::META_MANUFACTURER => 2000, self::META_RESPONSIBLE => 2000, self::META_SAFETY => 5000) as $key => $max) {
            $value = isset($_POST[$key]) ? mb_substr(sanitize_textarea_field(wp_unslash($_POST[$key])), 0, $max) : '';
            if ('' === trim($value)) {
                $product->delete_meta_data($key);
            } else {
                $product->update_meta_data($key, $value);
            }
        }
        if (!empty($_POST[self::META_HIDE_GUARANTEE])) {
            $product->update_meta_data(self::META_HIDE_GUARANTEE, 'yes');
        } else {
            $product->delete_meta_data(self::META_HIDE_GUARANTEE);
        }
        // phpcs:enable
    }
}
