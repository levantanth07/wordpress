<?php

use GDelivery\Libs\Helper\Response;
use Abstraction\Object\Result;
use Abstraction\Object\Message;

class AddMetaBoxMerchantCategory
{

    private function getMerchantCategoryByMerchantId($merchantId)
    {
        return get_terms([
            'taxonomy' => 'merchant-category',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'merchant_id',
                    'value' => $merchantId
                ],
                [
                    'key' => 'is_active',
                    'value' => true
                ]
            ]
        ]);
    }

    public function __construct()
    {
        add_action('save_post', [$this, 'saveMerchantCategory']);

        add_action("wp_ajax_generate_merchant_category", [$this, "generateMerchantCategory"]);

        add_action('acf/input/admin_footer', [$this, 'loadScript'], 10, 1);
    }

    public function generateMerchantCategory()
    {
        $res = new Result();

        $taxonomy = 'merchant-category';
        $merchantId = $_GET['merchantId'];
        $postId = $_GET['postId'];
        $categories = $this->getMerchantCategoryByMerchantId($merchantId);
        $walker = new Walker_Category_Checklist;
        $selectedCat = wp_get_object_terms(
            $postId,
            $taxonomy,
            array_merge( [
                'taxonomy'     => $taxonomy,
                'checked_ontop' => false,
            ], array( 'fields' => 'ids' ) ) );

        $res->messageCode = Message::SUCCESS;
        $boxContent = '<ul id="' . $taxonomy . '-tabs" class="category-tabs">';
        $boxContent .= '<li class="tabs">All</li>';
        $boxContent .= '</ul>';
        $boxContent .= '<div id="' . $taxonomy . '-all" class="tabs-panel">';
        $boxContent .= '<input type="hidden" name="tax_input[' . $taxonomy . '][]" value="0">';
        $boxContent .= '<ul id="' . $taxonomy . 'checklist" data-wp-lists="list:' . $taxonomy . '" class="categorychecklist form-no-clear">';
        $boxContent .= $walker->walk( $categories, 0, [
            'taxonomy'     => $taxonomy,
            'checked_ontop' => false,
            'selected_cats' => $selectedCat
        ] );
        $boxContent .= '</ul>';
        $boxContent .= '</div>';
        $res->result = $boxContent;

        Response::returnJson($res);
        die;
    }

    public function loadScript()
    {
        global $post_ID;
        if ($post_ID) {
            ?>
            <script type="text/javascript">
			        jQuery('#taxonomy-merchant-category').html('');
			        let merchantIdKey = jQuery('#merchant_id').attr('data-key');
			        console.log(jQuery('#acf-' + merchantIdKey).val());
			        if (jQuery('#acf-' + merchantIdKey).val()) {
				        let merchantId = jQuery('#acf-' + merchantIdKey).val();
				        generateMerchantCategory(<?=$post_ID?>, merchantId);
			        }
			        jQuery('#acf-' + merchantIdKey).on('change', function () {
				        let merchantId = this.value;
				        generateMerchantCategory(<?=$post_ID?>, merchantId);
			        });

			        function generateMerchantCategory(postId, merchantId) {
				        jQuery.ajax({
					        'type' : 'get',
					        'url' : '<?=admin_url('admin-ajax.php')?>',
					        'dataType' : 'json',
					        'data' : {
						        action: 'generate_merchant_category',
						        merchantId: merchantId,
						        postId: postId
					        },
					        'success' : function (res) {
						        if (res.messageCode == 1) {
							        jQuery('#taxonomy-merchant-category').html(res.result);
						        }
					        },
					        'error' : function (x, y, z) {
						        console.log('Có lỗi xảy ra! Hãy tải lại trang!');
					        },
					        'complete' : function () {
						        console.log('Đã xong!');
					        }
				        });
			        }
            </script>
            <?php
        }
    }

    public function saveMerchantCategory()
    {
//        global $post_ID;
//        if (isset($_POST['product_category_input'])) {
//            $productCategoryId = $_POST['product_category_input'];
//
//            update_post_meta($post_ID, 'product_category_id', $productCategoryId);
//        }
    }
}

// init
$AddMetaBoxMerchantCategory = new AddMetaBoxMerchantCategory();
