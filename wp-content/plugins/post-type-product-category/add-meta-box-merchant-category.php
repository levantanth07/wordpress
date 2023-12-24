<?php

class AddMetaBoxProductCategory
{

    public function __construct()
    {die;
        add_action('add_meta_boxes', [$this, 'productCategoryMetaBox']);
        add_action('save_post', [$this, 'saveMerchantCategory']);
    }

    public function productCategoryMetaBox()
    {
        add_meta_box('merchant-category', 'Danh mục merchant', [$this, 'metaBoxContent'], 'product', 'side', 'core');
    }

    public function metaBoxContent()
    {
        global $post_ID;
        $merchantId = get_field('merchant_id', $post_ID)->ID;
        $args = [
            'post_status' => "publish",
            'post_type' => "product_category",
            'meta_query' => [
                [
                    'key' => 'apply_for_merchant',
                    'value' => $merchantId,
                    'compare' => '=',
                ]
            ]
        ];

        $productCategories = get_posts($args);
        $curProductCategoryId = get_field('product_category_id', $post_ID);
        ?>
        <style>
            #merchant-categorydiv {
                display: none;
            }
        </style>
        <div class="inside">
            <div id="taxonomy-product_cat" class="categorydiv">
                <ul id="product_cat-tabs" class="category-tabs">
                    <li class="tabs">All</li>
                </ul>
                <div id="product_cat-all" class="tabs-panel">
                    <input type="hidden" name="tax_input[product_cat][]" value="0">
                    <ul id="product_catchecklist" data-wp-lists="list:product_cat" class="categorychecklist form-no-clear">
                        <?php
                        foreach ($productCategories as $productCategory):
                            $productCategoryId = $productCategory->ID;
                            $productCategoryName = $productCategory->post_title;
                        ?>
                        <li id="product_cat-<?=$productCategoryId?>" class="popular-category">
                            <label class="selectit">
                                <input value="<?=$productCategoryId?>" type="radio"
                                       name="product_category_input" id="in-product_cat-<?=$productCategoryId?>"
                                    <?=$curProductCategoryId == $productCategoryId ? 'checked' : ''?>> <?=$productCategoryName?>
                            </label>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div id="product_cat-adder" class="wp-hidden-children">
                    <a id="product_cat-add-toggle" target="_blank" href="<?=admin_url('post-new.php?post_type=product_category')?>" class="hide-if-no-js taxonomy-add-new">
                        + Thêm mới
                    </a>
                </div>
            </div>
        </div>
        <?php
    }

    public function saveMerchantCategory()
    {
        global $post_ID;
        if (isset($_POST['product_category_input'])) {
            $productCategoryId = $_POST['product_category_input'];

            update_post_meta($post_ID, 'product_category_id', $productCategoryId);
        }
    }
}

// init
$addMetaBoxProductCategory = new AddMetaBoxProductCategory();
