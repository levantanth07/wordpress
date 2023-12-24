<?php

class Report {
    // ------------------
    // 1. Register new endpoint to use for My Account page
    // Note: Resave Permalinks or it will give 404 error
    public function bbloomer_add_premium_support_endpoint() {
        add_rewrite_endpoint( 'premium-support', EP_ROOT | EP_PAGES );
    }

    // ------------------
    // 2. Add new query var

    public function bbloomer_premium_support_query_vars( $vars ) {
        $vars[] = 'premium-support';
        return $vars;
    }



    // ------------------
    // 3. Insert the new endpoint into the My Account menu

    public function bbloomer_add_premium_support_link_my_account( $items ) {
        $items['premium-support'] = 'Premium Support';
        return $items;
    }

    // ------------------
    // 4. Add content to the new endpoint
    public function bbloomer_premium_support_content() {
        echo '<h3>Premium WooCommerce Support</h3><p>Welcome to the WooCommerce support area. As a premium customer, you can submit a ticket should you have any WooCommerce issues with your website, snippets or customization. <i>Please contact your theme/plugin developer for theme/plugin-related support.</i></p>';
        echo do_shortcode( ' /* your shortcode here */ ' );
    }



    public function __construct()
    {
        add_action( 'init', 'bbloomer_add_premium_support_endpoint' );

        add_filter( 'query_vars', 'bbloomer_premium_support_query_vars', 0 );

        add_filter( 'woocommerce_account_menu_items', 'bbloomer_add_premium_support_link_my_account' );

        // add end point
        add_action( 'woocommerce_account_premium-support_endpoint', 'bbloomer_premium_support_content' );
        // Note: add_action must follow 'woocommerce_account_{your-endpoint-slug}_endpoint' format
    }
}