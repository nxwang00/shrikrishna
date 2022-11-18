<?php
/**
 * Integration Demo Integration.
 *
 * @package  Woo_Mailerlite_Integration
 * @category Integration
 */

if ( ! class_exists( 'Woo_Mailerlite_Integration' ) ) :

    class Woo_Mailerlite_Integration extends WC_Integration {

        private $api_key = '';
        private $api_status;
        private $double_optin;

        /**
         * Init and hook in the integration.
         */
        public function __construct() {
            global $woocommerce;

            $this->id                 = 'mailerlite';
            $this->method_title       = __( 'MailerLite', 'woo-mailerlite' );
            $this->method_description = __( 'Connect WooCommerce with MailerLite', 'woo-mailerlite' );

            $request = $_REQUEST;
            //making a request only on load of the integrations page
            if (isset($request['page']) && isset($request['tab'])
                && $request['page'] == 'wc-settings'
                && $request['tab'] == 'integration') {
                    $this->getShopSettingsFromDb();
            }
            // Load the settings.
            $this->create_new_initial_segments();
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables.
            $this->api_key          = $this->get_option('api_key');
            $this->api_status       = $this->get_option('api_status', false);
            $this->double_optin     = $this->get_option('double_optin', 'no');
            $this->popups           = $this->get_option('popups', 'no');
            $this->group            = $this->get_option('group', null);
            $this->resubscribe      = $this->get_option('resubscribe', 'no');
            
            // Actions.
            add_action( 'woocommerce_update_options_integration_' .  $this->id, array( $this, 'process_admin_options' ) );
            // Filters.
            add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );

        }


        /**
         * Initialize integration settings form fields.
         *
         * @return void
         */
        public function init_form_fields() {

            $notice_msg = get_transient( 'ml-admin-notice-invalid-key' );

            if( false !== $notice_msg && is_admin() ){

                add_action('admin_notices', function() use ($notice_msg) {

                    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( 'notice notice-error is-dismissible' ), esc_html( $notice_msg ) );
                });

                delete_transient( 'ml-admin-notice-invalid-key' );
            }

            if (get_option('ml_account_authenticated') || $this->get_option( 'api_status')) {
                $this->form_fields = array(
                    'api_key' => array(
                        'title'             => __( 'MailerLite API Key', 'woo-mailerlite' ),
                        'type'              => 'text',
                        'description'       => sprintf( wp_kses( __( 'You can find your Developer API key <a href="%s" target="_blank">here</a>.', 'woo-mailerlite' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://www.mailerlite.com/integrations/woocommerce' ) ),
                        'desc_tip'          => false,
                        'default'           => '',
                    )
                );

                if ((int) get_option('woo_mailerlite_platform', 1) !== ApiType::REWRITE) {
                    $this->form_fields = array_merge($this->form_fields, [
                        'consumer_key' => array(
                            'title'             => __( 'Consumer Key', 'woo-mailerlite' ),
                            'type'              => 'text',
                            'description'       => sprintf( wp_kses( __( 'Find out how to generate key <a href="https://docs.woocommerce.com/document/woocommerce-rest-api/" target="_blank">here</a>.', 'woo-mailerlite' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ) ),
                            'desc_tip'          => false,
                            'default'           => '',
                        ),
                        'consumer_secret' => array(
                            'title'             => __( 'Consumer Secret', 'woo-mailerlite' ),
                            'type'              => 'text',
                            'description'       => sprintf( wp_kses( __( 'Find out how to generate secret <a href="https://docs.woocommerce.com/document/woocommerce-rest-api/" target="_blank">here</a>.', 'woo-mailerlite' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ) ),
                            'desc_tip'          => false,
                            'default'           => '',
                        )
                    ]);
                }

                $this->form_fields = array_merge($this->form_fields, [
                    'group' => array(
                        'title' 		=> __( 'Group', 'woo-mailerlite' ),
                        'type' 			=> 'select',
                        'class'         => 'wc-enhanced-select',
                        'description' => __( 'The default group which will be taken for new subscribers', 'woo-mailerlite' ),
                        'default' 		=> '',
                        'options'		=> [ '' => __('Please wait...', 'woo-mailerlite' ) ],
                        'desc_tip' => true
                    ),
                    'resubscribe' => array(
                        'title'         => __('Resubscribe', 'woo-mailerlite'),
                        'type'          => 'checkbox',
                        'label'         => __('Check in order to resubscribe inactive subscribers once they subscribe via the checkout page'),
                        'default'       => 'no'
                    ),
                    'checkout' => array(
                        'title'             => __( 'Checkout', 'woo-mailerlite' ),
                        'type'              => 'checkbox',
                        'label'             => __( 'Enable list subscription via checkout page', 'woo-mailerlite' ),
                        'default'           => 'yes'
                    ),
                    'checkout_position' => array(
                        'title' 		=> __( 'Position', 'woo-mailerlite' ),
                        'type' 			=> 'select',
                        'class'         => 'wc-enhanced-select',
                        'default' 		=> 'checkout_billing',
                        'options'		=> array(
                            'checkout_billing' => __( 'After billing details', 'woo-mailerlite' ),
                            'checkout_shipping' => __( 'After shipping details', 'woo-mailerlite' ),
                            'checkout_after_customer_details' => __( 'After customer details', 'woo-mailerlite' ),
                            'review_order_before_submit' => __( 'Before submit button', 'woo-mailerlite' )
                        ),
                    ),
                    'checkout_preselect' => array(
                        'title'             => __( 'Preselect checkbox', 'woo-mailerlite' ),
                        'type'              => 'checkbox',
                        'label'             => __( 'Check to preselect the signup checkbox by default', 'woo-mailerlite' ),
                        'default'           => 'yes'
                    ),
                    'checkout_hide' => array(
                        'title'             => __( 'Hide checkbox', 'woo-mailerlite' ),
                        'type'              => 'checkbox',
                        'label'             => __( 'Check to hide the checkbox. All customers will be subscribed automatically', 'woo-mailerlite' ),
                        'default'           => 'yes'
                    ),
                    'checkout_label' => array(
                        'title'             => __( 'Checkbox label', 'woo-mailerlite' ),
                        'type'              => 'text',
                        'description'       => __( 'Text shown beside the checkbox.', 'woo-mailerlite' ),
                        'default'           => __( 'Yes, I want to receive your newsletter.', 'woo-mailerlite' ),
                        'desc_tip' => true
                    ),
                    'double_optin' => array(
                        'title'             => __( 'Double Opt-In', 'woo-mailerlite' ),
                        'type'              => 'checkbox',
                        'label'             => __( 'Check to enforce email confirmation before being added to your list', 'woo-mailerlite' ),
                        'description'       => __( 'Changing this setting will automatically update your double opt-in setting for your MailerLite account.', 'woo-mailerlite' ),
                        'default'           => 'yes',
                        'desc_tip'          => true
                    ),
                ]);

                if ((int) get_option('woo_mailerlite_platform', 1) === ApiType::REWRITE) {
                    $this->form_fields = array_merge($this->form_fields, [
                        'category_tracking_sync' => array(
                            'title'       => 'Synchronize Categories',
                            'type'        => 'woo_ml_sync_categories',
                            'description' => __("Synchronizing categories whose category data haven't been submitted to MailerLite.",
                                'woo-mailerlite'),
                            'desc_tip'    => true,
                        ),
                        'product_tracking_sync'  => array(
                            'title'       => 'Synchronize Products',
                            'type'        => 'woo_ml_sync_products',
                            'description' => __("Synchronizing products whose product data haven't been submitted to MailerLite.",
                                'woo-mailerlite'),
                            'desc_tip'    => true,
                        )
                    ]);
                }

                $this->form_fields = array_merge($this->form_fields, [
                    'order_tracking_sync' => array(
                        'title'             => 'Synchronize Orders',
                        'type'              => 'woo_ml_sync_orders',
                        'description'       => __( "Synchronizing orders whose customer and order data haven't been submitted to MailerLite.", 'woo-mailerlite' ),
                        'desc_tip'          => true,
                    ),
                    'popups' => array(
                        'title'             => __( 'MailerLite Pop-ups', 'woo-mailerlite' ),
                        'type'              => 'checkbox',
                        'label'             => __( 'Enable MailerLite subscribe pop-ups', 'woo-mailerlite' ),
                        'default'           => 'no',
                        'desc_tip'          => true
                    ),
                    'ignore_product_list' =>array(
                        'title' 		=> __( 'Ignore Products', 'woo-mailerlite' ),
                        'type' 			=> 'multiselect',
                        'class'         => 'wc-enhanced-select',
                        'description' => __( 'Select products that you do not wish to trigger any e-commerce automations', 'woo-mailerlite' ),
                        'default' 		=> '',
                        'options'		=> woo_ml_get_product_list(),
                        'desc_tip' => true
                    ),
                    'auto_update_plugin' =>array(
                        'title' 		=> __( 'Auto-updates', 'woo-mailerlite' ),
                        'type' 			=> 'checkbox',
                        'label'         => __( 'Check to receive the latest updates automatically', 'woo-mailerlite' ),
                        'default' 		=> 'yes',
                        'desc_tip' => true
                    )
                ]);
            } else {
                $this->form_fields = array(
                    'api_key' => array(
                        'title'             => __( 'MailerLite API Key', 'woo-mailerlite' ),
                        'type'              => 'text',
                        'description'       => sprintf( wp_kses( __( 'You can find your Developer API key <a href="%s" target="_blank">here</a>.', 'woo-mailerlite' ), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url( 'https://www.mailerlite.com/integrations/woocommerce' ) ),
                        'desc_tip'          => false,
                        'default'           => '',
                    )
                );
            }
            
        }

        /**
         * Generate Synchronize Existing Orders HTML.
         *
         * @access public
         * @param mixed $key
         * @param mixed $data
         * @since 1.0.0
         * @return string
         */
        public function generate_woo_ml_sync_orders_html( $key, $data ) {
            $field    = $this->plugin_id . $this->id . '_' . $key;
            $defaults = array(
                'class'             => 'button-secondary',
                'css'               => '',
                'custom_attributes' => array(),
                'desc_tip'          => false,
                'description'       => '',
                'title'             => '',
            );

            $data = wp_parse_args( $data, $defaults );
            
            $untracked_orders_count = woo_ml_count_untracked_orders_count();
            $tracked_orders_count = woo_ml_tracked_orders_count();
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                    <?php echo $this->get_tooltip_html( $data ); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <?php if (! get_transient('woo_ml_order_sync_in_progress')) { ?>
                            <input type="hidden" name="ml_platform" id="ml_platform" value="<?php echo get_option('woo_mailerlite_platform', 1); ?>" />
                        <?php } ?>
                        <?php if ( ! $this->api_status ) { ?>
                            <p class="description">
                                <?php _e('Plugin not connected to MailerLite yet.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } elseif ( $tracked_orders_count === 0 && $untracked_orders_count === 0) { ?>
                            <p class="description">
                                <?php _e('No completed orders found.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } elseif ( ! woo_ml_integration_setup_completed() ) { ?>
                            <p class="description">
                                <?php printf( wp_kses( __( 'MailerLite integration setup not completed yet. Please <a href="%s">click here</a>.', 'woo-mailerlite' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( woo_ml_get_complete_integration_setup_url() ) ); ?>
                            </p>
                        <?php } elseif ( ! empty( $untracked_orders_count ) && ! get_transient('woo_ml_order_sync_in_progress')) { ?>
                            <legend class="screen-reader-text">
                                <span><?php echo wp_kses_post( $data['title'] ); ?></span>
                            </legend>
                            <button id="woo-ml-sync-untracked-orders" class="button-secondary" data-woo-ml-sync-untracked-orders="true"
                                    data-woo-ml-untracked-orders-count="<?php echo $untracked_orders_count; ?>" 
                                    data-woo-ml-untracked-orders-left="<?php echo $untracked_orders_count; ?>">
                                <?php printf( esc_html( _n( 'Synchronize %d untracked order', 'Synchronize %d untracked orders', $untracked_orders_count  ) ), $untracked_orders_count ); ?>
                            </button>
                            <div id="woo-ml-sync-untracked-orders-progress-bar" class="woo-ml-progress-bar">
                                <div><?php _e('Syncing in progress. This may take a while. Please do not close this page until syncing finishes.', 'woo-mailerlite'); ?></div>
                            </div>
                            <p id="woo-ml-sync-untracked-orders-success" class="description" style="display: none; color: green; font-style: normal;">
                                <?php printf( esc_html('Untracked orders successfully submitted to MailerLite.','woo-mailerlite') ); ?>
                            </p>
                            <p id="woo-ml-sync-untracked-orders-fail" class="description" style="display: none; color: red; font-style: normal;">
                                <?php printf( esc_html('Oops, we did not manage to sync all of your orders, please try again.','woo-mailerlite') ); ?>
                            </p>
                            <?php echo $this->get_description_html( $data ); ?>

                        <?php } else if (empty($untracked_orders_count) && ! get_transient('woo_ml_order_sync_in_progress')) { ?>
                            <button id="woo-ml-reset-orders-sync" class="button-secondary" data-woo-ml-reset-orders-sync="true">
                                <?php _e( 'Reset order synchronization' ); ?>
                            </button>

                        <?php } else if (! empty($untracked_orders_count) && get_transient('woo_ml_order_sync_in_progress')) { ?>
                            <div id="woo-ml-sync-untracked-orders-progress-bar" style="display: block; color: black;" class="woo-ml-progress-bar">
                            <div>
                                <?php printf(esc_html(_n('Synchronizing of %d untracked order in progress', 'Synchronizing of %d untracked orders in progress', $untracked_orders_count)), $untracked_orders_count);?>
                            </div>
                        </div>
                        <?php } else { ?>
                            <p class="description">
                                <?php _e('Right now there are no untracked orders.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } ?>
                    </fieldset>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        /**
         * Generate Synchronize Products HTML.
         *
         * @access public
         * @param mixed $key
         * @param mixed $data
         * @since 1.0.0
         * @return string
         */
        public function generate_woo_ml_sync_products_html( $key, $data ) {
            $field    = $this->plugin_id . $this->id . '_' . $key;
            $defaults = array(
                'class'             => 'button-secondary',
                'css'               => '',
                'custom_attributes' => array(),
                'desc_tip'          => false,
                'description'       => '',
                'title'             => '',
            );

            $data = wp_parse_args( $data, $defaults );

            $untracked_products_count = woo_ml_count_untracked_products_count();
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                    <?php echo $this->get_tooltip_html( $data ); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <?php if ( ! $this->api_status ) { ?>
                            <p class="description">
                                <?php _e('Plugin not connected to MailerLite yet.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } elseif ( get_option('woo_ml_shop_id', false) === false ) { ?>
                            <p class="description">
                                <?php _e('Plugin not connected to MailerLite yet.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } elseif ( ! woo_ml_integration_setup_completed() ) { ?>
                            <p class="description">
                                <?php printf( wp_kses( __( 'MailerLite integration setup not completed yet. Please <a href="%s">click here</a>.', 'woo-mailerlite' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( woo_ml_get_complete_integration_setup_url() ) ); ?>
                            </p>
                        <?php } elseif ( ! empty( $untracked_products_count ) && ! get_transient('woo_ml_product_sync_in_progress')) { ?>
                            <legend class="screen-reader-text">
                                <span><?php echo wp_kses_post( $data['title'] ); ?></span>
                            </legend>
                            <button id="woo-ml-sync-untracked-products" class="button-secondary" data-woo-ml-sync-untracked-products="true"
                                    data-woo-ml-untracked-products-count="<?php echo $untracked_products_count; ?>"
                                    data-woo-ml-untracked-products-left="<?php echo $untracked_products_count; ?>">
                                <?php printf( esc_html( _n( 'Synchronize %d untracked products', 'Synchronize %d untracked products', $untracked_products_count  ) ), $untracked_products_count ); ?>
                            </button>
                            <div id="woo-ml-sync-untracked-products-progress-bar" class="woo-ml-progress-bar">
                                <div><?php _e('Syncing in progress. This may take a while. Please do not close this page until syncing finishes.', 'woo-mailerlite'); ?></div>
                            </div>
                            <p id="woo-ml-sync-untracked-products-success" class="description" style="display: none; color: green; font-style: normal;">
                                <?php printf( esc_html('Untracked products successfully submitted to MailerLite.','woo-mailerlite') ); ?>
                            </p>
                            <p id="woo-ml-sync-untracked-products-fail" class="description" style="display: none; color: red; font-style: normal;">
                                <?php printf( esc_html('Oops, we did not manage to sync all of your products, please try again.','woo-mailerlite') ); ?>
                            </p>
                            <?php echo $this->get_description_html( $data ); ?>

                        <?php } else if (empty($untracked_products_count) && ! get_transient('woo_ml_product_sync_in_progress')) { ?>

                            <button id="woo-ml-reset-products-sync" class="button-secondary" data-woo-ml-reset-products-sync="true">
                                <?php _e( 'Reset product synchronization' ); ?>
                            </button>

                        <?php } else if (! empty($untracked_products_count) && get_transient('woo_ml_product_sync_in_progress')) { ?>
                            <div id="woo-ml-sync-untracked-products-progress-bar" style="display: block; color: black;" class="woo-ml-progress-bar">
                                <div>
                                    <?php printf(esc_html(_n('Synchronizing of %d untracked products in progress', 'Synchronizing of %d untracked products in progress', $untracked_products_count)), $untracked_products_count);?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <p class="description">
                                <?php _e('Right now there are no untracked products.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } ?>
                    </fieldset>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        /**
         * Generate Synchronize Categories HTML.
         *
         * @access public
         * @param mixed $key
         * @param mixed $data
         * @since 1.0.0
         * @return string
         */
        public function generate_woo_ml_sync_categories_html( $key, $data ) {
            $field    = $this->plugin_id . $this->id . '_' . $key;
            $defaults = array(
                'class'             => 'button-secondary',
                'css'               => '',
                'custom_attributes' => array(),
                'desc_tip'          => false,
                'description'       => '',
                'title'             => '',
            );

            $data = wp_parse_args( $data, $defaults );

            $untracked_categories_count = woo_ml_count_untracked_categories_count();
            ob_start();
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
                    <?php echo $this->get_tooltip_html( $data ); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <?php if ( ! $this->api_status ) { ?>
                            <p class="description">
                                <?php _e('Plugin not connected to MailerLite yet.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } elseif ( get_option('woo_ml_shop_id', false) === false ) { ?>
                            <p class="description">
                                <?php _e('Plugin not connected to MailerLite yet.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } elseif ( ! woo_ml_integration_setup_completed() ) { ?>
                            <p class="description">
                                <?php printf( wp_kses( __( 'MailerLite integration setup not completed yet. Please <a href="%s">click here</a>.', 'woo-mailerlite' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( woo_ml_get_complete_integration_setup_url() ) ); ?>
                            </p>
                        <?php } elseif ( ! empty( $untracked_categories_count ) && ! get_transient('woo_ml_categories_sync_in_progress')) { ?>
                            <legend class="screen-reader-text">
                                <span><?php echo wp_kses_post( $data['title'] ); ?></span>
                            </legend>
                            <button id="woo-ml-sync-untracked-categories" class="button-secondary" data-woo-ml-sync-untracked-categories="true"
                                    data-woo-ml-untracked-categories-count="<?php echo $untracked_categories_count; ?>"
                                    data-woo-ml-untracked-categories-left="<?php echo $untracked_categories_count; ?>">
                                <?php printf( esc_html( _n( 'Synchronize %d untracked categories', 'Synchronize %d untracked categories', $untracked_categories_count  ) ), $untracked_categories_count ); ?>
                            </button>
                            <div id="woo-ml-sync-untracked-categories-progress-bar" class="woo-ml-progress-bar">
                                <div><?php _e('Syncing in progress. This may take a while. Please do not close this page until syncing finishes.', 'woo-mailerlite'); ?></div>
                            </div>
                            <p id="woo-ml-sync-untracked-categories-success" class="description" style="display: none; color: green; font-style: normal;">
                                <?php printf( esc_html('Untracked categories successfully submitted to MailerLite.','woo-mailerlite') ); ?>
                            </p>
                            <p id="woo-ml-sync-untracked-categories-fail" class="description" style="display: none; color: red; font-style: normal;">
                                <?php printf( esc_html('Oops, we did not manage to sync all of your categories, please try again.','woo-mailerlite') ); ?>
                            </p>
                            <?php echo $this->get_description_html( $data ); ?>

                        <?php } else if (empty($untracked_categories_count) && ! get_transient('woo_ml_categories_sync_in_progress')) { ?>

                            <button id="woo-ml-reset-categories-sync" class="button-secondary" data-woo-ml-reset-categories-sync="true">
                                <?php _e( 'Reset categories synchronization' ); ?>
                            </button>

                        <?php } else if (! empty($untracked_categories_count) && get_transient('woo_ml_categories_sync_in_progress')) { ?>
                            <div id="woo-ml-sync-untracked-categories-progress-bar" style="display: block; color: black;" class="woo-ml-progress-bar">
                                <div>
                                    <?php printf(esc_html(_n('Synchronizing of %d untracked categories in progress', 'Synchronizing of %d untracked categories in progress', $untracked_categories_count)), $untracked_categories_count);?>
                                </div>
                            </div>
                        <?php } else { ?>
                            <p class="description">
                                <?php _e('Right now there are no untracked categories.', 'woo-mailerlite' ); ?>
                            </p>
                        <?php } ?>
                    </fieldset>
                </td>
            </tr>
            <?php
            return ob_get_clean();
        }

        public function verify_custom_fields()
        {
            if (is_admin() && get_option('ml_account_authenticated')) {
                woo_ml_setup_integration_custom_fields();
            }
        }

        public function create_new_initial_segments()
        {
            if (! get_option('ml_new_group_segments')) {
                mailerlite_wp_set_consumer_data("....", "....", $this->get_option('group'),$this->get_option('resubscribe'), [], true);
                update_option('ml_new_group_segments', true);
            }
        }
        /**
         * Getting groups, selected group, double optiin and popups 
         * settings from MailerLite, only on load of the integrations page.
         */
        public function getShopSettingsFromDb()
        {
            $result = mailerlite_wp_get_shop_settings_from_db();
            $api_key = get_option('woo_ml_key');
            if(!$api_key) {
                if (! empty($this->get_option( 'api_key' ))) {
                    update_option('woo_ml_key', $this->get_option( 'api_key' ));

                    $temp_key = '';
                    if(! empty($api_key)) {
                        $temp_key = "....".substr($api_key, -4);
                    }
                    $this->update_option('api_key', $temp_key);
                }
            } else {
                $temp_key = '';
                if(! empty($api_key)) {
                    $temp_key = "....".substr($api_key, -4);
                }
                $this->update_option('api_key', $temp_key);
            }
            if (!empty($result) && isset($result->settings)) {

                $settings = $result->settings;

                $doi = (get_option('double_optin') == true || get_option('double_optin') == 'yes') ? 'yes' : 'no';

                if ((int) get_option('woo_mailerlite_platform', 1) === ApiType::CURRENT && $doi !== $settings->double_optin) {

                    $doi = $settings->double_optin;
                    update_option('double_optin', $doi);
                }

                $this->update_option('double_optin', $doi);

                $resubscribe = $settings->resubscribe ? 'yes' : 'no';
                $this->update_option('resubscribe', $resubscribe);
                $this->update_option('group', $settings->group_id);
                update_option('woo_ml_last_manually_tracked_order_id', $settings->last_tracked_order_id);    
                $popups_disabled = get_option('mailerlite_popups_disabled');
                $this->update_option('popups', $popups_disabled ? 'no' : 'yes');
                $auto_update_enabled = get_option('woo_ml_auto_update');
                $this->update_option('auto_update_plugin', $auto_update_enabled ? 'yes' : 'no');
            } elseif (isset($result->active_state)) {
                update_option('ml_shop_not_active', true);
            }
            
        }

        /**
         * Santize our settings
         * @see process_admin_options()
         */
        public function sanitize_settings( $settings ) {
            $setup_integration = false;
            $revoke_integration_setup = false;

            if ( isset( $settings['api_key'] ) ) {

                $api_status = $this->api_status;
                $api_key = $this->api_key;

                if ( empty( $settings['api_key'] ) ) {

                    $api_status = false;
                    $revoke_integration_setup = true;
                    delete_option('woo_ml_key');
                    delete_option('ml_account_authenticated');
                } elseif ( ! empty( $settings['api_key'] ) && $settings['api_key'] != $api_key ) {
                    $validation = woo_ml_validate_api_key( esc_html( $settings['api_key'] ) );
                    $api_status = ( $validation );

                    if ( $api_status ) {
                        $setup_integration = true;
                        update_option('woo_ml_key', $settings['api_key']);
                    }
                    $settings['api_key'] = "....".substr($settings['api_key'], -4);
                }

                // Store API validation
                $settings['api_status'] = $api_status;

            }

            // Handle Double Opt-In
            if ( isset( $settings['double_optin'] ) ) {

                if ( $settings['double_optin'] != $this->double_optin ) {

                    $double_optin = ( 'yes' === $settings['double_optin'] ) ? true : false;

                    $settings['double_optin'] = mailerlite_wp_set_double_optin( $double_optin ) === true ? 'yes' : 'no';
                }
            }

            if($settings['popups'] !== $this->popups) {
                $popups_disabled = $settings['popups'] === 'no' ? 1 : 0;
                update_option('mailerlite_popups_disabled', $popups_disabled);
            }

            if ( isset( $settings['auto_update_plugin'] ) ) {
                $auto_update_enabled = $settings['auto_update_plugin'] === 'yes' ? 1 : 0;
                update_option('woo_ml_auto_update', $auto_update_enabled);
            }

            // save shop to our db for ecommerce tracking
            // hiding the ck and cs values once save performed as we don't need to have them saved here anyway
            // we only need them for backwards connection from classic api to plugin to get products and categories.
            if ( (! empty($settings['consumer_key']) && ! empty($settings['consumer_secret'])) || (int) get_option('woo_mailerlite_platform', 1) === ApiType::REWRITE) {

                if ( (int) get_option('woo_mailerlite_platform', 1) === ApiType::REWRITE ) {
                    $settings['consumer_key'] = '';
                    $settings['consumer_secret'] = '';
                }

                $resubscribe = $settings['resubscribe'] === 'yes' ? 1 : 0;
                $result = mailerlite_wp_set_consumer_data(
                                $settings['consumer_key'],
                                $settings['consumer_secret'],
                                $settings['group'],
                                $resubscribe,
                                $settings['ignore_product_list']);

                if (isset($result['errors']) || $result === false)  {
                    $settings['consumer_key']  = '';
                    $settings['consumer_secret'] = '';

                    $error_msg = $result['errors'];

                    add_action('admin_notices', function() use ($error_msg) {

                        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( 'notice notice-error is-dismissible' ), esc_html( $error_msg ) );
                    });
                } else {
                    $settings['consumer_key']  = '....'.substr($settings['consumer_key'], -4);
                    $settings['consumer_secret'] = '....'.substr($settings['consumer_secret'], -4);

                    //update product ignore list in ml_data
                    $ignore_list = woo_ml_get_product_list();

                    $products = array_filter( $ignore_list, function( $k ) use ( $settings ) {
                        return in_array( $k, $settings['ignore_product_list'] );
                    }, ARRAY_FILTER_USE_KEY);

                    woo_ml_update_data($products);
                }
            }

            // Handle integration setup
            if ( $revoke_integration_setup )
                woo_ml_revoke_integration_setup();

            if ( $setup_integration )
                woo_ml_setup_integration();

            // Check if custom fields exist
            $this->verify_custom_fields();

            // Return sanitized settings
            return $settings;
        }
    }

endif;