<?php
/**
 * WooCommerceKlaviyo API
 *
 * Handles WCK-API endpoint requests
 *
 * @author      Klaviyo
 * @category    API
 * @package     WooCommerceKlaviyo/API
 * @since       0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WCK_API {

  /** This is the major version for the REST API and takes
   * first-order position in endpoint URLs
   */
  const VERSION = 1;

  /** @var WCK_API_Server the REST API server */
  public $server;

  /**
   * Setup class
   *
   * @access public
   * @since 0.9
   * @return WCK_API
   */
  public function __construct() {

    // add query vars
    add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );

    // register API endpoints
    add_action( 'init', array( $this, 'add_endpoint'), 0 );

    // handle REST/legacy API request
    add_action( 'parse_request', array( $this, 'handle_api_requests'), 0 );
  }

  /**
   * add_query_vars function.
   *
   * @access public
   * @since 0.9
   * @param $vars
   * @return array
   */
  public function add_query_vars( $vars ) {
    $vars[] = 'wck-api';
    $vars[] = 'wck-api-route';
    return $vars;
  }

  /**
   * add_endpoint function.
   *
   * @access public
   * @since 0.9
   * @return void
   */
  public function add_endpoint() {
    // REST API
    add_rewrite_rule( '^wck-api\/v' . self::VERSION . '/?$', 'index.php?wck-api-route=/', 'top' );
    add_rewrite_rule( '^wck-api\/v' . self::VERSION .'(.*)?', 'index.php?wck-api-route=$matches[1]', 'top' );
  }


  /**
   * API request - Trigger any API requests
   *
   * @access public
   * @since 0.9
   * @return void
   */
  public function handle_api_requests() {
    global $wp;

    if ( ! empty( $_GET['wck-api'] ) )
      $wp->query_vars['wck-api'] = $_GET['wck-api'];

    if ( ! empty( $_GET['wck-api-route'] ) )
      $wp->query_vars['wck-api-route'] = $_GET['wck-api-route'];

    // REST API request
    if ( ! empty( $wp->query_vars['wck-api-route'] ) ) {

      define( 'WCK_API_REQUEST', true );

      // load required files
      $this->includes();

      $this->server = new WCK_API_Server( $wp->query_vars['wck-api-route'] );

      // load API resource classes
      $this->register_resources( $this->server );

      // Fire off the request
      $this->server->serve_request();

      exit;
    }
  }


  /**
   * Include required files for REST API request
   *
   * @since 0.9
   */
  private function includes() {

    // API server / response handlers
    include_once( 'api/class-wck-api-server.php' );
    include_once( 'api/interface-wck-api-handler.php' );
    include_once( 'api/class-wck-api-json-handler.php' );

    // authentication
    include_once( 'api/class-wck-api-authentication.php' );
    $this->authentication = new WCK_API_Authentication();

    include_once( 'api/class-wck-api-resource.php' );
    include_once( 'api/class-wck-api-orders.php' );
    include_once( 'api/class-wck-api-carts.php' );
    include_once( 'api/class-wck-api-products.php' );
    include_once( 'api/class-wck-api-coupons.php' );
    include_once( 'api/class-wck-api-customers.php' );

    // allow plugins to load other response handlers or resource classes
    do_action( 'woocommerce_klaviyo_api_loaded' );
  }

  /**
   * Register available API resources
   *
   * @since 0.9
   * @param object $server the REST server
   */
  public function register_resources( $server ) {

    $api_classes = apply_filters( 'woocommerce_klaviyo_api_classes',
      array(
        'WCK_API_Customers',
        'WCK_API_Carts',
        'WCK_API_Orders',
        'WCK_API_Products',
        'WCK_API_Coupons'
      )
    );

    foreach ( $api_classes as $api_class ) {
      $this->$api_class = new $api_class( $server );
    }
  }

}
