<?php
/**
 * WooCommerceKlaviyo Uninstall
 *
 * Uninstalling WooCommerceKlaviyo deletes user roles, options, tables, and pages.
 *
 * @author    Klaviyo
 * @category  Core
 * @package   WooCommerceKlaviyo/Uninstaller
 * @version   0.9.0
 */
if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) 
  exit();

// Caps
$installer = include( 'includes/class-wck-install.php' );
$installer->remove_roles();