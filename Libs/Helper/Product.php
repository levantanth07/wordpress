<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;
use Predis\Client;
use stdClass;

class Product {

    public const PRODUCT_TIME_CONFIG_REDIS_KEY = 'ecom-cms:product:{productId}:timeConfig';

    /**
     * Convert info product
     *
     * @param \WP_Post|\WC_Product $product
     *
     * @return \stdClass
     */
    private static function initProductStdClass($product)
    {
        $temp = new \stdClass();

        if ($product instanceof \WC_Product) {
            $id = $product->get_id();
            $name = $product->get_name();
        } else {
            $id = $product->ID;
            $name = $product->post_title;
        }

        $thumbnail = get_the_post_thumbnail_url($id, 'shop_catalog') ? get_the_post_thumbnail_url($id, 'shop_catalog') : get_bloginfo('template_url').'/assets/images/no-product-image.png';

        $temp->id = $id;
        $temp->name = $name;
        $temp->thumbnail = $thumbnail;
        $temp->regularPrice = (float) get_field('_regular_price', $temp->id);
        $temp->textRegularPrice = number_format($temp->regularPrice);
        $temp->salePrice = (float) get_field('_sale_price', $temp->id);
        $temp->textSalePrice = number_format($temp->salePrice);
        $temp->unit = get_field('product_unit', $temp->id);
        $temp->textUnit = \GDelivery\Libs\Helper\Helper::productUnitText($temp->id);
        if ($temp->textUnit == 'Chưa xác định') {
            $temp->textUnit = '&nbsp;';
        }
        $quantitative = get_field('product_quantitative', $temp->id);
        $temp->quantitative = $quantitative ?: '&nbsp;';
        $temp->rkCode = get_field('product_rk_code', $temp->id);
        $temp->sapCode = get_field('product_sap_code', $temp->id);

        $productMeta = get_post_meta($id);
        $temp->sapPriceAfterTax = isset($productMeta['_sap_price_after_tax']) ? (float) $productMeta['_sap_price_after_tax'][0] : 0;
        $temp->textSapPriceAfterTax = number_format($temp->sapPriceAfterTax);
        $temp->priceAfterTax = isset($productMeta['_price_after_tax']) ? (float) $productMeta['_price_after_tax'][0] : 0;
        $temp->textPriceAfterTax = number_format($temp->priceAfterTax);

        $getTaxClass = get_field('_tax_class', $id);
        if ($getTaxClass) {
            $temp->taxClass = $getTaxClass;
        } else {
            $temp->taxClass = '';
        }

        // get tax rate
        $temp->taxRateValue = Product::getTaxRateValue($temp->id);

        return $temp;
    }

    /**
     * @param \WP_Post $product
     * @param array $option
     * @return \stdClass
     */
    private static function convertProductToStdClass($product, $option = [])
    {
        $temp = new \stdClass();
        $temp->name = $product->post_title;
        $temp->createdAt = $product->post_date;
        $temp->parentId = null;
        $temp->wooId = $product->ID;

        $oldProductId = $product->ID;
        $currentProductId = $oldProductId;
        $parentId = $oldProductId;

        $productInfo = wc_get_product($currentProductId);
        if ($productInfo->is_type('variable')) {
            $variations = $productInfo->get_available_variations();
            if (isset($variations[0])) {
                $currentProductId = $variations[0]['variation_id'];
                $temp->parentId = $oldProductId;
            }
        }
        if ($productInfo->is_type('variation')) {
            $temp->parentId = $productInfo->get_parent_id();
            $parentId = $temp->parentId;
        }

        $thumbnail = get_the_post_thumbnail_url($parentId, 'shop_catalog');
        if (!$thumbnail) {
            $thumbnail = get_bloginfo('template_url').'/assets/images/no-product-image.png';
        }

        // todo consider to use function initProductStdClass for this part
        $temp->id = $oldProductId;
        $temp->thumbnail = $thumbnail;
        $temp->shortDescription = get_field('product_short_description', $oldProductId);
        $temp->description = get_field('product_description', $oldProductId);
        $temp->regularPrice = (float) get_field('_regular_price',$currentProductId);
        $temp->textRegularPrice = number_format($temp->regularPrice);
        $temp->salePrice = (float) get_field('_sale_price',$currentProductId);
        $temp->textSalePrice = number_format($temp->salePrice);
        $temp->unit = get_field('product_unit', $oldProductId);
        $temp->textUnit = \GDelivery\Libs\Helper\Helper::productUnitText($temp->unit);
        $temp->createdAt = wc_format_datetime( $productInfo->get_date_created() , 'Y-m-d H:i:s');
        $temp->updatedAt = wc_format_datetime( $productInfo->get_date_modified() , 'Y-m-d H:i:s');
        $temp->sold = get_post_meta($parentId, 'total_sales', true );
        self::addMerchantInfo($temp, $parentId);
        self::addInventoryInfo($temp, $productInfo);
        self::addGallery($temp, $productInfo);
        $merchantId = $temp->merchantId ?? $temp->merchant->id ?? null;
        $productMeta = get_post_meta($temp->id);
        $temp->sapPriceBeforeTax = isset($productMeta['_sap_price_before_tax']) ? (float) $productMeta['_sap_price_before_tax'][0] : 0;
        $temp->sapPriceAfterTax = isset($productMeta['_sap_price_after_tax']) ? (float) $productMeta['_sap_price_after_tax'][0] : 0;
        $temp->textSapPriceAfterTax = number_format($temp->sapPriceAfterTax);
        $temp->priceAfterTax = isset($productMeta['_price_after_tax']) ? (float) $productMeta['_price_after_tax'][0] : 0;
        $temp->textPriceAfterTax = number_format($temp->priceAfterTax);

        // get merchant category
        $terms = get_the_terms($parentId, 'merchant-category');
        $parentTerm = [];
        $childTerm = [];
        $merchantCategory = [];
        if (is_array($terms)) {
            foreach ($terms as $term) {
                if ($term->parent) {
                    $childTerm[$term->parent] = $term;
                } else {
                    $parentTerm[] = $term;
                }
            }

            foreach ($parentTerm as $term) {
                $tempCategory = MerchantCategory::convertToStdClass($term);
                $child = [];
                if (isset($childTerm[$term->term_id])) {
                    foreach ($childTerm as $tempChildCat) {
                        $child[] = MerchantCategory::convertToStdClass($tempChildCat);
                    }
                }
                $tempCategory->child = $child;
                $merchantCategory[] = $tempCategory;
            }
        }
        $temp->merchantCategory = $merchantCategory;

        if ($temp->textUnit == 'Chưa xác định') {
            $temp->textUnit = null;
        }
        $quantitative = get_field('product_quantitative', $oldProductId);
        $temp->quantitative = $quantitative ?: null;

        $rkCode = get_field('product_rk_code', $oldProductId) ? get_field('product_rk_code', $oldProductId) : get_field('product_variation_rk_code', $oldProductId);
        $temp->rkCode = $rkCode;

        $temp->limitQuantity = (int) get_field('product_limited_quantity_per_day', $oldProductId);

        $sapCode = get_field('product_sap_code', $oldProductId) ? get_field('product_sap_code', $oldProductId) : get_field('product_variation_sap_code', $oldProductId);
        $temp->sapCode = $sapCode;
        $temp->minimumPrice = (float) get_field('product_minimum_cart_total_price', $parentId);

        // get tax rate
        $getTaxClass = get_field('_tax_class', $parentId);
        if ($getTaxClass) {
            $temp->taxClass = $getTaxClass;
        } else {
            $temp->taxClass = '';
        }
        $temp->taxRateValue = Product::getTaxRateValue($parentId);

        if (isset($option['getWith'])) {
            $getWith = $option['getWith'];
            if (strpos($getWith, 'variationProduct') !== false) {
                $productFactory = new \WC_Product_Factory();
                $productDetail = $productFactory->get_product($oldProductId);
                $availableVariations = [];
                if ($productDetail->is_type('variable')) {
                    $productVariations = self::getAvailableVariations($productDetail, true);
                    $availableVariations = $productVariations['availableVariations'];
                }

                if ($availableVariations) {
                    $temp->availableVariations = $availableVariations;
                } else {
                    $temp->availableVariations = [];
                }
            } else {
                $temp->availableVariations = [];
            }

            $toppingProductIds = get_post_meta($parentId, '_topping_product_ids', true);
            // topping
            if (strpos($getWith, 'topping') !== false) {
                $toppings = [];
                if ($toppingProductIds) {
                    foreach ($toppingProductIds as $oneId) {
                        // cross product
                        $crossProduct = wc_get_product($oneId);
                        if ($crossProduct) {
                            if ($crossProduct->is_type('topping')) {
                                $toppings[] = self::initProductStdClass($crossProduct);
                            }
                        }
                    }
                }

                $toppingObj = new \stdClass();
                $toppingObj->type = 'topping';
                $toppingObj->name = 'Topping'; // get from topping title
                $toppingObj->data = $toppings;
                $temp->topping = $toppingObj;
            } else {
                $temp->topping = null;
            }

            // modifier
            if (strpos($getWith, 'modifier') !== false) {
                $modifiers = [];

                $listModifiers = get_the_terms($parentId, 'product_modifier_category');
                $filteredParents = array_filter(is_array($listModifiers) ? $listModifiers : [], function($modifier) {
                    return $modifier->parent == 0;
                });
                
                if ($filteredParents) {
                    foreach ($filteredParents as $parentModifier) {
                        $tempOneGroup = new \stdClass();
                        $tempOneGroup->id = $parentModifier->term_id;
                        $tempOneGroup->name = $parentModifier->name;
                        $tempOneGroup->data = [];
                        $childIds = get_term_children($parentModifier->term_id, 'product_modifier_category');
                        if (!empty($childIds)) {
                            $childTerms = get_terms(array(
                                'taxonomy' => 'product_modifier_category',
                                'hide_empty' => false,
                                'include' => $childIds,
                            ));
                            foreach ($childTerms as $one) {
                                $mdf = new \stdClass();
                                $mdf->id = $one->term_id;
                                $mdf->name = $one->name;
                                $mdf->order = (int) (get_term_meta($one->term_id, 'modifier_order', true) ?: 999);
                                $tempOneGroup->data[] = $mdf;
                            }
                            usort($tempOneGroup->data, function($a, $b){
                                if ($a->order == $b->order) {
                                    return 0;
                                }
                                return ($a->order < $b->order) ? -1 : 1;
                            });
                        }
                        $modifiers[] = $tempOneGroup;
                    }
                }

                $temp->modifier = $modifiers;
            } else {
                $temp->modifier = [];
            }

            if (strpos($getWith, 'tags') !== false) {
                $getTags = get_the_terms( $temp->id, 'product_tag' );
                $tags = [];
                if ($getTags) {
                    foreach ($getTags as $tag) {
                        $tagObj = new \stdClass();
                        $tagObj->id = $tag->id;
                        $tagObj->name = $tag->name;
                        $tagObj->slug = $tag->slug;
                        $tags[] = $tagObj;
                    }
                }
                $temp->tags = $tags;
            } else {
                $temp->tags = [];
            }

            if (strpos($getWith, 'combo') !== false) {
                list($comboPriceTaxType, $comboChildProductType, $comboGroups) = self::getComboInfo($parentId);
                $comboObj = new \stdClass();
                $comboObj->priceTaxType = $comboPriceTaxType;
                $comboObj->childProductType = $comboChildProductType;
                $comboObj->groups = $comboGroups;
                $temp->combo = $comboObj;
            } else {
                $temp->combo = null;
            }

        } else {
            $temp->availableVariations = [];
            $temp->group = [];
            $temp->topping = null;
        }

        return $temp;
    }

