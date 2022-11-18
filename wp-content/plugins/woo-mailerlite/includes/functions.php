<?php
/**
 * Check if checkout action is active
 *
 * @return mixed
 */
function woo_ml_is_active() {
    $api_status = woo_ml_get_option( 'api_status', false );

    return $api_status;
}

function woo_ml_sync_failed()
{
    return get_option('woo_ml_order_sync_failed') || (get_option('woo_ml_sync_active') && !get_transient('woo_ml_order_sync_in_progress'));
}

/**
 * Get settings api key status
 *
 * @return string
 */
function woo_ml_settings_get_api_key_status() {

    $api_status = woo_ml_get_option( 'api_status', false );

    return ( $api_status ) ? '<span style="color: green;">' . __('Valid', 'woo-mailerlite' ) . '</span>' : '<span style="color: red;">' . __('Invalid', 'woo-mailerlite' ) . '</span>';
}

/**
 * Get settings group options
 *
 * @return array
 */
function woo_ml_settings_get_group_options() {

    if ( ! is_admin() ) {
        return [];
    }

    $options = array();

    $groups = mailerlite_wp_get_groups();

    if ( is_array( $groups ) && sizeof( $groups ) > 0 ) {
        $options[''] = __('Please select...', 'woo-mailerlite' );
        foreach ( $groups as $group ) {
            if ( isset( $group['id'] ) &&  isset( $group['name'] ) ) {
                $options[$group['id']] = $group['name'];
            }
        }
    } else {
        $options[''] = __('No groups found', 'woo-mailerlite' );
    }

    return $options;
}

/**
 * Validate given API key
 *
 * @param $api_key
 * @return bool
 */
function woo_ml_validate_api_key( $api_key ) {
    if ( empty( $api_key ) )
        return false;

    return mailerlite_wp_api_key_validation( $api_key );
}

/**
 * Process order create and subscription to newsletter
 *
 * @param $order_id
 * @return void
 */
