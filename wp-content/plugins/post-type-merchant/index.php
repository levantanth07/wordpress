<?php
/*
Plugin Name: Post Type Merchant
Plugin URI: http://ggg.com.vn/
Description: Manage Merchant.
Author: thienhaxanh2405 <toan.nguyenduc@ggg.com.vn>
Version: 1.0
Author URI: https://thienhaxanh.info/
*/

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

require_once 'libs/sync_product_job.php';

class MerchantPostType
{
    private const START_ROW_INDEX = 2;

    private const MAX_IMPORTED_FILE_COLUMN = 'E';

    private const MAPPING_COLUMN = [
        'A' => 'sap_code',
        'B' => 'rk_code',
        'C' => 'product_name',
        'D' => 'price',
        'E' => 'quantity',
    ];

    private const MAPPING_COLUMN_NAME = [
        'A' => 'Mã SAP',
        'B' => 'Mã RK',
        'C' => 'Tên món',
        'D' => 'Giá SAP (chưa VAT)',
        'E' => 'Số lượng tồn kho',
    ];

    private const SAP_CODE_COLUMN = 'A';

    private const RK_CODE_COLUMN = 'B';

    private const PRICE_COLUMN = 'D';

    private const QUANTITY_COLUMN = 'E';

    private const INVALID_IMPORTED_FILE = 'File có định dạng không đúng';

    public static $postTypeKey = "merchant";
    public static $merchantIdFilterKeyName = "custom_field_merchant_id";
    public static $masterProductFilterKeyName = "custom_field_is_master_product";
    public static $merchantIdCustomFieldName = "merchant_id";
    public static $masterProductCustomFieldName = "is_master_product";

    public function __construct()
    {
        add_action('woocommerce_loaded', [$this, 'addHooks']);
        add_action('init', [$this, 'registerPostType'], 0);
        add_action('admin_menu', [$this, 'addImportProductPage'], 999);
    }

    public function addHooks()
    {
        // add_action('wp_after_insert_post', [$this, 'savePostProductCallback']);
        add_action('admin_enqueue_scripts', [$this, 'addProductAdminScripts'], 10, 1);
        add_filter('parse_query', [$this, 'customProductsFilter']);
        add_action('restrict_manage_posts', [$this, 'customProductsFilterRestrictManagePosts']);
        add_action('post_submitbox_start', [$this, 'addSyncToMerchantButton']);
        add_action('admin_action_sync_product_to_merchant', [$this, 'syncProductToMerchant']);
        add_filter('post_row_actions', [$this, 'merchantProductList'], 10, 2);
        add_filter('post_row_actions', [$this, 'importProduct'], 11, 2);

        add_filter('page_row_actions', [$this, 'addProductCategoryLink'], 10, 2);
        add_filter('post_row_actions', [$this, 'addProductCategoryLink'], 10, 2);
    }

    function merchantProductList($actions, $post)
    {
        if ($post->post_type == self::$postTypeKey)
        {
            $url = add_query_arg(
                array(
                    'post_type' => 'product',
                    'post_status' => 'all',
                    'action' => -1,
                    'custom_field_merchant_id' => $post->ID,
                    'filter_action' => 'Filter',
                    'paged' => 1,
                    'action2' => -1
                ),
                'edit.php'
            );
            $actions['view_product_list'] = '<a target="_blank" href="' . $url . '" title="" rel="permalink">Product list</a>';
        }
        return $actions;
    }

    function customProductsFilter($query)
    {
        global $pagenow;
        $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
        if (is_admin() && $pagenow == 'edit.php' && in_array($post_type, ['product', 'merchant'])) {
            if (isset($_GET[self::$merchantIdFilterKeyName]) && $_GET[self::$merchantIdFilterKeyName] != '') {
                $query->query_vars['meta_key'] = self::$merchantIdCustomFieldName;
                $query->query_vars['meta_value'] = $_GET[self::$merchantIdFilterKeyName];
            }
            if (isset($_GET[self::$masterProductFilterKeyName]) && $_GET[self::$masterProductFilterKeyName] != '') {
                $query->query_vars['meta_key'] = self::$masterProductCustomFieldName;
                $query->query_vars['meta_value'] = '1';
            }
        }
    }

