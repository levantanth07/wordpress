<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;

class Category {

    /**
     * @param \WP_Term $category
     *
     * @return \stdClass
     */
    public static function convertToStdClass($category)
    {
        $temp = new \stdClass();

        if ($category) {
            $categoryId = $category->term_id;
            $temp->logo = wp_get_attachment_url(get_term_meta($categoryId, 'thumbnail_id', true));
            $temp->url = get_term_link($category);
            $temp->slug = $category->slug;
            $temp->name = $category->name;
            $temp->shortName = get_field('category_short_name', 'product_cat_' . $categoryId);
            $temp->id = $category->term_id;
        } else {
            $temp->logo = '';
            $temp->url = '';
            $temp->slug = '';
            $temp->name = '';
            $temp->shortName = '';
            $temp->id = 0;
        }

        return $temp;
    }

    public static function getCategories($params = [])
    {
        // get brand in province
        $args = [
            'hide_empty' => true
        ];

        if(isset($params['parentId']) && $params['parentId']) {
            $args['parent'] = $params['parentId'];
        }

        // list brands
        $terms = get_terms('product_cat', $args);

        $returnData = [];
        if ($terms) {
            foreach ($terms as $term) :
                if (!isset($params['withParent']) || ($params['withParent'] == 0)) {
                    if ($term->parent == 0) {
                        $returnData[] = self::convertToStdClass($term);
                    }
                } else {
                    $returnData[] = self::convertToStdClass($term);
                }
            endforeach; // end foreach brands
        }

        wp_reset_query();

        return $returnData;
    }

    public static function getCurrentCategoryFromProductId($productId) {
        $product = wc_get_product($productId);

        $currentCategory = null;
        if ($product) {
            if ($product->is_type('variation')) {
                $productId = $product->get_parent_id();
            }

            $categories = wp_get_post_terms($productId, 'product_cat');
            foreach ($categories as $category) {
                if ($category->parent != 0) {
                    $currentCategory = $category;
                    break;
                }
            }
        }
        return $currentCategory;
    }

    /**
     * Check selected brand
     *
     * @param $res Object result.
     * @param $categoryId
     * @return object
     */
    public static function checkSelectedBrand($res, $categoryId)
    {
        $check = false;
        $category = get_term($categoryId);
        $selectedCategory = Helper::getCurrentCategory();
        $selectedProvince = Helper::getSelectedProvince();
        if ($category) {
            if ($selectedCategory) {
                if ($category->term_id != $selectedCategory->term_id &&
                    WC()->cart->get_cart_contents_count() > 0) {
                    $res->messageCode = Message::SUCCESS_WITHOUT_DATA;
                    $res->message = "Trong giỏ hàng của bạn đang có sản phẩm của {$selectedCategory->name} tại {$selectedProvince->name}, tiếp tục mua?";
                } else {
                    $check = true;
                }
            } else {
                $check = true;
            }

            if ($check) {
                $temp = new \stdClass();
                $temp->id = $category->term_id;
                $temp->name = $category->name;
                $temp->logo = get_field('product_category_logo', 'product_cat_'.$category->term_id);
                $temp->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$category->term_id);
                $temp->url = get_term_link($category->term_id);

                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $temp;
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Rất tiếc, có một lỗi đã xảy ra khi lấy thông tin sản phẩm từ thương hiệu bạn đã chọn. Vui lòng thử lại.';
        }

        return $res;
    }

    /**
     * Get list product by list product id
     *
     * @param array $args Query list brand.
     */
    public static function getListBrandFromListId($args)
    {
        $brands = get_terms($args);
        $arrBrands = [];
        $arrIds = [];
        if (is_array($brands)) {
            foreach ($brands as $brand) {
                $brandId = get_field('product_category_brand_id', 'product_cat_'.$brand->term_id);
                if (in_array($brandId, $arrIds)) {
                    continue;
                }
                $temp = new \stdClass();
                $temp->logo = get_field('product_category_logo', 'product_cat_'.$brand->term_id);
                $temp->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$brand->term_id);
                $temp->url = get_term_link($brand);
                $temp->name = $brand->name;
                $temp->id = (int) $brandId;

                $listCategoryIds = get_terms([
                    'taxonomy' => 'product_cat',
                    'fields' => 'ids',
                    'childless' => true,
                    'meta_query' => [
                        [
                            'key'       => 'product_category_brand_id',
                            'value'     => $brandId,
                            'compare'   => '='
                        ],
                    ],
                ]);
                $query = [
                    'post_type' => 'product',
                    'post_status'=>'publish',
                    'posts_per_page'=> 5,
                    'tax_query'    => [
                        [
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $listCategoryIds,
                            'operator' => 'IN'
                        ]
                    ],
                ];
                $listProducts = Product::getListProduct($query);
                if ($listProducts->messageCode == Message::GENERAL_ERROR) {
                    $temp->listProduct = [];
                } else {
                    $temp->listProduct = $listProducts->result;
                }

                $arrIds[] = $brandId;
                $arrBrands[] = $temp;
            }
        }