function woo_ml_process_order_subscription( $order_id ) {
    $order = wc_get_order( $order_id );
    $customer_data = woo_ml_get_customer_data_from_order( $order_id );

    $subscribe = get_post_meta( $order_id, '_woo_ml_subscribe', true );

    if ($subscribe === "") {
        $subscribe = woo_ml_get_option('checkout_preselect') === 'yes' && woo_ml_get_option('checkout_hide') === 'yes';
        
        if ($subscribe)
            woo_ml_set_order_customer_subscribe($order_id);
    }
        

    $data = [];
    $data['email'] = $customer_data['email'];
    $data['checked_sub_to_mailist'] = $subscribe;
    $checkout_id = isset($_COOKIE['mailerlite_checkout_token']) ? $_COOKIE['mailerlite_checkout_token'] : woo_ml_get_saved_checkout_id_by_email($customer_data['email']);

    $data['checkout_id'] = $checkout_id;
    $data['order_id'] = $order_id;
    $data['payment_method'] = $order->get_payment_method();
    
    if ($data['payment_method'] == 'bacs' || $data['payment_method'] == 'cheque') {
        @setcookie('mailerlite_checkout_email', null, -1, '/');
        @setcookie('mailerlite_checkout_token', null, -1, '/');
    } else {
        $data['checkout_data'] = woo_ml_get_checkout_data();
    }
    $subscriber_fields = woo_ml_get_subscriber_fields_from_customer_data( $customer_data );
    if ( sizeof( $subscriber_fields ) > 0 )
        $data['fields'] = $subscriber_fields;

    $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

    if ($mailerliteClient->getApiType() === ApiType::CURRENT) {

        $subscriber_result = mailerlite_wp_add_subscriber_and_save_order($data, 'order_created');

        if (isset($subscriber_result->added_to_group)) {
            if ($subscriber_result->added_to_group) {
                woo_ml_complete_order_customer_subscribed($order_id);
            } else {
                woo_ml_complete_order_customer_already_subscribed($order_id);
            }
        }

        if (isset($subscriber_result->updated_fields) && $subscriber_result->updated_fields)
            woo_ml_complete_order_subscriber_updated( $order_id );
    }

    if ($mailerliteClient->getApiType() === ApiType::REWRITE) {

        $shop = get_option('woo_ml_shop_id', false);

        if ($shop === false) {

            return;
        }

        $subscribe = woo_ml_order_customer_subscribe($order_id);

        $cart_details = woo_ml_get_cart_details( $order_id );

        //rename zip key for API
        $zip = $subscriber_fields['zip'] ?? '';
        unset($subscriber_fields['zip']);
        $subscriber_fields['z_i_p'] = $zip;

        $order_customer = [
            'email' => $data['email'],
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

        // Delete existing cart (abandoned checkout)
        $checkout_id = $_COOKIE['mailerlite_checkout_token'] ?? woo_ml_get_saved_checkout_id_by_email($data['email']);

        if ($checkout_id !== null) {

            $mailerliteClient->deleteOrder($shop, $checkout_id);

            @setcookie('mailerlite_checkout_email', null, -1, '/');
            @setcookie('mailerlite_checkout_token', null, -1, '/');

            woo_ml_remove_checkout($data['email']);
        }

        // set order status completed (when processing)
        if ( $cart_details['status'] == 'pending' ) {
            $cart_details['status'] = 'completed';
        }

        // Create order
        $order_create = $mailerliteClient->syncOrder( $shop, $order_id, $order_customer, $order_cart, $cart_details['status'], $cart_details['total_price'], $cart_details['created_at']);

        if ($order_create) {

            woo_ml_complete_order_data_submitted($order_id);
        }
    }
}

/**
 * Process order tracking
 * 1.) Get current data from MailerLite
 * 2.) Prepare order data
 * 3.) Merge both data
 * 4.) Update subscriber data with updated values
 *
 * @param $order_id
 */
function woo_ml_process_order_tracking( $order_id ) {

    $order_tracked = get_post_meta( $order_id, '_woo_ml_order_tracking', true );

    if ( $order_tracked ) // Prevent tracking orders multiple times
        return;

    $customer_data = woo_ml_get_customer_data_from_order( $order_id );

    $ml_subscriber_obj = mailerlite_wp_get_subscriber_by_email( $customer_data['email'] );

    // Customer exists on MailerLite
    if ( $ml_subscriber_obj ) {

        /*
         * Step 1: Get order tracking data from order
         */
        $tracking_data = woo_ml_get_order_tracking_data( $order_id );

        /*
         * Step 2: Merge tracking data with the one from MailerLite
         */
        $tracking_data = woo_ml_get_merged_order_tracking_data( $tracking_data, $ml_subscriber_obj );

        /*
         * Step 3: Update subscriber data via API
         */
        $subscriber_data = array(
            'fields' => array(
                'woo_orders_count' => $tracking_data['orders_count'],
                'woo_total_spent' => $tracking_data['total_spent'],
                'woo_last_order' => $tracking_data['last_order'],
                'woo_last_order_id' => $order_id
            )
        );

        $subscriber_updated = mailerlite_wp_update_subscriber( $customer_data['email'], $subscriber_data );

        if ( $subscriber_updated )
            woo_ml_complete_order_data_submitted( $order_id );
    }

    // Mark order data as tracked
    woo_ml_complete_order_tracking( $order_id );
}

/**
 * Get order tracking data merged with the one from MailerLite's subscriber object
 *
 * @param $tracking_data
 * @param $ml_subscriber_obj
 * @return array
 */
function woo_ml_get_merged_order_tracking_data( $tracking_data, $ml_subscriber_obj ) {

    /*
     * Step 1: Collect current tracking data from MailerLite subscriber object
     */
    $ml_tracking_data = array(
        'orders_count' => 0,
        'total_spent' => 0,
        'last_order' => ''
    );

    if ( isset( $ml_subscriber_obj->fields ) && is_array( $ml_subscriber_obj->fields ) ) {

        foreach ( $ml_subscriber_obj->fields as $ml_subscriber_field ) {

            if ( ! isset( $ml_subscriber_field->key ) || ! isset( $ml_subscriber_field->value ) || ! isset( $ml_subscriber_field->type ) )
                continue;

            // Get orders
            if ( 'woo_orders_count' === $ml_subscriber_field->key && ! empty( $ml_subscriber_field->value ) && is_numeric( $ml_subscriber_field->value ) )
                $ml_tracking_data['orders_count'] = intval( $ml_subscriber_field->value );

            // Get revenues
            if ( 'woo_total_spent' === $ml_subscriber_field->key && ! empty( $ml_subscriber_field->value ) && is_numeric( $ml_subscriber_field->value ) )
                $ml_tracking_data['total_spent'] = intval( $ml_subscriber_field->value );

            // Get last order date
            if ( 'woo_last_order' === $ml_subscriber_field->key && ! empty( $ml_subscriber_field->value ) )
                $ml_tracking_data['last_order'] = $ml_subscriber_field->value;
        }
    }

    /*
     * Step 2: Merge order tracking data with the one on MailerLite
     */
    $tracking_data = array(
        'orders_count' => $ml_tracking_data['orders_count'] + $tracking_data['orders_count'],
        'total_spent' => $ml_tracking_data['total_spent'] + $tracking_data['total_spent'],
        'last_order' => $tracking_data['last_order'],
        'last_order_id' => $tracking_data['last_order_id']
    );

    return $tracking_data;
}

/**
 * Get order tracking data from order(s)
 *
 * @param int/array $order_ids
 * @return array|bool
 */
function woo_ml_get_order_tracking_data( $order_ids ) {

    if ( ! is_array( $order_ids ) && ! is_numeric( $order_ids ) )
        return false;

    if ( is_numeric( $order_ids ) )
        $order_ids = array( $order_ids );

    $tracking_data = array(
        'orders_count' => 0,
        'total_spent' => 0,
        'last_order' => '',
        'last_order_id' => 0
    );

    if ( sizeof( $order_ids ) > 0 ) {
        foreach ( $order_ids as $order_id ) {
            $order = wc_get_order( $order_id );

            $tracking_data['orders_count']++;

            $order_total = ( method_exists( $order, 'get_date_created' ) ) ? $order->get_total() : $order->total;
            $order_total = intval( $order_total );
            $tracking_data['total_spent'] += $order_total;

            $order_date = ( method_exists( $order, 'get_date_created' ) ) ? $order->get_date_created() : $order->date_created;
            $order_date = date( 'Y-m-d', strtotime( $order_date ) );
            $tracking_data['last_order'] = $order_date;
            $tracking_data['last_order_id'] = $order_id;
        }
    }

    return $tracking_data;
}

/**
 * Get customer data from order
 *
 * @param $order_id
 * @return array|bool
 */
function woo_ml_get_customer_data_from_order( $order_id ) {

    if ( empty( $order_id ) )
        return false;

    $order = wc_get_order( $order_id );

    if ( method_exists( $order, 'get_billing_email' ) ) {
        $data = array(
            'email' => $order->get_billing_email(),
            'name' => "{$order->get_billing_first_name()} {$order->get_billing_last_name()}",
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'company' => $order->get_billing_company(),
            'city' => $order->get_billing_city(),
            'postcode' => $order->get_billing_postcode(),
            'state' => $order->get_billing_state(),
            'country' => $order->get_billing_country(),
            'phone' => $order->get_billing_phone()
        );
    } else {
        // NOTE: Only for compatibility with WooCommerce < 3.0
        $data = array(
            'email' => $order->billing_email,
            'name' => "{$order->billing_first_name} {$order->billing_last_name}",
            'first_name' => $order->billing_first_name,
            'last_name' => $order->billing_last_name,
            'company' => $order->billing_company,
            'city' => $order->billing_city,
            'postcode' => $order->billing_postcode,
            'state' => $order->billing_state,
            'country' => $order->billing_country,
            'phone' => $order->billing_phone
        );
    }

    return $data;
}

/**
 * Get customer email address from order
 *
 * @param $order_id
 * @return bool|mixed|string
 */
function woo_ml_get_customer_email_from_order( $order_id ) {

    if ( empty( $order_id ) )
        return false;

    $order = wc_get_order( $order_id );

    return ( method_exists( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : $order->billing_email;
}

/**
 * Get subscriber fields from customer data
 *
 * @param $customer_data
 * @return array
 */
function woo_ml_get_subscriber_fields_from_customer_data( $customer_data ) {

    $subscriber_fields = array();

    if ( ! empty( $customer_data['first_name'] ) )
        $subscriber_fields['name'] = $customer_data['first_name'];

    if ( ! empty( $customer_data['last_name'] ) )
        $subscriber_fields['last_name'] = $customer_data['last_name'];

    if ( ! empty( $customer_data['company'] ) )
        $subscriber_fields['company'] = $customer_data['company'];

    if ( ! empty( $customer_data['city'] ) )
        $subscriber_fields['city'] = $customer_data['city'];

    if ( ! empty( $customer_data['postcode'] ) )
        $subscriber_fields['zip'] = $customer_data['postcode'];

    if ( ! empty( $customer_data['state'] ) )
        $subscriber_fields['state'] = $customer_data['state'];

    if ( ! empty( $customer_data['country'] ) )
        $subscriber_fields['country'] = $customer_data['country'];

    if ( ! empty( $customer_data['phone'] ) )
        $subscriber_fields['phone'] = $customer_data['phone'];

    return $subscriber_fields;
}

/**
 * Get untracked orders
 *
 * @param array $args
 * @return array
 */
function woo_ml_get_untracked_orders($args = array()) {

    $defaults = array(
        'numberposts' => 1,
        'post_type'   => 'shop_order',
        'post_status' => 'wc-completed',
        'order'       => 'ASC', // old to new in order to get the latest address data first
        'meta_key'     => '_woo_ml_order_tracked',
        'meta_compare' => 'NOT EXISTS'
    );

    $args = wp_parse_args( $args, $defaults );
    $order_posts = get_posts( $args );

    return $order_posts;
}

/**
 * Get untracked products
 *
 * @param array $args
 * @return array
 */
function woo_ml_get_untracked_products($args = array()) {

    $defaults = array(
        'post_type'   => 'product',
        'posts_per_page' => 100,
        'meta_key'     => '_woo_ml_product_tracked',
        'meta_compare' => 'NOT EXISTS'
    );

    $args = wp_parse_args($args, $defaults);
    $product_posts_query = new WP_Query( $args );

    $products = [];

    if ($product_posts_query->have_posts()) {
        $products = $product_posts_query->posts;
    }

    return $products;
}

/**
 * Get untracked product categories
 *
 * @return array
 */
function woo_ml_get_untracked_categories() {

    $term_args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'none',
        'meta_key' => '_woo_ml_category_tracked',
        'meta_compare' => 'NOT EXISTS'
    );

    return get_terms($term_args);
}

/**
 * Returns all orders associated with the given email
 * @param $email
 * @param $apiType
 * @return mixed
 */
function woo_ml_get_orders_by_email($email, $apiType) {

    $numPost = -1;

    if ($apiType == ApiType::REWRITE) {

        $numPost = 100;
    }

    $defaults = [
        'numberposts' => $numPost,
        'post_type'   => 'shop_order',
        'post_status' => 'wc-completed',
        'order'       => 'ASC',
        'meta_query'  => [
            'relations' =>  'AND',
            [
                'key' => '_billing_email',
                'value' => $email,
            ],
            [
                'key'     => '_woo_ml_order_tracked',
                'compare' => 'NOT EXISTS',
            ]
        ],
    ];

    $args = wp_parse_args( $defaults );
    $order_posts = get_posts( $args );

    return $order_posts;
}

/**
 * Resets the tracked orders so that they can be re-synced
 */
function woo_ml_reset_tracked_orders()
{

    $finished = woo_ml_reset_tracked_orders_process();

    echo json_encode([
        'allDone' => $finished
    ]);
    exit;
}

/**
 * Actual process to reset tracked orders and recreate shop if needed
 */
function woo_ml_reset_tracked_orders_process()
{
    set_time_limit(1800);

    $defaults = array(
        'numberposts' => 100,
        'post_type'   => 'shop_order',
        'post_status' => 'wc-completed',
        'order'       => 'ASC', // old to new in order to get latest address data first
        'meta_key'     => '_woo_ml_order_tracked',
        'meta_compare' => 'EXISTS'
    );

    $args = wp_parse_args( [], $defaults );

    $order_posts = get_posts( $args );

    $finished = count($order_posts) == 0;

    foreach ($order_posts as $post) {

        if (!isset($post->ID)) {

            continue;
        }

        delete_post_meta( $post->ID, '_woo_ml_order_tracked' );
    }

    return $finished;
}

/**
 * Resets the tracked products so that they can be re-synced
 */
function woo_ml_reset_tracked_products($exit = true)
{
    set_time_limit(1800);

    $defaults = array(
        'post_type'   => 'product',
        'posts_per_page' => 100,
        'meta_key'     => '_woo_ml_product_tracked',
        'meta_compare' => 'EXISTS'
    );

    $args = wp_parse_args($defaults);
    $product_posts_query = new WP_Query( $args );

    $product_posts = [];

    if ($product_posts_query->have_posts()) {
        $product_posts = $product_posts_query->get_posts();
    }

    $finished = count($product_posts) == 0;

    foreach ($product_posts as $post) {

        if (!isset($post->ID)) {

            continue;
        }

        delete_post_meta( $post->ID, '_woo_ml_product_tracked' );
    }


    if ($exit === true) {
        wp_send_json([
            'allDone' => $finished
        ]);
    }
}

/**
 * Resets the tracked categories so that they can be re-synced
 */
function woo_ml_reset_tracked_categories($exit = true)
{
    set_time_limit(1800);

    $term_args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'orderby' => 'none',
        'meta_key' => '_woo_ml_category_tracked',
        'meta_compare' => 'EXISTS'
    );

    $categories = get_terms($term_args);

    $finished = count($categories) == 0;

    foreach ($categories as $category) {

        if (!isset($category->term_id)) {

            continue;
        }

        delete_term_meta( $category->term_id, '_woo_ml_category_tracked' );
    }


    if ($exit === true) {
        wp_send_json([
            'allDone' => $finished
        ]);
    }
}

function woo_ml_count_untracked_orders_count()
{
    $defaults = array(
        'post_type'   => 'shop_order',
        'posts_per_page' => 1,
        'post_status' => 'wc-completed',
        'meta_key'     => '_woo_ml_order_tracked',
        'meta_compare' => 'NOT EXISTS'
    );

    $args = wp_parse_args($defaults);
    $order_posts_query = new WP_Query( $args );

    return $order_posts_query->found_posts;
}

function woo_ml_tracked_orders_count()
{
    $defaults = array(
        'post_type'   => 'shop_order',
        'posts_per_page' => 1,
        'post_status' => 'wc-completed',
        'meta_key'     => '_woo_ml_order_tracked',
        'meta_compare' => 'EXISTS'
    );

    $args = wp_parse_args($defaults);
    $order_posts_query = new WP_Query( $args );

    return $order_posts_query->found_posts;
}

function woo_ml_count_untracked_products_count()
{
    $defaults = array(
        'post_type'   => 'product',
        'posts_per_page' => 1,
        'meta_key'     => '_woo_ml_product_tracked',
        'meta_compare' => 'NOT EXISTS'
    );

    $args = wp_parse_args($defaults);
    $products_query = new WP_Query( $args );

    return $products_query->found_posts;
}

function woo_ml_count_untracked_categories_count()
{

    return count(woo_ml_get_untracked_categories());
}

/**
 * Get all tracked products
 */
function woo_ml_get_tracked_products()
{
    set_time_limit(1800);

    $defaults = array(
        'post_type'   => 'product',
        'posts_per_page' => -1,
        'meta_key'     => '_woo_ml_product_tracked',
        'meta_compare' => 'EXISTS'
    );

    $args = wp_parse_args($defaults);
    $product_posts_query = new WP_Query( $args );

    $product_posts = [];
    $products = [];

    if ($product_posts_query->have_posts()) {
        $product_posts = $product_posts_query->get_posts();
    }

    foreach ($product_posts as $post) {

        if (!isset($post->ID)) {

            continue;
        }

        $products[] = $post->ID;
    }

    return $products;
}

function woo_ml_valid_email($email) {

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $email = explode("@",$email);

        if (checkdnsrr(array_pop($email))) {

            return true;
        }
    }

    return false;
}

