<?php

namespace htrxuan\hdeu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Store-wide settings: one option, validated field by field on save.
 */
class HDEU_Settings
{

    const OPTION = 'hdeu_settings';

    public static function defaults()
    {
        return array(
            'price_enabled'     => true,
            'price_days'        => 30,
            /* translators: keep {days} and {price}: they are replaced by the number of days and the lowest price */
            'price_label'       => __('Lowest price in the {days} days before this discount: {price}', 'hdwebmobile-eu-compliance'),
            'price_in_lists'    => false,
            'safety_enabled'    => true,
            'tab_title'         => __('Product safety', 'hdwebmobile-eu-compliance'),
            'manufacturer'      => '',
            'responsible'       => '',
            'safety'            => '',
            'guarantee_enabled' => false,
            'guarantee_text'    => __('All products come with a 2-year legal guarantee of conformity under EU consumer law.', 'hdwebmobile-eu-compliance'),
        );
    }

    public static function get()
    {
        $stored = get_option(self::OPTION, array());
        return array_merge(self::defaults(), is_array($stored) ? array_intersect_key($stored, self::defaults()) : array());
    }

    /** $input must already be unslashed. */
    public static function save($input)
    {
        $days = isset($input['price_days']) ? absint($input['price_days']) : 30;
        $label = isset($input['price_label']) ? sanitize_text_field($input['price_label']) : '';
        if (false === strpos($label, '{price}')) {
            $label = self::defaults()['price_label'];
        }
        $settings = array(
            'price_enabled'     => !empty($input['price_enabled']),
            'price_days'        => min(365, max(1, $days)),
            'price_label'       => mb_substr($label, 0, 200),
            'price_in_lists'    => !empty($input['price_in_lists']),
            'safety_enabled'    => !empty($input['safety_enabled']),
            'tab_title'         => mb_substr(sanitize_text_field($input['tab_title'] ?? ''), 0, 60),
            'manufacturer'      => mb_substr(sanitize_textarea_field($input['manufacturer'] ?? ''), 0, 2000),
            'responsible'       => mb_substr(sanitize_textarea_field($input['responsible'] ?? ''), 0, 2000),
            'safety'            => mb_substr(sanitize_textarea_field($input['safety'] ?? ''), 0, 5000),
            'guarantee_enabled' => !empty($input['guarantee_enabled']),
            'guarantee_text'    => mb_substr(sanitize_textarea_field($input['guarantee_text'] ?? ''), 0, 1000),
        );
        if ('' === $settings['tab_title']) {
            $settings['tab_title'] = self::defaults()['tab_title'];
        }
        update_option(self::OPTION, $settings, true);
    }
}
