<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Post types
 *
 * Registers post types
 *
 * @class     WCK_Post_types
 * @version   0.9.0
 * @package   WooCommerceKlaviyo/Classes
 * @category  Class
 * @author    WooCommerceKlaviyo
 */
class WCK_Post_types {

  /**
   * Constructor
   */
  public function __construct() {
    add_action( 'init', array( __CLASS__, 'register_taxonomies' ), 5 );
    add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
  }

  /**
   * Register WooCommerce taxonomies.
   */
  public static function register_taxonomies() {
    if ( taxonomy_exists( 'klaviyo_shop_cart_status' ) )
      return;

    do_action( 'woocommerce_klaviyo_register_taxonomy' );    

    register_taxonomy( 'klaviyo_shop_cart_status',
        apply_filters( 'woocommerce_klaviyo_taxonomy_objects_shop_order_status', array('klaviyo_shop_cart') ),
        apply_filters( 'woocommerce_klaviyo_taxonomy_args_shop_order_status', array(
            'hierarchical' => false,
            'update_count_callback' => '_update_post_term_count',
            'show_ui' => false,
            'show_in_nav_menus' => false,
            'query_var' => is_admin(),
            'rewrite' => false,
            'public' => false
        ) )
    );

    do_action( 'woocommerce_klaviyo_after_register_taxonomy' );
  }

  /**
   * Register core post types
   */
  public static function register_post_types() {
    if ( post_type_exists('klaviyo_shop_cart') )
      return;

    do_action( 'woocommerce_klaviyo_register_post_type' );

    register_post_type( "klaviyo_shop_cart",
      apply_filters( 'woocommerce_register_post_type_shop_order',
        array(
          'labels' => array(
              'name' => __( 'Carts', 'woocommerce-klaviyo' ),
              'singular_name' => __( 'Cart', 'woocommerce-klaviyo' ),
              'add_new' => __( 'Add Cart', 'woocommerce-klaviyo' ),
              'add_new_item' => __( 'Add New Cart', 'woocommerce-klaviyo' ),
              'edit' => __( 'Edit', 'woocommerce-klaviyo' ),
              'edit_item' => __( 'Edit Cart', 'woocommerce-klaviyo' ),
              'new_item' => __( 'New Cart', 'woocommerce-klaviyo' ),
              'view' => __( 'View Cart', 'woocommerce-klaviyo' ),
              'view_item' => __( 'View Cart', 'woocommerce-klaviyo' ),
              'search_items' => __( 'Search Carts', 'woocommerce-klaviyo' ),
              'not_found' => __( 'No Carts found', 'woocommerce-klaviyo' ),
              'not_found_in_trash' => __( 'No Carts found in trash', 'woocommerce-klaviyo' ),
              'parent' => __( 'Parent Carts', 'woocommerce-klaviyo' ),
              'menu_name' => _x('Carts', 'Admin menu name', 'woocommerce-klaviyo' )
            ),
          'description' => __( 'This is where store carts are stored.', 'woocommerce-klaviyo' ),
          'public' => false,
          'show_ui' => false,
          'capability_type' => 'klaviyo_shop_cart',
          'map_meta_cap' => true,
          'publicly_queryable' => false,
          'exclude_from_search'  => true,
          'show_in_menu' => false,
          'hierarchical' => false,
          'show_in_nav_menus' => false,
          'rewrite' => false,
          'query_var' => false,
          'supports' => array( 'custom-fields' ),
          'has_archive' => false,
        )
      )
    );
  }
}

new WCK_Post_types();