function woo_ml_get_customers_orders($apiType) {

    $data = [];
    $checkOrders = woo_ml_get_untracked_orders();

    foreach ($checkOrders as $checkOrderPost) {

        if (!isset($checkOrderPost->ID))
            continue;

        $checkOrderId = $checkOrderPost->ID;

        $checkOrderEmail = woo_ml_get_customer_email_from_order($checkOrderId);

        if (empty($checkOrderEmail)) {

            // we can't do much without an email, so set the order to tracked and continue
            add_post_meta( $checkOrderId, '_woo_ml_order_tracked', true );
            continue;
        }

        if (woo_ml_valid_email($checkOrderEmail) === false) {

            // we can't do much with an invalid email, so set the order to tracked and continue
            add_post_meta( $checkOrderId, '_woo_ml_order_tracked', true );
            continue;
        }

        if (!isset($data[$checkOrderEmail])) {

            $data[$checkOrderEmail] = [];

            // get all customer orders for that email
            $orders = woo_ml_get_orders_by_email($checkOrderEmail, $apiType);

            foreach ($orders as $orderPost) {

                if (!isset($orderPost->ID)) {

                    continue;
                }

                $orderId = $orderPost->ID;

                $data[$checkOrderEmail][] = $orderId;

            }
        }
    }

    unset($checkOrders);

    return $data;
}

