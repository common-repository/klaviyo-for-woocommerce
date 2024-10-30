<?php
/**
 * Plugin Name: Klaviyo for WooCommerce
 * Plugin URI: http://wordpress.org/extend/plugins/woocommerce-klaviyo/
 * Description: A plugin to automatically sync your WooCommerce sales, products and customers with Klaviyo. With Klaviyo you can set up abandoned cart emails, collect emails for your newsletter to grow your business.
 * Version: 1.0.4
 * Author: Klaviyo, Inc.
 * Author URI: https://www.klaviyo.com
 * Requires at least: 3.8
 * Tested up to: 4.9.1
 *
 * Text Domain: woocommerce-klaviyo
 * Domain Path: /i18n/languages/
 *
 * @package WooCommerceKlaviyo
 * @category Core
 * @author Klaviyo
 */
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

if ( ! class_exists( 'WooCommerceKlaviyo' ) ) :

/**
 * Main WooCommerceKlaviyo Class
 *
 * @class WooCommerceKlaviyo
 * @version 0.9.0
 */
final class WooCommerceKlaviyo {

  /**
   * @var string
   */
  public static $version = '0.9.0';

  /**
   * @var WooCommerceKlaviyo The single instance of the class
   * @since 0.9.0
   */
  protected static $_instance = null;

  /**
   * Get plugin version number.
   *
   * @since 0.9.0
   * @static
   * @return int
   */
  public static function getVersion() {
    return self::$version;
  }

  /**
   * Main WooCommerceKlaviyo Instance
   *
   * Ensures only one instance of WooCommerceKlaviyo is loaded or can be loaded.
   *
   * @since 0.9.0
   * @static
   * @see WCK()
   * @return WooCommerceKlaviyo - Main instance
   */
  public static function instance() {
    if ( is_null( self::$_instance ) ) {
      self::$_instance = new self();
    }
    return self::$_instance;
  }

  /**
   * Cloning is forbidden.
   *
   * @since 2.1
   */
  public function __clone() {
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-klaviyo' ), '0.9' );
  }

  /**
   * Unserializing instances of this class is forbidden.
   *
   * @since 2.1
   */
  public function __wakeup() {
    _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce-klaviyo' ), '0.9' );
  }

  /**
   * WooCommerceKlaviyo Constructor.
   * @access public
   * @return WooCommerceKlaviyo
   */
  public function __construct() {
    // Auto-load classes on demand
    if ( function_exists( "__autoload" ) ) {
      spl_autoload_register( "__autoload" );
    }

    spl_autoload_register( array( $this, 'autoload' ) );

    // Define constants
    $this->define_constants();

    // Include required files
    $this->includes();

    // Init API
    $this->api = new WCK_API();

    // Hooks
    add_action( 'init', array( $this, 'init' ), 0 );
    // add_action( 'init', array( $this, 'include_template_functions' ) );

    // Loaded action
    do_action( 'woocommerce_klaviyo_loaded' );
  }

  /**
   * Auto-load in-accessible properties on demand.
   *
   * @param mixed $key
   * @return mixed
   */
  public function __get( $key ) {
    if ( method_exists( $this, $key ) ) {
      return $this->$key();
    }
    return false;
  }

  /**
   * Auto-load WC classes on demand to reduce memory consumption.
   *
   * @param mixed $class
   * @return void
   */
  public function autoload( $class ) {
    $path  = null;
    $class = strtolower( $class );
    $file = 'class-' . str_replace( '_', '-', $class ) . '.php';

    if ( $path && is_readable( $path . $file ) ) {
      include_once( $path . $file );
      return;
    }

    // Fallback
    if ( strpos( $class, 'wck_' ) === 0 ) {
      $path = $this->plugin_path() . '/includes/';
    }

    if ( $path && is_readable( $path . $file ) ) {
      include_once( $path . $file );
      return;
    }
  }

  /**
   * Define WC Constants
   */
  private function define_constants() {
    define( 'WCK_PLUGIN_FILE', __FILE__ );
    define( 'WCK_VERSION', $this->version );

    // if ( ! defined( 'WCK_TEMPLATE_PATH' ) ) {
    //   define( 'WCK_TEMPLATE_PATH', $this->template_path() );
    // }
  }

