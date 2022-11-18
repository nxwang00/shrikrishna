<?php

/**
 * Functions inside this file are being used in different MailerLite related WordPress plugins
 */

if ( ! function_exists( 'mailerlite_wp_api_key_validation') ) :
    /**
     * Check MailerLite API connection
     *
     * @param $api_key
     * @return bool
     */
    function mailerlite_wp_api_key_validation( $api_key ) {

        if ( empty( $api_key ) )
            return false;

        try {

            $mailerliteClient = new PlatformAPI( $api_key );
            
            $result = $mailerliteClient->validateAccount();

            if ( isset( $result ) && ! isset( $result->errors ) && $mailerliteClient->responseCode() !== 401 && $result !== false ) {
                $settings = get_option('woocommerce_mailerlite_settings');
                $settings['api_key'] = $api_key;
                $settings['api_status'] = true;

                update_option('ml_account_authenticated', true);
                update_option('woo_ml_key', $api_key);

                if ($mailerliteClient->getApiType() === ApiType::CURRENT) {
                    $settings['double_optin'] = $result->double_optin;
                    update_option('double_optin', $result->double_optin);
                }

                if ( (int) get_option( 'woo_mailerlite_platform', 1) !== $mailerliteClient->getApiType() ) {
                    mailerlite_reset_shop();
                }

                $shop_id = get_option('woo_ml_shop_id', false);

                if ($shop_id !== false) {

                    $verify_shop = $mailerliteClient->getShop($shop_id);

                    if ($verify_shop === false) {

                        mailerlite_reset_shop();

                        delete_option('woo_ml_shop_id');
                    }
                }

                update_option( 'woo_mailerlite_platform', $mailerliteClient->getApiType() );

                if ($mailerliteClient->getApiType() === ApiType::REWRITE) {

                    update_option('account_id', $result->id);
                    update_option('account_subdomain', '');

                    $settings['double_optin'] = $result->double_optin ? 'yes' : 'no';
                    update_option('double_optin', $result->double_optin ? 'yes' : 'no');
                }

                update_option('woocommerce_mailerlite_settings', $settings);
                return true;
            }else{

                switch ($mailerliteClient->responseCode()) {
                    case 401 :
                        set_transient( 'ml-admin-notice-invalid-key', 'Invalid API Key', 5 );
                        break;
                    case 0 :
                        set_transient( 'ml-admin-notice-invalid-key', $mailerliteClient->getResponseBody(), 5 );
                        break;
                    default:
                        set_transient( 'ml-admin-notice-invalid-key', 'Error: ' . $mailerliteClient->responseCode() , 5 );
                }
            }

        } catch (Exception $e) {
            return false;
        }

        return false;
    }
endif;

if ( ! function_exists( 'mailerlite_wp_api_key_exists') ) :
    /**
     * Check wether API key exists or not
     *
     * @return bool
     */
    function mailerlite_wp_api_key_exists() {

        if ( defined( 'MAILERLITE_WP_API_KEY' ) && ! empty( MAILERLITE_WP_API_KEY ) )
            return true;

        return false;
    }
endif;

