<?php

namespace htrxuan\hdeu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The "EU Compliance" hub tab. handle_save() is the only write path for the settings: it
 * checks manage_woocommerce and a nonce before reading input; HDEU_Settings validates each
 * field.
 */
class HDEU_Admin
{

    const NONCE = 'hdeu_save_settings';
    const TAB   = 'eu-compliance';

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
        require_once HDEU_PLUGIN_DIR . 'includes/class-hdeu-hub.php';
        add_filter('hdwebmobile_hub_tabs', array($this, 'register_hub_tabs'));
        add_action('admin_post_hdeu_save_settings', array($this, 'handle_save'));
    }

    public function register_hub_tabs($tabs)
    {
        $tabs[self::TAB] = array(
            'label'  => __('EU Compliance', 'hdwebmobile-eu-compliance'),
            'order'  => 169,
            'render' => array($this, 'render_page'),
        );
        return $tabs;
    }

    public function handle_save()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'hdwebmobile-eu-compliance'));
        }
        if (!isset($_POST['hdeu_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hdeu_nonce'])), self::NONCE)) {
            wp_die(esc_html__('Security check failed. Please try again.', 'hdwebmobile-eu-compliance'));
        }
        $input = array();
        foreach (array('price_days', 'price_label', 'tab_title') as $key) {
            $input[$key] = isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
        }
        foreach (array('manufacturer', 'responsible', 'safety', 'guarantee_text') as $key) {
            $input[$key] = isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
        }
        foreach (array('price_enabled', 'price_in_lists', 'safety_enabled', 'guarantee_enabled') as $key) {
            $input[$key] = !empty($_POST[$key]);
        }
        HDEU_Settings::save($input);
        wp_safe_redirect(admin_url('admin.php?page=hdwebmobile&tab=' . self::TAB . '&updated=1'));
        exit;
    }

    private function textarea($name, $value, $rows = 4)
    {
        printf('<textarea name="%1$s" class="large-text" rows="%3$d">%2$s</textarea>', esc_attr($name), esc_textarea($value), (int) $rows);
    }

    public function render_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            wp_die(esc_html__('You do not have permission to do this.', 'hdwebmobile-eu-compliance'));
        }
        $s = HDEU_Settings::get();
        ?>
        <p><?php esc_html_e('Helps EU stores show three things shoppers are entitled to see: the lowest price before a discount, who makes and is responsible for a product, and the legal guarantee. This plugin displays the information you enter; it is not legal advice, so check the rules for your country.', 'hdwebmobile-eu-compliance'); ?></p>

        <?php if (!empty($_GET['updated'])) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only success flag. ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Settings saved.', 'hdwebmobile-eu-compliance'); ?></p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="hdeu_save_settings" />
            <?php wp_nonce_field(self::NONCE, 'hdeu_nonce'); ?>

            <h2><?php esc_html_e('Lowest prior price (Omnibus)', 'hdwebmobile-eu-compliance'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Show notice', 'hdwebmobile-eu-compliance'); ?></th>
                    <td><label><input type="checkbox" name="price_enabled" value="1" <?php checked($s['price_enabled']); ?> /> <?php esc_html_e('Show the lowest earlier price under the price of products on sale', 'hdwebmobile-eu-compliance'); ?></label><br />
                        <label><input type="checkbox" name="price_in_lists" value="1" <?php checked($s['price_in_lists']); ?> /> <?php esc_html_e('Also show it in shop and category lists', 'hdwebmobile-eu-compliance'); ?></label></td>
                </tr>
                <tr>
                    <th scope="row"><label for="hdeu-days"><?php esc_html_e('Period (days)', 'hdwebmobile-eu-compliance'); ?></label></th>
                    <td><input type="number" id="hdeu-days" name="price_days" min="1" max="365" value="<?php echo esc_attr($s['price_days']); ?>" style="width:80px;" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="hdeu-label"><?php esc_html_e('Text', 'hdwebmobile-eu-compliance'); ?></label></th>
                    <td><input type="text" id="hdeu-label" name="price_label" class="large-text" value="<?php echo esc_attr($s['price_label']); ?>" />
                        <p class="description"><?php esc_html_e('Must contain {price}. {days} is replaced by the period.', 'hdwebmobile-eu-compliance'); ?></p></td>
                </tr>
            </table>
            <p class="description" style="max-width:820px;"><?php esc_html_e('Prices are recorded every time a product is saved with a new price, including when a scheduled sale starts or ends. History starts when the plugin is activated; until a product has older history, its regular price is shown as the earlier price.', 'hdwebmobile-eu-compliance'); ?></p>

            <h2><?php esc_html_e('Product safety (GPSR)', 'hdwebmobile-eu-compliance'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Product tab', 'hdwebmobile-eu-compliance'); ?></th>
                    <td><label><input type="checkbox" name="safety_enabled" value="1" <?php checked($s['safety_enabled']); ?> /> <?php esc_html_e('Show a tab with the safety details on product pages', 'hdwebmobile-eu-compliance'); ?></label>
                        <p><input type="text" name="tab_title" class="regular-text" maxlength="60" value="<?php echo esc_attr($s['tab_title']); ?>" /></p></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Default manufacturer', 'hdwebmobile-eu-compliance'); ?></th>
                    <td><?php $this->textarea('manufacturer', $s['manufacturer']); ?><p class="description"><?php esc_html_e('Name, postal address and email or website. Products can override this in their EU compliance fields.', 'hdwebmobile-eu-compliance'); ?></p></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Default EU responsible person', 'hdwebmobile-eu-compliance'); ?></th>
                    <td><?php $this->textarea('responsible', $s['responsible']); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Default safety information', 'hdwebmobile-eu-compliance'); ?></th>
                    <td><?php $this->textarea('safety', $s['safety'], 5); ?></td>
                </tr>
            </table>

            <h2><?php esc_html_e('Legal guarantee', 'hdwebmobile-eu-compliance'); ?></h2>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Show notice', 'hdwebmobile-eu-compliance'); ?></th>
                    <td><label><input type="checkbox" name="guarantee_enabled" value="1" <?php checked($s['guarantee_enabled']); ?> /> <?php esc_html_e('Show this text under the add-to-cart button and in the safety tab', 'hdwebmobile-eu-compliance'); ?></label>
                        <p><?php $this->textarea('guarantee_text', $s['guarantee_text'], 3); ?></p></td>
                </tr>
            </table>

            <?php submit_button(__('Save settings', 'hdwebmobile-eu-compliance')); ?>
        </form>
        <?php
    }
}
