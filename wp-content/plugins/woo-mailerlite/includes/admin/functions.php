<?php
/**
 * Get settings page url
 *
 * @return string
 */
function woo_ml_get_settings_page_url() {
    return admin_url( 'admin.php?page=wc-settings&tab=integration&section=mailerlite' );
}

/**
 * Get complete integration setup url
 *
 * @return string
 */
function woo_ml_get_complete_integration_setup_url() {
    return add_query_arg( 'woo_ml_action', 'setup_integration', woo_ml_get_settings_page_url() );
}

/**
 * Update ignore product list
 *
 * @return boolean
 */
function woo_ml_update_ignore_product_list( $products ) {

    $settings = mailerlite_wp_get_shop_settings_from_db();

    if ($settings !== false) {

        $resubscribe = (woo_ml_get_option('resubscribe', 'no') == 'yes') ? 1 : 0;

        if (isset($settings->settings->resubscribe)) {

            $resubscribe = $settings->settings->resubscribe;
        }

        $results = mailerlite_wp_set_consumer_data(
            woo_ml_get_option('consumer_key'),
            woo_ml_get_option('consumer_secret'),
            woo_ml_get_option('group'),
            $resubscribe,
            $products );

        return $results;
    }

    return false;
}

/**
 * Update ignore product list in ml_data table
 *
 * @return mixed
 */
function woo_ml_update_data( $products ) {

    global $wpdb;

    $table = $wpdb->prefix . 'ml_data';

    $tableCreated = get_option('ml_data_table');

    if ($tableCreated != 1) {

        woo_create_mailer_data_table();
    }

    $updateQuery = $wpdb->prepare("
                INSERT INTO $table (data_name, data_value) VALUES ('products', %s) ON DUPLICATE KEY UPDATE data_value = %s
                ", json_encode( $products ), json_encode( $products ) );

    return $wpdb->query($updateQuery);
}

/**
 * Save ignored products to WooCommerce Integration and ml_data table
 *
 */
function woo_ml_save_local_ignore_products( $products ) {

    $ignore_map = woo_ml_remap_list( $products );

    if ( woo_ml_update_ignore_product_list( $ignore_map ) === true ) {

        // save updated ignore product list to WooCommerce Integration
        $settings = get_option('woocommerce_mailerlite_settings');

        if ( ! isset( $settings['ignore_product_list'] ) ) {
            $settings['ignore_product_list'] = array();
        }

        $settings['ignore_product_list'] = $ignore_map;

        update_option('woocommerce_mailerlite_settings', $settings);

        //save product ignore list to ml_data
        woo_ml_update_data( $products );
    }
}

/**
 * Remove product from product ignore list for ml_data
 *
 * @return array
 */
function woo_ml_remove_product_from_list( $products, $remove_list ) {

    return array_filter( $products, function( $k ) use ( $remove_list ) {
        return ! in_array( $k, $remove_list );
    }, ARRAY_FILTER_USE_KEY);
}