  /**
   * Include required core files used in admin and on the frontend.
   */
  private function includes() {
    include_once( 'includes/wck-core-functions.php' );
    include_once( 'includes/class-wck-install.php' );
    // include_once( 'includes/class-wc-download-handler.php' );
    // include_once( 'includes/class-wc-comments.php' );
    // include_once( 'includes/class-wc-post-data.php' );
    // include_once( 'includes/abstracts/abstract-wc-session.php' );
    // include_once( 'includes/class-wc-session-handler.php' );

    // if ( is_admin() ) {
    //   include_once( 'includes/admin/class-wc-admin.php' );
    // }

    // Query class
    // $this->query = include( 'includes/class-wc-query.php' );        // The main query class

    // Post types
    include_once( 'includes/class-wck-post-types.php' );           // Registers post types

    // API Class
    include_once( 'includes/class-wck-api.php' );

    // Include abstract classes
    // include_once( 'includes/abstracts/abstract-wc-product.php' );     // Products
    // include_once( 'includes/abstracts/abstract-wc-settings-api.php' );    // Settings API (for gateways, shipping, and integrations)
    // include_once( 'includes/abstracts/abstract-wc-shipping-method.php' ); // A Shipping method
    // include_once( 'includes/abstracts/abstract-wc-payment-gateway.php' );   // A Payment gateway
    // include_once( 'includes/abstracts/abstract-wc-integration.php' );   // An integration with a service

    // Classes (used on all pages)
    // include_once( 'includes/class-wc-product-factory.php' );        // Product factory
    // include_once( 'includes/class-wc-countries.php' );            // Defines countries and states
    // include_once( 'includes/class-wc-integrations.php' );         // Loads integrations
    // include_once( 'includes/class-wc-cache-helper.php' );         // Cache Helper
    // include_once( 'includes/class-wc-https.php' );              // https Helper

    // Include template hooks in time for themes to remove/modify them
    // include_once( 'includes/wc-template-hooks.php' );
  }

  /**
   * Function used to Init WooCommerce Template Functions - This makes them pluggable by plugins and themes.
   */
  // public function include_template_functions() {
  //   include_once( 'includes/wc-template-functions.php' );
  // }

  /**
   * Init WooCommerceKlaviyo when WordPress Initialises.
   */
  public function init() {
    // Init action
    do_action( 'woocommerce_klaviyo_init' );
  }

  /** Helper functions ******************************************************/

  /**
   * Get the plugin url.
   *
   * @return string
   */
  public function plugin_url() {
    return untrailingslashit( plugins_url( '/', __FILE__ ) );
  }

  /**
   * Get the plugin path.
   *
   * @return string
   */
  public function plugin_path() {
    return untrailingslashit( plugin_dir_path( __FILE__ ) );
  }

  /**
   * Get the template path.
   *
   * @return string
   */
  // public function template_path() {
  //   return apply_filters( 'WC_TEMPLATE_PATH', 'woocommerce/' );
  // }

  /**
   * Return the WC API URL for a given request
   *
   * @param mixed $request
   * @param mixed $ssl (default: null)
   * @return string
   */
  public function api_request_url( $request, $ssl = null ) {
    if ( is_null( $ssl ) ) {
      $scheme = parse_url( get_option( 'home' ), PHP_URL_SCHEME );
    } elseif ( $ssl ) {
      $scheme = 'https';
    } else {
      $scheme = 'http';
    }

    if ( get_option('permalink_structure') ) {
      return esc_url_raw( trailingslashit( home_url( '/wck-api/' . $request, $scheme ) ) );
    } else {
      return esc_url_raw( add_query_arg( 'wck-api', $request, trailingslashit( home_url( '', $scheme ) ) ) );
    }
  }
}

endif;

/**
 * Returns the main instance of WCK to prevent the need to use globals.
 *
 * @since  0.9
 * @return WooCommerceKlaviyo
 */
function WCK() {
  return WooCommerceKlaviyo::instance();
}

// Global for backwards compatibility.
$GLOBALS['woocommerce-klaviyo'] = WCK();
