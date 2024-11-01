<?php

/**
 * Define the woocommerce functionality.
 *
 * Loads and defines the woocommerce files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Woot
 * @subpackage Woot/includes
 * @author     Woot.ro <tehnic@woot.ro>
 */
class Woot_Woocommerce
{
    private $couriers = [];
    private $cities = [];
    private $locations = [];

    private $dropdown_cities;

    public function get_couriers($enabled = false)
    {
        if (empty($this->couriers)) {
            if (!$response = get_transient('woot_couriers')) {
                $request = wp_remote_get('https://ws.woot.ro/latest/general/couriers');

                if (is_array($request) && !is_wp_error($request)) {
                    $response = $request['body'];
                    set_transient('woot_couriers', $response, 24 * 60 * 60);
                }
            }

            if (!empty($response)) {
                $results = json_decode($response, true);
                $this->couriers = apply_filters('woot_couriers', $results);
            }
        }

        // Return only enabled couriers
        if ($enabled && (is_cart() || is_checkout())) {
            $shipping_methods = WC()->session->get('chosen_shipping_methods');
            $shipping_method = explode(':', $shipping_methods[0]);

            if (!empty($shipping_method[0])) {
                $settings = get_option('woocommerce_' . $shipping_method[0] . '_' . $shipping_method[1] . '_settings');

                if (!empty($settings['couriers'])) {
                    $couriers = $settings['couriers'];

                    $this->couriers = array_values(array_filter($this->couriers, function ($row) use ($couriers) {
                        return in_array($row['uid'], $couriers);
                    }));
                }
            }
        }

        return $this->couriers;
    }

    /**
     * Get cities
     * @param mixed $country_code 
     * @return mixed 
     */
    public function get_cities($country_code = null)
    {
        if (empty($this->cities)) {
            $allowed = array_merge(WC()->countries->get_allowed_countries(), WC()->countries->get_shipping_countries());

            $cities = [];

            if ($allowed) {
                foreach ($allowed as $code => $country) {
                    if ($code === 'RO') {

                        if (!$response = get_transient('woot_cities')) {
                            $request = wp_remote_get('https://ws.woot.ro/latest/general/cities');

                            if (is_array($request) && !is_wp_error($request)) {
                                $response = $request['body'];
                                set_transient('woot_cities', $response, 24 * 60 * 60);
                            }
                        }

                        if (!empty($response)) {
                            $results = json_decode($response, true);

                            foreach ($results as $result) {
                                if (!isset($cities[$code][$result['county_code']]))
                                    $cities[$code][$result['county_code']] = [];

                                $cities[$code][$result['county_code']][] = $result['name'];
                            }
                            $this->cities = apply_filters('woot_cities', $cities);
                        }
                    }
                }
            }
        }

        if (!empty($this->cities) && !is_null($country_code)) {
            return isset($this->cities[$country_code]) ? $this->cities[$country_code] : false;
        } else {
            return $this->cities;
        }
    }

    public function billing_fields($fields, $country)
    {
        $fields['billing_city']['type'] = 'city';
        return $fields;
    }

    public function shipping_fields($fields, $country)
    {
        $fields['shipping_city']['type'] = 'city';
        return $fields;
    }

