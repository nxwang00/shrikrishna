<?php

/**
 * Scripts
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Load admin scripts
 *
 * @since       1.0.0
 * @global      string $post_type The type of post that we are editing
 * @return      void
 */
function woo_ml_admin_scripts($hook)
{

    // Use minified libraries if SCRIPT_DEBUG is turned off
    $suffix = ((defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) || (defined('WOO_ML_DEBUG') && WOO_ML_DEBUG)) ? '' : '.min';

    wp_enqueue_script('woo-ml-admin-script', WOO_MAILERLITE_URL . 'public/js/admin.js', array('jquery'), WOO_MAILERLITE_VER);
    wp_enqueue_style('woo-ml-admin-style', WOO_MAILERLITE_URL . 'public/css/admin' . $suffix . '.css', false, WOO_MAILERLITE_VER);

    // Ajax
    wp_localize_script('woo-ml-admin-script', 'woo_ml_post', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'woo_ml_admin_scripts', 100);

function woo_ml_public_scripts()
{

    if ( ! get_option('ml_account_authenticated') ) {

        return false;
    }

    wp_enqueue_script('woo-ml-public-script', WOO_MAILERLITE_URL . 'public/js/public.js', array('jquery'), WOO_MAILERLITE_VER);
    wp_localize_script('woo-ml-public-script', 'woo_ml_public_post', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'woo_ml_public_scripts', 100);