function woo_ml_get_sync_orders($api_type) {

    $data = woo_ml_get_customers_orders($api_type);

    $syncOrders = [];

    foreach ($data as $customer_email => $order_ids) {
        if (sizeof($order_ids) > 1) {
            $last_order_id = array_values(array_slice($order_ids, -1))[0];
        } else {
            $last_order_id = $order_ids[0];
        }

        $customer_data = woo_ml_get_customer_data_from_order($last_order_id);

        $subscriber_fields = woo_ml_get_subscriber_fields_from_customer_data($customer_data);

        if ( $api_type === ApiType::CURRENT ) {

            $tracking_data = woo_ml_get_order_tracking_data($order_ids);

            $subscriber_fields['woo_orders_count']  = $tracking_data['orders_count'];
            $subscriber_fields['woo_total_spent']   = $tracking_data['total_spent'];
            $subscriber_fields['woo_last_order']    = $tracking_data['last_order'];
            $subscriber_fields['woo_last_order_id'] = $tracking_data['last_order_id'];

            $subscriber_updated = mailerlite_wp_sync_customer($customer_email, $subscriber_fields);

            foreach ($order_ids as $order_id) {

                woo_ml_complete_order_tracking($order_id);

                if ($subscriber_updated) {
                    woo_ml_complete_order_subscriber_updated($order_id);
                    woo_ml_complete_order_data_submitted($order_id);
                    woo_ml_order_customer_already_subscribed($order_id);
                }
            }
        }

        if ( $api_type === ApiType::REWRITE ) {

            foreach ($order_ids as $order_id) {

                $subscribe = woo_ml_order_customer_subscribe($order_id);

                $cart_details = woo_ml_get_cart_details($order_id);

                //rename zip key for API
                $zip = $subscriber_fields['zip'] ?? '';
                unset($subscriber_fields['zip']);
                $subscriber_fields['z_i_p'] = $zip;

                $order_status = 'pending';

                if ($cart_details['status'] == 'completed') {
                    $order_status = 'complete';
                }

                if (! empty($cart_details['items'])) {

                    $cart_items = array_column($cart_details['items'], 'product_resource_id');
                    $synced_products = [];

                    foreach($cart_items as $item) {

                        if (woo_ml_product_tracking_completed($item)) {
                            $synced_products[] = $item;
                        }
                    }

                    $cart_diff  = array_diff($cart_items, $synced_products);

                    if (empty($cart_diff)) {
                        $syncOrder = [
                            'resource_id' => (string) $order_id,
                            'customer'    => [
                                'email' => $customer_email,
                                'create_subscriber' => $subscribe,
                                'accepts_marketing' => $subscribe,
                                'subscriber_fields' => $subscriber_fields
                            ],
                            'cart'        => [
                                'items' => $cart_details['items']
                            ],
                            'status'      => $order_status,
                            'total_price' => $cart_details['total_price'],
                            'created_at' => date('Y-m-d h:m:s', strtotime($cart_details['created_at']))
                        ];

                        if (strval($cart_details['customer_id']) !== "0") {

                            $syncOrder['customer']['resource_id'] = (string) $cart_details['customer_id'];
                        }

                        $syncOrders[] = $syncOrder;
                    }else {

                        foreach ($cart_diff as $product_id) {
                            if ($product_id == 0) {

                                // mark as tracked
                                woo_ml_complete_product_tracking($product_id);
                            } else {

                                // try to sync product
                                mailerlite_sync_product($product_id);
                            }
                        }
                    }
                }else{

                    woo_ml_complete_order_tracking($order_id);
                    woo_ml_complete_order_data_submitted($order_id);
                }
            }

            unset($synced_products);
        }
    }

    unset($data);

    return $syncOrders;
}

/**
 * Bulk synchronize untracked orders
 *
 * @return bool
 */
