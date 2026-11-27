<?php

/**
 * Removes the plugin's settings, price history table and per-product EU fields.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

delete_option('hdeu_settings');
delete_option('hdeu_db_version');
wp_clear_scheduled_hook('hdeu_prune_history');

$hdeu_table = $wpdb->prefix . 'hdeu_price_history';
$wpdb->query($wpdb->prepare('DROP TABLE IF EXISTS %i', $hdeu_table)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange -- dropping this plugin's own table on uninstall.

foreach (array('_hdeu_manufacturer', '_hdeu_responsible', '_hdeu_safety', '_hdeu_hide_guarantee') as $hdeu_key) {
    delete_post_meta_by_key($hdeu_key);
}
