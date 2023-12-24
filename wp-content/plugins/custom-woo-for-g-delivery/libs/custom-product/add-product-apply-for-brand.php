<?php
/**
 * Class for custom option
 */
class RegisterProductApplyForBrandTaxonomy {

    /**
     * Build the instance
     */
    public function __construct() {

        add_action( 'init', [ $this, 'registerTaxonomy' ] );
    }

    public function registerTaxonomy()
    {
        $labels = array(
            'name' => 'Thương hiệu GG',
            'singular_name' => 'Thương hiệu GG',
            'menu_name' => 'Thương hiệu GG',
        );
        $args = array(
            'labels'                     => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'hierarchical' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'query_var' => true,
            'query_var_slug' => "",
            'rewrite' => true,
            'rewrite_slug' => "",
            'rewrite_withfront' => true,
            'rewrite_hierarchical' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'show_in_quick_edit' => true,
        );
        register_taxonomy( 'product_apply_for_brand', 'product', $args );
        register_taxonomy_for_object_type( 'product_apply_for_brand', 'product' );
    }
}

$productApplyForBrand = new RegisterProductApplyForBrandTaxonomy();