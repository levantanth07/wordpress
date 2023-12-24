<?php
/**
 * Class for custom option
 */
class RegisterProductGroupTaxonomy {

    /**
     * Build the instance
     */
    public function __construct() {

        add_action( 'init', [ $this, 'registerTaxonomy' ] );
    }

    public function registerTaxonomy()
    {
        $labels = array(
            'name' => 'Nhóm sản phẩm',
            'singular_name' => 'Nhóm sản phẩm',
            'menu_name' => 'Nhóm sản phẩm',
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
        register_taxonomy( 'product_group', 'product', $args );
        register_taxonomy_for_object_type( 'product_group', 'product' );
    }
}

$productGroupTaxonomy = new RegisterProductGroupTaxonomy();