    function customProductsFilterRestrictManagePosts($postType)
    {
        if('product' !== $postType){
            return;
        }
        global $wpdb;
        $listMerchant = $wpdb->get_results("
            SELECT ID,post_title 
            FROM  $wpdb->posts
            WHERE post_type = '" . self::$postTypeKey . "' AND post_status = 'publish'
        ");
        $current = isset($_GET[self::$merchantIdFilterKeyName]) ? $_GET[self::$merchantIdFilterKeyName] : '';
        $masterProductChecked = isset($_GET[self::$masterProductFilterKeyName]) && $_GET[self::$masterProductFilterKeyName] == 'yes' ? 'checked' : '';
        ?>
        <select name="<?php echo self::$merchantIdFilterKeyName; ?>">
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
        <label>
            <input style="height: 16px;" type="checkbox" name="<?php echo self::$masterProductFilterKeyName; ?>" value="1" <?php echo $masterProductChecked;?>>
            Show only master product
        </label>
        <?php
    }

    public function addProductAdminScripts($hook)
    {
        global $post;
        if ($hook == 'post-new.php' || $hook == 'post.php') {
            if ('product' === $post->post_type) {
                wp_enqueue_script('productAdminScript', plugin_dir_url(__FILE__) . '/assets/js/product-admin.js');
            }
        }
    }

    function addSyncToMerchantButton($post)
    {
        $isMasterProduct = get_field('is_master_product', $post->ID) ?? false;
        if (!current_user_can('edit_posts') || !$isMasterProduct) {
            return false;
        }
        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'action' => 'sync_product_to_merchant',
                    'post' => $post->ID,
                ),
                'admin.php'
            ),
            basename(__FILE__),
            'sync_product_nonce'
        );
        $html = "<a target='_blank' id='sync_to_merchant' class='button-primary' style='margin: 10px 0;' href='" . $url . "'>SYNC TO MERCHANT</a>";
        echo $html;
    }

    function syncProductToMerchant()
    {
        if (!current_user_can('edit_posts')) {
            wp_die('Permission denied!');
        }
        if (empty($_GET['post'])) {
            wp_die('No post to sync has been provided!');
        }
        // Nonce verification
        if (!isset($_GET['sync_product_nonce']) || !wp_verify_nonce($_GET['sync_product_nonce'], basename(__FILE__))) {
            return;
        }
        $masterProductId = absint($_GET['post']);
        $isMasterProduct = get_field('is_master_product', $masterProductId);
        if (!$isMasterProduct) {
            wp_die('Is not the master product');
        }
        (new SyncProductJob($masterProductId))->handle();
        echo 'Sync completed!';
    }

    public function savePostProductCallback($postId)
    {
        global $post;
        if (isset($post->post_type) && $post->post_type != 'product') {
            return;
        }
    }

    public function registerProductCatForMerchant()
    {
        register_taxonomy_for_object_type('product_cat', 'merchant');
    }

    public function registerPostType()
    {
        $post_type = self::$postTypeKey;
        $args = [
            'label' => 'Merchant',
            'labels' => [
                'name' => 'Merchant',
                'singular_name' => 'Merchant',
                'all_items' => 'Tất cả',
                'add_new' => 'Thêm mới',
                'edit_item' => 'Chỉnh sửa',
                'menu_name' => 'Merchant',
            ],
            'menu_position' => 20,
            'menu_icon' => 'dashicons-store',
            'rewrite' => ['slug' => 'merchants'],
            'supports' => ['title', 'thumbnail'],
            //'taxonomies'  => ['product_cat'],
            'exclude_from_search' => false,
            'capabilities' => [
                /*'edit_post'          => 'edit_tgs-notification',
                'read_post'          => 'read_tgs-notification',
                'delete_post'        => 'delete_tgs-notification',
                'edit_posts'         => 'edit_tgs-notification',
                'edit_others_posts'  => 'edit_tgs-notification',
                'publish_posts'      => 'publish_tgs-notification',
                'read_private_posts' => 'read_private_tgs-notification',
                'create_posts'       => 'edit_tgs-notification',*/],
            'show_ui' => true,
            'public' => true,
            'show_in_rest' => true,
        ];
        register_post_type($post_type, $args);
    }

    public function addProductCategoryLink($actions, $page_object)
    {
        global $post;
        $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
        if ($post_type == 'merchant') {
            $url = admin_url('edit.php?post_type=product_category&apply_for_merchant=' . $post->ID);
            $actions['product_category_link'] = '<a href="' . $url . '" class="facebook_link">' . __('Product category') . '</a>';
        }

        return $actions;
    }

    public function addImportProductPage()
    {
        add_submenu_page(
            '',
            'Import sản phẩm',
            'Import sản phẩm',
            'edit_posts',
            'import-products',
            [$this, 'editPromotionCallback']
        );
    }

    public function editPromotionCallback()
    {
        $merchantId = $_GET['merchant_id'];
        $redirectUrl =  add_query_arg(
            [
                'post_type' => 'merchant',
            ],
            'edit.php'
        );
        if (! $merchantId) {
            wp_redirect($redirectUrl);
            exit;
        }

        $merchantQuery = new WP_Query([
            'post_type' => 'merchant',
            'p' => $merchantId,
        ]);
        if (! $merchantQuery->have_posts()) {
            wp_redirect($redirectUrl);
            exit;
        }

        $message = null;
        $errors = [];
        $merchant = $merchantQuery->post;
        if ($_FILES) {
            $file = $_FILES['file'];
            $fileExtension = strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
            if ($fileExtension !== 'xlsx') {
                $message = 'Hệ thống chỉ hỗ trợ file định dạng xlsx !';
            } else {
                $reader = new Xlsx();
                $spreadsheet = $reader->load($file['tmp_name']);
                $sheets = $spreadsheet->getAllSheets();
                $sheet = $sheets[0];
                $validateImportedFileResult = $this->validateImportedFile($sheet);
                if ($validateImportedFileResult->messageCode === Message::SUCCESS) {
                    /** @var array $productData */
                    $productData = $validateImportedFileResult->result;
                    $importProductDataResult = $this->importProductData($productData, $merchant);
                    $message = $importProductDataResult->message;
                } else {
                    $message = $validateImportedFileResult->message;
                    $errors = $validateImportedFileResult->result ?? [];
                }
            }
        }

        $stubUrl = plugins_url( 'templates/stub.xlsx', __FILE__ );
        require_once plugin_dir_path(__FILE__) . 'templates/import_product.php';
    }

    public function importProduct($actions, $post)
    {
        if ($post->post_type === self::$postTypeKey) {
            $importProductUrl = admin_url('admin.php?page=import-products&amp;merchant_id=' . $post->ID);
            $actions['import_product_page'] = '<a href="' . $importProductUrl . '" title="" rel="permalink">Import Product</a>';
        }
        return $actions;
    }

    private function validateImportedFile(Worksheet $sheet)
    {
        $result = new Result();
        $highestColumn = $sheet->getHighestColumn();
        if ($highestColumn < self::MAX_IMPORTED_FILE_COLUMN) {
            $result->messageCode = Message::GENERAL_ERROR;
            $result->message = self::INVALID_IMPORTED_FILE;
            return $result;
        }

        $highestRow = $sheet->getHighestRow();
        $productData = [];
        $uniqueCompositeCode = [];
        $errors = [];
        for ($row = self::START_ROW_INDEX; $row <= $highestRow; $row++) {
            $rowData = [];
            $compositeCode = [];
            $isUnique = true;
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $sheet->getCell($col.$row)->getValue();
                if (! $cellValue) {
                    $errors[$row][] = vsprintf('%s đang để trống. Vui lòng kiểm tra lại !', [self::MAPPING_COLUMN_NAME[$col]]);
                    continue;
                }

                if ($col === self::SAP_CODE_COLUMN) {
                    if (! is_int($cellValue) || ! ctype_digit((string) $cellValue)) {
                        $errors[$row][] = 'Mã SAP sai định dạng. Vui lòng kiểm tra lại !';
                        continue;
                    }
                    $compositeCode[] = $cellValue;
                }

                if ($col === self::RK_CODE_COLUMN) {
                    if (! is_int($cellValue) || ! ctype_digit((string) $cellValue)) {
                        $errors[$row][] = 'Mã RK sai định dạng. Vui lòng kiểm tra lại !';
                        continue;
                    }
                    $compositeCode[] = $cellValue;
                }

                if (($col === self::PRICE_COLUMN) && ! preg_match('/^\d+(\.\d+)?$/', $cellValue)) {
                    $errors[$row][] = 'Giá SAP (chưa VAT) sai định dạng. Vui lòng kiểm tra lại !';
                    continue;
                }

                if (($col === self::QUANTITY_COLUMN) && ! is_int($cellValue)) {
                    $errors[$row][] = 'Số lượng tồn kho sai định dạng. Vui lòng kiểm tra lại !';
                    continue;
                }

                $rowData[self::MAPPING_COLUMN[$col]] = $cellValue;
            }

            if (empty($rowData)) {
                unset($errors[$row]);
                continue;
            }

            if (empty($compositeCode)) {
                continue;
            }

            $needCheckCompositeCode = implode('_', $compositeCode);
            foreach ($uniqueCompositeCode as $index => $compositeCode) {
                if ($needCheckCompositeCode === $compositeCode) {
                    $errors[$row][] = vsprintf("Mã SAP và Mã RK bị trùng với dòng %d. Vui lòng kiểm tra lại !", [$index]);
                    $isUnique = false;
                    break;
                }
            }

            if (! $isUnique) {
                continue;
            }

            $uniqueCompositeCode[$row] = $needCheckCompositeCode;
            $productData[] = $rowData;
        }

        if (! empty($errors)) {
            $result->messageCode = Message::GENERAL_ERROR;
            $result->result = $errors;
            return $result;
        }

        if (empty($productData)) {
            $result->messageCode = Message::GENERAL_ERROR;
            $result->message = 'Không có dữ liệu. Vui lòng kiểm tra lại !';
            return $result;
        }

        $result->messageCode = Message::SUCCESS;
        $result->result = $productData;
        return $result;
    }

    private function importProductData(array $productData, WP_Post $merchant)
    {
        $result = new Result();
        $author = get_current_user_id();
        foreach ($productData as $product) {
            $productWithCodeQuery = new WP_Query([
                'post_type' => 'product',
                'meta_query' => [
                    [
                        'key' => 'merchant_id',
                        'value' => $merchant->ID,
                        'compare' => '='
                    ],
                    [
                        'key' => 'product_sap_code',
                        'value' => $product['sap_code'],
                        'compare' => '='
                    ],
                    [
                        'key' => 'product_rk_code',
                        'value' => $product['rk_code'],
                        'compare' => '='
                    ],
                ],
                'numberposts' => 1,
            ]);

            if ($productWithCodeQuery->have_posts()) {
                $productId = $productWithCodeQuery->posts[0]->ID;
                if (wp_update_post([
                    'ID' => $productId,
                    'post_title' => $product['product_name'],
                ]) instanceof WP_Error) {
                    $result->messageCode = Message::GENERAL_ERROR;
                    $result->message = 'Cập nhật thất bại';
                    return $result;
                }

                update_post_meta($productId, 'merchant_id', $merchant->ID);
                update_post_meta($productId, '_sap_price_before_tax', $product['price']);
                update_post_meta($productId, '_regular_price', $product['price']);
                update_post_meta($productId, '_price', $product['price']);
                update_post_meta($productId, '_manage_stock', 'yes');
                update_post_meta($productId, '_stock', $product['quantity']);
                continue;
            }

            $productId = wp_insert_post([
                'post_author' => $author,
                'post_content' => '',
                'post_status' => 'draft',
                'post_title' => $product['product_name'],
                'post_type' => 'product',
            ]);
            if ($productId) {
                update_post_meta($productId, 'merchant_id', $merchant->ID);
                update_post_meta($productId, '_sap_price_before_tax', $product['price']);
                update_post_meta($productId, '_regular_price', $product['price']);
                update_post_meta($productId, '_price', $product['price']);
                update_post_meta($productId, '_manage_stock', 'yes');
                update_post_meta($productId, '_stock', $product['quantity']);
                update_post_meta($productId, 'product_sap_code', $product['sap_code']);
                update_post_meta($productId, 'product_rk_code', $product['rk_code']);
                continue;
            }

            $result->messageCode = Message::GENERAL_ERROR;
            $result->message = 'Cập nhật thất bại';
            return $result;
        }

        $result->messageCode = Message::SUCCESS;
        $result->message = 'Cập nhật thành công';
        return $result;
    }
} // end class

// init
$merchantPostType = new MerchantPostType();
