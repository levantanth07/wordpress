<?php
/**
 * Combo Product Type
 */
class WC_Product_Combo extends WC_Product {

    public const COMBO_TYPE = 'combo';

    public function get_type() {
        return self::COMBO_TYPE;
    }
}

/**
 * Class for custom Combo Product Type
 */
class ComboProductType {

    public const COMBO_PRICE_TAX_TYPE = 'combo_price_tax_type';
    public const COMBO_CHILD_PRODUCT_TYPE = 'combo_child_product_type';
    public const COMBO_GROUP_ITEMS = 'combo_group_items';
    public const COMBO_GROUP_NAME = 'combo_group_name';
    public const COMBO_GROUP_MAX_ITEM = 'combo_group_max_item';
    public const COMBO_GROUP_MIN_ITEM = 'combo_group_min_item';
    public const COMBO_GROUP_SHOW_PRICE_TYPE = 'combo_group_show_price_type';
    public const COMBO_PRODUCT_ITEM = 'combo_product_item';
    public const COMBO_PRODUCT_ITEM_QUANTITY = 'combo_product_item_quantity';
    public const COMBO_PRODUCT_ITEM_IS_FIXED = 'combo_product_item_is_fixed';
    public const COMBO_PRODUCT_ITEM_BASE_PRICE = 'combo_product_item_base_price';

    public const PRICE_TAX_BY_PARENT = 1;
    public const PRICE_TAX_BY_CHILD = 2;
    public const COMBO_LIST_PRICE_TAX = [
        self::PRICE_TAX_BY_PARENT => 'Theo món cha',
        self::PRICE_TAX_BY_CHILD => 'Theo món con',
    ];

    public const CHILD_PRODUCT_FIXED_TYPE = 1;
    public const CHILD_PRODUCT_SELECTION = 2;
    public const COMBO_LIST_CHILD_PRODUCT_TYPE = [
        self::CHILD_PRODUCT_FIXED_TYPE => 'Món con cố định',
        self::CHILD_PRODUCT_SELECTION => 'Món con lựa chọn',
    ];

    public const SHOW_PRICE_COMPARISON = 1;
    public const SHOW_PRICE_DETAIL = 2;
    public const COMBO_LIST_SHOW_PRICE_TYPE = [
        0 => ' - Chọn hiển thị giá - ',
        self::SHOW_PRICE_COMPARISON => 'Hiển thị giá so sánh',
        self::SHOW_PRICE_DETAIL => 'Hiển thị giá chi tiết',
    ];

    /**
     * Build the instance
     */
    public function __construct() {
        add_filter( 'woocommerce_product_class', [$this, 'registerComboProductTypeClass'], 10, 2 );
        add_filter( 'product_type_selector', [ $this, 'addComboType' ] );

        add_action( 'woocommerce_loaded', [ $this, 'loadComboType' ] );
        add_action( 'save_post_product', [$this, 'saveComboProductInfo'] );
        add_action( 'wp_ajax_get_combo_child_product', [$this, 'getComboChildProduct'] );
        add_action( 'admin_footer', array( $this, 'showPriceTab' ) );
        add_action( 'add_meta_boxes_product', [$this, 'addComboProductMetaBox'] );
        add_action( 'admin_enqueue_scripts', [$this, 'addComboProductScripts'] );

    }

    function registerComboProductTypeClass($classname, $productType) {
        if ( $productType == WC_Product_Combo::COMBO_TYPE ) {
            $classname = 'WC_Product_Combo';
        }
        return $classname;
    }

    /**
     * Load WC Dependencies
     *
     * @return void
     */
    public function loadComboType() {
        new WC_Product_Combo();
    }

    /**
     * add combo to type selector
     *
     */
    public function addComboType($types) {
        $types[WC_Product_Combo::COMBO_TYPE] = __('Sản phẩm Combo');

        return $types;
    }