function woo_ml_sync_untracked_orders() {

    set_time_limit(600);

    $message = 'Oops, we did not manage to sync all of your products, please try again.';

    try {

        $untracked_orders = woo_ml_count_untracked_orders_count();

        if ( $untracked_orders > 0 ) {

            $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

            $syncOrders = woo_ml_get_sync_orders($mailerliteClient->getApiType());

            $syncCount = 0;

            // Send orders to Ecommerce API
            if (count($syncOrders) > 0) {

                $shop = get_option('woo_ml_shop_id', false);

                if ($shop === false) {

                    return json_encode([
                        'error' => true,
                        'allDone' => false,
                        'message' => 'Shop is not activated.'
                    ]);
                }

                $result = $mailerliteClient->importOrders( $shop, $syncOrders );

                if ($mailerliteClient->responseCode() !== 200) {

                    $errorMsg = json_decode($mailerliteClient->getResponseBody());
                    $message = 'Oops, we did not manage to sync all of your orders, please try again. (' . $mailerliteClient->responseCode() . ')';

                    if ($mailerliteClient->responseCode() == 422 && isset($errorMsg->message)) {

                        $message = $errorMsg->message;
                    }

                    return json_encode([
                        'error' => true,
                        'allDone' => false,
                        'code' => $mailerliteClient->responseCode(),
                        'message' => $message
                    ]);
                }

                foreach ($result as $order) {

                    woo_ml_complete_order_tracking($order->resource_id);
                    woo_ml_complete_order_data_submitted($order->resource_id);
                    $syncCount++;
                }
            }

            return json_encode([
                'allDone' => false,
                'completed' => $syncCount,
                'untracked' => $untracked_orders
            ]);
        } else {

            return json_encode([
                'allDone' => true
            ]);
        }

    } catch(\Exception $e) {

        return json_encode([
            'error' => true,
            'allDone' => false,
            'message' => $message
        ]);
    }
}

/**
 * Bulk synchronize untracked products
 *
 * @return bool
 */
function woo_ml_sync_untracked_products() {

    set_time_limit(600);

    $message = 'Oops, we did not manage to sync all of your products, please try again.';

    try {

        $checkProducts = woo_ml_get_untracked_products();

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ( is_array( $checkProducts ) && sizeof( $checkProducts ) > 0 ) {

            $shop = get_option('woo_ml_shop_id', false);

            if ($shop === false) {

                return json_encode([
                    'error' => true,
                    'allDone' => false,
                    'message' => 'Shop is not activated.'
                ]);
            }

            $syncProducts = [];

            foreach ($checkProducts as $post) {

                if (!isset($post->ID))
                    continue;

                $product = wc_get_product( $post->ID);

                $productID      = $product->get_id();
                $productName    = $product->get_name() ?: 'Untitled product';
                $productPrice   = floatval($product->get_price('edit'));
                $productImage   = woo_ml_product_image($product);

                $productURL     = $product->get_permalink();

                $categories   = get_the_terms( $productID, 'product_cat');

                $productCategories = [];

                foreach ( $categories as $category ) {

                    $productCategories[] = (string) $category->term_id;
                }

                $exclude_automation = get_post_meta( $productID, '_woo_ml_product_ignored', true) === "1";

                $syncProduct = [
                    'resource_id' => (string) $productID,
                    'name' => $productName,
                    'price' => $productPrice,
                    'url' => $productURL,
                    'exclude_from_automations' => $exclude_automation,
                    'categories' => $productCategories
                ];

                if (! empty($productImage)) {
                    
                    $syncProduct['image'] = (string)$productImage;
                }


                $syncProducts[] = $syncProduct;
            }

            $syncCount = 0;

            if (count($syncProducts) > 0) {

                $result = $mailerliteClient->importProducts($shop, $syncProducts);

                if ( empty($result) || $mailerliteClient->responseCode() == 422 || $mailerliteClient->responseCode() == 500) {

                    $errorMsg = json_decode($mailerliteClient->getResponseBody());
                    $message = 'Oops, we did not manage to sync all of your products, please try again. (' . $mailerliteClient->responseCode() . ')';

                    if ($mailerliteClient->responseCode() == 422 && isset($errorMsg->message)) {

                        $message = $errorMsg->message;
                    }

                    return json_encode([
                        'error' => true,
                        'allDone' => false,
                        'code' => $mailerliteClient->responseCode(),
                        'message' => $message
                    ]);
                }

                if ( $mailerliteClient->responseCode() == 201 || $mailerliteClient->responseCode() == 200 ) {

                    foreach ($syncProducts as $product) {

                        woo_ml_complete_product_tracking($product['resource_id']);
                        $syncCount++;
                    }
                }
            }

            return json_encode([
                'allDone' => false,
                'completed' => $syncCount,
                'code' => $mailerliteClient->responseCode()
            ]);

        } else {

            return json_encode([
                'allDone' => true
            ]);
        }

    } catch(\Exception $e) {

        return json_encode([
            'error' => true,
            'allDone' => false,
            'message' => $message
        ]);
    }
}

/**
 * Bulk synchronize untracked categories
 *
 * @return bool
 */
function woo_ml_sync_untracked_categories() {

    set_time_limit(600);

    try {

        $checkCategories = woo_ml_get_untracked_categories();

        $mailerliteClient = new PlatformAPI( MAILERLITE_WP_API_KEY );

        if ( is_array( $checkCategories ) && sizeof( $checkCategories ) > 0 ) {

            $shop = get_option('woo_ml_shop_id', false);

            if ($shop === false) {

                return json_encode([
                    'error' => true,
                    'allDone' => false
                ]);
            }

            $importCategories = [];

            foreach ($checkCategories as $category) {

                if (!isset($category->term_id))
                    continue;

                $importCategories[] = [
                    'name' => $category-> name,
                    'resource_id' => (string) $category->term_id
                ];
            }

            if (count($importCategories) > 0) {

                $result = $mailerliteClient->importCategories( $shop, $importCategories );

                if ($mailerliteClient->responseCode() !== 200) {

                    return json_encode([
                        'error' => true,
                        'allDone' => false
                    ]);
                }

                foreach ($result as $category) {

                    woo_ml_complete_category_tracking($category->resource_id, $category->id);
                }
            }else{

                return json_encode([
                    'allDone' => true
                ]);
            }

            return json_encode([
                'allDone' => false
            ]);
        } else {

            return json_encode([
                'allDone' => true
            ]);
        }

    } catch(\Exception $e) {

        return json_encode([
            'error' => true,
            'allDone' => false
        ]);
    }
}

/**
 * Check whether a customer wants to be subscribed to our mailing list or not
 *
 * @param $order_id
 * @return bool
 */
function woo_ml_order_customer_subscribe( $order_id ) {

    $subscribe = get_post_meta( $order_id, '_woo_ml_subscribe', true );

    return ( '1' == $subscribe ) ? true : false;
}

/**
 * Mark order as "wants to be subscribed to mailing our list"
 *
 * @param $order_id
 */
function woo_ml_set_order_customer_subscribe( $order_id ) {
    add_post_meta( $order_id, '_woo_ml_subscribe', true );
}

/**
 * Check whether a customer was subscribed via API or not
 *
 * @param $order_id
 * @return bool
 */
function woo_ml_order_customer_subscribed( $order_id ) {

    $subscribed = get_post_meta( $order_id, '_woo_ml_subscribed', true );

    return ( '1' == $subscribed ) ? true : false;
}

