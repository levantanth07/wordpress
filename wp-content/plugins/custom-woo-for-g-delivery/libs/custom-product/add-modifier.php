<?php

use Abstraction\Object\Result;
use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;

class ModifierPostType {

    CONST MERCHANT_ID_FILTER_KEY_NAME = "custom_field_merchant_id";
    CONST MERCHANT_CUSTOM_FIELD_NAME = "product_mofifier_merchant";

    public function __construct()
    {

        // register tgs banner post type
        // add_action( 'init', [$this, 'registerPostType'], 0 );

        add_action( 'init', [$this, 'taxonomiesForModifier'], 0 );

        // filter position
        // add_action( 'restrict_manage_posts', [$this, "restrictManagePostType"] );

        // add_action('post_submitbox_start', [$this, 'addBlockSyncMerchant']);

        // add_action("wp_ajax_sync_product_modifier", [$this, "syncProductModifier"]);

        // add_filter('parse_query', [$this, 'customQueryProductModifierFilter']);
        // add_action('restrict_manage_posts', [$this, 'customProductModifierFilter']);

        add_filter( 'manage_edit-product_modifier_category_columns', [$this, 'customProductModifierCategoryColumns'] );
        add_action( 'manage_product_modifier_category_custom_column', [$this, 'customProductModifierCategoryColumnData'], 10, 3 );
    }

    function customProductModifierCategoryColumns( $columns ) {
        $columns['order'] = 'Thứ tự';
        return $columns;
    }

    function customProductModifierCategoryColumnData( $content, $columnName, $termId ) {
        if ('order' === $columnName) {
            $orderMeta = (int) get_term_meta( $termId, 'modifier_order', true );
            $orderMeta = $orderMeta > 0 ? $orderMeta : '';
            $content = '<span>'.$orderMeta.'</span>';
        }
        return $content;
    }

    public function registerPostType()
    {
        $post_type = 'product_modifier';
        $args = [
            'label' => 'Modifier',
            'labels' => [
                'name' => 'Modifier',
                'singular_name' => 'Modifier',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Modifier',
            ],
            'menu_position' => 20,
            'rewrite' => ['slug' => 'product_modifier'],
            'supports' => ['title'],
            'taxonomies'          => ['product_modifier_category', ],
            'exclude_from_search' => false,
            'capabilities' => [],
            'show_ui' => true,
            'public' => true,
            'show_in_rest' => true,
        ];
        register_post_type( $post_type, $args );
    }

    public function taxonomiesForModifier()
    {
        $labels = array(
            'name' => 'Nhóm Modifier',
            'menu_name' => 'Nhóm Modifier',
        );
        $args = array(
            'labels' => $labels,
            'show_in_nav_menus' => true,
            'show_ui' => true,
            'hierarchical' => true,
        );
        register_taxonomy( 'product_modifier_category', ['product', 'product_modifier'], $args );
    }

    public function restrictManagePostType()
    {
        global $typenow;
        $taxonomy = 'product_modifier_category';
        if( $typenow == 'product_modifier') {
            $filters = array($taxonomy);
            foreach ($filters as $tax_slug) {
                $tax_obj = get_taxonomy($tax_slug);
                $tax_name = $tax_obj->labels->name;
                $terms = get_terms($tax_slug);
                echo "<select id=\"{$tax_slug}\" class=\"postform\" name=\"{$tax_slug}\">";
                echo "<option value=\"\">Show All {$tax_name}</option>";
                foreach ($terms as $term) { ?>
                    <option <?=(isset($_GET[$tax_slug]) && $term->slug == $_GET[$tax_slug] ? 'selected' : '')?> value="<?=$term->slug?>"><?=$term->name?> (<?=$term->count?>)</option>
                    <?php
                }
                echo "</select>";
            } // end foreach filters
        } // end if
    }