        return $arrBrands;
    }

    /**
     * Get list brand by province id
     *
     * @param $provinceId
     * @return array List brand
     */
    public static function getListBrands($provinceId, $params = [])
    {
        $args = [
            'hide_empty' => true
        ];

        $meta_query[] = [
            [
                'key' => 'product_category_province_id',
                'value' => $provinceId,
                'compare' => '='
            ]
        ];

        if (!isset($params['all']) || $params['all'] == '') {
            $meta_query[] = [
                'key' => 'product_category_is_show',
                'value' => 1,
                'compare' => '='
            ];
        }

        $args['meta_query'] = $meta_query;

        $arrBrands = [];
        $brands = get_terms('product_cat', $args);
        if (is_array($brands)) {
            foreach ($brands as $brand) {
                $temp = new \stdClass();
                $temp->logo = get_field('product_category_logo', 'product_cat_'.$brand->term_id);
                $temp->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$brand->term_id);
                $temp->url = get_term_link($brand);
                $temp->name = $brand->name;
                $temp->id = $brand->term_id;
                $arrBrands[] = $temp;
                $temp->brandId = (int) get_field('product_category_brand_id', 'product_cat_'.$brand->term_id);
                $temp->isActive = get_field('product_category_is_show', 'product_cat_'.$brand->term_id);
            }
        }

        return $arrBrands;
    }

    /**
     * Get category info from provinceId and brandId
     *
     * @param $provinceId
     * @param $brandId
     * @return array
     */
    public static function getCategoryFromProvinceAndBrand($provinceId, $brandId)
    {
        $bookingService = new \GDelivery\Libs\BookingService();
        $province = $bookingService->getProvince($provinceId)->result;

        $args = [
            'hide_empty' => true,
            'meta_query' => [
                [
                    'key' => 'product_category_province_id',
                    'value' => $provinceId,
                    'compare' => '='
                ],
                [
                    'key' => 'product_category_brand_id',
                    'value' => $brandId,
                    'compare' => '='
                ]
            ]
        ];
        $terms = get_terms('product_cat', $args);
        $category = [];
        if ($terms) {
            $categoryInfo = $terms[0];
            $category = new \stdClass();
            $category->id = $categoryInfo->term_id;
            $category->name = $categoryInfo->name;
            $category->brandId = (int) $brandId;
            $category->slug = $categoryInfo->slug;
            $category->logo = get_field('product_category_logo', 'product_cat_'.$categoryInfo->term_id);
            $category->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$categoryInfo->term_id);
            $category->minimizeLogo = get_field('product_category_minimize_logo', 'product_cat_'.$categoryInfo->term_id);
            $category->isActive = get_field('product_category_is_show', 'product_cat_'.$categoryInfo->term_id);
            $category->url = get_term_link($categoryInfo);
        }

        return [
            'province' => $province,
            'category' => $category,
            'openTime' => Helper::restaurantOpenTime($categoryInfo->term_id)
        ];
    }

    public static function getCategoryFromProvinceId($provinceId)
    {
        $bookingService = new \GDelivery\Libs\BookingService();
        $province = $bookingService->getProvince($provinceId)->result;

        $args = [
            'hide_empty' => true,
            'meta_query' => [
                [
                    'key' => 'product_category_province_id',
                    'value' => $provinceId,
                    'compare' => '='
                ]
            ]
        ];
        $terms = get_terms('product_cat', $args);
        $category = new \stdClass();
        if ($terms) {
            $categoryInfo = $terms[0];
            $category->id = $categoryInfo->term_id;
            $category->name = $categoryInfo->name;
            $category->slug = $categoryInfo->slug;
            $category->logo = get_field('product_category_logo', 'product_cat_'.$categoryInfo->term_id);
            $category->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$categoryInfo->term_id);
            $category->minimizeLogo = get_field('product_category_minimize_logo', 'product_cat_'.$categoryInfo->term_id);
            $category->isActive = get_field('product_category_is_show', 'product_cat_'.$categoryInfo->term_id);
            $category->url = get_term_link($categoryInfo);
        }

        return [
            'province' => $province,
            'category' => $category,
            'openTime' => Helper::restaurantOpenTime($categoryInfo->term_id)
        ];
    }

    public static function getCategoryById($termId, $getWith = '')
    {
        $terms = get_term_by( 'id', $termId, 'product_cat' );
        $category = new \stdClass();
        if ($terms) {
            $categoryInfo = $terms;
            $category->id = $categoryInfo->term_id;
            $category->name = $categoryInfo->name;
            $category->slug = $categoryInfo->slug;
            $category->logo = get_field('product_category_logo', 'product_cat_'.$categoryInfo->term_id);
            $category->logoBW = get_field('product_category_logo_bw', 'product_cat_'.$categoryInfo->term_id);
            $category->minimizeLogo = get_field('product_category_minimize_logo', 'product_cat_'.$categoryInfo->term_id);
            $category->isActive = get_field('product_category_is_show', 'product_cat_'.$categoryInfo->term_id);
            $category->url = get_term_link($categoryInfo);
            if (str_contains($getWith, 'restaurant')) {
                $getRestaurants = Helper::getRestaurantsInCategory($categoryInfo->term_id);
                if ($getRestaurants->messageCode == Message::SUCCESS) {
                    $category->restaurant = $getRestaurants->result ? $getRestaurants->result[0] : null;
                }
            }
        }

        return $category;
    }

} // end class