function woo_ml_order_customer_already_subscribed( $order_id ) {

    $already_subscribed = get_post_meta( $order_id, '_woo_ml_already_subscribed', true );

    return ( '1' == $already_subscribed ) ? true : false;
}

/**
 * Mark order as "customer subscribed via API"
 *
 * @param $order_id
 */
function woo_ml_complete_order_customer_subscribed( $order_id ) {
    add_post_meta( $order_id, '_woo_ml_subscribed', true );
}

function woo_ml_complete_order_customer_already_subscribed( $order_id ) {
    add_post_meta( $order_id, '_woo_ml_already_subscribed', true );
}

/**
 * Check whether a subscriber was updated from order or not
 *
 * @param $order_id
 * @return bool
 */
function woo_ml_order_subscriber_updated( $order_id ) {

    $subscriber_updated_from_order = get_post_meta( $order_id, '_woo_ml_subscriber_updated', true );

    return ( '1' == $subscriber_updated_from_order ) ? true : false;
}

/**
 * Mark order as "subscriber was updated"
 *
 * @param $order_id
 */
function woo_ml_complete_order_subscriber_updated( $order_id ) {
    add_post_meta( $order_id, '_woo_ml_subscriber_updated', true );
}

/**
 * Check whether order data was submitted via API or not
 *
 * @param $order_id
 * @return bool
 */
function woo_ml_order_data_submitted( $order_id ) {

    $data_submitted = get_post_meta( $order_id, '_woo_ml_order_data_submitted', true );

    return ( '1' == $data_submitted ) ? true : false;
}

/**
 * Mark order as "order data submitted"
 *
 * @param $order_id
 */
function woo_ml_complete_order_data_submitted( $order_id ) {
    add_post_meta( $order_id, '_woo_ml_order_data_submitted', true );
}

/**
 * Check whether order was already tracked or not
 *
 * @param $order_id
 * @return bool
 */
function woo_ml_order_tracking_completed( $order_id ) {
    $order_tracked = get_post_meta( $order_id, '_woo_ml_order_tracked', true );

    return ( '1' == $order_tracked ) ? true : false;
}

/**
 * Mark order as being tracked
 *
 * @param $order_id
 */
function woo_ml_complete_order_tracking( $order_id ) {
    add_post_meta( $order_id, '_woo_ml_order_tracked', true );
}

/**
 * Check whether product was already tracked or not
 *
 * @param $product_id
 * @return bool
 */
function woo_ml_product_tracking_completed( $product_id ) {

    $product_tracked = get_post_meta( $product_id, '_woo_ml_product_tracked', true );

    return ( '1' == $product_tracked ) ? true : false;
}

/**
 * Mark product as being tracked
 *
 * @param $product_id
 */
function woo_ml_complete_product_tracking( $product_id ) {

    add_post_meta( $product_id, '_woo_ml_product_tracked', true, true );
}

/**
 * Mark product as ignored for automation
 *
 * @param $product_id
 */
function woo_ml_ignore_product( $product_id ) {

    add_post_meta( $product_id, '_woo_ml_product_ignored', true, true );
}

/**
 * Remove product from ignored
 *
 * @param $product_id
 */
function woo_ml_remove_ignore_product( $product_id ) {

    delete_post_meta( $product_id, '_woo_ml_product_ignored');
}

/**
 * Check whether category was already tracked or not
 *
 * @param $categoru_id
 * @return mixed
 */
function woo_ml_category_tracking_completed( $category_id ) {

    return get_term_meta( $category_id, '_woo_ml_category_tracked', true );
}

/**
 * Mark category as being tracked
 *
 * @param $category_id
 * @param $ecommerce_id
 */
function woo_ml_complete_category_tracking( $category_id, $ecommerce_id ) {

    add_term_meta( $category_id, '_woo_ml_category_tracked', $ecommerce_id, true);
}

/**
 * Get settings option
 *
 * @param $key
 * @param null $default
 * @return null
 */
function woo_ml_get_option( $key, $default = null ) {
    $settings = get_option( 'woocommerce_mailerlite_settings' );

    return ( isset( $settings[$key] ) ) ? $settings[$key] : $default;
}

/**
 * Check whether we are on our admin pages or not
 *
 * @return bool
 */
function woo_ml_is_plugin_admin_area() {
    $screen = get_current_screen();

    return ( strpos( $screen->id, 'wc-settings') !== false ) ? true : false;
}

/**
 * Debug
 *
 * @param $args
 * @param bool $title
 */
function woo_ml_debug( $args, $title = false ) {

    if ( $title )
        echo '<h3>' . $title . '</h3>';

    echo '<pre>';
    print_r( $args );
    echo '</pre>';
}

/**
 * Debug to log file
 *
 * @param $message
 */
function woo_ml_debug_log( $message ) {

    if ( WP_DEBUG === true && defined( 'WOO_ML_DEBUG' ) && WOO_ML_DEBUG === true ) {
        if (is_array( $message ) || is_object( $message ) ) {
            error_log( print_r( $message, true ) );
        } else {
            error_log( $message );
        }
    }
}

/**
* MailerLite universal script for tracking visits
* @return void
*/
function mailerlite_universal_woo_commerce()
{

    require_once WOO_MAILERLITE_DIR . 'includes/shared/api/class.woo-mailerlite-api-type.php';

    if ((int) get_option('woo_mailerlite_platform', 1) === ApiType::CURRENT) {

        $shopUrl = home_url();
        $shopUrl = str_replace('http://', '', $shopUrl);
        $shopUrl = str_replace('https://', '', $shopUrl);

        $popups_enabled = !get_option('mailerlite_popups_disabled');
        $load = '';

        if ($popups_enabled)
            $load = 'load';

        ?>
            <!-- MailerLite Universal -->
            <script>
            (function(m,a,i,l,e,r){ m['MailerLiteObject']=e;function f(){
            var c={ a:arguments,q:[]};var r=this.push(c);return "number"!=typeof r?r:f.bind(c.q);}
            f.q=f.q||[];m[e]=m[e]||f.bind(f.q);m[e].q=m[e].q||f.q;r=a.createElement(i);
            var _=a.getElementsByTagName(i)[0];r.async=1;r.src=l+'?v'+(~~(new Date().getTime()/1000000));
            _.parentNode.insertBefore(r,_);})(window, document, 'script', 'https://static.mailerlite.com/js/universal.js', 'ml');

            window.mlsettings = window.mlsettings || {};
            window.mlsettings.shop = '<?php echo $shopUrl; ?>';
            var ml_account = ml('accounts', '<?php echo get_option("account_id"); ?>', '<?php echo get_option("account_subdomain"); ?>', '<?php echo $load; ?>');
            ml('ecommerce', 'visitor', 'woocommerce');
            </script>
            <!-- End MailerLite Universal -->
        <?php
    }

    if ((int) get_option('woo_mailerlite_platform', 1) === ApiType::REWRITE) {

        $mailerlite_popups = ! ((get_option('mailerlite_popups_disabled') == '1'));
        ?>
        <!-- MailerLite Universal -->
        <script>
            (function(w,d,e,u,f,l,n){w[f]=w[f]||function(){(w[f].q=w[f].q||[])
                .push(arguments);},l=d.createElement(e),l.async=1,l.src=u,
                n=d.getElementsByTagName(e)[0],n.parentNode.insertBefore(l,n);})
            (window,document,'script','https://assets.mailerlite.com/js/universal.js','ml');
            ml('account', '<?php echo get_option('account_id'); ?>');
            ml('enablePopups', <?php echo $mailerlite_popups ? 'true' : 'false'; ?>);
        </script>
        <!-- End MailerLite Universal -->
        <?php
    }
}

