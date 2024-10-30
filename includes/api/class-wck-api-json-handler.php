<?php
/**
 * WooCommerceKlaviyo API
 *
 * Handles parsing JSON request bodies and generating JSON responses
 *
 * @author      Klaviyo
 * @category    API
 * @package     WooCommerceKlaviyo/API
 * @since       0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WCK_API_JSON_Handler implements WCK_API_Handler {

	/**
	 * Get the content type for the response
	 *
	 * @since 0.9
	 * @return string
	 */
	public function get_content_type() {

		return 'application/json; charset=' . get_option( 'blog_charset' );
	}

	/**
	 * Parse the raw request body entity
	 *
	 * @since 0.9
	 * @param string $body the raw request body
	 * @return array|mixed
	 */
	public function parse_body( $body ) {

		return json_decode( $body, true );
	}

	/**
	 * Generate a JSON response given an array of data
	 *
	 * @since 0.9
	 * @param array $data the response data
	 * @return string
	 */
	public function generate_response( $data ) {

		if ( isset( $_GET['_jsonp'] ) ) {

			// JSONP enabled by default
			if ( ! apply_filters( 'woocommerce_klaviyo_api_jsonp_enabled', true ) ) {

				WC()->api->server->send_status( 400 );

				$data = array( array( 'code' => 'woocommerce_api_jsonp_disabled', 'message' => __( 'JSONP support is disabled on this site', 'woocommerce-klaviyo' ) ) );
			}

			// Check for invalid characters (only alphanumeric allowed)
			if ( preg_match( '/\W/', $_GET['_jsonp'] ) ) {

				WC()->api->server->send_status( 400 );

				$data = array( array( 'code' => 'woocommerce_api_jsonp_callback_invalid', __( 'The JSONP callback function is invalid', 'woocommerce-klaviyo' ) ) );
			}

			return $_GET['_jsonp'] . '(' . json_encode( $data ) . ')';
		}

		return json_encode( $data );
	}

}
