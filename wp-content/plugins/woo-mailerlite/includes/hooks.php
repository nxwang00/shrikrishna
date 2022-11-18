<?php
/**
 * Shows the final purchase total at the bottom of the checkout page
 *
 * @since 1.5
 * @return void
 */
function woo_ml_checkout_label() {
    if ( ! woo_ml_is_active() )
        return;

    $checkout = woo_ml_get_option('checkout', 'no' );

    if ( 'yes' != $checkout )
        return;

    $group = woo_ml_get_option('group' );

    if ( empty( $group ) )
        return;

    $label = woo_ml_get_option('checkout_label' );
    $preselect = woo_ml_get_option('checkout_preselect', 'no' );
    $hidden = woo_ml_get_option('checkout_hide', 'no' );

    if ( 'yes' === $hidden ) {
        ?>
        <input name="woo_ml_subscribe" type="hidden" id="woo_ml_subscribe" value="1" checked="checked" />
        <?php
    } else {

        woocommerce_form_field( 'woo_ml_subscribe', array(
            'type'      => 'checkbox',
            'label'     => __($label),
        ),  ( 'yes' === $preselect) ? 1 : 0 );
    }
}
$checkout_position = woo_ml_get_option('checkout_position', 'checkout_billing' );
$checkout_position_hook = 'woocommerce_' . $checkout_position;
add_action( $checkout_position_hook, 'woo_ml_checkout_label', 20 );

/**
 * Remove (optional) string for ML label
 *
 */
function remove_ml_checkout_optional_text( $field, $key, $args, $value ) {

    if( is_checkout() && ! is_wc_endpoint_url() && strpos($field, 'woo_ml_subscribe') !== false && get_option('ml_account_authenticated') ) {

        $optional = '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
        $field = str_replace( $optional, '', $field );
    }
    return $field;
}
add_filter( 'woocommerce_form_field' , 'remove_ml_checkout_optional_text', 10, 4 );

/**
 * Maybe prepare signup
 *
 * @param $order_id
 */
function woo_ml_checkout_maybe_prepare_signup( $order_id ) {

    if ( isset( $_POST['woo_ml_subscribe'] ) && '1' == $_POST['woo_ml_subscribe'] ) {
        woo_ml_set_order_customer_subscribe( $order_id );
    }
}
add_action( 'woocommerce_checkout_update_order_meta', 'woo_ml_checkout_maybe_prepare_signup' );

/**
 * Process checkout completed
 *
 * @param $order_id
 */
function woo_ml_process_checkout_completed( $order_id ) {

    if ( ! get_option('ml_account_authenticated') ) {

        return false;
    }

    woo_ml_process_order_subscription( $order_id );
}
add_action( 'woocommerce_checkout_order_processed', 'woo_ml_process_checkout_completed' );

/**
 * Process order completed (and finally paid)
 *
 * @param $order_id
 */
function woo_ml_process_order_completed( $order_id ) {

    if ( ! woo_ml_integration_setup_completed() )
        woo_ml_setup_integration();

    if ( ! woo_ml_old_integration() ) {
        woo_ml_send_completed_order($order_id);
    }
    
    woo_ml_process_order_tracking( $order_id );
}
add_action( 'woocommerce_order_status_completed', 'woo_ml_process_order_completed' );

function woo_ml_proceed_to_checkout() {

    if ( ! get_option('ml_account_authenticated') ) {

        return false;
    }

    if ( ! woo_ml_old_integration() )
        woo_ml_send_cart();
}
add_action('woocommerce_add_to_cart', 'woo_ml_proceed_to_checkout');
add_action('woocommerce_cart_item_removed', 'woo_ml_proceed_to_checkout');
add_action('woocommerce_update_cart_action_cart_updated', 'woo_ml_proceed_to_checkout');

function woo_ml_order_status_change($order_id) {
    if ( ! woo_ml_old_integration() )
        woo_ml_payment_status_processing($order_id);
}
add_action('woocommerce_order_status_changed', 'woo_ml_order_status_change');


function woo_ml_enqueue_styles(){

    if ( ! get_option('ml_account_authenticated') ) {

        return false;
    }

    wp_enqueue_style('related-styles', plugins_url('/../public/css/style.css', __FILE__));
}
add_action('wp_enqueue_scripts','woo_ml_enqueue_styles');

/**
 * WooCommerce product update
 *
 * @param $product_id
 */
function woo_ml_product_update( $product_id ) {

    mailerlite_wp_sync_product( $product_id );
}
add_action( 'woocommerce_update_product', 'woo_ml_product_update', 10, 1 );

/**
 * WooCommerce order update
 *
 * @param $order_id
 */
function woo_ml_order_update( $order_id, $order ) {

    mailerlite_wp_sync_order( $order_id, $order );
}
add_action( 'woocommerce_process_shop_order_meta', 'woo_ml_order_update', 10, 2);
add_action( 'woocommerce_saved_order_items', 'woo_ml_order_update', 10, 2);

/**
 * WooCommerce bulk order update
 *
 * @param $redirect_to
 * @param $action
 * @param $order_ids
 */
function woo_ml_bulk_order_update( $redirect_to, $action, $order_ids ) {

    mailerlite_wp_sync_bulk_order( $action, $order_ids );

    return $redirect_to;
}
add_filter( 'handle_bulk_actions-edit-shop_order', 'woo_ml_bulk_order_update', 10, 3 );


/**
 * WP Post trash
 *
 * @param $post_id
 */
function woo_ml_post_trash( $post_id ) {

    mailerlite_wp_sync_post_trash( $post_id );
}
add_action( 'wp_trash_post', 'woo_ml_post_trash', 10 );

/**
 * WP Post restore
 *
 * @param $post_id
 */
function woo_ml_post_restore( $post_id ) {

    mailerlite_wp_sync_post_restore( $post_id );
}
add_action( 'untrashed_post', 'woo_ml_post_restore' );

/**
 * WP Post delete
 *
 * @param $post_id
 */
function woo_ml_post_delete( $post_id ) {

    mailerlite_wp_sync_post_delete( $post_id );
}
add_action( 'delete_post', 'woo_ml_post_delete', 10 );

function woo_ml_customer_update ( $customer_id, $customer_data = '' ) {

    mailerlite_wp_sync_ecommerce_customer( $customer_id );
}
add_action( 'woocommerce_update_customer', 'woo_ml_customer_update', 99, 2 );
add_action( 'user_register', 'woo_ml_customer_update', 10, 1 );
add_action( 'profile_update', 'woo_ml_customer_update', 10, 2 );

/**
 * WP Product Category create/update
 * @param $category_id
 */
function woo_ml_product_category_sync( $category_id ) {

    mailerlite_wp_sync_product_category( $category_id );
}
add_action( 'created_product_cat', 'woo_ml_product_category_sync', 10, 2 );
add_action( 'edited_product_cat', 'woo_ml_product_category_sync', 10, 2 );

function woo_ml_product_category_delete( $term, $tt_id, $deleted_term, $object_ids ) {

    // Avoid auto save from calling API
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    mailerlite_wp_delete_product_category( $term );
}
add_action( 'delete_product_cat', 'woo_ml_product_category_delete', 10, 4);