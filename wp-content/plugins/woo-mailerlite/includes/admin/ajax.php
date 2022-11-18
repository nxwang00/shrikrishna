<?php
/**
 * Ajax
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Refresh groups
 */
function woo_ml_admin_ajax_refresh_groups() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

        wp_send_json([
            'groups' => woo_ml_settings_get_group_options(),
            'current' => woo_ml_get_option('group')
        ]);
    }
}
add_action( 'wp_ajax_nopriv_post_woo_ml_refresh_groups', 'woo_ml_admin_ajax_refresh_groups' );
add_action( 'wp_ajax_post_woo_ml_refresh_groups', 'woo_ml_admin_ajax_refresh_groups' );

/**
 * Sync Orders
 */
function woo_ml_admin_ajax_sync_untracked_orders() {

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $response = false;
        try{
            $orders_synced = woo_ml_sync_untracked_orders();
            if ( is_bool($orders_synced) ) {
                $response = true;
            } else {
                $response = $orders_synced;
            }

            echo $response;
        } catch(\Exception $e) {
            return true;
        }
    
        
    }
    exit;
}
add_action( 'wp_ajax_nopriv_post_woo_ml_sync_untracked_orders', 'woo_ml_admin_ajax_sync_untracked_orders' );
add_action( 'wp_ajax_post_woo_ml_sync_untracked_orders', 'woo_ml_admin_ajax_sync_untracked_orders' );

/**
 * Sync Products
 */
function woo_ml_admin_ajax_sync_untracked_products() {

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $response = false;
        try{

            $products_synced = woo_ml_sync_untracked_products();
            if ( is_bool($products_synced) ) {
                $response = true;
            } else {
                $response = $products_synced;
            }

            echo $response;
        } catch(\Exception $e) {
            return true;
        }


    }
    exit;
}
add_action( 'wp_ajax_nopriv_post_woo_ml_sync_untracked_products', 'woo_ml_admin_ajax_sync_untracked_products' );
add_action( 'wp_ajax_post_woo_ml_sync_untracked_products', 'woo_ml_admin_ajax_sync_untracked_products' );

/**
 * Sync Products
 */
function woo_ml_admin_ajax_sync_untracked_categories() {

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $response = false;
        try{

            $categories_synced = woo_ml_sync_untracked_categories();
            if ( is_bool($categories_synced) ) {
                $response = true;
            } else {
                $response = $categories_synced;
            }

            echo $response;
        } catch(\Exception $e) {
            return true;
        }


    }
    exit;
}
add_action( 'wp_ajax_nopriv_post_woo_ml_sync_untracked_categories', 'woo_ml_admin_ajax_sync_untracked_categories' );
add_action( 'wp_ajax_post_woo_ml_sync_untracked_categories', 'woo_ml_admin_ajax_sync_untracked_categories' );

/**
 * Is called when the user presses the Reset orders sync button in the plugin admin settings
 */
function woo_ml_reset_orders_sync()
{

    woo_ml_reset_tracked_orders();
}
add_action( 'wp_ajax_post_woo_ml_reset_orders_sync', 'woo_ml_reset_orders_sync' );

/**
 * Is called when the user presses the Reset products sync button in the plugin admin settings
 */
function woo_ml_reset_products_sync()
{

    woo_ml_reset_tracked_products();
}
add_action( 'wp_ajax_post_woo_ml_reset_products_sync', 'woo_ml_reset_products_sync' );

/**
 * Is called when the user presses the Reset categories sync button in the plugin admin settings
 */
function woo_ml_reset_categories_sync()
{

    woo_ml_reset_tracked_categories();
}
add_action( 'wp_ajax_post_woo_ml_reset_categories_sync', 'woo_ml_reset_categories_sync' );

function woo_ml_email_cookie() {

    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        try{
            $email = isset($_POST['email']) ? $_POST['email'] : null;
            $subscribe = isset($_POST['signup']) ? ('true' == $_POST['signup']) : null;

            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                
                //setting email cookie and cart token for two days
                @setcookie('mailerlite_checkout_email', $email, time()+172800, '/');
                if (! isset($_COOKIE['mailerlite_checkout_token'])) {
                    @setcookie('mailerlite_checkout_token', md5(uniqid(rand(), true)), time()+172800, '/');
                }
                woo_ml_send_cart($email, $subscribe);
            }
        }catch(\Exception $e) {
            return true;
        }
    }
    exit;
}
add_action( 'wp_ajax_nopriv_post_woo_ml_email_cookie', 'woo_ml_email_cookie' );
add_action( 'wp_ajax_post_woo_ml_email_cookie', 'woo_ml_email_cookie' );

function woo_ml_validate_key() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        if (! empty($_POST['key']))
            woo_ml_validate_api_key($_POST['key']);
    }
    exit;
}

add_action( 'wp_ajax_nopriv_post_woo_ml_validate_key', 'woo_ml_validate_key' );
add_action( 'wp_ajax_post_woo_ml_validate_key', 'woo_ml_validate_key' );