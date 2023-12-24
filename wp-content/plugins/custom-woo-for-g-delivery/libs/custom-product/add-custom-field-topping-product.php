<?php

class AddCustomFieldToppingProduct
{
    public static $toppingProductIdsKey = '_topping_product_ids';

    public function __construct()
    {
        add_action('add_meta_boxes_product', [$this, 'addToppingProductMetaBox']);
        add_action('admin_enqueue_scripts', [$this, 'addToppingProductScripts'], 10, 1);
        add_action('admin_enqueue_scripts', [$this, 'localizeToppingMetaBoxScript']);
        add_action('wp_ajax_getToppingProductOptions', [$this, 'getToppingProductOptions']);
        add_action('save_post_product', [$this, 'saveToppingProductIds']);
    }

    function addToppingProductMetaBox()
    {
        add_meta_box('topping-meta-box', __('Topping products', 'text-domain'), [$this, 'toppingProductMetaBoxCallback'], 'product', 'normal', 'high');
    }

    function toppingProductMetaBoxCallback($post)
    {
        wp_nonce_field(basename(__FILE__), 'topping_meta_box_nonce');
        $toppingProductIds = get_post_meta($post->ID, self::$toppingProductIdsKey, true);
        $toppingProductIds = !is_array($toppingProductIds) ? [] : $toppingProductIds;
        if (!empty($toppingProductIds)) {
            $toppingProductIds = array_map('intval', $toppingProductIds);
            global $wpdb;
            $query = "SELECT ID, post_title FROM $wpdb->posts 
                    WHERE post_type = 'product' AND ID IN (" . implode(',', $toppingProductIds) . ")";
            $results = $wpdb->get_results($query);
            $toppingProducts = [];
            foreach ($results as $result) {
                $toppingProducts[$result->ID] = $result->post_title;
            }
        }
?>
        <table class="form-table">
            <thead>
                <tr>
                    <td><label for="topping-meta-box-value"> <?php echo __('Topping products', 'text-domain') ?> </label></td>
                    <td><select id="topping-meta-box-value" name="select_topping_product_ids" class="select2" style="width: 300px"></select></td>
                    <td><a href="#" class="btn-add-topping button button-primary">Add topping</button></td>
                </tr>
            </thead>
            <tbody class="list_topping_product">
                <?php
                foreach ($toppingProductIds as $toppingProductId) {
                ?>
                    <tr>
                        <td>
                        </td>
                        <td>
                            <span><?php echo isset($toppingProducts[$toppingProductId]) ? $toppingProducts[$toppingProductId] : '-'; ?></span>
                            <span class="dashicons dashicons-trash remove-topping-item" style="cursor: pointer;"></span>
                        </td>
                        <td><input class='topping_product_id' type='hidden' name='_topping_product_ids[]' value='<?php echo $toppingProductId; ?>'></td>
                    </tr>
                <?php
                }
                ?>
            </tbody>
        </table>
<?php
    }

    function addToppingProductScripts($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ('product' === $post->post_type) {
                $pluginDirUrl = plugins_url('', dirname(dirname(__FILE__)) );
                wp_enqueue_script('toppingMetaBoxScript', $pluginDirUrl . '/assets/js/topping-meta-box.js');
            }
        }
    }

    function localizeToppingMetaBoxScript()
    {
        $data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'postId' => get_the_ID(),
            'nonce' => wp_create_nonce('topping_meta_box_nonce')
        );

        wp_localize_script('toppingMetaBoxScript', 'toppingMetaBoxScriptVars', $data);
    }

    function getToppingProductOptions()
    {
        check_ajax_referer('topping_meta_box_nonce', 'nonce');
        $merchantId = get_post_meta($_GET['postId'], 'merchant_id', true);
        if (empty($merchantId)) {
            wp_send_json([]);
            return;
        }
        $args = [
            'posts_per_page' => -1,
            'post_type' => 'product',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'topping',
                ),
            ),
            'meta_query' => array(
                array(
                    'key' => 'merchant_id',
                    'value' => intval($merchantId),
                    'compare' => '='
                )
            )
        ];
        $searchString = $_GET['q'] ?? '';
        $excludeIds = $_GET['excludeIds'] ?? [];
        if (!empty($searchString)) {
            $args['s'] = sanitize_text_field($searchString);
        }
        if (!empty($excludeIds)) {
            $args['post__not_in'] = array_filter($excludeIds, 'is_numeric');
        }
        $posts = get_posts($args);
        $options = array();
        foreach ($posts as $post) {
            $options[] = array(
                'id' => $post->ID,
                'text' => $post->post_title
            );
        }
        wp_send_json($options);
    }


    function saveToppingProductIds($postId)
    {
        if (isset($_POST['topping_meta_box_nonce']) && wp_verify_nonce($_POST['topping_meta_box_nonce'], basename(__FILE__))) {
            if (isset($_POST[self::$toppingProductIdsKey])) {
                $toppingProductIds = array_unique($_POST[self::$toppingProductIdsKey]);
                update_post_meta($postId, self::$toppingProductIdsKey, $toppingProductIds);
            }
        }
    }
}

$initCustomFieldToppingProduct = new AddCustomFieldToppingProduct();
