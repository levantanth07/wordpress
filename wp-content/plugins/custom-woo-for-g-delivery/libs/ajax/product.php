<?php
use Abstraction\Object\Result;
use GDelivery\Libs\Helper\Response;
use Abstraction\Object\Message;
use GDelivery\Libs\Config;

class AjaxProduct extends \Abstraction\Core\AAjaxHook {

    private $redis;

    public function __construct()
    {
        parent::__construct();

        // list product by tag
        add_action("wp_ajax_load_product_by_tag", [$this, "loadByTag"]);
        add_action("wp_ajax_nopriv_load_product_by_tag", [$this, "loadByTag"]);

        // list product by group
        add_action("wp_ajax_load_product_by_group", [$this, "loadByGroup"]);
        add_action("wp_ajax_nopriv_load_product_by_group", [$this, "loadByGroup"]);

        // Get data sorted
        add_action("wp_ajax_get_list_group_sorted_by_province_brand", [$this, "getListGroupSorted"]);
        add_action("wp_ajax_get_list_group_sorted_and_product_by_province_brand", [$this, "getListGroupAndProductSorted"]);
        add_action("wp_ajax_nopriv_get_list_group_sorted_and_product_by_province_brand", [$this, "getListGroupAndProductSorted"]);
        add_action("wp_ajax_get_list_suggestion_sorted", [$this, "getListSuggestionSorted"]);
        add_action("wp_ajax_get_list_tag_by_province", [$this, "getListTagByProvince"]);

        // Save and update data sorted
        add_action("wp_ajax_update_sort_group", [$this, "updateSortGroup"]);
        add_action("wp_ajax_save_sort_product_hotdeal", [$this, "saveSortProductHotdeal"]);
        add_action("wp_ajax_save_sort_product_suggestion", [$this, "saveSortProductSuggestion"]);
        add_action("wp_ajax_save_sort_product_on_group", [$this, "saveSortProductOnGroup"]);

        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]
        );
    }

    public function loadByTag()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['tag'])) {
            $tag = $_REQUEST['tag'];

            if (isset($_REQUEST['page'])) {
                $page = $_REQUEST['page'];
            } else {
                $page = 1;
            }

            if (isset($_REQUEST['numberPerPage'])) {
                $numberPerPage = $_REQUEST['numberPerPage'];
            } else {
                $numberPerPage = 8;
            }

            if (isset($_REQUEST['provinceId'])) {
                $provinceId = $_REQUEST['provinceId'];
            } else {
                $provinceId = \GDelivery\Libs\Helper\Helper::getSelectedProvince()->id;
            }
            $products = \GDelivery\Libs\Helper\Product::getProductByTagOnHome($tag, $provinceId, $page, -1)->sorted;

            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = array_slice($products, ($page - 1) * $numberPerPage, $numberPerPage, true);

        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi tham số ajax, vui lòng làm mới trang và thử lại.';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function loadByGroup()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['group'])) {
            $group = $_REQUEST['group'];

            if (isset($_REQUEST['page'])) {
                $page = $_REQUEST['page'];
            } else {
                $page = 1;
            }

            if (isset($_REQUEST['numberPerPage'])) {
                $numberPerPage = $_REQUEST['numberPerPage'];
            } else {
                $numberPerPage = 8;
            }

            if (isset($_REQUEST['provinceId'])) {
                $provinceId = $_REQUEST['provinceId'];
            } else {
                $provinceId = \GDelivery\Libs\Helper\Helper::getSelectedProvince()->id;
            }

            $products = \GDelivery\Libs\Helper\Product::getProductByGroup($group, $provinceId, $page, $numberPerPage);

            if (isset($_REQUEST['keySortOption'])) {
                $keySortOption = $_REQUEST['keySortOption'];
                $products = \GDelivery\Libs\Helper\Product::sortProduct($products, $keySortOption);
            }

            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $products;

        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi tham số ajax, vui lòng làm mới trang và thử lại.';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function getListGroupSorted()
    {
        $res = new Result();
        $provinceId = $_REQUEST['provinceId'];
        $brandId = $_REQUEST['brandId'];
        $key = "icook:province:{$provinceId}:brand:{$brandId}:group";
        $groups = get_terms('product_group');
        $arrGroup = [];

        if (get_option($key)) {
            $listSort = array_flip(unserialize(get_option($key)));
            foreach ($groups as $group) {
                if (isset($listSort[$group->term_id])) {
                    $arrGroup['sorted'][$listSort[$group->term_id]] = [
                        'id' => $group->term_id,
                        'name' => $group->name,
                    ];
                } else {
                    $arrGroup['unSort'][] = [
                        'id' => $group->term_id,
                        'name' => $group->name,
                    ];
                }
            }
        } else {
            foreach ($groups as $group) {
                $arrGroup['unSort'][] = [
                    'id' => $group->term_id,
                    'name' => $group->name,
                ];
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $arrGroup;

        Response::returnJson($res);
        die;
    }

    public function getListGroupAndProductSorted()
    {
        $res = new Result();
        $provinceId = $_REQUEST['provinceId'];
        $brandId = $_REQUEST['brandId'];
        $productGroups = get_terms(
            'product_group',
            [
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => [15]
            ]
        );
        $arrProductInGroups = [];
        $arrGroup = [];
        $category = \GDelivery\Libs\Helper\Category::getCategoryFromProvinceAndBrand($provinceId, $brandId)['category'];

        $query = new WP_Query(
            [
                'post_type' => 'product',
                'post_status'=>'publish',
                'posts_per_page'=> -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'id',
                        'terms'    => $category->id,
                    ),
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => ['topping', 'voucher-coupon'],
                        'operator' => 'NOT IN'
                    ),
                ),
            ]
        );
        $products = $query->posts;

        foreach ($products as $product) {
            $productFactory = new WC_Product_Factory();
            $productDetail = $productFactory->get_product($product->ID);
            $availableVariations = [];
            if ($productDetail->is_type('variable')) {
                $productVariations = \GDelivery\Libs\Helper\Product::getAvailableVariations($productDetail, true);
                $availableVariations = $productVariations['availableVariations'];
            }
            if (!empty($availableVariations)) {
                $product->availableVariations = $availableVariations;
            }

            $term_obj_list = get_the_terms($product->ID, 'product_group');

            if (isset($term_obj_list[0]->term_id)) {
                if ($term_obj_list[0]->term_id) {
                    $productFormatted = \GDelivery\Libs\Helper\Product::formatProductInfo($product);
//                    $productFormatted->ID = $availableVariations[0]->id;
                    $arrProductInGroups[$term_obj_list[0]->term_id]['products'][] = $productFormatted;

                    foreach ($productGroups as $group) {
                        if ($group->term_id == $term_obj_list[0]->term_id) {
                            $arrProductInGroups[$term_obj_list[0]->term_id]['group'] = $group;
                            $arrGroup[$term_obj_list[0]->term_id] = $group;
                            break;
                        }
                    }
                }
            }
        }

        $arrGroupSort = [
            'sorted' => [],
            'unSort' => [],
        ];
        $keySortGroup = "icook:province:{$provinceId}:brand:{$brandId}:group";

        if (get_option($keySortGroup)) {
            $listSort = array_flip(unserialize(get_option($keySortGroup)));
            foreach ($arrGroup as $group) {
                if (isset($listSort[$group->term_id])) {
                    $arrGroupSort['sorted'][$listSort[$group->term_id]] = [
                        'id' => $group->term_id,
                        'name' => $group->name,
                        'slug' => $group->slug,
                    ];
                } else {
                    $arrGroupSort['unSort'][] = [
                        'id' => $group->term_id,
                        'name' => $group->name,
                        'slug' => $group->slug,
                    ];
                }
            }
        } else {
            foreach ($arrGroup as $group) {
                $arrGroupSort['unSort'][] = [
                    'id' => $group->term_id,
                    'name' => $group->name,
                ];
            }
        }

        foreach ($arrProductInGroups as $key=>$value) {
            $products = $value['products'];
            $keyOptionSort = "icook:province:{$provinceId}:brand:{$brandId}:group:{$key}";
            $arrProductInGroups[$key]['products'] = \GDelivery\Libs\Helper\Product::sortProduct($products, $keyOptionSort);
        }
        $result = [
            'listProduct' => $arrProductInGroups,
            'arrGroupSort' => array_merge($arrGroupSort['sorted'], $arrGroupSort['unSort'])
        ];
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $result;

        Response::returnJson($res);
        die;
    }

    public function getListSuggestionSorted()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['tag']) || isset($_REQUEST['provinceId'])) {
            $tag = $_REQUEST['tag'];
            $tagValue = $tag === '' ? 'tat-ca' : $tag;

            if (isset($_REQUEST['page'])) {
                $page = $_REQUEST['page'];
            } else {
                $page = 1;
            }

            if (isset($_REQUEST['numberPerPage'])) {
                $numberPerPage = $_REQUEST['numberPerPage'];
            } else {
                $numberPerPage = 8;
            }

            $provinceId = $_REQUEST['provinceId'];
            $keyCache = "icook:province:{$provinceId}:tag:{$tagValue}";
            $listProduct = $this->redis->get($keyCache);
            if ($tag === '') {
                $key = "icook:province:{$provinceId}:tag:{$tagValue}";
                $arrGroup = [];
                $products = \GDelivery\Libs\Helper\Product::getProductByTag($tag, $provinceId, $page, $numberPerPage);
                if (get_option($key)) {
                    $listSort = array_flip(unserialize(get_option($key)));
                    foreach ($products as $product) {
                        if (isset($listSort[$product->id])) {
                            $arrGroup['sorted'][$listSort[$product->id]] = $product;
                        } else {
                            $arrGroup['unSort'][] = $product;
                        }
                    }
                } else {
                    foreach ($products as $product) {
                        $arrGroup['unSort'][] = $product;
                    }
                }
                $products = $arrGroup;
            } else {
                if (empty($listProduct) || $listProduct == 'null') {
                    $products = [
                        'sorted' => \GDelivery\Libs\Helper\Product::getProductByTag($tag, $provinceId, $page, $numberPerPage)
                    ];
                    if (isset($_REQUEST['keySortOption']) && get_option($_REQUEST['keySortOption'])) {
                        $products = [
                            'sorted' => \GDelivery\Libs\Helper\Product::sortProduct($products['sorted'], $_REQUEST['keySortOption'])
                        ];
                    }
                    $this->redis->set($keyCache, \json_encode($products));
                } else {
                    $products = json_decode($listProduct);
                }
            }

            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $products;

        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi tham số ajax, vui lòng làm mới trang và thử lại.';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function getListTagByProvince()
    {
        $res = new \Abstraction\Object\Result();
        if (isset($_REQUEST['provinceId'])) {
            $provinceId = $_REQUEST['provinceId'];
            $listTag = \GDelivery\Libs\Helper\Product::getListProductTags($provinceId);

            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listTag;

        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Có lỗi tham số ajax, vui lòng làm mới trang và thử lại.';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function updateSortGroup()
    {
        $res = new Result();
        $provinceId = $_REQUEST['provinceId'];
        $brandId = $_REQUEST['brandId'];
        $groupId = $_REQUEST['groupId'];

        if ($provinceId && $brandId) {
            $key = "icook:province:{$provinceId}:brand:{$brandId}:group";
            $groups = get_terms('product_group');
            update_option($key, serialize($groupId));
            $arrGroup = [];
            if (get_option($key)) {
                $listSort = array_flip(unserialize(get_option($key)));
                foreach ($groups as $group) {
                    if (isset($listSort[$group->term_id])) {
                        $arrGroup['sorted'][$listSort[$group->term_id]] = [
                            'id' => $group->term_id,
                            'name' => $group->name,
                        ];
                    } else {
                        $arrGroup['unSort'][] = [
                            'id' => $group->term_id,
                            'name' => $group->name,
                        ];
                    }
                }
            }

            $ipsBe = explode(',', \GDelivery\Libs\Config::ECOMMERCE_BE_IP_SERVERS);
            $query = http_build_query(
                array(
                    'type' => 'tag',
                    'value' => ['product-group']
                )
            );
            try {
                if ($ipsBe) {
                    foreach ($ipsBe as $ip) {
                        if ($ip) {
                            file_get_contents("http://{$ip}/api/v1/services/clear-cache?{$query}");
                        }
                    }
                }
            } catch (\Exception $e) {
                echo 'Clear cache không thành công: ' . $e->getMessage();
            }

            $res->messageCode = Message::SUCCESS;
            $res->message = 'Cập nhật thành công';
            $res->result = $arrGroup;
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Thông tin province, brand hoặc group không đúng';
        }

        Response::returnJson($res);
        die;
    }

    public function saveSortProductHotdeal()
    {
        $res = new Result();
        $productId = $_REQUEST['productId'];
        $provinceId = $_REQUEST['provinceId'] ?: 5;
        $groupSlug = $_REQUEST['groupSlug'];

        if ($productId) {
            $key = $_REQUEST['keySortOption'];
            update_option($key, serialize($productId));
            $products = \GDelivery\Libs\Helper\Product::getProductByGroup($groupSlug, $provinceId, 1, -1);
            $res->result = \GDelivery\Libs\Helper\Product::sortProduct($products, $key);
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Cập nhật thành công';

            $ipsBe = explode(',', \GDelivery\Libs\Config::ECOMMERCE_BE_IP_SERVERS);
            $query = http_build_query(
                array(
                    'type' => 'tag',
                    'value' => ['product-hotdeal-home']
                )
            );
            try {
                if ($ipsBe) {
                    foreach ($ipsBe as $ip) {
                        if ($ip) {
                            file_get_contents("http://{$ip}/api/v1/services/clear-cache?{$query}");
                        }
                    }
                }
            } catch (\Exception $e) {
                echo 'Clear cache không thành công: ' . $e->getMessage();
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Sắp xếp thất bại';
        }

        Response::returnJson($res);
        die;
    }

    public function saveSortProductSuggestion()
    {
        $res = new Result();
        $productIds = $_REQUEST['productId'];
        $provinceId = $_REQUEST['provinceId'];
        $tagValue = $_REQUEST['tag'];

        $keySortOption = $_REQUEST['keySortOption'];
        update_option($keySortOption, serialize($productIds));
        $keyCache = "icook:province:{$provinceId}:tag:{$tagValue}";
        $tag = $tagValue === 'tat-ca' ? '' : $tagValue;
        $options = [
            'productIds' => $productIds,
        ];
        $listUnSort = [];
        $products = \GDelivery\Libs\Helper\Product::getProductByTag($tag, $provinceId, 1, -1, $options);
        if (get_option($keySortOption)) {
            $listProductFilter = [];
            $listSorted = unserialize(get_option($keySortOption));
            foreach ($products as $product) {
                if (is_array($listSorted) && in_array($product->id, $listSorted)) {
                    $listProductFilter[] = $product;
                } else {
                    $listUnSort[] = $product;
                }
            }
            $products = $listProductFilter;
        }

        $products = [
            'sorted' => \GDelivery\Libs\Helper\Product::sortProduct($products, $keySortOption)
        ];
        if ($tagValue === 'tat-ca') {
            $products['unSort'] = $listUnSort;
        }
        $this->redis->set($keyCache, \json_encode($products));

        $res->result = $products;
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Cập nhật thành công';

        $ipsBe = explode(',', \GDelivery\Libs\Config::ECOMMERCE_BE_IP_SERVERS);
        $query = http_build_query(
            array(
                'type' => 'tag',
                'value' => ['product-suggestion-home']
            )
        );
        try {
            if ($ipsBe) {
                foreach ($ipsBe as $ip) {
                    if ($ip) {
                        file_get_contents("http://{$ip}/api/v1/services/clear-cache?{$query}");
                    }
                }
            }
        } catch (\Exception $e) {
            echo 'Clear cache không thành công: ' . $e->getMessage();
        }

        Response::returnJson($res);
        die;
    }

    public function saveSortProductOnGroup()
    {
        $res = new Result();
        $productId = $_REQUEST['productId'];
        $provinceId = $_REQUEST['provinceId'];
        $brandId = $_REQUEST['brandId'];
        $groupSlug = $_REQUEST['groupSlug'];

        if ($productId) {
            $key = $_REQUEST['keySortOption'];
            update_option($key, serialize($productId));
            $products = \GDelivery\Libs\Helper\Product::getProductByGroupAndBrand($provinceId, $brandId, $groupSlug);
            $res->result = \GDelivery\Libs\Helper\Product::sortProduct($products, $key);
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Cập nhật thành công';

            $ipsBe = explode(',', \GDelivery\Libs\Config::ECOMMERCE_BE_IP_SERVERS);
            $query = http_build_query(
                array(
                    'type' => 'tag',
                    'value' => ['product-in-group']
                )
            );
            try {
                if ($ipsBe) {
                    foreach ($ipsBe as $ip) {
                        if ($ip) {
                            file_get_contents("http://{$ip}/api/v1/services/clear-cache?{$query}");
                        }
                    }
                }
            } catch (\Exception $e) {
                echo 'Clear cache không thành công: ' . $e->getMessage();
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Sắp xếp thất bại';
        }

        Response::returnJson($res);
        die;
    }
} //end class

// init class
$productAjax = new AjaxProduct();
