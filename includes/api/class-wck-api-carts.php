<?php
/**
 * WooCommerceKlaviyo API Carts Class
 *
 * Handles requests to the /carts endpoint
 *
 * @author      Klaviyo
 * @category    API
 * @package     WooCommerceKlaviyo/API
 * @since       0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WCK_API_Carts extends WCK_API_Resource {

  /** @var string $base the route base */
  protected $base = '/carts';

  /**
   * Register the routes for this class
   *
   * GET /carts
   * GET /carts/count
   * GET|PUT /carts/<id>
   * GET /carts/<id>/notes
   *
   * @since 0.9
   * @param array $routes
   * @return array
   */
  public function register_routes( $routes ) {

    # GET /carts
    $routes[ $this->base ] = array(
      array( array( $this, 'get_carts' ),     WCK_API_Server::READABLE ),
    );

    # GET /carts/count
    $routes[ $this->base . '/count'] = array(
      array( array( $this, 'get_carts_count' ), WCK_API_Server::READABLE ),
    );

    # GET|PUT /carts/<id>
    $routes[ $this->base . '/(?P<id>\d+)' ] = array(
      array( array( $this, 'get_cart' ),  WCK_API_Server::READABLE ),
    );

    return $routes;
  }

  /**
   * Get all carts
   *
   * @since 0.9
   * @param string $fields
   * @param array $filter
   * @param string $status
   * @param int $page
   * @return array
   */
  public function get_carts( $fields = null, $filter = array(), $status = null, $page = 1 ) {

    if ( ! empty( $status ) )
      $filter['status'] = $status;

    $filter['page'] = $page;

    $query = $this->query_carts( $filter );

    $carts = array();

    foreach( $query->posts as $cart_id ) {

      if ( ! $this->is_readable( $cart_id ) )
        continue;

      $carts[] = current( $this->get_cart( $cart_id, $fields ) );
    }

    $this->server->add_pagination_headers( $query );

    return array( 'carts' => $carts );
  }


  /**
   * Get the cart for the given ID
   *
   * @since 0.9
   * @param int $id the cart ID
   * @param array $fields
   * @return array
   */
  public function get_cart( $id, $fields = null ) {

    // ensure cart ID is valid & user has permission to read
    $id = $this->validate_request( $id, 'klaviyo_shop_cart', 'read' );

    if ( is_wp_error( $id ) )
      return $id;

    $cart = new WCK_Cart( $id );

    $cart_post = get_post( $id );

    $cart_data = array(
      'id'                        => $cart->id,
      'created_at'                => $this->server->format_datetime( $cart_post->post_date_gmt ),
      'updated_at'                => $this->server->format_datetime( $cart_post->post_modified_gmt ),
      'status'                    => $cart->status,
      'currency'                  => $cart->cart_currency,
      'total'                     => wc_format_decimal( $cart->get_total(), 2 ),
      'subtotal'                  => wc_format_decimal( $this->get_cart_subtotal( $cart ), 2 ),
      'total_line_items_quantity' => $cart->get_item_count(),
      // 'total_tax'                 => wc_format_decimal( $cart->get_total_tax(), 2 ),
      // 'total_shipping'            => wc_format_decimal( $cart->get_total_shipping(), 2 ),
      // 'cart_tax'                  => wc_format_decimal( $cart->get_cart_tax(), 2 ),
      // 'shipping_tax'              => wc_format_decimal( $cart->get_shipping_tax(), 2 ),
      // 'total_discount'            => wc_format_decimal( $cart->get_total_discount(), 2 ),
      // 'cart_discount'             => wc_format_decimal( $cart->get_cart_discount(), 2 ),
      // 'order_discount'            => wc_format_decimal( $cart->get_order_discount(), 2 ),
      'customer' => array(
        'id'         => $cart->customer_user,
        'first_name' => $cart->first_name,
        'last_name'  => $cart->last_name,
        'email'      => $cart->email,
      ),
      'line_items' => array(),
    );

    // add line items
    foreach( $cart->get_items() as $item_id => $item ) {

      $product = $cart->get_product_from_item( $item );

      $cart_data['line_items'][] = array(
        'id'         => $item_id,
        'subtotal'   => wc_format_decimal( $cart->get_line_subtotal( $item ), 2 ),
        'total'      => wc_format_decimal( $cart->get_line_total( $item ), 2 ),
        'total_tax'  => wc_format_decimal( $cart->get_line_tax( $item ), 2 ),
        'price'      => wc_format_decimal( $cart->get_item_total( $item ), 2 ),
        'quantity'   => (int) $item['qty'],
        'tax_class'  => ( ! empty( $item['tax_class'] ) ) ? $item['tax_class'] : null,
        'name'       => $item['name'],
        'product_id' => ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id,
        'sku'        => is_object( $product ) ? $product->get_sku() : null,
      );
    }

    return array( 'cart' => apply_filters( 'woocommerce_klaviyo_api_cart_response', $cart_data, $cart, $fields, $this->server ) );
  }

  /**
   * Get the total number of carts
   *
   * @since 0.9
   * @param string $status
   * @param array $filter
   * @return array
   */
  public function get_carts_count( $status = null, $filter = array() ) {

    if ( ! empty( $status ) )
      $filter['status'] = $status;

    $query = $this->query_carts( $filter );

    if ( ! current_user_can( 'read_private_shop_orders' ) )
      return new WP_Error( 'woocommerce_klaviyo_api_user_cannot_read_carts_count', __( 'You do not have permission to read the carts count', 'woocommerce-klaviyo' ), array( 'status' => 401 ) );

    return array( 'count' => (int) $query->found_posts );
  }

  /**
   * Helper method to get cart post objects
   *
   * @since 0.9
   * @param array $args request arguments for filtering query
   * @return WP_Query
   */
  private function query_carts( $args ) {

    // set base query arguments
    $query_args = array(
      'fields'      => 'ids',
      'post_type'   => 'klaviyo_shop_cart',
      'post_status' => 'publish',
    );

    // add status argument
    if ( ! empty( $args['status'] ) ) {

      $statuses = explode( ',', $args['status'] );

      $query_args['tax_query'] = array(
        array(
          'taxonomy' => 'klaviyo_shop_cart_status',
          'field'    => 'slug',
          'terms'    => $statuses,
        ),
      );

      unset( $args['status'] );
    }

    $query_args = $this->merge_query_args( $query_args, $args );

    return new WP_Query( $query_args );
  }

  /**
   * Helper method to get the cart subtotal
   *
   * @since 0.9
   * @param WCK_Cart $cart
   * @return float
   */
  private function get_cart_subtotal( $cart ) {

    $subtotal = 0;

    // subtotal
    foreach ( $cart->get_items() as $item ) {

      $subtotal += ( isset( $item['line_subtotal'] ) ) ? $item['line_subtotal'] : 0;
    }

    return $subtotal;
  }

}