    /**
     * display price for combo
     */
    public function showPriceTab() {
        global $post, $product_object;

        if ( ! $post ) {
            return;
        }

        if ( 'product' != $post->post_type ) {
            return;
        }

        $isComboType = $product_object && $product_object->get_type() == WC_Product_Combo::COMBO_TYPE ? true : false;

        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {
                jQuery('#general_product_data .pricing').addClass('show_if_<?=WC_Product_Combo::COMBO_TYPE?>');

                jQuery('.product_data_tabs .general_tab').attr('style', 'display: block;');
                jQuery('.product_data_tabs .general_tab a').click();

                <?php if ( $isComboType ) { ?>
                    jQuery('#general_product_data .pricing').show();
                    jQuery('#general_product_data ._tax_status_field').parent().show();
                <?php } ?>
            });
        </script>
        <?php
    }

    function addComboProductMetaBox()
    {
        add_meta_box('combo-meta-box', __('Thông tin combo', 'text-domain'), [$this, 'comboProductMetaBoxCallback'], 'product', 'normal', 'high');
    }

    function addComboProductScripts($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ('product' === $post->post_type) {
                $pluginDirUrl = plugins_url('', dirname(dirname(__FILE__)) );
                wp_enqueue_style( 'comboProductMetaBoxCss', $pluginDirUrl . '/assets/css/combo-meta-box.css');
                wp_enqueue_script('comboProductMetaBoxScript', $pluginDirUrl . '/assets/js/combo-meta-box.js');

                $data = array(
                    'comboProductType' => WC_Product_Combo::COMBO_TYPE,
                    'comboGroupName' => self::COMBO_GROUP_NAME,
                    'comboGroupMaxItem' => self::COMBO_GROUP_MAX_ITEM,
                    'comboGroupMinItem' => self::COMBO_GROUP_MIN_ITEM,
                    'comboProductItem' => self::COMBO_PRODUCT_ITEM,
                    'comboProductItemQuantity' => self::COMBO_PRODUCT_ITEM_QUANTITY,
                    'comboProductItemIsFixed' => self::COMBO_PRODUCT_ITEM_IS_FIXED,
                    'comboProductItemBasePrice' => self::COMBO_PRODUCT_ITEM_BASE_PRICE,
                    'comboHtml' => $this->getComboHtml(),
                    'comboProductItemHtml' => $this->getComboProductItemHtml(),
                );
                wp_localize_script('comboProductMetaBoxScript', 'comboScriptVars', $data);
            }
        }
    }

    function saveComboProductInfo($postId)
    {
        if (empty($_POST) || WC_Product_Combo::COMBO_TYPE != $_POST['product-type']) {
            return;
        }
        $comboPriceTaxType = $_POST[self::COMBO_PRICE_TAX_TYPE] ?? 1;
        $comboChildProductType = $_POST[self::COMBO_CHILD_PRODUCT_TYPE] ?? 1;
        $comboGroups = $this->makeComboData($_POST);

        update_post_meta($postId, self::COMBO_PRICE_TAX_TYPE, $comboPriceTaxType);
        update_post_meta($postId, self::COMBO_CHILD_PRODUCT_TYPE, $comboChildProductType);
        update_post_meta($postId, self::COMBO_GROUP_ITEMS, $comboGroups);
    }

    public function makeComboData($postData) 
    {
        $comboPriceTaxType = $_POST[self::COMBO_PRICE_TAX_TYPE] ?? 1;
        $comboChildProductType = $_POST[self::COMBO_CHILD_PRODUCT_TYPE] ?? 1;

        $comboGroupName = $postData[self::COMBO_GROUP_NAME] ?? [];
        $comboGroupMinItem = $postData[self::COMBO_GROUP_MIN_ITEM] ?? [];
        $comboGroupMaxItem = $postData[self::COMBO_GROUP_MAX_ITEM] ?? [];
        $comboGroupShowPriceType = $postData[self::COMBO_GROUP_SHOW_PRICE_TYPE] ?? [];
        $comboProducts = $postData[self::COMBO_PRODUCT_ITEM] ?? [];
        $comboProductQuantity = $postData[self::COMBO_PRODUCT_ITEM_QUANTITY] ?? [];
        $comboProductIsFixed = $postData[self::COMBO_PRODUCT_ITEM_IS_FIXED] ?? [];
        $comboProductBasePrice = $postData[self::COMBO_PRODUCT_ITEM_BASE_PRICE] ?? [];

        $comboGroups = [];
        foreach ($comboGroupName as $key => $comboGroupNameItem) {
            if (empty($comboGroupNameItem)) {
                continue;
            }
            $comboGroupItem = [];
            $comboGroupItem['group_name'] = $comboGroupNameItem;
            $comboGroupItem['group_max_item'] = null;
            $comboGroupItem['group_min_item'] = null;
            $comboGroupItem['group_show_price_type'] = null;
            if (intval($comboChildProductType) == self::CHILD_PRODUCT_SELECTION) {
                if (isset($comboGroupMaxItem[$key]) && $comboGroupMaxItem[$key]) {
                    $comboGroupItem['group_max_item'] = $comboGroupMaxItem[$key];
                }
                if (isset($comboGroupMinItem[$key]) && $comboGroupMinItem[$key]) {
                    $comboGroupItem['group_min_item'] = $comboGroupMinItem[$key];
                }

                $showPriceTypeCondition = intval($comboPriceTaxType) == self::PRICE_TAX_BY_CHILD 
                                        && intval($comboGroupItem['group_max_item']) == 1;
                if ($showPriceTypeCondition) {
                    $comboGroupItem['group_show_price_type'] = $comboGroupShowPriceType[$key];
                }
            }
            $isValidProductSelection = isset($comboProducts[$key], $comboProductQuantity[$key]) 
                && !empty($comboProducts[$key]) && !empty($comboProductQuantity[$key]);
            if ($isValidProductSelection) {
                $comboGroupItem['product_items'] = [];
                foreach ($comboProducts[$key] as $productItemKey => $comboProductItemId) {
                    if (isset($comboProductQuantity[$key][$productItemKey]) 
                        && 0 < $productItemQuantity = intval($comboProductQuantity[$key][$productItemKey])) {
                        $productItemIsFixed = ($comboProductIsFixed[$key][$productItemKey] ?? '') == '1' ? 1 : 0;
                        $productItemIsBasePrice = isset($comboProductBasePrice[$key]) && $comboProductBasePrice[$key]  == $productItemKey ? 1 : 0;
                        if (intval($comboGroupItem['group_show_price_type']) != self::SHOW_PRICE_COMPARISON) {
                            $productItemIsBasePrice = 0;
                        }
                        $comboGroupItem['product_items'][] = [
                            'id' => $comboProductItemId,
                            'quantity' => $productItemQuantity,
                            'is_fixed' => $productItemIsFixed,
                            'is_base_price' => $productItemIsBasePrice
                        ];
                    }
                }
            }
            $comboGroups[] = $comboGroupItem;
        }
        return $comboGroups;
    }

    function getComboChildProduct()
    {
        $args = [
            'posts_per_page' => 100,
            'post_type' => 'product',
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => 'simple',
                ),
            ),
        ];
        $searchString = $_GET['q'] ?? '';
        $merchantId = $_GET['merchantId'] ?? '';
        $excludeIds = $_GET['excludeIds'] ?? [];
        if (!empty($searchString)) {
            $args['s'] = sanitize_text_field($searchString);
        }
        if (!empty($merchantId)) {
            $args['meta_query'][] = [
                'key' => 'merchant_id',
                'value' => intval($merchantId),
                'type' => 'NUMERIC',
                'compare' => '='
            ];
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

    function getProductsFromCombo($comboGroups)
    {
        global $wpdb;
        $products = $productIds = [];
        foreach ($comboGroups as $groupItem) {
            $productIds = array_merge($productIds, array_column($groupItem['product_items'] ?? [], 'id'));
        }
        $query = "SELECT ID, post_title FROM $wpdb->posts 
                    WHERE post_type = 'product' AND ID IN (" . implode(',', $productIds) . ")";
        $results = $wpdb->get_results($query);
        foreach ($results as $productItem) {
            $products[$productItem->ID] = $productItem->post_title;
        }
        return $products;
    }

    function comboProductMetaBoxCallback($post)
    {
        $comboPriceTaxType = get_post_meta($post->ID, self::COMBO_PRICE_TAX_TYPE, true);
        $comboChildProductType = get_post_meta($post->ID, self::COMBO_CHILD_PRODUCT_TYPE, true);
        $comboGroups = get_post_meta($post->ID, self::COMBO_GROUP_ITEMS, true);
        $products = $this->getProductsFromCombo($comboGroups);
        $comboContainerClass[] = empty($comboChildProductType) ? 'child-product-type-1' : "child-product-type-$comboChildProductType";
        $comboContainerClass[] = empty($comboPriceTaxType) ? 'price-tax-type-1' : "price-tax-type-$comboPriceTaxType";
        ?>
        <div class="combo-container mt-4 <?=implode(' ', $comboContainerClass)?>">
            <div class="list-group-combo">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-5">
                                        Loại thuế giá
                                    </div>
                                    <div class="col-md-7">
                                        <select class="col-md-12 select" id="priceTaxType" name="<?=self::COMBO_PRICE_TAX_TYPE?>">
                                            <?php
                                                foreach(self::COMBO_LIST_PRICE_TAX as $value => $priceTaxText) {
                                                    ?>
                                                        <option value="<?=$value?>" <?=$comboPriceTaxType == $value ? 'selected' : ''?>><?=$priceTaxText?></option>
                                                    <?php
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-md-5">
                                        Kiểu chọn món con
                                    </div>
                                    <div class="col-md-7">
                                        <select class="select col-md-12" id="childProductType" name="<?=self::COMBO_CHILD_PRODUCT_TYPE?>">
                                            <?php
                                                foreach(self::COMBO_LIST_CHILD_PRODUCT_TYPE as $value => $text) {
                                                    ?>
                                                        <option value="<?=$value?>" <?=$comboChildProductType == $value ? 'selected' : ''?>><?=$text?></option>
                                                    <?php
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-12">
                        Sản phẩm combo
                    </div>
                </div>
                <?php 
                    if (empty($comboGroups)) {
                        echo $this->getComboHtml();
                    } else {
                        foreach ($comboGroups as $groupKey => $comboItem) {
                            echo $this->getComboHtml($groupKey, $comboItem, $products);
                        }
                    }
                ?>
            </div>
            <div class='group-combo-action mt-4'>
                <div class='row'>
                    <div class='col-md-12 text-center'>
                        <a href='javascript:void(0);' id='btn-add-more-group-combo' class='button button-primary'>
                            <i class='fa fa-plus'></i> Thêm nhóm món
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    function getComboHtml($groupUniqueKey = 0, $comboItem = [], $productInfo = [])
    {
        ob_start();
        $comboGroupName = $comboItem['group_name'] ?? '';
        $comboGroupMinItem = $comboItem['group_min_item'] ?? 1;
        $comboGroupMaxItem = $comboItem['group_max_item'] ?? 1;
        $comboGroupShowPriceType = $comboItem['group_show_price_type'] ?? 0;
        $comboProductItems = $comboItem['product_items'] ?? [];
        $groupComboClass[] = $comboGroupMaxItem == 1 ? 'show-price-type' : '';
        $groupComboClass[] = $comboGroupShowPriceType != 0 ? "show-price-type-$comboGroupShowPriceType" : '';
        ?>
        <div class="row mt-2 group-combo-item <?=implode(' ', $groupComboClass)?>" data-group-unique-key='<?=$groupUniqueKey?>'>
            <div class="col-md-12 pt-1 pb-2">
                <div class="row">
                    <div class="col-md-5">
                        <strong>Tên nhóm</strong>
                    </div>
                    <div class="col-md-7 combo-group-number">
                        <strong>SL tối thiểu - tối đa được chọn trong nhóm</strong>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-5">
                        <input class='short w-100' type="text" value="<?=$comboGroupName?>" name='<?=self::COMBO_GROUP_NAME?>[<?=$groupUniqueKey?>]' >
                    </div>
                    <div class="col-md-2 combo-group-min-item">
                        <select name='<?=self::COMBO_GROUP_MIN_ITEM?>[<?=$groupUniqueKey?>]'>
                            <?php
                                for($i = 1; $i <= 20; $i++) {
                                    ?>
                                        <option value="<?=$i?>" <?=$comboGroupMinItem == $i ? 'selected' : ''?>><?=$i?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 combo-group-max-item">
                        <select name='<?=self::COMBO_GROUP_MAX_ITEM?>[<?=$groupUniqueKey?>]'>
                            <?php
                                for($i = 1; $i <= 20; $i++) {
                                    ?>
                                        <option value="<?=$i?>" <?=$comboGroupMaxItem == $i ? 'selected' : ''?>><?=$i?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 combo-group-show-price-type">
                        <select class='select-show-price-type' name='<?=self::COMBO_GROUP_SHOW_PRICE_TYPE?>[<?=$groupUniqueKey?>]'>
                            <?php
                                foreach(self::COMBO_LIST_SHOW_PRICE_TYPE as $value => $showPriceType) {
                                    ?>
                                        <option value="<?=$value?>" <?=$comboGroupShowPriceType == $value ? 'selected' : ''?>><?=$showPriceType?></option>
                                    <?php
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <a href='javascript:void(0);' class='text-danger btn-remove-group'>
                            <span class="dashicons dashicons-trash"></span>
                        </a>
                    </div>
                </div>
                <div class="row mt-2 children-product-container">
                    <div class='col-md-12'>
                        <div class="row">
                            <div class="col-md-5">
                                Sản phẩm
                            </div>
                            <div class="col-md-4">
                                Số lượng
                            </div>
                            <div class="col-md-2">
                                <a href='javascript:void(0);' class='button button-small button-primary btn-add-more-product'>Thêm</a>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 children-product-list">
                                <?php
                                    if (empty($comboProductItems)) {
                                        echo $this->getComboProductItemHtml();
                                    } else {                                               
                                        foreach ($comboProductItems as $productIndex => $productItem) {
                                            echo $this->getComboProductItemHtml($groupUniqueKey, $productIndex, $productItem, $productInfo);
                                        }
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    function getComboProductItemHtml($groupUniqueKey = 0, $productUniqueKey = 0, $productItem = [], $productInfo = [])
    {
        ob_start();
        ?>
        <div class="row mt-2 product-item">
            <div class="col-md-5">
                <select class='w-100 combo-product-item' name='<?=self::COMBO_PRODUCT_ITEM?>[<?=$groupUniqueKey?>][<?=$productUniqueKey?>]'>
                    <?php if(!empty($productItem)): ?>
                        <option selected='selected' value="<?=$productItem['id']?>">
                            <?=isset($productInfo[$productItem['id']]) ? $productInfo[$productItem['id']] : 'Không lấy được tên sản phẩm';?>
                        </option>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name='<?=self::COMBO_PRODUCT_ITEM_QUANTITY?>[<?=$groupUniqueKey?>][<?=$productUniqueKey?>]'>
                    <?php
                        for($i = 1; $i <= 20; $i++) {
                            ?>
                                <option value="<?=$i?>" <?=isset($productItem['quantity']) && $productItem['quantity'] == $i ? 'selected' : ''?>><?=$i?></option>
                            <?php
                        }
                    ?>
                </select>
            </div>
            <div class="col-md-2 combo-product-item-is-fixed">
                <label>
                    <input class='' type="checkbox" <?= ($productItem['is_fixed'] ?? '') == '1' ? 'checked' : ''; ?> value="1" name='<?=self::COMBO_PRODUCT_ITEM_IS_FIXED?>[<?=$groupUniqueKey?>][<?=$productUniqueKey?>]' >
                    Món cố định
                </label>
            </div>
            <div class="col-md-2 combo-product-item-base-price">
                <label>
                    <input <?=($productItem['is_base_price'] ?? '') == '1' ? 'checked' : '';?> class='' type="radio" data-base-price-trace value="<?=$productUniqueKey?>" name='<?=self::COMBO_PRODUCT_ITEM_BASE_PRICE?>[<?=$groupUniqueKey?>]' >
                    Giá cơ sở
                </label>
            </div>
            <div class="col-md-1">
                <a href='javascript:void(0);' class="text-danger btn-remove-product-item"><span class="dashicons dashicons-trash"></span></a>
            </div>
        </div>
        <?php
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
}

$comboProductType = new ComboProductType();