if ( ! function_exists( 'mailerlite_wp_get_groups') ) :
    /**
     * Get groups from API
     *
     * @param $api_key
     * @return array|bool
     */
    function mailerlite_wp_get_groups() {

        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        $groups = array();

        try {
            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            if ( $mailerliteClient->getApiType() === ApiType::INVALID ) {

                return false;
            }

            $results = $mailerliteClient->getGroups();

            if( $results ) {

                if (is_array($results) && ! isset($results->error->message)) {
                    if (sizeof($results) > 0) {
                        foreach ($results as $result) {
                            $groups[] = (array) $result;
                        }
                    }
                }

                return $groups;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }
endif;

if ( ! function_exists( 'mailerlite_wp_get_subscriber_by_email') ) :
    /**
     * Get subscriber from API by email
     *
     * @param $email
     * @return mixed
     */
    function mailerlite_wp_get_subscriber_by_email( $email ) {

        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        if ( empty( $email ) )
            return false;

        try {
            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $subscriber = $mailerliteClient->searchSubscriber( $email );

            if ( isset( $subscriber->id ) ) {
                return $subscriber;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

if ( ! function_exists( 'mailerlite_wp_update_subscriber') ) :
    /**
     * Update subscriber via API
     *
     * @param $subscriber_email
     * @param array $subscriber_data
     * @return mixed
     */
    function mailerlite_wp_update_subscriber( $subscriber_email, $subscriber_data = array() ) {

        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        if ( empty( $subscriber_email ) )
            return false;
        try {

            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $subscriber_updated = $mailerliteClient->updateSubscriber( $subscriber_email, $subscriber_data ); // returns updated subscriber
            if ( isset( $subscriber_updated->id ) ) {
                return $subscriber_updated;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

if (! function_exists( 'mailerlite_wp_sync_customer')) :
    function mailerlite_wp_sync_customer($email, $fields)
    {
        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        if ( empty( $email ) )
            return false;
        try {

            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $customer_id = '';

            $store = home_url();

            $subscriber_updated = $mailerliteClient->syncCustomer( $store, $customer_id, $email, $fields );
            if ( isset( $subscriber_updated->updated_subscriber) ) {
                return $subscriber_updated;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

if (! function_exists('mailerlite_wp_sync_ecommerce_customer')) :
    function mailerlite_wp_sync_ecommerce_customer($customer_id)
    {

        $shop = get_option('woo_ml_shop_id', false);

        if ($shop === false) {

            return false;
        }

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ($mailerliteClient->getApiType() == ApiType::REWRITE) {

            $customer = new WC_Customer( $customer_id );

            $email = $customer->get_email();

            $fields = [
                'name'      => $customer->get_first_name(),
                'last_name' => $customer->get_last_name(),
                'company'   => $customer->get_billing_company(),
                'city'      => $customer->get_billing_city(),
                'z_i_p'     => $customer->get_billing_postcode(),
                'state'     => $customer->get_billing_state(),
                'country'   => $customer->get_billing_country(),
                'phone'     => $customer->get_billing_phone()
            ];

            $mailerliteClient->syncCustomer( $shop, $customer_id, $email, $fields );
        }
    }
endif;

if ( ! function_exists( 'mailerlite_wp_set_double_optin') ) :
    /**
     * Set MailerLite double opt in status
     *
     * @param bool $status
     * @return bool
     */
    function mailerlite_wp_set_double_optin( $status ) {

        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        try {

            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            if ($mailerliteClient->getApiType() === ApiType::CURRENT) {

                $result = $mailerliteClient->setDoubleOptin( $status );

                if (isset($result->enabled)) {
                    $double_optin = $result->enabled == true ? 'yes' : 'no';
                    update_option('double_optin', $double_optin);

                    return $result->enabled;
                } else {
                    return false;
                }
            }

            if ($mailerliteClient->getApiType() === ApiType::REWRITE) {

                $doi = $mailerliteClient->getDoubleOptin();

                if ($doi !== $status) {
                    $result = $mailerliteClient->setDoubleOptin($status);

                    $double_optin = $result == true ? 'yes' : 'no';

                    update_option('double_optin', $double_optin);

                    return $result;
                }

                return $doi;
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

if ( ! function_exists( 'mailerlite_wp_create_custom_field') ) :
    /**
     * Create custom field in MailerLite
     *
     * @param array $field_data
     * @return bool
     */
    function mailerlite_wp_create_custom_field( $field_data ) {

        if ( ! mailerlite_wp_api_key_exists() ) {

            return false;
        }

        if ( ! isset( $field_data['title'] ) || ! isset( $field_data['type'] ) ) {

            return false;
        }

        try {

            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $temp_name = false;

            if (isset($field_data['key'])) {

                $temp_name = $field_data['key'] . ' ' . $field_data['title'];
            }

            $field_added = $mailerliteClient->createField( $temp_name ?: $field_data['title'], $field_data['type'] );

            if ( isset( $field_added->id ) ) {

                if ($mailerliteClient->getApiType() === ApiType::REWRITE) {

                    $mailerliteClient->updateField($field_added->id, $field_data['name'] . ' ' . $field_data['title']);
                }

                return $field_added;
            } else {
                return false;
            }

        } catch (Exception $e) {
            return false;
        }
    }
endif;

if ( ! function_exists( 'mailerlite_wp_get_custom_fields') ) :
    /**
     * Get custom fields from MailerLite
     *
     * @param array $args
     * @return mixed
     */
    function mailerlite_wp_get_custom_fields( $args = array() ) {

        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        try {

            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            return $mailerliteClient->getFields();

        } catch (Exception $e) {

            return false;
        }
    }
endif;

/**
 * Sends to api shop data needed to make back and forth connection with woo commerce
 * Api returns account id and subdomain used to for universal script
 *
 * @param string $consumerKey
 * @param string $consumerSecret
 * @param string $apiKey
 *
 * @return array|bool
 */
if (! function_exists('mailerlite_wp_set_consumer_data') ) :
    function mailerlite_wp_set_consumer_data($consumerKey, $consumerSecret, $group, $resubscribe, $ignoreList = [], $create_segments = false) {
        if (!mailerlite_wp_api_key_exists())
            return false;

        try {
            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $store = home_url();
            $currency = get_option('woocommerce_currency');

            if (empty($group)) {
                return ['errors' => 'MailerLite - WooCommerce integration: Please select a group.'];
            }

            if (strpos($store, 'https://') !== false) {

                $shop_name      = get_bloginfo( 'name' );
                $shop_id        = get_option( 'woo_ml_shop_id', false);
                $popups_enabled = !get_option('mailerlite_popups_disabled');

                if (empty($shop_name)) {
                    $shop_name = $store;
                }

                if ( $mailerliteClient->getApiType() === ApiType::REWRITE && $shop_id === false) {

                    $shops = $mailerliteClient->getShops();

                    foreach ($shops as $shop) {

                        if ($shop->url == $store) {

                            $shop_id = $shop->id;
                            update_option('woo_ml_shop_id', $shop->id);
                            break;
                        }
                    }
                }

                $result = $mailerliteClient->setConsumerData( $consumerKey, $consumerSecret, $store, $currency, $group, $resubscribe, $ignoreList, $create_segments, $shop_name, $shop_id, $popups_enabled);

                if ( $mailerliteClient->getApiType() === ApiType::CURRENT ) {

                    if (isset($result->account_id) && (isset($result->account_subdomain))) {

                        update_option('account_id', $result->account_id);
                        update_option('account_subdomain', $result->account_subdomain);
                        update_option('new_plugin_enabled', true);
                        update_option('ml_shop_not_active', false);
                    } elseif (isset($result->errors)) {
                        return ['errors' => $result->errors];
                    }
                }

                if ( $mailerliteClient->getApiType() === ApiType::REWRITE ) {

                    if (isset($result->id) && ($mailerliteClient->responseCode() === 200 || $mailerliteClient->responseCode() === 201)) {

                        update_option('woo_ml_shop_id', $result->id);
                        update_option('new_plugin_enabled', true);
                        update_option('ml_shop_not_active', false);
                        update_option('mailerlite_popups_disabled', $result->enable_popups);
                    } elseif (isset($result->errors)) {

                        return ['errors' => $result->errors];
                    } elseif ($result === false) {

                        $response = json_decode($mailerliteClient->getResponseBody());

                        $message  = $response->message ?? 'Unknown error.';

                        return ['errors' => $message];
                    }
                }

                return true;
            } else {
                return ['errors' => 'MailerLite - WooCommerce integration: Your shop url does not have the right security protocol'];
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

/**
 * Reset shop sync on platform change
 *
 * @return bool|void
 */
if ( ! function_exists('mailerlite_reset_shop') ) :
    function mailerlite_reset_shop()
    {

        woo_ml_reset_tracked_categories(false);
        woo_ml_reset_tracked_products(false);
        woo_ml_reset_tracked_orders_process();
    }
endif;

/**
 * Sends completed order data to api to be evaluated and saved and/if trigger automations
 *
 * @param array $order_data
 *
 * @return bool|void
 */
if ( ! function_exists( 'mailerlite_wp_send_order') ) :
    function mailerlite_wp_send_order($order_data)
    {
        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        try {
            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            if ( $mailerliteClient->getApiType() == ApiType::CURRENT ) {

                $store = home_url();

                $shop_url                = site_url();
                $order_data['order_url'] = $shop_url."/wp-admin/post.php?post=".$order_data['order']['id']."&action=edit";

                $result = $mailerliteClient->saveOrder($store, $order_data);

                if (isset($result->deactivate) && $result->deactivate) {
                    woo_ml_deactivate_woo_ml_plugin(true);
                }
            }

            if ( $mailerliteClient->getApiType() == ApiType::REWRITE ) {

                $shop = get_option('woo_ml_shop_id', false);

                if ($shop === false) {

                    return false;
                }

                $subscribe = woo_ml_order_customer_subscribe( $order_data['order']['id'] );

                $cart_details = woo_ml_get_cart_details( $order_data['order']['id'] );

                $customer_data = woo_ml_get_customer_data_from_order( $order_data['order']['id'] );
                $subscriber_fields = woo_ml_get_subscriber_fields_from_customer_data( $customer_data );

                //rename zip key for API
                $zip = $subscriber_fields['zip'] ?? '';
                unset($subscriber_fields['zip']);
                $subscriber_fields['z_i_p'] = $zip;

                $order_customer = [
                    'email' => $order_data['order']['billing']['email'],
                    'create_subscriber' => $subscribe,
                    'accepts_marketing' => $subscribe,
                    'subscriber_fields' => $subscriber_fields
                ];

                if (strval($cart_details['customer_id']) !== "0") {

                    $order_customer['resource_id'] = (string) $cart_details['customer_id'];
                }

                $order_cart = [
                    'items' => $cart_details['items']
                ];

                $mailerliteClient->syncOrder( $shop, $order_data['order']['id'], $order_customer, $order_cart, $order_data['order']['status'], $cart_details['total_price'], $cart_details['created_at']);

                if ($mailerliteClient->responseCode() === 201 || $mailerliteClient->responseCode() === 200) {

                    woo_ml_order_data_submitted($order_data['order']['id']);

                    // Delete existing cart (abandoned checkout)
                    if ($order_data['checkout_id'] !== null) {

                        $mailerliteClient->deleteOrder($shop, $order_data['checkout_id']);
                    }
                }

                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

/**
 * Get triggered on deactivate plugin event. Sends store name to api
 * to toggle its active status
 *
 * @param bool $active_state
 *
 * @return bool|void
 */
if ( ! function_exists( 'mailerlite_wp_toggle_shop_connection') ) :
    function mailerlite_wp_toggle_shop_connection($active_state)
    {
        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        try {
            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $store = home_url();

            $shop_id = get_option('woo_ml_shop_id', false);

            if ($mailerliteClient->getApiType() === ApiType::REWRITE && $shop_id === false) {

                return false;
            }

            $shop_name = get_bloginfo( 'name' );;

            return $mailerliteClient->toggleShop($store, $active_state, $shop_id, $shop_name);

        } catch (Exception $e) {

            return false;
        }
    }
endif;

/**
 * Sending cart data on cart update
 *
 * @param bool $cart_data
 *
 * @return bool|void
 */
if (! function_exists('mailerlite_wp_send_cart')) :
    function mailerlite_wp_send_cart($cart_data) {

        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        try {

            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            if ($mailerliteClient->getApiType() == ApiType::CURRENT ) {

                $shop = home_url();

                $result = $mailerliteClient->sendCart($shop, $cart_data);

                if (isset($result->deactivate) && $result->deactivate) {
                    woo_ml_deactivate_woo_ml_plugin(true);
                }
            }

            if ( $mailerliteClient->getApiType() == ApiType::REWRITE ) {

                $shop = get_option('woo_ml_shop_id', false);

                if ($shop === false) {

                    return false;
                }

                $order_customer = [
                    'email' => $cart_data['email'],
                    'accepts_marketing' => $cart_data['subscribe'] ?? false,
                    'create_subscriber' => $cart_data['subscribe'] ?? false
                ];

                $order_cart = [
                    'resource_id' => $cart_data['id'],
                    'checkout_url' => $cart_data['abandoned_checkout_url'],
                    'items' => []
                ];

                foreach ($cart_data['line_items'] as $item) {

                    $product = wc_get_product($item['product_id']);

                    $order_cart['items'][] = [
                        'product_resource_id' => (string) $item['product_id'],
                        'variant' => $product->get_name(),
                        'quantity' => (int) $item['quantity'],
                        'price' => floatval($product->get_price('edit')),
                    ];
                }

                // check if order exists
                $result = $mailerliteClient->fetchOrder( $shop, $cart_data['id'] );

                if ($mailerliteClient->responseCode() === 404) {

                    // no order exists, create new one
                    $result = $mailerliteClient->syncOrder( $shop, $cart_data['id'], $order_customer, $order_cart, 'pending', $cart_data['total_price'], $cart_data['created_at'] );

                    if ($result->cart) {

                        $mailerliteClient->updateCart( $shop, $result->cart->id, $cart_data['abandoned_checkout_url'], $cart_data['total_price'], $cart_data['id'] );
                    }
                }else{

                    if ($order_customer['email'] !== $result->customer->email) {

                        $deleted_order = $mailerliteClient->deleteOrder( $shop, $cart_data['id']);

                        if ($deleted_order) {

                            $result = $mailerliteClient->syncOrder( $shop, $cart_data['id'], $order_customer, $order_cart, 'pending', $cart_data['total_price'], $cart_data['created_at'] );
                        }
                    }else{

                        if ($result->customer->id) {

                            $mailerliteClient->updateCustomer( $shop, $result->customer->id, $order_customer['email'], $order_customer['accepts_marketing'], $order_customer['create_subscriber'] );
                        }

                        $mailerliteClient->updateOrder( $shop, $cart_data['id'], 'pending', $cart_data['total_price'] );
                    }

                    if ($result->cart) {

                        $mailerliteClient->updateCart( $shop, $result->cart->id, $cart_data['abandoned_checkout_url'], $cart_data['total_price'], $cart_data['id'] );
                    }

                    $mailerliteClient->replaceCartItems( $shop, $cart_data['id'], $order_cart['items']);
                }
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

/**
 * Sending order data on creation of order and/or order status change to processing
 * @param array $data
 * @param string $event
 *
 * @return bool
 */
if(! function_exists('mailerlite_wp_add_subscriber_and_save_order')) :
    function mailerlite_wp_add_subscriber_and_save_order($data, $event)
    {
        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        try {
            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $shop_id = '';

            $shop_url = site_url();
            $data['shop_url'] = home_url();
            $data['order_url'] = $shop_url."/wp-admin/post.php?post=".$data['order_id']."&action=edit";
            //order_created case also takes care of processing sub if they have ticked the box to
            //receive newsletters

            if ($mailerliteClient->getApiType() === ApiType::CURRENT) {
                if ($event === 'order_created') {
                    $result = $mailerliteClient->sendSubscriberData($shop_id, $data);

                    if (isset($result->added_to_group) && isset($result->updated_fields)) {
                        return $result;
                    } elseif (isset($result->deactivate) && $result->deactivate) {
                        woo_ml_deactivate_woo_ml_plugin(true);

                        return false;
                    } else {
                        return false;
                    }
                } else {

                    $result = $mailerliteClient->sendOrderProcessing($shop_id, $data);
                    if (isset($result->deactivate) && $result->deactivate) {
                        woo_ml_deactivate_woo_ml_plugin(true);
                    }

                    if ($event === 'order_processing') {
                        woo_ml_send_completed_order($data['order_id']);
                    }

                    return true;
                }
            }

        } catch (Exception $e) {
            return false;
        }
    }
endif;

/**
 * API call to get all shop settings from the MailerLite side
 *
 * @return array|bool
 */
if (! function_exists('mailerlite_wp_get_shop_settings_from_db')) :
    function mailerlite_wp_get_shop_settings_from_db()
    {
        if ( ! mailerlite_wp_api_key_exists() )
            return false;

        try {
            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            if ($mailerliteClient->getApiType() === ApiType::CURRENT) {
                $result = $mailerliteClient->getShopSettings(home_url());
                if ($result) {

                    if (isset($result->deactivate) && $result->deactivate) {
                        $warning_msg = __( 'Your shop appears to be deactivated, please save the configuration to re-activate.', 'woo-mailerlite' );

                        add_action('admin_notices', function() use ($warning_msg) {

                            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( 'notice notice-warning is-dismissible' ), esc_html( $warning_msg ) );
                        });
                    } else {
                        return $result;
                    }
                } else {
                    return false;
                }
            }

            if ($mailerliteClient->getApiType() === ApiType::REWRITE) {

                return true;
            }
        } catch (Exception $e) {
            return false;
        }
    }
endif;

if (! function_exists('mailerlite_wp_sync_product')):
    function mailerlite_wp_sync_product( $product_id )
    {

        if ( did_action('woocommerce_update_product') === 1) {

            mailerlite_sync_product($product_id);
        }
    }
endif;

if (! function_exists('mailerlite_sync_product')):
    function mailerlite_sync_product( $product_id ) {

        $shop = get_option('woo_ml_shop_id', false);

        if ($shop === false) {

            return false;
        }

        $product = wc_get_product($product_id);

        $productID    = $product->get_id();
        $productName  = $product->get_name() ?: 'Untitled product';
        $productPrice = floatval($product->get_price('edit'));
        $productImage = woo_ml_product_image($product);
        $productURL   = $product->get_permalink();

        $categories   = get_the_terms( $productID, 'product_cat');

        $productCategories = [];

        foreach ( $categories as $category ) {

            $productCategories[] = (string) $category->term_id;
        }

        $exclude_automation = get_post_meta( $productID, '_woo_ml_product_ignored', true) === "1";

        $mailerliteClient = new PlatformAPI(MAILERLITE_WP_API_KEY);

        if ( $mailerliteClient->getApiType() !== ApiType::REWRITE ) {

            return false;
        }

        $result = $mailerliteClient->syncProduct(
            $shop,
            $productID,
            $productName,
            $productPrice,
            $exclude_automation,
            $productURL,
            $productImage,
            []
        );

        if ( $result !== false && ($mailerliteClient->responseCode() == 201 || $mailerliteClient->responseCode() == 200) ) {

            if (!woo_ml_product_tracking_completed($productID))
                woo_ml_complete_product_tracking($productID);

            $mailerliteClient->replaceProductCategories($shop, $productID, $productCategories);

            return true;
        }

        return false;
    }
endif;

if (! function_exists('mailerlite_wp_sync_product_category')):
    function mailerlite_wp_sync_product_category( $category_id )
    {

        $category = get_term( $category_id );

        if (! isset($category->term_id))
            return;

        $shop = get_option('woo_ml_shop_id', false);

        if ($shop === false) {

            return;
        }

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ( $mailerliteClient->getApiType() !== ApiType::REWRITE ) {

            return;
        }

        $result = $mailerliteClient->syncCategory( $shop, $category->term_id, $category->name );

        if ($mailerliteClient->responseCode() === 200 || $mailerliteClient->responseCode() === 201 ) {

            woo_ml_complete_category_tracking($result->resource_id, $result->id);
        }
    }
endif;

if (! function_exists('mailerlite_wp_delete_product_category')):
    function mailerlite_wp_delete_product_category( $category_id )
    {

        $shop = get_option('woo_ml_shop_id', false);

        if ($shop === false) {

            return false;
        }

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ( $mailerliteClient->getApiType() !== ApiType::REWRITE ) {

            return false;
        }

        $mailerliteClient->deleteCategory( $shop, $category_id );
    }
endif;

/**
 * Call to handle product, order and customer trash event
 *
 */
if (! function_exists('mailerlite_wp_sync_post_trash')):
    function mailerlite_wp_sync_post_trash( $post_id )
    {

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ($mailerliteClient->getApiType() === ApiType::REWRITE ) {

            $post_type = get_post_type($post_id);

            switch ($post_type) {
                case 'product':
                case 'shop_order':
                    // Skip trash
                    break;
                default:
                    // skip
                    break;
            }
        }
    }
endif;

/**
 * Call to handle product, order and customer delete event
 *
 */
if (! function_exists('mailerlite_wp_sync_post_delete')):
    function mailerlite_wp_sync_post_delete( $post_id )
    {

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ($mailerliteClient->getApiType() === ApiType::REWRITE ) {

            $post_type = get_post_type($post_id);

            $shop = get_option('woo_ml_shop_id', false);

            if ($shop === false) {

                return false;
            }

            switch ($post_type) {
                case 'shop_order':

                    $mailerliteClient->deleteOrder( $shop, $post_id );
                    break;
                case 'product':

                    $mailerliteClient->deleteProduct( $shop, $post_id );
                    break;
                default:
                    // skip
                    break;
            }
        }
    }
endif;

/**
 * Call to handle product, order and customer restore event
 *
 */
if (! function_exists('mailerlite_wp_sync_post_restore')):
    function mailerlite_wp_sync_post_restore( $post_id )
    {

        $post_type = get_post_type( $post_id );

        switch ( $post_type ) {
            case 'product':
            case 'shop_order':
                // Skip, nothing to restore
                break;
            default:
                // skip
                break;
        }
    }
endif;

/**
 * Call to handle order update event
 *
 */
if (! function_exists('mailerlite_wp_sync_order')):
    function mailerlite_wp_sync_order( $order_id, $post )
    {

        $order = wc_get_order( $order_id );

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ( $mailerliteClient->getApiType() == ApiType::REWRITE ) {

            $shop = get_option('woo_ml_shop_id', false);

            if ($shop === false) {

                return false;
            }

            $subscribe = woo_ml_order_customer_subscribe($order_id );

            $cart_details = woo_ml_get_cart_details( $order_id );

            $customer_data = woo_ml_get_customer_data_from_order( $order_id );
            $subscriber_fields = woo_ml_get_subscriber_fields_from_customer_data( $customer_data );

            //rename zip key for API
            $zip = $subscriber_fields['zip'] ?? '';
            unset($subscriber_fields['zip']);
            $subscriber_fields['z_i_p'] = $zip;

            $order_customer = [
                'email' => $order->get_billing_email(),
                'create_subscriber' => $subscribe,
                'accepts_marketing' => $subscribe,
                'subscriber_fields' => $subscriber_fields
            ];

            if (strval($cart_details['customer_id']) !== "0") {

                $order_customer['resource_id'] = (string) $cart_details['customer_id'];
            }

            $order_cart = [
                'items' => $cart_details['items']
            ];

            $mailerliteClient->syncOrder( $shop, $order_id, $order_customer, $order_cart, $order->get_status(), $order->get_total(), date('Y-m-d h:m:s', strtotime($order->get_date_created())) );

            if ($mailerliteClient->responseCode() === 201) {

                woo_ml_complete_order_data_submitted($order_id);

                return true;
            }
        }
    }
endif;

/**
 * Call to handle bulk order update event
 *
 */
if (! function_exists('mailerlite_wp_sync_bulk_order')):
    function mailerlite_wp_sync_bulk_order( $action, $order_ids )
    {

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ( $mailerliteClient->getApiType() == ApiType::REWRITE ) {

            foreach ($order_ids as $order_id) {

                $order = wc_get_order($order_id);

                $shop = get_option('woo_ml_shop_id', false);

                if ($shop === false) {

                    return false;
                }

                $subscribe = woo_ml_order_customer_subscribe($order_id);

                $cart_details = woo_ml_get_cart_details($order_id);

                $customer_data = woo_ml_get_customer_data_from_order( $order_id );
                $subscriber_fields = woo_ml_get_subscriber_fields_from_customer_data( $customer_data );

                //rename zip key for API
                $zip = $subscriber_fields['zip'] ?? '';
                unset($subscriber_fields['zip']);
                $subscriber_fields['z_i_p'] = $zip;

                $order_customer = [
                    'email'             => $order->get_billing_email(),
                    'create_subscriber' => $subscribe,
                    'accepts_marketing' => $subscribe,
                    'subscriber_fields' => $subscriber_fields
                ];

                if (strval($cart_details['customer_id']) !== "0") {

                    $order_customer['resource_id'] = (string) $cart_details['customer_id'];
                }

                $order_cart = [
                    'items' => $cart_details['items']
                ];

                $mailerliteClient->syncOrder(
                    $shop,
                    $order_id,
                    $order_customer,
                    $order_cart,
                    $order->get_status(),
                    $order->get_total(),
                    date('Y-m-d h:m:s', strtotime($order->get_date_created()))
                );
            }
        }
    }
endif;
