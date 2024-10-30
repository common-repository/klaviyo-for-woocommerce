<?php
/**
 * WooCommerceKlaviyo Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @author    Klaviyo
 * @category  Core
 * @package   WooCommerceKlaviyo/Functions
 * @version   0.9.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include( 'wck-cart-functions.php' );

/**
 * Get the URL to the WooCommerceKlaviyo REST API
 *
 * @since 0.9
 * @param string $path an endpoint to include in the URL
 * @return string the URL
 */
function get_woocommerce_klaviyo_api_url( $path ) {

  $url = get_home_url( null, 'wck-api/v' . WCK_API::VERSION . '/', is_ssl() ? 'https' : 'http' );

  if ( ! empty( $path ) && is_string( $path ) ) {
    $url .= ltrim( $path, '/' );
  }

  return $url;
}