    /**
     * Replace the default city field with a select box.
     */
    public function form_field_city($field, $key, $args, $value)
    {
        // Do we need a clear div?
        if ((!empty($args['clear']))) {
            $after = '<div class="clear"></div>';
        } else {
            $after = '';
        }

        // Required markup
        if ($args['required']) {
            $args['class'][] = 'validate-required';
            $required = ' <abbr class="required" title="' . esc_attr__('required', 'woocommerce') . '">*</abbr>';
        } else {
            $required = '';
        }

        // Custom attribute handling
        $custom_attributes = array();

        if (!empty($args['custom_attributes']) && is_array($args['custom_attributes'])) {
            foreach ($args['custom_attributes'] as $attribute => $attribute_value) {
                $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
            }
        }

        // Validate classes
        if (!empty($args['validate'])) {
            foreach ($args['validate'] as $validate) {
                $args['class'][] = 'validate-' . $validate;
            }
        }

        // field p and label
        $field  = '<p class="form-row ' . esc_attr(implode(' ', $args['class'])) . '" id="' . esc_attr($args['id']) . '_field">';
        if ($args['label']) {
            $field .= '<label for="' . esc_attr($args['id']) . '" class="' . esc_attr(implode(' ', $args['label_class'])) . '">' . $args['label'] . $required . '</label>';
        }

        // Get Country
        $country_key = $key == 'billing_city' ? 'billing_country' : 'shipping_country';
        $current_cc  = WC()->checkout->get_value($country_key);

        $state_key = $key == 'billing_city' ? 'billing_state' : 'shipping_state';
        $current_sc  = WC()->checkout->get_value($state_key);

        // Get country cities
        $cities = $this->get_cities($current_cc);

        if (is_array($cities)) {

            $field .= '<select name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" class="city_select ' . esc_attr(implode(' ', $args['input_class'])) . '" ' . implode(' ', $custom_attributes) . ' placeholder="' . esc_attr($args['placeholder']) . '">
                <option value="">' . __('Select an option&hellip;', 'woocommerce') . '</option>';

            if ($current_sc && $cities[$current_sc]) {
                $this->dropdown_cities = $cities[$current_sc];
            } else {
                $this->dropdown_cities = [];
                array_walk_recursive($cities, array($this, 'add_city_to_dropdown'));
                sort($this->dropdown_cities);
            }

            foreach ($this->dropdown_cities as $city_name) {
                $field .= '<option value="' . esc_attr($city_name) . '" ' . selected($value, $city_name, false) . '>' . $city_name . '</option>';
            }

            $field .= '</select>';
        } else {

            $field .= '<input type="text" class="input-text ' . esc_attr(implode(' ', $args['input_class'])) . '" value="' . esc_attr($value) . '"  placeholder="' . esc_attr($args['placeholder']) . '" name="' . esc_attr($key) . '" id="' . esc_attr($args['id']) . '" ' . implode(' ', $custom_attributes) . ' />';
        }

        // field description and close wrapper
        if ($args['description']) {
            $field .= '<span class="description">' . esc_attr($args['description']) . '</span>';
        }

        $field .= '</p>' . $after;

        return $field;
    }

    private function add_city_to_dropdown($item)
    {
        $this->dropdown_cities[] = $item;
    }

    /**
     * Change default address fields priority
     * @param mixed $fields 
     * @return mixed 
     */
    public function default_address_fields($fields)
    {
        $fields['state']['priority'] = 50;
        $fields['city']['priority'] = 60;
        $fields['address_1']['priority'] = 70;
        $fields['address_2']['priority'] = 80;

        return $fields;
    }

