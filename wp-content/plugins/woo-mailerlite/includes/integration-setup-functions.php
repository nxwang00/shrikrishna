<?php
/**
 * Check if order tracking setup was finished
 */
function woo_ml_integration_setup_completed() {

    $integration_setup = get_option( 'woo_ml_integration_setup', false );

    return ( '1' == $integration_setup ) ? true : false;
}

/**
 * Mark order tracking setup as completed
 */
function woo_ml_complete_integration_setup() {
    add_option( 'woo_ml_integration_setup', true );
}

/**
 * Revoke order tracking setup completion
 */
function woo_ml_revoke_integration_setup() {
    delete_option( 'woo_ml_integration_setup' );
}

/**
 * Setup MailerLite integration
 */
function woo_ml_setup_integration() {
    $setup_custom_fields = woo_ml_setup_integration_custom_fields();

    if ($setup_custom_fields)
        woo_ml_complete_integration_setup();
}

/**
 * Setup Integration Custom Fields
 *
 * - Get existing custom fields via API
 * - Check if our custom fields were already created
 * - Create missing custom fields
 *
 * @return bool
 */
function woo_ml_setup_integration_custom_fields($fields = null) {

    $api_type = (int) get_option('woo_mailerlite_platform', 1);

    $ml_fields = mailerlite_wp_get_custom_fields();

    if (! $fields) {

        $fields = woo_ml_get_integration_custom_fields($api_type);
    }

    if (is_array($ml_fields)) {

        foreach ($ml_fields as $ml_field) {

            $ml_field = (array) $ml_field;

            if (isset($ml_field['key']) && isset($fields[$ml_field['key']])) {

                unset($fields[$ml_field['key']]);
            }
        }

        if ( sizeof($fields) > 0 ) {
            foreach ($fields as $field_data) {

                mailerlite_wp_create_custom_field($field_data);
            }
        }
    }

    return true;
}

/**
 * Get integration custom fields
 *
 * @return array
 */
function woo_ml_get_integration_custom_fields($api_type) {

    if ($api_type !== ApiType::REWRITE) {

        return [
            'woo_orders_count' => [
                'title' => 'Woo Orders Count',
                'type' => 'NUMBER'
            ],
            'woo_total_spent' => [
                'title' => 'Woo Total Spent',
                'type' => 'NUMBER'
            ],
            'woo_last_order' => [
                'title' => 'Woo Last Order',
                'type' => 'DATE'
            ],
            'woo_last_order_id' => [
                'title' => 'Woo Last Order ID',
                'type' => 'NUMBER'
            ],
        ];
    } else {

        $shopUrl = home_url();
        $shopKey = preg_replace('/[^A-Za-z0-9 ]/', '', $shopUrl);

        $shop_name = get_bloginfo( 'name' );

        if (empty($shop_name)) {
            $shop_name = $shopUrl;
        }

        return [
            $shopKey . '_total_spent' => [
                'key' => $shopKey,
                'name' => $shop_name,
                'title' => 'Total spent',
                'type' => 'number',
            ],
            $shopKey . '_orders_count' => [
                'key' => $shopKey,
                'name' => $shop_name,
                'title' => 'Orders count',
                'type' => 'number',
            ],
            $shopKey. '_accepts_marketing' => [
                'key' => $shopKey,
                'name' => $shop_name,
                'title' => 'Accepts marketing',
                'type' => 'number',
            ],
        ];
    }
}

function woo_ml_old_integration()
{
    return ! get_option('new_plugin_enabled');
}

function woo_ml_shop_not_active()
{
    return get_option('ml_shop_not_active');
}