    public function addBlockSyncMerchant($post)
    {
        $isMaster = get_field('is_master_product_mofifier', $post->ID) ?? false;
        if (!current_user_can('edit_posts') || !$isMaster) {
            return false;
        }
        $html = "<div class='notice notice-success notice-sync-product-modifier is-dismissible hidden'><p>Đồng bộ thành công.</p></div>";
        $html .= "<button type='button' class='button-primary text-center' style='margin: 10px 0; width: 100%;' id='sync_product_modifier' data-product-modifier-id='{$post->ID}'>SYNC TO MERCHANT</button>";
        ?>
        <div class="block-merchant"></div>
        <script type="text/javascript">
	        jQuery(document).ready(function($) {
		        $('#sync_product_modifier').on('click', function () {
                    let _self = $(this);
                    _self.addClass('loading');
                    let productModifierId = $(this).attr('data-product-modifier-id');
                    let data = {
                            action: 'sync_product_modifier',
                            productModifierId: productModifierId,
                        };
                    $.ajax({
                        url : '<?=admin_url('admin-ajax.php')?>',
                        type : 'post',
                        dataType : 'json',
                        data : data,
                        success : function (res) {
                            if (res.messageCode == 1) {
                                $('.notice-sync-product-modifier').removeClass('hidden');
                            } else {
                                //
                            }
                        },
                        error : function (x, y, z) {
                            //
                        },
                        complete : function () {
	                        _self.removeClass('loading');
                        }
                    });
		        });
	        });
        </script>
        <?php
        echo $html;
    }

    public function syncProductModifier()
    {
        $res = new Result();
        if (!isset($_REQUEST['productModifierId'])) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Có lỗi tham số ajax, vui lòng làm mới trang và thử lại.';
            Response::returnJson($res);
            die;
        }
        $productModifierId = $_REQUEST['productModifierId'];
        $productModifierTitle = get_the_title($productModifierId);
        $merchantList = get_field('product_mofifier_merchant_list', $productModifierId);
        foreach ($merchantList as $merchant) {
            $productModifierData = array(
                'post_author' => get_current_user_id(),
                'post_content' => '',
                'post_status' => "publish",
                'post_title' => $productModifierTitle,
                'post_type' => "product_modifier",
            );
            $args = array(
                'post_type' => 'product_modifier',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'master_product_modifier_id',
                        'value' => $productModifierId,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'product_mofifier_merchant',
                        'value' => $merchant->ID,
                        'compare' => '='
                    )
                )
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $merchantProductModifierId = get_the_ID();
                    wp_update_post([
                        'ID' => $merchantProductModifierId,
                        'post_title' => $productModifierTitle,
                    ]);
                    $this->updateTaxonomies($productModifierId, $merchantProductModifierId);
                }
                wp_reset_postdata();
            } else {
                $postId = wp_insert_post($productModifierData);
                update_post_meta($postId, 'product_mofifier_merchant', $merchant->ID);
                update_post_meta($postId, 'master_product_modifier_id', $productModifierId);
                $this->updateTaxonomies($productModifierId, $postId);
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = '';
        Response::returnJson($res);
        die;
    }

    private function updateTaxonomies($sourcePostId, $duplicatePostId)
    {
        $listTaxonomies = ['product_modifier_category'];
        foreach ($listTaxonomies as $taxonomy) {
            $terms = get_the_terms($sourcePostId, $taxonomy);
            if (!is_wp_error($terms)) {
                wp_set_object_terms($duplicatePostId, wp_list_pluck($terms, 'term_id'), $taxonomy);
            }
        }
    }

    function customQueryProductModifierFilter($query)
    {
        global $pagenow;
        $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
        if (is_admin() && $pagenow == 'edit.php' && in_array($post_type, ['product_modifier'])) {
            if (isset($_GET[self::MERCHANT_ID_FILTER_KEY_NAME]) && $_GET[self::MERCHANT_ID_FILTER_KEY_NAME] != '') {
                $query->query_vars['meta_key'] = self::MERCHANT_CUSTOM_FIELD_NAME;
                $query->query_vars['meta_value'] = $_GET[self::MERCHANT_ID_FILTER_KEY_NAME];
            }
        }
    }

    function customProductModifierFilter($postType)
    {
        if('product_modifier' !== $postType){
            return;
        }
        global $wpdb;
        $listMerchant = $wpdb->get_results("
            SELECT ID,post_title 
            FROM  $wpdb->posts
            WHERE post_type = 'merchant' AND post_status = 'publish'
        ");
        $current = isset($_GET[self::MERCHANT_ID_FILTER_KEY_NAME]) ? $_GET[self::MERCHANT_ID_FILTER_KEY_NAME] : '';
        ?>
        <select name="<?php echo self::MERCHANT_ID_FILTER_KEY_NAME; ?>">
            <option value=""><?php _e('Filter By Merchant', 'baapf'); ?></option>
            <?php
            foreach ($listMerchant as $merchant) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    $merchant->ID,
                    $merchant->ID == $current ? ' selected="selected"' : '',
                    $merchant->post_title
                );
            }
            ?>
        </select>
        <?php
    }

}

// init
$modifierPostType = new ModifierPostType();
