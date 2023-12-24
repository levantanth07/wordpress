<?php
/**
 * Topping Product Type
 */
class ToppingProductType extends \WC_Product_Simple {

    /**
     * Return the product type
     * @return string
     */
    public function get_type() {
        return 'topping';
    }
}

/**
 * Class for custom option
 */
class RegisterToppingProductType {

    /**
     * Build the instance
     */
    public function __construct() {
        // load topping produc type
        add_action( 'woocommerce_loaded', [ $this, 'loadToppingType' ] );

        register_activation_hook( __FILE__, [ $this, 'createToppingType' ] );

        // register product class
        add_filter( 'woocommerce_product_class', [$this, 'registerToppingProductTypeClass'], 10, 2 );

        // add product selector
        add_filter( 'product_type_selector', [ $this, 'addType' ] );

        // show price
        add_action( 'admin_footer', array( $this, 'showPrice' ) );

    }

    function registerToppingProductTypeClass( $classname, $product_type ) {
        if ( $product_type == 'topping' ) {
            $classname = 'ToppingProductType';
        }
        return $classname;
    }

    /**
     * Load WC Dependencies
     *
     * @return void
     */
    public function loadToppingType() {
        new ToppingProductType();
    }

    /**
     * add topping to type selector
     *
     */
    public function addType( $types ) {
        $types['topping'] = __('Sản phẩm topping');

        return $types;
    }

    /**
     * display price for topping
     */
    public function showPrice() {
        global $post, $product_object;

        if ( ! $post ) {
            return;
        }

        if ( 'product' != $post->post_type ) {
            return;
        }

        if ($product_object && $product_object->get_type() == 'topping') {
            $isToppingType = true;
        } else {
            $isToppingType = false;
        }

        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                //for Price tab
                jQuery('#general_product_data .pricing').addClass('show_if_topping');

                jQuery('.product_data_tabs .general_tab').attr('style', 'display: block;');
                jQuery('.product_data_tabs .general_tab a').click();

                <?php if ( $isToppingType ) { ?>
                    jQuery('#general_product_data .pricing').show();
                <?php } ?>
            });
        </script>
        <?php
    }

    /**
     * Installing on activation
     *
     * @return void
     */
    public function createToppingType() {
        // If there is no advanced product type taxonomy, add it.
        if ( ! get_term_by( 'slug', 'topping', 'product_type' ) ) {
            wp_insert_term( 'topping', 'product_type' );
        }
    }
}

$toppingProductType = new RegisterToppingProductType();