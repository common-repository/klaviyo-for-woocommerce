<?php
/**
 * WooCommerceKlaviyo Order Functions
 *
 * Functions for order specific things.
 *
 * @author    Klaviyo
 * @category  Core
 * @package   WooCommerceKlaviyo/Functions
 * @version   0.9.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wck_check_carts_content_modified($cart_id, $cart_contents) {
  $prev_contents = get_post_meta( $cart_id, '_contents');

  if (!$prev_contents) {
    return TRUE;
  }
  $prev_contents = json_decode($prev_contents[0], TRUE);

  if (count($prev_contents) != count($cart_contents)) {
    return TRUE;
  }

  foreach ( $cart_contents as $line_key => $line ) {
    if (!isset($prev_contents[$line_key])) {
      return TRUE;
    }

    $prev_line = $prev_contents[$line_key];

    if (!isset($prev_line['product_id']) || $prev_line['product_id'] != $line['product_id'] ||
      !isset($prev_line['variation_id']) || $prev_line['variation_id'] != $line['variation_id'] ||
      !isset($prev_line['quantity']) || $prev_line['quantity'] != $line['quantity']) {
      return TRUE;
    }
  }

  return FALSE;
};

/**
 * Insert tracking code code for tracking started checkout.
 *
 * @access public
 * @return void
 */
function wck_insert_checkout_tracking($checkout) {

  global $current_user;
  wp_reset_query();

  get_currentuserinfo();

  $cart = WC()->cart;
  $event_data = array(
    '$service' => 'woocommerce',
    '$value' => $cart->total,
    '$extra' => array(
      'Items' => array(),
      'SubTotal' => $cart->subtotal,
      'ShippingTotal' => $cart->shipping_total,
      'TaxTotal' => $cart->tax_total,
      'GrandTotal' => $cart->total
    )
  );

  foreach ( $cart->get_cart() as $cart_item_key => $values ) {
    $product = $values['data'];

    $event_data['$extra']['Items'] []= array(
      'Quantity' => $values['quantity'],
      'ProductID' => $product->id,
      'Name' => $product->post->post_title,
      'URL' => get_permalink($product->id),
      'Images' => array(
        array(
          'URL' => wp_get_attachment_url(get_post_thumbnail_id($product->id))
        )
      ),
      'Description' => $product->post->post_content,
      'Variation' => $values['variation'],

      'SubTotal' => $values['line_subtotal'],
      'Total' => $values['line_subtotal_tax'],
      'LineTotal' => $values['line_total'],
      'Tax' => $values['line_tax']
    );
  }

  if ( empty($event_data['$extra']['Items']) ) {
    return;
  }

  echo "\n" . '<!-- Start Klaviyo for WooCommerce // Plugin Version: ' . WooCommerceKlaviyo::getVersion() . ' -->' . "\n";
  echo '<script type="text/javascript">' . "\n";
  echo 'var _learnq = _learnq || [];' . "\n";

  echo 'var WCK = WCK || {};' . "\n";
  echo 'WCK.trackStartedCheckout = function () {' . "\n";
  echo '  _learnq.push(["track", "$started_checkout", ' . json_encode($event_data) . ']);' . "\n";
  echo '};' . "\n\n";

  if ($current_user->user_email) {
    echo '_learnq.push(["identify", {' . "\n";
    echo '  $email : "' . $current_user->user_email . '"' . "\n";
    echo '}]);' . "\n\n";
    echo 'WCK.trackStartedCheckout();' . "\n\n";
  } else {
    // See if current user is a commenter
    $commenter = wp_get_current_commenter();
    if ($commenter['comment_author_email']) {
      echo '_learnq.push(["identify", {' . "\n";
      echo '  $email : "' . $commenter['comment_author_email'] . '"' . "\n";
      echo '}]);' . "\n\n";
      echo 'WCK.trackStartedCheckout();' . "\n\n";
    }
  }

  echo 'if (jQuery) {' . "\n";
  echo '  jQuery(\'input[name="billing_email"]\').change(function () {' . "\n";
  echo '    var elem = jQuery(this),' . "\n";
  echo '        email = jQuery.trim(elem.val());' . "\n\n";

  echo '    if (email && /@/.test(email)) {' . "\n";
  echo '      _learnq.push(["identify", { $email : email }]);' . "\n";
  echo '      WCK.trackStartedCheckout();' . "\n";
  echo '    }' . "\n";
  echo '  })' . "\n";
  echo '}' . "\n";

  echo '</script>' . "\n";
  echo '<!-- end: Klaviyo Code. -->' . "\n";

}

add_action( 'woocommerce_after_checkout_form', 'wck_insert_checkout_tracking' );