    private static function getComboInfo($parentId)
    {
        $comboPriceTaxType = (int) get_post_meta($parentId, 'combo_price_tax_type', true);
        $comboChildProductType = (int) get_post_meta($parentId, 'combo_child_product_type', true);
        $comboGroups = get_post_meta($parentId, 'combo_group_items', true);
        if (empty($comboGroups)) {
            return [$comboPriceTaxType, $comboChildProductType, []];
        }
        $groups = [];
        foreach ($comboGroups as $comboGroupItem) {
            $groupItem = new \stdClass();
            $groupItem->name = $comboGroupItem['group_name'];
            $groupItem->maxItem = isset($comboGroupItem['group_max_item']) ? intval($comboGroupItem['group_max_item']) : null;
            $groupItem->minItem = isset($comboGroupItem['group_min_item']) ? intval($comboGroupItem['group_min_item']) : null;
            $groupItem->showPriceType = isset($comboGroupItem['group_show_price_type']) ? intval($comboGroupItem['group_show_price_type']) : null;
            $groupItem->productItems = [];
            foreach ($comboGroupItem['product_items'] as $product) {
                $arr = [
                    'post_type' => ['product', 'product_variation'],
                    'post_status' => array('publish', 'draft'),
                    'posts_per_page' => 1,
                    'p' => intval($product['id']),
                ];
                $query = new \WP_Query($arr);
                if (!$query->have_posts()) {
                    continue;
                }
                $productInfo = $query->posts[0];
                $productItem = self::convertProductToStdClass(
                    $productInfo, 
                    [
                        'getWith' => 'variationProduct,topping,modifier,tags'
                    ]
                );
                $productItem->quantity = intval($product['quantity']);
                $productItem->isFixed = $product['is_fixed'];
                $productItem->isBasePrice = isset($product['is_base_price']) ? intval($product['is_base_price']) : 0;
                unset($productItem->merchantCategory);
                unset($productItem->merchant);
                $groupItem->productItems[] = $productItem;
            }
            $groups[] = $groupItem;
        }
        return [$comboPriceTaxType, $comboChildProductType, $groups];
    }

    private static function addMerchantInfo($productObject, $parentId)
    {
        $merchant = get_field('merchant_id', $parentId);
        if ($merchant) {
            if (is_object($merchant)) {
                $merchantId = $merchant->ID;
            } else {
                $merchantId = $merchant;
            }
            $productObject->merchantId = $merchantId;
            $productObject->merchant = Helper::getMerchantInfo(get_post($merchantId));
        } else {
            $productObject->merchantId = null;
            $productObject->merchant = null;
        }
    }

    public static function addInventoryInfo(&$productObject, $productInfo)
    {
        $productObject->inventory = new stdClass();
        $productObject->inventory->sku = $productInfo->get_sku();
        $productObject->inventory->stockQuantity = $productInfo->get_stock_quantity();
    }

    public static function addGallery(&$productObject, $productInfo)
    {
        $productObject->gallery = [];
        $attachmentIds = $productInfo->get_gallery_image_ids();
        if (!empty($attachmentIds)) {
            foreach ($attachmentIds as $attachmentId) {
                $productObject->gallery[] = wp_get_attachment_url($attachmentId) ?: '';
            }
        }
    }

    public static function buildTitle($string, $yourDesiredWidth) {
        $parts = preg_split('/([\s\n\r]+)/u', $string, null, PREG_SPLIT_DELIM_CAPTURE);
        $parts_count = count($parts);

        $length = 0;
        $lastPart = 0;
        for (; $lastPart < $parts_count; ++$lastPart) {
            $length += strlen($parts[$lastPart]);
            if ($length > $yourDesiredWidth) { break; }
        }

        $returnString = trim(implode(array_slice($parts, 0, $lastPart)));
        if (strlen($string) > $yourDesiredWidth) {
            return $returnString.'....';
        } else {
            return $returnString;
        }
    }

