<?php

namespace htrxuan\hdeu;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Price history for the "lowest prior price" notice (EU Omnibus Directive).
 *
 * Every time a product or variation is saved with a different active price, one row is
 * added: (product_id, price, recorded_at). A row's price applies from its recorded_at until
 * the next row. That covers manual edits, bulk edits, imports that save through the product
 * API, and WooCommerce's own scheduled sales (its cron saves the product when a sale starts
 * or ends).
 *
 * All queries use $wpdb->prepare(); ids are cast to int and prices to float before storage.
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- a dedicated table this plugin owns; results are per-request and cheap.
class HDEU_Price_History
{

    const DB_VERSION        = '1';
    const DB_VERSION_OPTION = 'hdeu_db_version';
    const KEEP_DAYS         = 400;

    public static function table()
    {
        global $wpdb;
        return $wpdb->prefix . 'hdeu_price_history';
    }

    public static function install()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $table   = self::table();
        dbDelta("CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            price decimal(19,4) NOT NULL,
            recorded_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY product_time (product_id, recorded_at)
        ) {$charset};");
        update_option(self::DB_VERSION_OPTION, self::DB_VERSION, false);
    }

    public static function maybe_install()
    {
        if (get_option(self::DB_VERSION_OPTION) !== self::DB_VERSION) {
            self::install();
        }
    }

    /** @return array<int, array{price: float, time: int}> oldest first */
    public static function rows($product_id)
    {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            'SELECT price, recorded_at FROM %i WHERE product_id = %d ORDER BY recorded_at ASC, id ASC',
            self::table(),
            (int) $product_id
        ), ARRAY_A);
        $out = array();
        foreach ((array) $rows as $r) {
            $out[] = array('price' => (float) $r['price'], 'time' => (int) strtotime($r['recorded_at'] . ' UTC'));
        }
        return $out;
    }

    /**
     * Adds a row when the product's active price differs from the last recorded one.
     *
     * @param \WC_Product $product
     * @param int|null    $now Unix time (for tests).
     */
    public static function record($product, $now = null)
    {
        if (!$product instanceof \WC_Product || !$product->get_id() || $product->is_type(array('variable', 'grouped', 'external'))) {
            return false;
        }
        $price = $product->get_price('edit');
        if ('' === $price || null === $price || !is_numeric($price)) {
            return false;
        }
        $price = round((float) $price, 4);
        $rows  = self::rows($product->get_id());
        $last  = $rows ? end($rows) : null;
        if ($last && abs($last['price'] - $price) < 0.00005) {
            return false;
        }
        global $wpdb;
        $wpdb->insert(self::table(), array(
            'product_id'  => (int) $product->get_id(),
            'price'       => $price,
            'recorded_at' => gmdate('Y-m-d H:i:s', null === $now ? time() : (int) $now),
        ), array('%d', '%f', '%s'));
        return true;
    }

    /**
     * When did the current price start? The sale start date if WooCommerce has one,
     * otherwise the time the current price was first recorded.
     */
    public static function current_price_since($product, $rows)
    {
        $from = $product->get_date_on_sale_from('edit');
        if ($from && $from->getTimestamp() <= time()) {
            return $from->getTimestamp();
        }
        if (!$rows) {
            return null;
        }
        $current = end($rows)['price'];
        $since   = end($rows)['time'];
        for ($i = count($rows) - 1; $i >= 0; $i--) {
            if (abs($rows[$i]['price'] - $current) >= 0.00005) {
                break;
            }
            $since = $rows[$i]['time'];
        }
        return $since;
    }

    /**
     * Lowest price that applied during the $days days before the current price started.
     * Falls back to the regular price when there is no earlier history.
     *
     * @return float|null
     */
    public static function lowest_prior_price($product, $days)
    {
        $rows  = self::rows($product->get_id());
        $since = self::current_price_since($product, $rows);
        $lowest = null;
        if (null !== $since) {
            $window_start = $since - DAY_IN_SECONDS * (int) $days;
            $count = count($rows);
            for ($i = 0; $i < $count; $i++) {
                $start = $rows[$i]['time'];
                $end   = $i + 1 < $count ? $rows[$i + 1]['time'] : PHP_INT_MAX;
                if ($start < $since && $end > $window_start) {
                    $lowest = null === $lowest ? $rows[$i]['price'] : min($lowest, $rows[$i]['price']);
                }
            }
        }
        if (null === $lowest) {
            $regular = $product->get_regular_price('edit');
            $lowest  = is_numeric($regular) ? (float) $regular : null;
        }
        return $lowest;
    }

    /** Daily clean-up: drop rows older than KEEP_DAYS, but keep each product's newest old row. */
    public static function prune()
    {
        global $wpdb;
        $cutoff = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS * self::KEEP_DAYS);
        $wpdb->query($wpdb->prepare(
            'DELETE h FROM %i h
             JOIN (SELECT product_id, MAX(recorded_at) AS keep_at FROM %i WHERE recorded_at < %s GROUP BY product_id) k
               ON h.product_id = k.product_id
             WHERE h.recorded_at < k.keep_at',
            self::table(),
            self::table(),
            $cutoff
        ));
    }

    public static function delete_for_product($product_id)
    {
        global $wpdb;
        $wpdb->delete(self::table(), array('product_id' => (int) $product_id), array('%d'));
    }
}