if (get_option('account_id') && get_option('account_subdomain'))
{

    add_action('wp_head', 'mailerlite_universal_woo_commerce');
}

if ((int) get_option('woo_mailerlite_platform', 1) === 2 && get_option('account_id')) {

    add_action('wp_head', 'mailerlite_universal_woo_commerce');
}

/**
 * Gets triggered on completed order event. Fetches order data
 * and passes it along to api
 * 
 * @param Integer $order_id
 * @return void
 */
function woo_ml_send_completed_order($order_id)
{
    $order = wc_get_order($order_id);
    $order_data['order'] = $order->get_data();
    $order_items = $order->get_items();
    $customer_email = $order->get_billing_email();

    $saved_checkout = woo_ml_get_saved_checkout_by_email($customer_email);
    $order_data['checkout_id'] = ! empty($saved_checkout) ?$saved_checkout->checkout_id : null;

    $ignored_products = woo_ml_remap_list( woo_ml_get_product_list() );

    foreach ($order_items as $key => $value) {
        $item_data = $value->get_data();
        $order_data['order']['line_items'][$key] = $item_data;
        $order_data['order']['line_items'][$key]['ignored_product'] = in_array($item_data['product_id'], $ignored_products) ? 1 : 0;
    }
    @setcookie('mailerlite_checkout_email', null, -1, '/');
    @setcookie('mailerlite_checkout_token', null, -1, '/');

    mailerlite_wp_send_order($order_data);

    if (! empty($saved_checkout)) {

        woo_ml_remove_checkout($customer_email);
    }

}
/**
 * Sending cart data on updated cart contents event (add or remove from cart)
 * @param $cookie_email
 * @return void
 */
function woo_ml_send_cart($cookie_email = null, $subscribe = null)
{
    $checkout_data = woo_ml_get_checkout_data($cookie_email, $subscribe);
    if (! empty($checkout_data)) {

        mailerlite_wp_send_cart($checkout_data);
    }
    
}
/**
 * Preparing checkout data for api
 * @param $cookie_email
 * @return array
 */
function woo_ml_get_checkout_data($cookie_email = null, $subscribe = null)
{
    if (!function_exists('WC')) 
        return false;

    $cart = WC()->cart;
    $cart_items = $cart->get_cart();
    $customer = $cart->get_customer();
    $customer_email = $customer->get_email();
    if (! $customer_email) {
        $customer_email = isset($_COOKIE['mailerlite_checkout_email']) ? $_COOKIE['mailerlite_checkout_email'] : $cookie_email;
    }

    // check if email was updated recently in checkout
    if (filter_var($cookie_email, FILTER_VALIDATE_EMAIL) && $customer_email !== $cookie_email) {
        $customer_email = $cookie_email;
    }

    $checkout_data = [];
    if (! empty($customer_email)) {
        $line_items = [];
        $total = 0;

        foreach($cart_items as $key => $value) {
            $subtotal = intval($value['quantity']) * floatval($value['data']->get_price('edit'));

            $line_item = [
                'key' => $key,
                'line_subtotal' => $subtotal,
                'line_total' => $subtotal,
                'product_id' => $value['product_id'],
                'quantity' =>  $value['quantity'],
                'variation' => $value['variation'],
                'variation_id' => $value['variation_id']
            ];
            $line_items[] = $line_item;

            $total += $subtotal;
        }

        if (! isset($_COOKIE['mailerlite_checkout_token'])) {
            $checkout_id = md5(uniqid(rand(), true));            ;
            @setcookie('mailerlite_checkout_token', $checkout_id, time()+172800, '/');
        } else {
            $checkout_id = $_COOKIE['mailerlite_checkout_token'];
        }
            
        $shop_checkout_url = wc_get_checkout_url();
        $checkout_url = $shop_checkout_url.'?ml_checkout='.$checkout_id;

        $checkout_data = [
            'id'                     => $checkout_id,
            'email'                  => $customer_email,
            'line_items'             => $line_items,
            'abandoned_checkout_url' => $checkout_url,
            'total_price'            => $total,
            'created_at'             => date('Y-m-d h:m:s')
        ];

        if ($subscribe === true) {
            $checkout_data['subscribe'] = true;
        }

        woo_ml_save_or_update_checkout($checkout_id, $customer_email, $cart_items);
    }

    return $checkout_data;
}

/**
 * On change of order status to processing send order data
 * @param Integer $order_id
 * @return void 
 */
function woo_ml_payment_status_processing($order_id)
{
    $order = wc_get_order($order_id);

    if ($order->get_status() === 'processing') {
        $data = [];
        $customer_email = $order->get_billing_email();

        // load the checkout id from the cookie first
        // if that fails, then check the mailerlite checkouts table
        $checkoutId = null;
        if (isset($_COOKIE['mailerlite_checkout_token'])) {

            $checkoutId = $_COOKIE['mailerlite_checkout_token'];
        } else {

            $saved_checkout = woo_ml_get_saved_checkout_by_email($customer_email);
            $checkoutId = ! empty($saved_checkout) ? $saved_checkout->checkout_id : null;
        }

        $data['checkout_id'] = $checkoutId;
        $data['order_id'] = $order_id;
        $data['payment_method'] = $order->get_payment_method();
        
        @setcookie('mailerlite_checkout_email', null, -1, '/');
        @setcookie('mailerlite_checkout_token', null, -1, '/');

        mailerlite_wp_add_subscriber_and_save_order($data, 'order_processing');
        woo_ml_remove_checkout($customer_email);
    }
}
/**
 * Clears ml specific options from the database,
 * Drops mailerlite_checkouts table,
 * Sends api request
 * 
 * @param Bool $active_status
 * @return void
 */
