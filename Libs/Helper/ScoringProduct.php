<?php

namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\BookingService;

class ScoringProduct
{
    /**
     * @param array $args
     * @return Result
     */
    public static function getListProducts($args)
    {
        $result = new Result();

        $query = new \WP_Query($args);

        $products = [];
        foreach ($query->posts as $product) {
            $products[] = self::convertProductToStdClass($product);
        }

        $result->messageCode = Message::SUCCESS;
        if ($products) {
            $result->result = $products;
            $result->numberOfResult = count($products);
            $result->total = $query->found_posts;
            $result->lastPage = $query->max_num_pages;
            $result->message = 'Thành công';
        } else {
            $result->message = 'Ko có dữ liệu sản phẩm';
        }

        return $result;
    }

    public static function getListSpecificProducts($args)
    {
        $result = new Result();

        $query = new \WP_Query($args);

        $products = [];
        foreach ($query->posts as $product) {
            $products[] = self::convertProductToStdClass($product);
        }

        $result->messageCode = Message::SUCCESS;
        if ($products) {
            $result->result = $products;
            $result->numberOfResult = count($products);
            $result->total = $query->found_posts;
            $result->lastPage = $query->max_num_pages;
            $result->message = 'Thành công';
        } else {
            $result->message = 'Ko có dữ liệu sản phẩm';
        }

        return $result;
    }

    /**
     * @param int $id
     * @return Result
     */
    public static function getDetailProduct($id)
    {
        $result = new Result();
        $query = new \WP_Query([
            'post_type' => ['product', 'product_variation'],
            'post_status'=> array_values(get_post_stati()),
            'p' => $id,
        ]);

        if (! $query->have_posts()) {
            $result->messageCode = Message::NOT_FOUND;
            $result->message = 'Sản phẩm không tồn tại';
            return $result;
        }

        $result->messageCode = Message::SUCCESS;
        $result->message = 'Thành công';
        $result->result = self::convertProductToStdClass($query->posts[0]);
        return $result;
    }

    /**
     * @param \WP_Post $product
     * @return \stdClass
     */
    private static function convertProductToStdClass($product)
    {
        $productObject = new \stdClass();
        $productObject->parentId = null;
        $productObject->status = $product->post_status;
        $oldProductId = $product->ID;
        $currentProductId = $oldProductId;
        $parentId = $oldProductId;
        $productObject->sold = (int) get_post_meta($parentId, 'total_sales', true);
        $productInfo = wc_get_product($currentProductId);
        if ($productInfo->is_type('variable')) {
            $variations = $productInfo->get_available_variations();
            if (isset($variations[0])) {
                $currentProductId = $variations[0]['variation_id'];
                $productObject->parentId = $oldProductId;
            }
        }
        if ($productInfo->is_type('variation')) {
            $productObject->parentId = $productInfo->get_parent_id();
            $parentId = $productObject->parentId;
        }

        $productObject->id = $oldProductId;
        $productObject->type = $productInfo->get_type();
        self::addMerchantInfo($productObject, $parentId);

        $productObject->productCategory = self::getProductCategory($parentId);
        $productObject->inventory = self::getInventoryInfo($productInfo);
        $productObject->tags = self::getTags($oldProductId);
        $productObject->eComCategoryIds = self::getEComCategoryIds($parentId);
        $productObject->productInfo = Product::getProductInfo($parentId);

        return $productObject;
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

    public static function getProductCategory($parentId)
    {
        $terms = get_the_terms($parentId,'merchant-category');
        $productCategoryObject = new \stdClass();
        if (($terms instanceof \WP_Error && $terms->has_errors()) || ! $terms) {
            $productCategoryObject->priority = 0;
            $productCategoryObject->day = [];
            $productCategoryObject->meal = [];
            return $productCategoryObject;
        }

        $result = [];
        foreach ($terms as $term) {
            if (! $term->parent) {
                $result = array_merge($result, self::getDepth($terms, $term, 0));
            }
        }

        if (empty($result)) {
            $productCategoryObject->priority = 0;
            $productCategoryObject->day = [];
            $productCategoryObject->meal = [];
            return $productCategoryObject;
        }

        $grouped = array_reduce($result, function($carry, $item) {
            $depth = $item->depth;
            if (!isset($carry[$depth])) {
                $carry[$depth] = [];
            }
            $carry[$depth][] = $item;
            return $carry;
        }, []);

        $lastChild = end($grouped);
        $productCategory = $lastChild[array_rand($lastChild)];

        $productCategoryObject->priority = (int) get_field('ranking_order', $productCategory) ?: 0;
        $productCategoryObject->day = array_map(fn ($day) => (int) $day, get_field('ranking_day', $productCategory) ?: []);
        $productCategoryObject->meal = get_field('ranking_meal', $productCategory) ?: [];
        return $productCategoryObject;
    }

    public static function getEComCategoryIds($parentId)
    {
        $terms = get_the_terms($parentId,'ecom-category');
        if (($terms instanceof \WP_Error && $terms->has_errors()) || ! $terms) {
            return [];
        }

        $arrId = [];
        if ($terms) {
            foreach ($terms as $term) {
                $arrId[] = $term->term_id;
            }
        }

        return $arrId;
    }

    /**
     * @param int $productId
     * @return array|\stdClass[]
     */
    public static function getTags($productId)
    {
        $tags = get_the_terms($productId, 'product_tag');
        if (($tags instanceof \WP_Error && $tags->has_errors()) || ! $tags) {
            return [];
        }

        return array_map(function (\WP_Term $tag) {
            $tagObject = new \stdClass();
            $tagObject->id = $tag->term_id;
            $tagObject->name = $tag->name;
            $tagObject->slug = $tag->slug;
            return $tagObject;
        }, $tags);
    }

    /**
     * @param \WC_Product $productInfo
     * @return \stdClass
     */
    private static function getInventoryInfo($productInfo)
    {
        $productInventory = new \stdClass();
        $productInventory->sku = $productInfo->get_sku();
        $productInventory->stockQuantity = $productInfo->get_stock_quantity();
        return $productInventory;
    }

    private static function getDepth($terms, $term, $depth)
    {
        $term->depth = $depth;
        $result[] = $term;
        foreach ($terms as $child) {
            if ($child->parent == $term->term_id) {
                $result = array_merge($result, self::getDepth($terms, $child, $depth + 1));
            }
        }
        return $result;
    }
}