    public static function validateMinimumAddToCart($productId, $quantity = 1, $cart = null)
    {
        if ($cart) {
            $cartTotal = \GDelivery\Libs\Helper\Helper::calculateCartTotals($cart);
        } else {
            $cartTotal = \GDelivery\Libs\Helper\Helper::calculateCartTotals();
        }

        // existing in cart
        $existingInCart = false;
        foreach (WC()->cart->get_cart() as $itemKey => $item) {
            if ($item['product_id'] == $productId) {
                $existingInCart = true;
            }
        }

        $minimumPrice = (float) get_field('product_minimum_cart_total_price', $productId);

        $res = new Result();
        if ($minimumPrice) {
            $price = (float) get_field('_price', $productId);
            if ($existingInCart) {
                $selfPrice = $price * $quantity;
            } else {
                $selfPrice = 0;
            }

            if (isset($cartTotal->shipping) && $cartTotal->shipping) {
                $baseTotalPrice = $cartTotal->totalPrice - $cartTotal->shipping->price;
            } else {
                $baseTotalPrice = $cartTotal->totalPrice;
            }

            if (($baseTotalPrice - $selfPrice) >= $minimumPrice) {
                $res->messageCode = Message::SUCCESS;
                $res->result = $minimumPrice;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->result = $minimumPrice;
            }
        } else {
            $res->messageCode = Message::SUCCESS;
            $res->result = $minimumPrice;
        }

        return $res;
    }

    public static function validateQuantityToAddToCart($productId, $quantity = 1, $user = null, $cart = null)
    {
        $res = new Result();
        if (!$cart) {
            $cart = WC()->cart;
        }

        if (!$user) {
            $user = wp_get_current_user();
        }

        // get product info
        $product = wc_get_product($productId);
        $isVariation = $product->is_type('variation');

        $limitQuantity = (int) get_field('product_limited_quantity_per_day', $productId);
        // limit is 0; it means unlimited
        if ($limitQuantity == 0) {
            $limitQuantity = 999;
        }
        if ($quantity <= $limitQuantity) {
            // check limit with order
            $args = [
                'customer_id' => $user->ID,
                'date_created' => date_i18n('Y-m-d')
            ];

            $getOrders = wc_get_orders($args);
            if ($getOrders) {
                $countCurrentQuantity = 0;
                foreach ($getOrders as $order) {
                    if ($countCurrentQuantity <= $limitQuantity) {
                        foreach ($order->get_items() as $item) {
                            $itemData = $item->get_data();
                            if ($isVariation) {
                                $itemProductId = $itemData['variation_id'];
                            } else {
                                $itemProductId = $itemData['product_id'];
                            }
                            if ($itemProductId == $productId) {
                                $countCurrentQuantity += $item->get_quantity();
                            }
                        }
                    } else {
                        break;
                    }
                }

                if (($countCurrentQuantity + $quantity) <= $limitQuantity) {
                    // free to add to cart
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Hợp lệ để thêm vào giỏ hàng';
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = "Chỉ được mua tối đa {$limitQuantity} sản phẩm này mỗi ngày.";
                }
            } else {
                // free to add to cart
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Hợp lệ để thêm vào giỏ hàng';
            }
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "Chỉ được mua tối đa {$limitQuantity} sản phẩm này.";
        }

        return $res;
    }

    /**
     * Get tax rate value
     *
     * @param $productId
     * @return float|int Return tax rate value
     */
    public static function getTaxRateValue($productId)
    {
        $getTaxClass = get_field('_tax_class', $productId);
        $taxRates = \WC_Tax::get_rates_for_tax_class(Config::TAX_CLASS_STANDARD);

        if ($getTaxClass) {
            $taxRates = \WC_Tax::get_rates_for_tax_class($getTaxClass);
        }
        $rates = array_shift($taxRates);

        return ($rates && $rates->tax_rate) ? ((float) $rates->tax_rate)/100 : 0;
    }