    /**
     * Init the woocommerce couriers class
     */
    public function couriers_init()
    {
        /**
         * The class responsible for defining woocommerce couriers functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woot-woocommerce-couriers.php';

        // Return the instance of the class
        return new Woot_Woocommerce_Couriers();
    }

    /**
     * Init the woocommerce locations class
     */
    public function locations_init()
    {
        /**
         * The class responsible for defining woocommerce shipping functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woot-woocommerce-locations.php';

        // Return the instance of the class
        return new Woot_Woocommerce_Locations();
    }

    /**
     * Add the shipping method to woocommerce
     * @param mixed $methods 
     * @return mixed 
     */
    public function shipping_methods($methods)
    {
        $methods['woot_couriers'] = 'Woot_Woocommerce_Couriers';
        $methods['woot_locations'] = 'Woot_Woocommerce_Locations';
        return $methods;
    }

    public function review_order_after_shipping()
    {
        $post_data = array();

        if (!empty($_POST['post_data'])) {
            parse_str($_POST['post_data'], $post_data);
            wc_clean($post_data);
        }

        if (is_checkout()) {
            $shipping_methods = WC()->session->get('chosen_shipping_methods');
            $shipping_method = explode(':', $shipping_methods[0]);

            if (!empty($shipping_method)) {
                if ($shipping_method[0] === 'woot_locations') {
                    echo '<tr class="woocommerce-wt-locations">';
                    echo '<th></th>';
                    echo '<td>';
                    echo '<button type="button" class="button alt wp-element-button" onclick="wootOpenLocationsMap()" style="width: 100%">' . __('Harta locatii', 'wc-pickup-store') . '</button>';
                    echo '<div class="wt-location-details" id="wt-location-details" style="display: none;"></div>';
                    echo '<div id="wt-locations-modal" class="wt-modal">
                                <div class="wt-modal-content">
                                    <div class="wt-modal-toolbar">
                                        <div class="wt-toolbar-title">Selecteaza un punct de livrare</div>
                                        <div class="wt-toolbar-close"><button type="button" class="button alt wp-element-button" onclick="wootCloseLocationsMap()">Inchide</button></div>
                                    </div>
                                    <div class="wt-modal-body"></div>
                                </div>
                            </div>';
                    echo '</td>';
                    echo '</tr>';
                }
            }
        }
    }

    /**
     * Validate order location
     */
    public function checkout_process()
    {
        $shipping_methods = WC()->session->get('chosen_shipping_methods');
        $shipping_method = explode(':', $shipping_methods[0]);

        if ($shipping_method[0] === 'woot_locations' && empty($_POST['location_id'])) {
            wc_add_notice(__('Please select a location!'), 'error');
        }
    }

    public function checkout_update_order_review($post_data)
    {
        $packages = WC()->cart->get_shipping_packages();

        foreach ($packages as $key => $value) {
            $shipping_session = "shipping_for_package_$key";
            unset(WC()->session->$shipping_session);
        }
        return;
    }

    /**
     * Set location id to order meta
     * @param mixed $order_id 
     * @return void 
     */
    public function checkout_update_order_meta($order_id)
    {
        // Get order
        $order = WC()->order_factory->get_order($order_id);

        foreach ($order->get_shipping_methods() as $shipping_method) {
            // Set location id
            if ($shipping_method->get_method_id() === 'woot_locations') {

                if (!empty($_POST['location_id']))
                    $shipping_method->add_meta_data('location_id', sanitize_text_field($_POST['location_id']), true);

                if (!empty($_POST['location_name']))
                    $shipping_method->add_meta_data('location_name', sanitize_text_field($_POST['location_name']), true);

                if (!empty($_POST['location_address']))
                    $shipping_method->add_meta_data('location_address', sanitize_text_field($_POST['location_address']), true);

                // Save meta data
                $shipping_method->save_meta_data();
            }
        }
    }

    /**
     * Calculate cart fees
     * @param WC_Cart $cart 
     * @return void 
     */
    public function cart_calculate_fees(WC_Cart $cart)
    {
        if (is_checkout()) {
            $shipping = WC()->session->get('chosen_shipping_methods');
            $shipping = explode(':', $shipping[0]);

            if (!empty($shipping[0])) {
                $shipping_settings = get_option('woocommerce_' . $shipping[0] . '_' . $shipping[1] . '_settings');
                $payment_id = WC()->session->get('chosen_payment_method');

                if (!empty($shipping_settings[$payment_id . '_' . 'price'])) {
                    $gateways = WC_Payment_Gateways::instance();
                    $payment = $gateways->payment_gateways()[$payment_id];

                    $cart->add_fee($payment->title, $shipping_settings[$payment_id . '_' . 'price'], !empty($cart->get_shipping_tax()));
                }
            }
        }
    }

    public function after_order_notes(WC_Checkout $checkout)
    {
        echo '<input type="hidden" id="location_id" name="location_id" />';
        echo '<input type="hidden" id="location_name" name="location_name" />';
        echo '<input type="hidden" id="location_address" name="location_address" />';
    }
}