function woo_ml_toggle_shop_connection($active_status)
{
    if (! $active_status) {
        delete_option('woocommerce_mailerlite_settings');
        delete_option('double_optin');
        delete_option('woo_ml_version');
        woo_ml_drop_mailerlite_checkouts_table();
        if (!function_exists('WC')) 
            return false;
        mailerlite_wp_toggle_shop_connection($active_status);
        delete_option('woo_ml_key');
    } else {
        woo_ml_create_mailerlite_checkouts_table();
        update_option('ml_account_authenticated', false);
    }
}
/**
 * Intial creation of mailerlite_checkouts table
 * 
 * @return void
 */
function woo_ml_create_mailerlite_checkouts_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'mailerlite_checkouts';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table(
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		checkout_id varchar(55) NOT NULL,
		email text NOT NULL,
		cart_content text DEFAULT '' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
}
/**
 * @return void
 */
function woo_ml_drop_mailerlite_checkouts_table()
{
    global $wpdb;
    $table = $wpdb->prefix . 'mailerlite_checkouts';
    $wpdb->query("DROP TABLE IF EXISTS $table");
}
/**
 * Insert/update/delete checkout entry from the table
 * 
 * @param string $checkout_id
 * @param string $customer_email
 * @param array $cart
 * @return void
 */
function woo_ml_save_or_update_checkout($checkout_id, $customer_email, $cart)
{
    global $wpdb;
    $table = $wpdb->prefix . 'mailerlite_checkouts';

    if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
        woo_ml_create_mailerlite_checkouts_table();
    }

    $checkout = woo_ml_get_saved_checkout($checkout_id);
    if (!empty($checkout) && !empty($cart)) {
        $wpdb->query( $wpdb->prepare("UPDATE $table 
                SET email = %s, cart_content = %s
                WHERE checkout_id = %s",$customer_email, serialize($cart), $checkout_id)
        );
    } else if(!empty($checkout) && empty($cart)) {
        $wpdb->delete($table, array('checkout_id' => $checkout_id));
    } else {
        $wpdb->insert( 
            $table, 
            array( 
                'time' => current_time( 'mysql' ), 
                'checkout_id' => $checkout_id, 
                'email' => $customer_email, 
                'cart_content' => serialize($cart)
            ) 
        );
    }
}
/**
 * @param string $checkout_id
 * @return array
 */
function woo_ml_get_saved_checkout($checkout_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'mailerlite_checkouts';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE checkout_id = %s", $checkout_id));
}

function woo_ml_get_saved_checkout_by_email($email)
{
    global $wpdb;
    $table = $wpdb->prefix . 'mailerlite_checkouts';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE email = %s ORDER BY time DESC LIMIT 1", $email));
}

function woo_ml_get_saved_checkout_id_by_email($email)
{
    $checkout = woo_ml_get_saved_checkout_by_email($email);
    if (!empty($checkout)) 
        return $checkout->checkout_id;
    
    return null;
}

function woo_ml_remove_checkout($email) 
{
    global $wpdb;
    $table = $wpdb->prefix . 'mailerlite_checkouts';

    $wpdb->delete($table, array('email' => $email));
}
/**
 * Sets checkout for user session when clicking on Return to checkout email
 * 
 * @return void
 */
function woo_ml_reload_checkout()
{
    if (!function_exists('WC')) 
        return false;

    if (! is_object( WC()->session))
        return false;
    
    if (isset($_GET['ml_checkout'])) {
        $checkout_id = substr($_GET['ml_checkout'], 0, strpos($_GET['ml_checkout'], "?"));
        $checkout = woo_ml_get_saved_checkout($checkout_id);
        
        if ($checkout && !empty($checkout->cart_content)) {
            WC()->session->set('cart', unserialize($checkout->cart_content));
            @setcookie('mailerlite_checkout_token', $checkout->checkout_id, time()+172800, '/');
            @setcookie('mailerlite_checkout_email', $checkout->email, time()+172800, '/');
        }
    }
}

function woo_ml_set_to_tracked_orders($order)
{
    if ($order->post_status === 'wc-completed') {
        woo_ml_complete_order_data_submitted( $order->ID );
        woo_ml_complete_order_tracking( $order->ID );
        
        return true;
    }

    return false;
}

function woo_ml_get_product_list()
{
    global $wpdb;

    $table = $wpdb->prefix . 'ml_data';

    $tableCreated = get_option('ml_data_table');

    if ($tableCreated != 1) {

        woo_create_mailer_data_table();
    }

    $data = $wpdb->get_row("SELECT * FROM $table WHERE data_name = 'products'");

    if (!empty($data)) {

        $products = json_decode($data->data_value, true);

        if ($products !== false) {

            return $products;
        } else {

            return [];
        }
    } else {

        return [];
    }

}


/**
 * Creates the ml_data table and sets the ml_data_table option flag
 */
function woo_create_mailer_data_table() {

    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $table = $wpdb->prefix . 'ml_data';

    $sql = "CREATE TABLE $table (
            data_name varchar(45) NOT NULL,
            data_value text NOT NULL,
            PRIMARY KEY  (data_name)
        ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    update_option('ml_data_table', 1);

}

/**
 * Map array in correct structure for WooCommerce Integration
 *
 * @return array
 */
function woo_ml_remap_list( $products ) {

    return array_map('strval', array_keys( $products ) );
}

/**
 * Get cart details from order for the API
 * @param   $order_id
 * @return  array
 */
function woo_ml_get_cart_details( $order_id ) {

    $order = wc_get_order($order_id);

    $items = [];

    foreach ($order->get_items() as $item_key => $item ) {

        if ($item->get_product_id() !== 0) {

            $items[] = [
                'product_resource_id' => (string)$item->get_product_id(),
                'variant' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => (float)$item->get_total()
            ];
        }
    }

    return [
        'customer_id' => $order->get_customer_id(),
        'items' => $items,
        'status' => $order->get_status(),
        'total_price' => $order->get_total(),
        'created_at' => date('Y-m-d h:m:s', strtotime($order->get_date_created()))
    ];
}

/**
 * Retrieves the url of the image of the given product
 * @param $product
 * @param string $size
 * @return mixed|null
 */
function woo_ml_product_image($product, $size = 'large')
{
    if ($product->get_image_id()) {

        $image = wp_get_attachment_image_src( $product->get_image_id(), $size, false );
        list( $src, $width, $height ) = $image;

        return $src;
    } else if ($product->get_parent_id()) {

        $parentProduct = wc_get_product( $product->get_parent_id() );
        if ( $parentProduct ) {

            return woo_ml_product_image($parentProduct, $size);
        }
    }

    return '';
}