    public static function getProductByTag($tag, $provinceId = 0, $page = 1, $numberPerPage = 8, $options = [])
    {
        $params = [
            'post_type' => 'product',
            'post_status'=>'publish',
            'posts_per_page'=> $numberPerPage,
            'paged' => $page,
        ];

        if ($tag) {
            $params['tax_query'] = [
                [
                    'taxonomy' => 'product_tag',
                    'field'    => 'slug',
                    'terms'    => $tag,
                ]
            ];
        }
        $params['tax_query'][] = array(
            'taxonomy' => 'product_type',
            'field'    => 'slug',
            'terms'    => ['topping', 'voucher-coupon'],
            'operator' => 'NOT IN'
        );

        // get list brand in province
        if ($provinceId) {
            $brands = Category::getListInProvince($provinceId);
            $brandIds = [];
            foreach ($brands as $brand) {
                $brandIds[] = $brand->id;
            }

            $params['tax_query'][] = [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $brandIds,
                'operator' => 'IN'
            ];
        }

        $query = new \WP_Query($params);
        $returnData = [];
        if ($query->posts) {
            $products = $query->posts;
            $productGroups = get_terms(
                'product_group',
                [
                    'hide_empty' => 0,
                    'parent' => 0,
                    'exclude' => [15]
                ]
            );
            foreach ($products as $product) {
                $returnData[] = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,groupProduct,topping,modifier,tags',
                    'productGroups' => $productGroups
                ]);
            }
        }

        wp_reset_query();

        return $returnData;
    }

    public static function getProductByTagOnHome($tag, $provinceId = 0, $page = 1, $numberPerPage = 8, $options = [])
    {
        $redis = new Client([
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]);
        $tagValue = $tag === '' ? 'tat-ca' : $tag;
        $keyCache = "icook:province:{$provinceId}:tag:{$tagValue}";
        $listProduct = $redis->get($keyCache);
        if (empty($listProduct) || $listProduct == 'null') {
            $params = [
                'post_type' => 'product',
                'post_status'=>'publish',
                'posts_per_page'=> $numberPerPage,
                'paged' => $page,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => ['topping', 'voucher-coupon'],
                        'operator' => 'NOT IN'
                    )
                ),
            ];

            if ($tag) {
                $params['tax_query'][] = array(
                    'taxonomy' => 'product_tag',
                    'field'    => 'slug',
                    'terms'    => $tag,
                );
            }

            // get list brand in province
            if ($provinceId) {
                $brands = Category::getListInProvince($provinceId);
                $brandIds = [];
                foreach ($brands as $brand) {
                    $brandIds[] = $brand->id;
                }

                $params['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $brandIds,
                    'operator' => 'IN'
                );
            }

            $getWith = 'variationProduct,groupProduct,tags,modifier,topping';

            $query = new \WP_Query($params);
            $products = $query->posts;
            $returnData = [];
            $listSorted = unserialize(get_option($keyCache));
            $productGroups = get_terms(
                'product_group',
                [
                    'hide_empty' => 0,
                    'parent' => 0,
                    'exclude' => [15]
                ]
            );
            if ($tag === '') {
                foreach ($products as $product) {
                    if (is_array($listSorted) && in_array($product->ID, $listSorted)) {
                        $returnData[] = self::convertProductToStdClass(
                            $product,
                            [
                                'getWith' => $getWith,
                                'productGroups' => $productGroups,
                            ]
                        );
                    }
                }
            } else {
                foreach ($products as $product) {
                    $returnData[] = self::convertProductToStdClass(
                        $product,
                        [
                            'getWith' => $getWith,
                            'productGroups' => $productGroups,
                        ]
                    );
                }
            }

            $dataProduct = new \stdClass();
            $dataProduct->sorted = \GDelivery\Libs\Helper\Product::sortProduct($returnData, $keyCache);
            $products = $dataProduct;
            $redis->set($keyCache, \json_encode($products));
            wp_reset_query();
        } else {
            $products = json_decode($listProduct);
        }

        return $products;
    }

    public static function getProductByGroup($groupSlug, $provinceId = 0, $page = 1, $numberPerPage = 8, $option = [])
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => Config::REDIS_HOST,
            'port'   => Config::REDIS_PORT,
            'password' => Config::REDIS_PASS
        ]);
        $keyCache = "icook:province:{$provinceId}:home-hotdeal:sort-product";
        $listProduct = $redis->get($keyCache);
        if (empty($listProduct) || $listProduct == 'null') {
            $params = [
                'post_type' => 'product',
                'post_status'=>'publish',
                'posts_per_page'=> $numberPerPage,
                'paged' => $page,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_group',
                        'field'    => 'slug',
                        'terms'    => $groupSlug,
                    ),
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => ['topping', 'voucher-coupon'],
                        'operator' => 'NOT IN'
                    )
                ),
            ];

            $getWith = 'variationProduct,groupProduct,tags,modifier,topping';

            // get list brand in province
            if ($provinceId) {
                $brands = Category::getListInProvince($provinceId);
                $brandIds = [];
                foreach ($brands as $brand) {
                    $brandIds[] = $brand->id;
                }
                $params['tax_query'][] = [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $brandIds,
                    'operator' => 'IN'
                ];
            }

            $query = new \WP_Query($params);

            $returnData = [];
            if ($query->posts) {
                $productGroups = get_terms(
                    'product_group',
                    [
                        'hide_empty' => 0,
                        'parent' => 0,
                        'exclude' => [15]
                    ]
                );
                foreach ($query->posts as $post) {
                    $returnData[] = self::convertProductToStdClass(
                        $post,
                        [
                            'getWith' => $getWith,
                            'productGroups' => $productGroups
                        ]
                    );
                }
            }

            $products = \GDelivery\Libs\Helper\Product::sortProduct($returnData, $keyCache);
            $redis->set($keyCache, \json_encode($products));
            wp_reset_query();
        } else {
            $products = json_decode($listProduct);
        }

        return $products;
    }

    public static function getProductByGroupAndBrand($provinceId, $brandId, $groupSlug, $options = [])
    {
        $numberPerPage = $options['numberPerPage'] ?? -1;
        $page = $options['page'] ?? 1;
        $category = Category::getCategoryFromProvinceAndBrand($provinceId, $brandId)['category'];
        $params = [
            'post_type' => 'product',
            'post_status'=>'publish',
            'posts_per_page'=> $numberPerPage,
            'paged' => $page,
            'tax_query' => [
                [
                    'taxonomy' => 'product_group',
                    'field'    => 'slug',
                    'terms'    => $groupSlug,
                ],
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category->id,
                ],
                [
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => ['topping', 'voucher-coupon'],
                    'operator' => 'NOT IN'
                ]
            ],
        ];

        $query = new \WP_Query($params);

        $returnData = [];
        if ($query->posts) {
            $productGroups = get_terms(
                'product_group',
                [
                    'hide_empty' => 0,
                    'parent' => 0,
                    'exclude' => [15]
                ]
            );
            $getWith = 'variationProduct,groupProduct,tags,modifier,topping';
            foreach ($query->posts as $post) {
                $returnData[] = self::convertProductToStdClass($post, [
                    'getWith' => $getWith,
                    'productGroups' => $productGroups
                ]);
            }
        }

        wp_reset_query();

        return $returnData;
        // get product by
    }

    public static function getListProductTags($provinceId)
    {
        $getTerms = get_terms(
            [
                'taxonomy' => 'product_tag',
//                'hide_empty' => false,
                'meta_query' => [
                    [
                        'key'       => 'product_tag_province_id',
                        'value'     => serialize((string) $provinceId),
                        'compare'   => 'like'
                    ]
                ]
            ]
        );

        $returnData = [];
        if ($getTerms) {
            /** @var \WP_Term $term */
            foreach ($getTerms as $term) {
                $temp = new \stdClass();

                $temp->id = $term->term_id;
                $temp->name = $term->name;
                $temp->slug = $term->slug;

                $returnData[] = $temp;
            }
        }

        return $returnData;
    }

    public static function getListProductGroups($provinceId, $brandId)
    {
        // get category id
        $category = Category::getCategoryFromProvinceAndBrand($provinceId, $brandId);

        $res = new Result();
        if ($category) {

        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Category không tồn tại.';
        }

        return $res;
    }

    /**
     * @param $product
     * @return array
     */
    public static function getAvailableVariations($product, $available = false)
    {
        $availableVariations = [];
        $attrNames = [];
        if ($product->is_type('variable')) {
            foreach ($product->get_available_variations() as $variation) {
                if (($available && $variation['variation_is_active'] && $variation['is_in_stock']) || !$available) {
                    $productVariation = new \WC_Product_Variation($variation['variation_id']);

                    // Loop through the product attributes for this variation
                    $variations = [];
                    foreach ($variation['attributes'] as $attribute => $slug) {
                        // Get the taxonomy slug
                        $attrSlug = str_replace('attribute_', '', $attribute);

                        // Get the attribute label name
                        $attrName = wc_attribute_label($attrSlug);

                        // Display attribute labe name
                        $attrValue = get_term_by('slug', $slug, $attrSlug) ? get_term_by('slug', $slug, $attrSlug)->name : '';
                        $variations[] = [
                            'attrName' => $attrName,
                            'attrValue' => $attrValue,
                        ];
                        $attrNames[] = $attrName;
                    }
                    $thumb = get_the_post_thumbnail_url($variation['variation_id'], 'medium') ? get_the_post_thumbnail_url($variation['variation_id'], 'medium') : get_the_post_thumbnail_url($product->get_id(), 'medium');
                    $productVariationMeta = get_post_meta($variation['variation_id']);
                    $sapPriceAfterTax = isset($productVariationMeta['_sap_price_after_tax']) ? (float) $productVariationMeta['_sap_price_after_tax'][0] : 0;
                    $priceAfterTax = isset($productVariationMeta['_price_after_tax']) ? (float) $productVariationMeta['_price_after_tax'][0] : 0;
                    $availableVariations[] = [
                        'price' => (float) $productVariation->get_price(),
                        'salePrice' => (float) $productVariation->get_sale_price(),
                        'regularPrice' => (float) $productVariation->get_regular_price(),
                        'textPrice' => number_format((float) $productVariation->get_price()) . '₫',
                        'textSalePrice' => number_format((float) $productVariation->get_sale_price()) . '₫',
                        'textRegularPrice' => number_format((float) $productVariation->get_regular_price()) . '₫',
                        'sapPriceAfterTax' => $sapPriceAfterTax,
                        'textSapPriceAfterTax' => number_format($sapPriceAfterTax) . '₫',
                        'priceAfterTax' => $priceAfterTax,
                        'textPriceAfterTax' => number_format($priceAfterTax) . '₫',
                        'active' => $variation['variation_is_active'],
                        'image' => [
                            'src' => $variation['image']['src'] ?? '',
                            'title' => $variation['image']['title'] ?? ''
                        ],
                        'variationId' => $variation['variation_id'],
                        'isInStock' => $variation['is_in_stock'],
                        'variations' => $variations,
                        'thumbnail' => $thumb ?: get_bloginfo('template_url').'/assets/images/no-product-image.png'
                    ];
                }
            }
        }
        return [
            'availableVariations' => $availableVariations,
            'attrNames' => $attrNames
        ];
    }

    /**
     * @param $product
     * @return string
     */
    public static function getAttributes($product)
    {
        $variationNames = '';
        if($product->is_type('variation')){

            $attributes = $product->get_attributes();
            if( $attributes ){
                $i = 0;
                foreach ($attributes as $key => $value) {
                    $attrName = wc_attribute_label($key);
                    $attrValue = get_term_by('slug', $value, $key)->name;
                    $variationNames .= ' '.$attrName. ' ' . $attrValue . ',';
                    $i++;
                }
            }
        }
        return rtrim($variationNames, ", ");
    }

    public static function searchProductByRkCode($rkCode, $categoryId = null)
    {
        $params = [
            'post_type' => 'product',
            'post_status'=>'publish',
            'meta_query' => [
                [
                    'key' => 'product_rk_code',
                    'value'    => $rkCode,
                ],
            ],
        ];

        if ($categoryId) {
            $params['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'id',
                    'terms'    => $categoryId,
                ],
            ];
        }
        $query = new \WP_Query($params);

        $returnData = [];
        if ($query->posts) {
            $productGroups = get_terms(
                'product_group',
                [
                    'hide_empty' => 0,
                    'parent' => 0,
                    'exclude' => [15]
                ]
            );
            $getWith = 'variationProduct,groupProduct,tags,modifier,topping';
            foreach ($query->posts as $post) {
                $returnData[] = self::convertProductToStdClass($post, [
                    'getWith' => $getWith,
                    'productGroups' => $productGroups
                ]);
            }
        }

        wp_reset_query();

        return $returnData;
    }

    public static function getProductInfo($productId)
    {
        $product = get_post($productId);

        if ($product) {
            $productGroups = get_terms(
                'product_group',
                [
                    'hide_empty' => 0,
                    'parent' => 0,
                    'exclude' => [15]
                ]
            );
            $getWith = 'variationProduct,groupProduct,tags,modifier,topping';
            return self::convertProductToStdClass($product, [
                'getWith' => $getWith,
                'productGroups' => $productGroups
            ]);
        } else {
            return null;
        }
    }

    /**
     * Check product of icook hcm but not book in restaurant icook
     *
     * @param $productId
     * @param mixed $restaurant Is restaurant book of order
     * @return bool
     */
    public static function isProductIcookHCM($productId, $restaurant)
    {
        $listCategoryOfRestaurant = get_the_terms($restaurant->id, 'product_cat');
        $isRestaurantIcook = false;
        foreach ($listCategoryOfRestaurant as $item) {
            $brandId = get_field('product_category_brand_id', 'product_cat_' . $item->term_id);
            if ($brandId == $restaurant->restaurant->brandId && $item->slug === 'hcm-icook') {
                $isRestaurantIcook = true;
                break;
            }
        }

        $category = Category::getCurrentCategoryFromProductId($productId);
        $province = Province::getProvinceFromProductId($productId);
        if ($category->slug === 'hcm-icook' && $province->slug === 'ho-chi-minh' && !$isRestaurantIcook) {
            return true;
        }

        return false;
    }

    /**
     * Get list product by categoryID, group, tag
     *
     * @param array $query query list product.
     */
    public static function getListProduct($args, $options = []) {
        $res = new Result();

        $query = new \WP_Query($args);

        $getWith = $args['getWith'] ?? 'variationProduct,groupProduct,tags,modifier,topping';
        $productGroups = get_terms(
            'product_group',
            [
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => [15]
            ]
        );

        $listProducts = [];
        foreach ($query->posts as $product) {
            $productInfo = self::convertProductToStdClass($product, [
                'getWith' => $getWith,
                'productGroups' => $productGroups
            ]);
            if (isset($options['productType']) && $options['productType'] == 'voucher-coupon') {
                $productInfo->campaignCode = get_post_meta($product->ID, '_evoucher_campaign_id', true);
                $startDate = get_post_meta($product->ID, '_campaign_start_date', true);
                $endDate = get_post_meta($product->ID, '_campaign_end_date', true);
                $productInfo->startDate = $startDate ?: '';
                $productInfo->endDate = $endDate ?: '';
            }

            $listProducts[] = $productInfo;
        }

        $res->messageCode = Message::SUCCESS;
        if ($listProducts) {
            $res->result = $listProducts;
            $res->numberOfResult = count($listProducts);
            $res->total = $query->found_posts;
            $res->lastPage = $query->max_num_pages;
            $res->message = 'Thành công';
        } else {
            $res->message = 'Brand này không có sản phẩm';
        }

        return $res;
    }

    /**
     * Get detail of product by ID
     *
     * @param int $id Product ID.
     * @param array $options
     */
    public static function getDetailProduct($id, $options = []) {
        $res = new Result();
        if ($id) {
            $arr = [
                'post_type' => ['product', 'product_variation'],
                'post_status' => 'publish',
                'p' => $id,
            ];
            $query = new \WP_Query($arr);

            if ($query->posts) {
                $productGroups = get_terms(
                    'product_group',
                    [
                        'hide_empty' => 0,
                        'parent' => 0,
                        'exclude' => [15]
                    ]
                );

                $product = $query->posts[0];

                $productInfo = self::convertProductToStdClass(
                    $product,
                    [
                        'getWith' => 'variationProduct,topping,modifier,tags,combo',
                        'productGroups' => $productGroups
                    ]
                );
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $productInfo;
            } else {
                $res->messageCode = Message::NOT_FOUND;
                $res->message = 'Sản phẩm không tồn tại';
            }
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Sản phẩm không tồn tại';
        }
        return $res;
    }

    /**
     * Get detail of voucher by ID
     *
     * @param int $id Voucher ID.
     */
    public static function getDetailVoucher($id) {
        $res = new Result();
        $query = new \WP_Query(
            [
                'post_type' => ['product'],
                'post_status'=>'publish',
                'p' => $id,
                'tax_query' => [
                    [
                        'taxonomy' => 'product_type',
                        'field'    => 'slug',
                        'terms'    => 'voucher-coupon',
                        'operator' => 'IN'
                    ]
                ]
            ]
        );

        if ($query->posts) {
            $product = $query->posts[0];

            $productInfo = self::convertProductToStdClass($product);
            $productInfo->campaignCode = get_post_meta($product->ID, '_evoucher_campaign_id', true);
            $startDate = get_post_meta($product->ID, '_campaign_start_date', true);
            $endDate = get_post_meta($product->ID, '_campaign_end_date', true);
            $productInfo->startDate = $startDate ?: '';
            $productInfo->endDate = $endDate ?: '';
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $productInfo;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Sản phẩm không tồn tại';
        }

        return $res;
    }

    /**
     * Get list upsell of product
     *
     * @param $productId
     * @return Result List product
     */
    public static function getListProductUpSells($productId)
    {
        $res = new Result();
        $productFactory = new \WC_Product_Factory();
        $product = $productFactory->get_product($productId);

        if (!$product) {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Product id truyền lên không tồn tại';

            return $res;
        }

        $productUpSellIds = $product->get_upsell_ids();
        $listProductUpSells = [];
        foreach ($productUpSellIds as $productUpSellId) {
            $productUpSell = $productFactory->get_product($productUpSellId);
            if ($productUpSell && $productUpSell->get_status() == 'publish') {
                $cartUpSellItem = Helper::productInCart($productUpSellId);
                $listProductUpSells[] = array(
                    'id' => $productUpSell->get_ID(),
                    'name' => $productUpSell->get_title(),
                    'price' => (float) $productUpSell->get_price(),
                    'textPrice' => number_format((float) $productUpSell->get_price()).'₫',
                    'regularPrice' => (float) $productUpSell->get_regular_price(),
                    'textRegularPrice' => number_format((float) $productUpSell->get_regular_price()).'₫',
                    'salePrice' => (float) $productUpSell->get_sale_price(),
                    'textSalePrice' => number_format((float)$productUpSell->get_sale_price()).'₫',
                    'thumbnail' => get_the_post_thumbnail_url($productUpSellId, 'medium') ? get_the_post_thumbnail_url($productUpSellId, 'medium') : '',
                    'quantity' => $cartUpSellItem ? $cartUpSellItem['quantity'] : 0,
                );
            }
        }

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $listProductUpSells;

        return $res;
    }

    /**
     * Get list product by list product id
     *
     * @param array $query Query list product.
     *
     * @return Result
     */
    public static function getListProductFromListId($args) {
        $res = new Result();
        $query = new \WP_Query($args);

        $productGroups = get_terms(
            'product_group',
            [
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => [15]
            ]
        );

        $listProducts = [];
        foreach ($query->posts as $product) {
            $listProducts[] = self::convertProductToStdClass($product, [
                'getWith' => 'variationProduct,groupProduct,topping,modifier,tags,productBrand,productType',
                'productGroups' => $productGroups
            ]);
        }

        if ($listProducts) {
            $res->result = [
                'data' => $listProducts,
                'total' => $query->found_posts,
                'currentPage' => (int) $args['paged'],
                'lastPage' => $query->max_num_pages,
                'perPage' => (int) $args['posts_per_page'],
            ];
            $res->numberOfResult = count($listProducts);
            $res->message = 'Thành công';
            $res->messageCode = Message::SUCCESS;
        } else {
            $res->message = 'Không có sản phẩm nào!';
            $res->messageCode = Message::GENERAL_ERROR;
        }

        return $res;
    }

    public static function sortGroups()
    {
        //
    }

    public static function formatProductInfo($product)
    {
        $productGroups = get_terms(
            'product_group',
            [
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => [15]
            ]
        );
        $getWith = 'variationProduct,groupProduct,tags,modifier,topping';

        return self::convertProductToStdClass($product, [
            'getWith' => $getWith,
            'productGroups' => $productGroups
        ]);
    }

    public static function sortProduct($products, $keySortOption)
    {
        if (get_option($keySortOption)) {
            $listSort = array_flip(unserialize(get_option($keySortOption)));
            $arrGroup = [
                'sorted' => [],
                'unSort' => []
            ];
            foreach ($products as $product) {
                $productId = $product->id;
                if (isset($listSort[$productId])) {
                    $arrGroup['sorted'][$listSort[$productId]] = $product;
                } else {
                    $arrGroup['unSort'][] = $product;
                }
            }
            ksort($arrGroup['sorted']);

            return array_merge($arrGroup['sorted'], $arrGroup['unSort']);
        }

        return $products;
    }

    public static function getListSortedProductOnGroup($provinceId, $groupSlug, $options = [])
    {
        $res = new Result();
        $productGroups = get_terms(
            'product_group',
            [
                'hide_empty' => 0,
                'parent' => 0,
                'exclude' => [15]
            ]
        );
        $getWith = 'variationProduct,groupProduct,tags,modifier,topping';
        $page = isset($options['page']) ? (int) $options['page'] : 1;
        $perPage = isset($options['perPage']) ? (int) $options['perPage'] : 8;

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'tax_query' => array(
                array(
                    'taxonomy' => 'product_type',
                    'field'    => 'slug',
                    'terms'    => ['topping', 'voucher-coupon'],
                    'operator' => 'NOT IN'
                ),
                array(
                    'taxonomy' => 'product_group',
                    'field' => 'slug',
                    'terms' => $groupSlug,
                ),
            ),
        ];

        $query = new \WP_Query($args);

        $listProducts = [];
        foreach ($query->posts as $product) {
            $productInfo = self::convertProductToStdClass($product, [
                'getWith' => $options['getWith'] ?? '',
                'productGroups' => $productGroups
            ]);
            if (isset($options['productType']) && $options['productType'] == 'voucher-coupon') {
                $productInfo->campaignCode = get_post_meta($product->ID, '_evoucher_campaign_id', true);
                $startDate = get_post_meta($product->ID, '_campaign_start_date', true);
                $endDate = get_post_meta($product->ID, '_campaign_end_date', true);
                $productInfo->startDate = $startDate ?: '';
                $productInfo->endDate = $endDate ?: '';
            }

            $listProducts[] = $productInfo;
        }

        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $listProducts;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;

        return $res;
    }

    /**
     * Get list sorted product on Home
     *
     * @param $provinceId
     * @param $options array Type of list product and tag option.
     * @return array|mixed
     */
    public static function getListSortedProductHome($provinceId, $options) {
        $listProducts = [];
        $type = $options['type'] ?? 'hot-deal';
        if ($type == 'hot-deal') {
            $hotDealProducts = \GDelivery\Libs\Helper\Product::getProductByGroup('hot-deal', $provinceId, 1, -1, $options);
            $keySortOption = "icook:province:{$provinceId}:home-hotdeal:sort-product";
            $listProducts = \GDelivery\Libs\Helper\Product::sortProduct($hotDealProducts, $keySortOption);
        } elseif ($type == 'suggestion') {
            $tag = $options['tag'];
            $allSuggestionProducts = \GDelivery\Libs\Helper\Product::getProductByTagOnHome($tag, $provinceId, 1, -1, $options);
            $listProducts = $allSuggestionProducts->sorted;
        }

        return $listProducts;
    }

    public static function getListSortedGroup($categoryId)
    {
	    global $wpdb;
        $key = "icook:category:{$categoryId}:group";
	    $groups = $wpdb->get_results( $wpdb->prepare( "
	        SELECT ts.term_id, ts.name, ts.slug FROM wp_terms AS ts
	        WHERE ts.term_id IN (
		        SELECT MAX(t.term_id) AS termId FROM wp_terms AS t
				JOIN wp_term_taxonomy AS tt ON tt.term_id = t.term_id AND tt.taxonomy = 'product_group'
				JOIN wp_term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
				JOIN wp_posts AS p ON tr.object_id = p.ID
				WHERE p.ID IN (
					SELECT p1.ID FROM wp_posts AS p1 
					JOIN wp_term_relationships as tr1 ON p1.ID = tr1.object_id 
					JOIN wp_term_taxonomy as tt1 
						ON tr1.term_taxonomy_id = tt1.term_taxonomy_id 
						AND tt1.taxonomy = 'product_cat' AND tt1.term_id = %d
					WHERE 
							p1.post_status = 'publish' OR 
							p1.post_status = 'acf-disabled' OR 
							p1.post_status = 'wc-need-to-transfer' OR 
							p1.post_status = 'wc-need-to-cancel' OR 
							p1.post_status = 'wc-trans-requested' OR 
							p1.post_status = 'wc-trans-accepted' OR 
							p1.post_status = 'wc-trans-rejected' OR 
							p1.post_status = 'wc-trans-allocating' OR 
							p1.post_status = 'wc-trans-going' OR 
							p1.post_status = 'wc-trans-delivered' OR 
							p1.post_status = 'wc-trans-returned' OR 
							p1.post_status = 'wc-waiting-payment'
				)
				GROUP BY p.ID
			)
		   ", [
			    $categoryId,
		    ] ) );
	    $arrGroup = [];
	    $listUnSort = [];

        if (get_option($key)) {
	        $listSort = array_flip( unserialize( get_option( $key ) ) );
        }

        foreach ($groups as $group) {
            $groupId = $group->term_id;
            $groupInfo = [
	            'id' => $groupId,
	            'name' => $group->name,
	            'slug' => $group->slug,
            ];

            if (isset($listSort[$groupId])) {
                $arrGroup[$listSort[$groupId]] = $groupInfo;
            } else {
                $listUnSort[] = $groupInfo;
            }
        }
        ksort($arrGroup);
        $arrGroup = array_merge($arrGroup, $listUnSort);

        return $arrGroup;
    }

    /**
     * Get list product by merchant id
     *
     * @param int $merchantId id of merchant.
     *
     * @return Result
     */
    public static function getListProductByMerchantId($merchantId, $params = [])
    {
        $res = new Result();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'ep_integrate' => true,
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'tax_query' => self::getDefaultTaxQuery(),
        );
        $args['meta_query'] = array_merge(
            [
                'relation' => 'AND',
                [
                    'key' => 'merchant_id',
                    'value' => intval($merchantId),
                    'type' => 'NUMERIC',
                    'compare' => '='
                ]
            ],
            self::getDefaultMetaQuery()
        );
        if (isset($params['search']) && $params['search']) {
            $args['s'] = trim(strip_tags($params['search']));
        }
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags,combo',
                ]);
                $products[] = $productInfo;
            }
        }
        $products = array_values($products);
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $products;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list same buy product by product id
     *
     * @param int $productId id of product.
     *
     * @return Result
     */
    public static function getListSameBuyProduct($productId, $params = [])
    {
        $res = new Result();
        $product = wc_get_product($productId);
        if (!$product) {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Sản phẩm không tồn tại';
            return $res;
        }
        $upsellIds = $product->get_upsell_ids();
        if (empty($upsellIds)) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm nào!';
            return $res;
        }
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'post__in' => $upsellIds,
            'posts_per_page' => $perPage,
            'paged' => $page,
        );
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags',
                ]);
                $products[] = $productInfo;
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $products;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list upsells product by product id
     *
     * @param int $productId id of product.
     *
     * @return Result
     */
    public static function getListUpsellsProduct($productId, $params = [])
    {
        $res = new Result();
        $product = wc_get_product($productId);
        if (!$product) {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Sản phẩm không tồn tại';
            return $res;
        }
        $upsellIds = $product->get_upsell_ids();
        if (empty($upsellIds)) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm nào!';
            return $res;
        }
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'post__in' => $upsellIds,
            'posts_per_page' => $perPage,
            'paged' => $page,
        );
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags',
                ]);
                $products[] = $productInfo;
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $products;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list same category product by product id
     *
     * @param int $productId id of product.
     *
     * @return Result
     */
    public static function getListSameCategoryProduct($productId, $params = [])
    {
        $res = new Result();
        $product = wc_get_product($productId);
        if (!$product) {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Sản phẩm không tồn tại';
            return $res;
        }
        $productCategoryId = get_field('product_category_id', $productId);
        if (!$productCategoryId) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Không có sản phẩm nào!';
            return $res;
        }
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'post__not_in' => array($productId),
            'meta_query' => array(
                array(
                    'key' => 'product_category_id',
                    'value' => $productCategoryId,
                    'compare' => '='
                )
            )
        );
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags',
                ]);
                $products[] = $productInfo;
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $products;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list group by merchant id
     *
     * @param int $productId id of product.
     *
     * @return Result
     */
    public static function getListGroupByMerchant($merchantId, $params = [])
    {
        $res = new Result();
        $args = array(
            'post_type' => 'merchant',
            'p' => $merchantId,
        );
        $query = new \WP_Query($args);
        $merchantExists = $query->have_posts();
        if (!$merchantExists) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Merchant không tồn tại';
            return $res;
        }
        wp_reset_postdata();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'post_type' => 'product_category',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'meta_query' => array(
                array(
                    'key' => 'apply_for_merchant',
                    'value' => $merchantId,
                    'compare' => '='
                )
            )
        );
        $query = new \WP_Query($args);
        $merchantGroups = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $productCategory) {
                $merchantGroups[] = [
                    'id' => $productCategory->ID,
                    'name' => $productCategory->post_title,
                    'slug' => $productCategory->post_name,
                ];
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $merchantGroups;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list product by group id
     *
     * @param int $productId id of product.
     *
     * @return Result
     */
    public static function getListProductByGroup($groupId, $params = [])
    {
        $res = new Result();
        $args = array(
            'post_type' => 'product_category',
            'p' => $groupId,
        );
        $query = new \WP_Query($args);
        $groupExists = $query->have_posts();
        if (!$groupExists) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Nhóm không tồn tại';
            return $res;
        }
        wp_reset_postdata();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'meta_query' => array(
                array(
                    'key' => 'product_category_id',
                    'value' => $groupId,
                    'compare' => '='
                )
            )
        );
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags',
                ]);
                $products[] = $productInfo;
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $products;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list suggestion product
     *
     * @param int $productId id of product.
     *
     * @return Result
     */
    public static function getListSuggestionProduct($params = [])
    {
        $res = new Result();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
            'meta_query' => array(
                "relation" => 'AND',
                array(
                    'key' => 'is_master_product',
                    'value' => 0,
                    'compare' => '='
                ),
                array(
                    'key' => 'merchant_id',
                    'value' => 0,
                    'compare' => '>'
                )
            )
        );
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags',
                ]);
                $products[] = $productInfo;
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $products;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list product by category
     *
     * @param int $categoryId id of category.
     * @param mixed $params extra params.
     *
     * @return Result
     */
    public static function getListProductByCategory($categoryId, $params = [])
    {
        $res = new Result();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 8;
        $args = array(
            'ep_integrate' => true,
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
        );
        $args['meta_query'] = array_merge(
            [
                "relation" => 'AND',
            ],
            self::getDefaultMetaQuery()
        );
        $args['tax_query'] = array_merge(
            [
                "relation" => 'AND',
                [
                    'taxonomy' => 'ecom-category',
                    'field' => 'term_id',
                    'terms' => intval($categoryId),
                ]
            ],
            self::getDefaultTaxQuery()
        );
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags,combo',
                ]);
                $products[] = $productInfo;
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = $products;
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    /**
     * Get list product by merchant category
     *
     * @param int $merchantCategoryId id of category.
     * @param mixed $params extra params.
     *
     * @return Result
     */
    public static function getListProductByMerchantCategory($merchantCategoryId, $params = [])
    {
        $res = new Result();
        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $perPage = isset($params['perPage']) ? (int) $params['perPage'] : 100;
        $merchantCategoryId = intval($merchantCategoryId);
        $args = array(
            'ep_integrate' => true,
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => $perPage,
            'paged' => $page,
        );
        if (isset($params['search']) && $params['search']) {
            $args['s'] = trim(strip_tags($params['search']));
        }
        $args['meta_query'] = array_merge(
            [
                "relation" => 'AND',
            ],
            self::getDefaultMetaQuery()
        );
        $taxQuery = [
            "relation" => 'AND',
            [
                'taxonomy' => 'merchant-category',
                'field' => 'term_id',
                'terms' => $merchantCategoryId,
            ]
        ];
        if (isset($params['subCategory']) && !empty($params['subCategory'])) {
            $subCategories = array_map('intval', explode(',', $params['subCategory']));
            $subCategoryTaxQuery = [
                'taxonomy' => 'merchant-category',
                'field' => 'term_id',
                'terms' => $subCategories,
            ];
            if (count($subCategories) > 1) {
                $subCategoryTaxQuery['operator'] = 'IN';
            }
            $taxQuery[] = $subCategoryTaxQuery;
        }

        $args['tax_query'] = array_merge(
            $taxQuery,
            self::getDefaultTaxQuery()
        );
        $query = new \WP_Query($args);
        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $productInfo = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags,combo',
                ]);
                $products[] = $productInfo;
            }
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Thành công';
        $res->result = self::sortProductBySubCategory($products, $merchantCategoryId);
        $res->lastPage = $query->max_num_pages;
        $res->currentPage = $page;
        $res->total = $query->found_posts;
        return $res;
    }

    public static function sortProductBySubCategory($products, $merchantCategoryId)
    {
        $sortedProducts = self::initProductsBySubCategory($merchantCategoryId);
        if (empty($sortedProducts)) {
            return $products;
        }
        foreach ($products as $product) {
            foreach ($product->merchantCategory as $merchantCategory) {
                if ($merchantCategory->id != $merchantCategoryId) {
                    continue;
                }
                if (empty($merchantCategory->child) || count($merchantCategory->child) == 0) {
                    $sortedProducts[$merchantCategory->id][] = $product;
                    continue;
                }
                foreach ($merchantCategory->child as $subCategory) {
                    if (isset($sortedProducts[$subCategory->id])) {
                        $sortedProducts[$subCategory->id][] = $product;
                    }
                }
            }
        }
        return call_user_func_array('array_merge', $sortedProducts);
    }

    /**
     * Sort products by sub category
     */
    public static function initProductsBySubCategory($merchantCategoryId)
    {
        $sortedProducts = [];
        $sortedChildTerms = get_terms(array(
            'taxonomy' => 'merchant-category',
            'parent' => $merchantCategoryId,
            'hide_empty' => true,
            'orderby' => 'meta_value_num',
            'meta_key' => 'order',
            'order' => 'ASC',
        ));
        $subCategories = array_column($sortedChildTerms ?? [], 'term_id');
        foreach ($subCategories as $subCategory) {
            $sortedProducts[$subCategory] = [];
        }
        return $sortedProducts;
    }

    public static function getSortedScoringProducts($params = [])
    {
        $result = new Result();

        if (empty($params['product_ids'])) {
            $result->messageCode = Message::GENERAL_ERROR;
            $result->message = 'Không có products';
            return $result;
        }

        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => Config::REDIS_HOST,
            'port'   => Config::REDIS_PORT,
            'password' => Config::REDIS_PASS
        ]);
        $keyCache = sprintf('cms:scoring-products:%s', md5(json_encode($params)));
        $sortedScoringProducts = $redis->get($keyCache);

        if ($sortedScoringProducts) {
            $result->messageCode = Message::SUCCESS;
            $result->message = 'Thành công';
            $result->result = json_decode($sortedScoringProducts);
            return $result;
        }

        $query = new \WP_Query([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'post__in' => $params['product_ids'],
            'orderby' => 'post__in',
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'is_master_product',
                    'value' => 0,
                    'compare' => '=',
                ],
                [
                    'key' => 'merchant_id',
                    'value' => 0,
                    'compare' => '>',
                ],
            ],
        ]);

        $products = [];
        if ($query->have_posts()) {
            foreach ($query->posts as $product) {
                $products[] = self::convertProductToStdClass($product, [
                    'getWith' => 'variationProduct,topping,modifier,tags',
                ]);
            }
        }

        $result->messageCode = Message::SUCCESS;
        $result->message = 'Thành công';
        $result->result = $products;
        $redis->set($keyCache, \json_encode($products));
        return $result;
    }

    public static function resortListProduct($products, $arrSort)
    {
        $listSort = array_flip($arrSort);
        $arrProduct = [];
        foreach ($products as $product) {
            $productId = $product->parentId ?? $product->id;
            $arrProduct[$listSort[$productId]] = $product;
        }
        ksort($arrProduct);

        return array_values($arrProduct);
    }

    /**
     * Default meta query for list product
     *
     * @param string $date d-m-y format
     * @param string $weekDay monday->sunday format
     * @param mixed $time H:i format
     *
     * @return array $metaQuery
     */
    public static function getDefaultMetaQuery($customParams = [])
    {
        $params = [
            'date' => date_i18n('d-m-Y'),
            'weekday' => date_i18n('l'),
            'timeFrame' => date_i18n('H:i')
        ];
        $paramKeys = array_keys($params);
        foreach ($paramKeys as $paramKey) {
            if (isset($customParams[$paramKey]) && $customParams[$paramKey]) {
                $params[$paramKey] = $customParams[$paramKey];
            }
        }
        $metaQuery = [
            [
                'relation' => 'AND',
                [
                    'key' => 'time_config_start_date',
                    'value' => strtotime($params['date']),
                    'type' => 'NUMERIC',
                    'compare' => '<=',
                ],
                [
                    'key' => 'time_config_end_date',
                    'value' => strtotime($params['date']),
                    'type' => 'NUMERIC',
                    'compare' => '>=',
                ],
            ],
        ];
        $metaQuery[] = [
            'key' => 'is_master_product',
            'value' => 0,
            'type' => 'NUMERIC',
            'compare' => '=',
        ];
        $metaQuery[] = [
            'key' => 'time_frames',
            'value' => self::getTimeFrameOfSpecificTime($params['timeFrame']),
            'compare' => 'LIKE',
        ];
        $metaQuery[] = [
            'key' => 'time_config_list_of_days',
            'value' => $params['date'],
            'compare' => 'LIKE',
        ];
        return $metaQuery;
    }

    public static function getDefaultTaxQuery()
    {
        $productTypeIds = [2, 4, 276]; // simple, combo and variable product type
        $taxQuery = [
            [
                'taxonomy' => 'product_type',
                'field' => 'term_id',
                'terms' => $productTypeIds,
                'operator' => 'IN',
            ]
        ];
        return $taxQuery;
    }

    public static function getTimeFrameOfSpecificTime($specificTime)
    {
        $format = 'H:i';
        return date($format, strtotime($specificTime));
    }

    public static function getListTimeFrameFromRangeTime($rangeStart, $rangeEnd)
    {
        $format = 'H:i';
        $startTime = strtotime($rangeStart);
        $endTime = strtotime($rangeEnd);
        $timeStep = 60; //second

        $timeFrames = [];
        $currentTime = $startTime;
        while ($currentTime <= $endTime) {
            $timeFrame = date($format, $currentTime);
            $currentTime += $timeStep;
            $timeFrames[] = $timeFrame;
        }
        return $timeFrames;
    }

    public static function makeProductTimeConfig($productId)
    {
        $metaData = get_post_meta($productId);
        $intStartDate = intval($metaData['time_config_start_date'][0]);
        $intEndDate = intval($metaData['time_config_end_date'][0]);
        $listDate = explode(',', $metaData['time_config_list_of_days'][0]);
        $timeFrames = explode(',', explode('|', $metaData['time_frames'][0])[1]);
        return [
            'intStartDate' => $intStartDate,
            'intEndDate' => $intEndDate,
            'listDate' => $listDate,
            'timeFrames' => $timeFrames
        ];
    }

    public static function syncProductTimeConfig($productIds)
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => Config::REDIS_HOST,
            'port'   => Config::REDIS_PORT,
            'database' => Config::REDIS_DB_INDEX,
            'password' => Config::REDIS_PASS
        ]);
        foreach($productIds as $productId) {
            $redis->set(
                str_replace('{productId}', $productId, self::PRODUCT_TIME_CONFIG_REDIS_KEY), 
                json_encode(self::makeProductTimeConfig($productId))
            );
        }
    }

    /**
     * Checks if the given productIds have valid time configurations.
     *
     * @param array $productIds An array of integers representing product ids to check.
     * @param string $checkingTime A number representing the time to check. Defaults to 0.
     * @throws None
     * @return array An array of data containing productId and isValidTime.
     */
    public static function checkProductTimeConfig($productIds, $checkingTime = 0)
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host'   => Config::REDIS_HOST,
            'port'   => Config::REDIS_PORT,
            'database' => Config::REDIS_DB_INDEX,
            'password' => Config::REDIS_PASS
        ]);
        
        $fnCheckProductTime = function ($timeConfig, $checkingTime) {
            if ($checkingTime < $timeConfig['intStartDate'] || $checkingTime > $timeConfig['intEndDate']) {
                return false;
            }
    
            $checkingDate = date_i18n('d-m-Y', $checkingTime);
            if (!in_array($checkingDate, $timeConfig['listDate'])) {
                return false;
            }
    
            $checkingHour = date_i18n('H:i', $checkingTime);
            if (!in_array($checkingHour, $timeConfig['timeFrames'])) {
                return false;
            }

            return true;
        };

        $returnData = [];
        $productIds = array_map('intval', $productIds);
        foreach($productIds as $productId) {
            $timeConfig = json_decode($redis->get(str_replace('{productId}', $productId, self::PRODUCT_TIME_CONFIG_REDIS_KEY)), true);
            if (empty($timeConfig)) {
                $timeConfig = self::makeProductTimeConfig($productId);
            }
    
            if (empty($checkingTime)) {
                $checkingTime = time();
            }
            $checkingTime = intval($checkingTime);
            
            $returnData[] = [
                'productId' => $productId,
                'isValidTime' => $fnCheckProductTime($timeConfig, $checkingTime),
            ];
        }

        return $returnData;
    }
} // end class
