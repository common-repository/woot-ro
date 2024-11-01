<?php

/**
 * Define the woocommerce shipping method functionality.
 *
 * Loads and defines the woocommerce files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woot
 * @subpackage Woot/includes
 * @author     Woot.ro <tehnic@woot.ro>
 */
class Woot_Woocommerce_Locations extends WC_Shipping_Method
{
    /**
     * Woot_Woocommerce_Locations constructor.
     *
     * @param int $instance_id
     */
    public function __construct($instance_id = 0)
    {
        parent::__construct($instance_id);

        $this->id = 'woot_locations';
        $this->method_title = __('Woot.ro - Locations', 'woot_locations');
        $this->method_description = __('Shipping method for Woot.ro locations.', 'woot_locations');

        $this->supports = array(
            'shipping-zones',
            'instance-settings'
        );

        $this->init();

        $this->enabled = isset($this->instance_settings['enabled']) ? $this->instance_settings['enabled'] : 'yes';
        $this->title = !empty($this->instance_settings['title']) ? $this->instance_settings['title'] : __('Location shipping', 'woot_locations');
    }

    private function init(): void
    {
        $this->init_instance_settings();
        $this->init_form_fields();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function process_admin_options(): bool
    {
        $post_data = $this->get_post_data();

        // Validate couriers
        if (empty($post_data['woocommerce_' . $this->id . '_couriers'])) {
            $this->add_error('Select at least one courier');
            return false;
        }

        $this->set_post_data($post_data);
        return parent::process_admin_options();
    }

    public function calculate_shipping($package = array()): void
    {
        $cost = $this->instance_settings['shipping_price'] ?? 0;

        if (!empty($this->instance_settings['shipping_free']) &&  WC()->cart->subtotal >= $this->instance_settings['shipping_free']) {
            $cost = 0;
        }

        $this->add_rate([
            'label' => $this->instance_settings['title'] ?? __('Location shipping', 'woot_locations'),
            'cost' => $cost,
            // 'meta_data' => array(
            //     'service_id' => 1,
            //     'service_code' => 'location'
            // )
        ]);
    }

    public function init_form_fields()
    {
        $fields = array(
            'title' => array(
                'title' => __('Title', 'woot_locations'),
                'type' => 'text',
                'description' => __('Title to be display on site', 'woot_locations'),
                'default' => __('Location shipping', 'woot_locations')
            ),

            'shipping_price' => array(
                'title' => __('Cost', 'woot_locations'),
                'type' => 'number',
                'description' => __('Shipping price', 'woot_locations'),
                'default' => __('', 'woot_locations')
            ),

            'shipping_free' => array(
                'title' => __('Free', 'woot_locations'),
                'type' => 'number',
                'description' => __('Minimum subtotal for free shipping', 'woot_locations'),
                'default' => __('', 'woot_locations')
            ),

            // 'show_map' => array(
            //     'title'   => __('Show map', 'woot_locations'),
            //     'default' => 'yes',
            //     'type'    => 'select',
            //     'options' => [
            //         'no' => __('No', 'woot_locations'),
            //         'yes' => __('Yes', 'woot_locations'),
            //     ]
            // ),

            'couriers' => array(
                'title' => __('Couriers', 'woot_locations'),
                'type' => 'multiselect',
                'description' => __('Select the couriers you want to appear on the map or in select', 'woot_locations'),
                'default' => [],
                'options' => [
                    // 'sameday' => 'Sameday',
                    // 'fancourier' => 'Fan Courier'
                ]
            )
        );

        $plugin_woocommerce = new Woot_Woocommerce();
        $couriers = $plugin_woocommerce->get_couriers();

        if (!empty($couriers)) {
            foreach ($couriers as $courier) {
                if ($courier['locations']) {
                    $fields['couriers']['options'][$courier['id']] = $courier['name'];
                }
            }
        }

        $this->instance_form_fields = $fields;
    }
}
