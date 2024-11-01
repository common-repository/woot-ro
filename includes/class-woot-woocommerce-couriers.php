<?php

/**
 * Define the woocommerce couriers method functionality.
 *
 * Loads and defines the woocommerce files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woot
 * @subpackage Woot/includes
 * @author     Woot.ro <tehnic@woot.ro>
 */
class Woot_Woocommerce_Couriers extends WC_Shipping_Method
{
    /**
     * Woot_Woocommerce_Shipping constructor.
     *
     * @param int $instance_id
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = 'woot_couriers';
        $this->method_title = __('Woot.ro - Couriers', 'woot_couriers');
        $this->method_description = __('Shipping method for Woot.ro couriers.', 'woot_couriers');

        $this->supports = array(
            'shipping-zones',
            'instance-settings'
        );

        $this->init();

        $this->enabled = isset($this->instance_settings['enabled']) ? $this->instance_settings['enabled'] : 'yes';
        $this->title = !empty($this->instance_settings['title']) ? $this->instance_settings['title'] : __('Courier shipping', 'woot_couriers');
    }

    private function init(): void
    {
        $this->init_form_fields();
        $this->init_instance_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Checks to see whether or not the admin settings are being accessed by the current request.
     *
     * @return bool
     */
    private function is_accessing_settings()
    {
        if (is_admin()) {
            if (!isset($_REQUEST['page']) || 'wc-settings' !== $_REQUEST['page']) {
                return false;
            }
            if (!isset($_REQUEST['tab']) || 'shipping' !== $_REQUEST['tab']) {
                return false;
            }
            return true;
        }
        return false;
    }

    public function process_admin_options(): void
    {
        $post_data = $this->get_post_data();
        $this->set_post_data($post_data);
        parent::process_admin_options();
    }

    public function calculate_shipping($package = array()): void
    {
        $cost = $this->instance_settings['shipping_price'] ?? 0;

        if (!empty($this->instance_settings['shipping_free']) &&  WC()->cart->subtotal >= $this->instance_settings['shipping_free']) {
            $cost = 0;
        }

        $this->add_rate([
            'label' => $this->instance_settings['title'] ?? __('Courier shipping', 'woot_couriers'),
            'cost' => $cost
        ]);
    }

    public function init_form_fields()
    {
        if (!$this->is_accessing_settings()) return;

        $fields = array(
            'title' => array(
                'title' => __('Title', 'woot_couriers'),
                'type' => 'text',
                'description' => __('Title to be display on site', 'woot_couriers'),
                'default' => __('Courier shipping', 'woot_couriers')
            ),

            'shipping_price' => array(
                'title' => __('Cost', 'woot_couriers'),
                'type' => 'number',
                'description' => __('Shipping price', 'woot_couriers'),
                'default' => __('', 'woot_couriers')
            ),

            'shipping_free' => array(
                'title' => __('Free', 'woot_couriers'),
                'type' => 'number',
                'description' => __('Minimum subtotal for free shipping', 'woot_couriers'),
                'default' => __('', 'woot_couriers')
            )
        );

        $gateways = WC()->payment_gateways()->get_available_payment_gateways();

        if ($gateways) {
            $fields[] = array(
                'title' => __('Payment methods fees', 'woot_couriers'),
                'type' => 'title',
                'description' => __('Extra charges for specific payment method alongside this shipping method.', 'woot_couriers'),
                'default' => __('', 'woot_couriers')
            );

            foreach ($gateways as $gateway) {
                if ($gateway->enabled == 'yes') {
                    $fields[$gateway->id . '_price'] = array(
                        'title' => $gateway->title,
                        'type' => 'number',
                        'default' => __('', 'woot_couriers'),
                        'desc_tip' => $gateway->method_description
                    );
                }
            }
        }

        $this->instance_form_fields = $fields;
    }
}
