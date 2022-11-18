<?php
/**
 * Uninstalling option for hide categories or products
 */

/*
 * Exit if accessed directly
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
delete_option('hcps_data');