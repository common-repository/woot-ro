<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://woot.ro
 * @since      1.0.0
 *
 * @package    Woot
 * @subpackage Woot/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woot
 * @subpackage Woot/public
 * @author     Woot.ro <tehnic@woot.ro>
 */
class Woot_Public
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woot_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woot_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woot-public.css', array(), $this->version, 'all');

		if (is_checkout()) {
			wp_enqueue_style('woot-leaflet', plugin_dir_url(__FILE__) . 'css/leaflet.css', array(), $this->version, 'all');
			wp_enqueue_style('woot-markercluster', plugin_dir_url(__FILE__) . 'css/MarkerCluster.css', array(), $this->version, 'all');
			wp_enqueue_style('woot-markercluster-default', plugin_dir_url(__FILE__) . 'css/MarkerCluster.Default.css', array(), $this->version, 'all');
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woot_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woot_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woot-public.js', array('jquery'), $this->version, false);

		if (is_cart() || is_checkout()) {
			$plugin_woocommerce = new Woot_Woocommerce();

			// $couriers = $plugin_woocommerce->get_couriers(true);

			if (is_cart() || is_checkout() || is_wc_endpoint_url('edit-address')) {
				// Get cities list
				$cities = json_encode($plugin_woocommerce->get_cities());

				// Cities
				wp_enqueue_script('woot-city', plugin_dir_url(__FILE__) . 'js/woot-city.min.js', array('jquery', 'woocommerce'), $this->version, false);

				wp_localize_script('woot-city', 'woot_select_params', array(
					'cities' => $cities,
					'i18n_select_city_text' => esc_attr__('Select an option&hellip;', 'woocommerce')
				));
			}

			if (is_checkout()) {
				// Locations
				wp_enqueue_script('woot-locations', plugin_dir_url(__FILE__) . 'js/woot-locations.min.js', array('jquery', 'woocommerce'), $this->version, false);

				$shipping_methods = WC()->session->get('chosen_shipping_methods');
				$shipping_method = explode(':', $shipping_methods[0]);

				if (!empty($shipping_method)) {
					$settings = get_option('woocommerce_' . $shipping_method[0] . '_' . $shipping_method[1] . '_settings');

					if (!empty($settings['couriers'])) {
						$couriers = $settings['couriers'];
					}
				}

				wp_localize_script('woot-locations', 'woot_locations_params', array(
					'couriers' => $couriers ?? [],
					'logo' => [
						'cargus' => plugins_url($this->plugin_name . '/public/css/images/couriers/cargus.png'),
						'dpd' => plugins_url($this->plugin_name . '/public/css/images/couriers/dpd.png'),
						'fancourier' => plugins_url($this->plugin_name . '/public/css/images/couriers/fancourier.png'),
						'gls' => plugins_url($this->plugin_name . '/public/css/images/couriers/gls.png'),
						'sameday' => plugins_url($this->plugin_name . '/public/css/images/couriers/sameday.png'),
						'postaromana' => plugins_url($this->plugin_name . '/public/css/images/couriers/postaromana.png')
					]
				));

				wp_enqueue_script('woot-leaflet', plugin_dir_url(__FILE__) . 'js/leaflet.js', array('jquery'), $this->version, false);
				wp_enqueue_script('woot-leaflet-markcluster', plugin_dir_url(__FILE__) . 'js/leaflet.markercluster.js', array('jquery'), $this->version, false);
			}
		}
	}
}
