<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://woot.ro
 * @since      1.0.0
 *
 * @package    Woot
 * @subpackage Woot/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woot
 * @subpackage Woot/includes
 * @author     Woot.ro <tehnic@woot.ro>
 */
class Woot
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Woot_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WOOT_VERSION')) {
			$this->version = WOOT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'woot';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_woocommerce_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Woot_Loader. Orchestrates the hooks of the plugin.
	 * - Woot_i18n. Defines internationalization functionality.
	 * - Woot_Admin. Defines all hooks for the admin area.
	 * - Woot_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woot-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woot-i18n.php';

		/**
		 * The class responsible for defining woocommerce functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woot-woocommerce.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woot-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-woot-public.php';

		$this->loader = new Woot_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Woot_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Woot_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new Woot_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$plugin_public = new Woot_Public($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	private function define_woocommerce_hooks()
	{
		$plugin_woocommerce = new Woot_Woocommerce();

		$this->loader->add_filter('woocommerce_billing_fields', $plugin_woocommerce, 'billing_fields', 10, 2);
		$this->loader->add_filter('woocommerce_shipping_fields', $plugin_woocommerce, 'shipping_fields', 10, 2);
		$this->loader->add_filter('woocommerce_form_field_city', $plugin_woocommerce, 'form_field_city', 10, 4);
		$this->loader->add_filter('woocommerce_default_address_fields', $plugin_woocommerce, 'default_address_fields', 99);

		$this->loader->add_action('woocommerce_shipping_init', $plugin_woocommerce, 'couriers_init');
		$this->loader->add_action('woocommerce_shipping_init', $plugin_woocommerce, 'locations_init');
		$this->loader->add_action('woocommerce_shipping_methods', $plugin_woocommerce, 'shipping_methods');

		$this->loader->add_action('woocommerce_review_order_after_shipping', $plugin_woocommerce, 'review_order_after_shipping');
		$this->loader->add_action('woocommerce_cart_calculate_fees', $plugin_woocommerce, 'cart_calculate_fees');
		$this->loader->add_action('woocommerce_after_order_notes', $plugin_woocommerce, 'after_order_notes');
		$this->loader->add_action('woocommerce_checkout_process', $plugin_woocommerce, 'checkout_process');
		$this->loader->add_action('woocommerce_checkout_update_order_review', $plugin_woocommerce, 'checkout_update_order_review');
		$this->loader->add_action('woocommerce_checkout_update_order_meta', $plugin_woocommerce, 'checkout_update_order_meta');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Woot_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
