<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
    // Exit if accessed directly
}
/**
 * Freemius SDK
 */
if ( !function_exists( 'wpb_gqb_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wpb_gqb_fs() {
        global $wpb_gqb_fs;
        if ( !isset( $wpb_gqb_fs ) ) {
            // Include Freemius SDK.
            require_once dirname( __DIR__ ) . '/vendor/freemius/start.php';
            $wpb_gqb_fs = fs_dynamic_init( array(
                'id'               => '20797',
                'slug'             => 'get-a-quote-button-for-woocommerce',
                'type'             => 'plugin',
                'public_key'       => 'pk_6fd107f80a8d1d92a8ef5a2b0ab31',
                'is_premium'       => false,
                'is_premium_only'  => false,
                'has_addons'       => false,
                'has_paid_plans'   => true,
                'is_org_compliant' => true,
                'trial'            => array(
                    'days'               => 14,
                    'is_require_payment' => false,
                ),
                'menu'             => array(
                    'slug'       => 'get-a-quote-button-settings',
                    'first-path' => 'admin.php?page=get-a-quote-button-settings',
                ),
                'is_live'          => true,
            ) );
        }
        return $wpb_gqb_fs;
    }

    // Init Freemius.
    wpb_gqb_fs();
    // Signal that SDK was initiated.
    do_action( 'wpb_gqb_fs_loaded' );
    // Send free users to our own landing page instead of the Freemius-hosted
    // pricing/checkout page. This covers every Freemius-generated
    // "Upgrade"/"Pricing" CTA in one place (admin menu "Upgrade" submenu,
    // license/trial expiry notices, update nag, etc.) - see pricing_url()
    // in vendor/freemius/includes/class-freemius.php.
    add_filter( 'fs_pricing_url_' . wpb_gqb_fs()->get_slug(), function () {
        if ( !defined( 'WPB_GQB_PREMIUM_URL' ) ) {
            return null;
        }
        return esc_url( add_query_arg( [
            'utm_content'  => 'Get+A+Quote+Pro',
            'utm_campaign' => 'adminmenu',
            'utm_medium'   => 'plugin-admin-menu',
            'utm_source'   => 'FreeVersion',
        ], WPB_GQB_PREMIUM_URL ) );
    } );
    // Freemius also adds its own "Upgrade" link to the Plugins list row for
    // free users, opening the in-dashboard pricing table/checkout. We already
    // show our own "Get the Pro" link there (see plugin_action_links() in
    // main.php) pointing to WPB_GQB_PREMIUM_URL, so drop Freemius's to avoid
    // a duplicate/competing CTA. Runs after Freemius's own action-links hook
    // (added on `admin_init` at the default priority).
    add_filter( 'plugin_action_links_' . wpb_gqb_fs()->get_plugin_basename(), function ( $links ) {
        unset($links['upgrade']);
        return $links;
    }, 20 );
}