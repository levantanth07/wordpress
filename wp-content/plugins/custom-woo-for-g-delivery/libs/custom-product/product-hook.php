<?php

use GDelivery\Libs\GBackendScoringService;
use GDelivery\Libs\Config;
use GDelivery\Libs\GBackendService;
use GDelivery\Libs\Helper\Product;
use GDelivery\Libs\InventoryService;
use Predis\Client;

class ProductHook
{

    /**
     * ProductHook constructor.
     */
    public function __construct()
    {
        add_action('save_post', [$this, 'savePostCallback'], 20, 2);
        add_action('deleted_post', [$this, 'onDeletedPost'], 10, 2);
        add_action('create_term', [$this, 'saveTaxonomyCallback'], 10, 3);
        add_action('edit_term', [$this, 'saveTaxonomyCallback'], 10, 3);
        add_action('delete_product_tag', [$this, 'onDeleteProductTagTaxonomy'], 10, 4);
        add_action('delete_merchant-category', [$this, 'onDeleteProductCategoryTaxonomy'], 10, 4);
        add_action('woocommerce_updated_product_sales', [$this, 'onUpdateProductSales']);
        add_action('woocommerce_save_product_variation', [$this, 'onWoocommerceSaveVariations']);
    }

    /**
     * @param int $postId
     * @param WP_Post $post
     */
    public function savePostCallback($postId, $post)
    {
        if (! $post->post_type) return;
        if ($post->post_type == 'product') {
            $this->syncElasticsearchProduct($post);
            $wcProduct = wc_get_product($post);
            InventoryService::syncProductInventory([$wcProduct]);
            Product::syncProductTimeConfig([$post->ID]);
            if (! $wcProduct->is_type(['simple', 'variable'])) return;
            $this->syncElasticsearchScoringProducts($post->ID);
            $this->syncElasticsearchScoringMerchantByProduct($post);
        } elseif ($post->post_type == 'chinhanh') {
            $this->clearRedisCacheByTag('restaurant,restaurant-list');
        } elseif ($post->post_type == 'promotion') {
            $this->clearRedisCacheByTag('promotion');
        } elseif ($post->post_type == 'banners') {
            $this->clearRedisCacheByTag('banner-list');
        } elseif ($post->post_type == 'brand') {
            $this->clearRedisCacheByTag('brand-list');
        } elseif (
            $post->post_type == 'merchant'
            || $post->post_type == 'rating'
        ) {
            if ($post->post_type == 'merchant') {
                $this->syncElasticsearchScoringProductsByMerchant($post->ID);
                $this->syncElasticsearchScoringMerchants($post->ID);
            }
            $this->clearRedisCacheByTag("merchant,merchant-list");
        } elseif ($post->post_type == 'block_dynamic') {
            $this->syncElasticsearchScoringMerchantsByBlockDynamic($post);
        } elseif ($post->post_type == 'payment_methods') {
            $this->clearRedisCacheByTag('payment-method');
        }
    }

    /**
     * @param int $termId
     * @param int $ttId
     * @param string $taxonomy
     */
    public function saveTaxonomyCallback($termId, $ttId, $taxonomy)
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host' => Config::REDIS_HOST,
            'port' => Config::REDIS_PORT,
            'password' => Config::REDIS_PASS,
        ]);
        $redis->flushdb();
        $this->clearRedisCacheByTag('brand,brand-list,product-tag,product-hotdeal-home,product-suggestion-home,product-in-group,product-group,product-tag,product-list,product-detail');
        if (! in_array($taxonomy, ['product_tag', 'merchant-category', 'ecom-category'])) return;
        if ($taxonomy === 'product_tag') {
            $this->syncElasticsearchScoringProducts($this->productsQuery([
                'post_status' => array_values(get_post_stati()),
                'tax_query' => [
                    [
                        'taxonomy' => 'product_tag',
                        'field' => 'term_id',
                        'terms' => $termId,
                    ],
                ],
            ]));
            $this->syncElasticsearchScoringMerchants($this->productsQuery([
                'tax_query' => [
                    [
                        'taxonomy' => 'product_tag',
                        'field' => 'term_id',
                        'terms' => $termId,
                    ],
                ],
            ]));
            return;
        }

        $this->syncElasticsearchScoringProducts($this->productsQuery([
            'post_status' => array_values(get_post_stati()),
            'tax_query' => [
                [
                    'taxonomy' => 'merchant-category',
                    'field' => 'term_id',
                    'terms' => $termId,
                ],
            ],
        ]));
        $this->syncElasticsearchScoringMerchants($this->productsQuery([
            'tax_query' => [
                [
                    'taxonomy' => 'merchant-category',
                    'field' => 'term_id',
                    'terms' => $termId,
                ],
            ],
        ]));

        $this->syncElasticsearchScoringProducts($this->productsQuery([
            'post_status' => array_values(get_post_stati()),
            'tax_query' => [
                [
                    'taxonomy' => 'ecom-category',
                    'field' => 'term_id',
                    'terms' => $termId,
                ],
            ],
        ]));
    }

    /**
     * @param int $termId
     * @param int $ttId
     * @param WP_Term $deletedTerm
     * @param array $objectIds
     * @return void
     */
    public function onDeleteProductTagTaxonomy($termId, $ttId, $deletedTerm, $objectIds)
    {
        if (empty($objectIds)) return;
        $this->syncElasticsearchScoringProducts($objectIds);
        $this->syncElasticsearchScoringMerchants($this->productsQuery([
            'post__in' => $objectIds,
        ]));
    }

    /**
     * @param int $termId
     * @param int $ttId
     * @param WP_Term $deletedTerm
     * @param array $objectIds
     * @return void
     */
    public function onDeleteProductCategoryTaxonomy($termId, $ttId, $deletedTerm, $objectIds)
    {
        if (empty($objectIds)) return;
        $this->syncElasticsearchScoringProducts($objectIds);
        $this->syncElasticsearchScoringMerchants($this->productsQuery([
            'post__in' => $objectIds,
        ]));
    }

    /**
     * @param int $postId
     * @param WP_Post $post
     * @return void
     */
    public function onDeletedPost($postId, $post)
    {
        if (! $post->post_type) return;
        if ($post->post_type == 'product') {
            $this->syncElasticsearchProduct($post);
            $wcProduct = wc_get_product($post);
            if (! $wcProduct->is_type(['simple', 'variable'])) return;
            $this->syncElasticsearchScoringProducts($postId);
            $this->syncElasticsearchScoringMerchantByProduct($post);
        } elseif ($post->post_type == 'chinhanh') {
            $this->clearRedisCacheByTag('restaurant,restaurant-list');
        } elseif ($post->post_type == 'promotion') {
            $this->clearRedisCacheByTag('promotion');
        } elseif ($post->post_type == 'banners') {
            $this->clearRedisCacheByTag('banner-list');
        } elseif ($post->post_type == 'brand') {
            $this->clearRedisCacheByTag('brand-list');
        } elseif (
            $post->post_type == 'merchant'
            || $post->post_type == 'rating'
        ) {
            if ($post->post_type == 'merchant') {
                $this->syncElasticsearchScoringProductsByMerchant($post->ID);
                $this->syncElasticsearchScoringMerchants($post->ID);
            }
            $this->clearRedisCacheByTag("merchant,merchant-list");
        } elseif ($post->post_type == 'block_dynamic') {
            $this->syncElasticsearchScoringMerchantsByBlockDynamic($post);
        } elseif ($post->post_type == 'payment_methods') {
            $this->clearRedisCacheByTag('payment-method');
        }
    }

    /**
     * @param int $productId
     * @return void
     */
    public function onUpdateProductSales($productId)
    {
        $this->syncElasticsearchScoringProducts($productId);
    }

    public function onWoocommerceSaveVariations($productId)
    {
        $product = get_post($productId);
        $this->syncElasticsearchProduct($product);
    }

    /**
     * @param int $merchantId
     * @return void
     */
    private function syncElasticsearchScoringProductsByMerchant($merchantId)
    {
        $this->syncElasticsearchScoringProducts($this->productsQuery([
            'post_status' => array_values(get_post_stati()),
            'meta_query' => [
                [
                    'key' => 'merchant_id',
                    'value' => $merchantId,
                    'compare' => '=',
                ],
            ]
        ]));
    }

    private function syncElasticsearchScoringMerchantsByBlockDynamic($blockDynamic)
    {
        $blockDynamicType = get_field('type', $blockDynamic->ID);
        if ($blockDynamicType !== 'merchant') return;
        $listSortedMerchants = get_field('list_item_sorted', $blockDynamic->ID);
        if (! $listSortedMerchants) return;
        $sortedMerchants = array_filter(explode(',', $listSortedMerchants), fn ($merchantId) => $merchantId && (is_int($merchantId) || ctype_digit($merchantId)));
        if (empty($sortedMerchants)) return;
        $this->syncElasticsearchScoringMerchants($sortedMerchants);
    }

    private function syncElasticsearchProduct($post)
    {
        $gBackendService = new GBackendService();
        $this->clearRedisCacheByKey('icook:province:*');
        $product = wc_get_product($post);
        $regular = (float)$product->get_regular_price();
        $sale = (float)$product->get_sale_price();
        $discount = $rate = 0;
        if ($sale > 0) {
            $discount = $regular - $sale;
            $rate = ceil(($discount / $regular) * 100);
        }

        update_post_meta($post->ID, '_discount_amount', $discount);
        update_post_meta($post->ID, '_discount_rate', $rate);

        $gBackendService->syncElasticSearchProduct($post->ID);
        $this->clearRedisCacheByTag("product,product:detail:{$post->ID},product:suggestion-for-you,product:upsells,product:by-group,
        product:by-merchant,product:by-category,product:by-merchant-category,product:same-category,product:same-buy,product-detail,dynamic-listing,product-rkCode");
    }

    private function clearRedisCacheByTag($tags)
    {
        $gBackendService = new GBackendService();
        $gBackendService->clearRedisCache($tags);
    }

    private function clearRedisCacheByKey(string $key)
    {
        $redis = new Client([
            'scheme' => 'tcp',
            'host' => Config::REDIS_HOST,
            'port' => Config::REDIS_PORT,
            'password' => Config::REDIS_PASS
        ]);

        $redisKeys = $redis->keys($key);
        foreach ($redisKeys as $key) {
            $redis->del($key);
        }
    }

    /**
     * @param array $query
     * @return WP_Query
     */
    private function productsQuery($query)
    {
        $baseQuery = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
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
            'tax_query' => Product::getDefaultTaxQuery(),
        ];

        foreach ($query as $key => $value) {
            if (in_array($key, ['meta_query', 'tax_query'])) {
                $baseQuery[$key] = array_merge($baseQuery[$key], $value);
                continue;
            }

            $baseQuery[$key] = $value;
        }

        return new WP_Query($baseQuery);
    }

    /**
     * @param WP_Post $product
     * @return void
     */
    private function syncElasticsearchScoringMerchantByProduct($product)
    {
        $merchant = get_field('merchant_id', $product->ID);
        if (! $merchant) return;
        if (is_object($merchant)) $merchant = $merchant->ID;
        $this->syncElasticsearchScoringMerchants($merchant);
    }

    /**
     * @param WP_Query|array|int $post
     * @return void
     */
    private function syncElasticsearchScoringProducts($post)
    {
        $backendService = new GBackendScoringService();
        if ($post instanceof WP_Query && ! $post->have_posts()) return;
        $backendService->clearRedisCacheByTags('product:suggestion-for-you');
        if ($post instanceof WP_Query) {
            $backendService->addScoringProductsToQueueForSyncing(array_map(fn (WP_Post $post) => $post->ID, $post->posts));
            return;
        }

        $backendService->addScoringProductsToQueueForSyncing($post);
    }

    /**
     * @param WP_Query|array|int $post
     * @return void
     */
    private function syncElasticsearchScoringMerchants($post)
    {
        $backendService = new GBackendScoringService();
        if ($post instanceof WP_Query && ! $post->have_posts()) return;
        $this->clearRedisCacheByKey('cms:scoring-merchants:*');
        $backendService->clearRedisCacheByTags('merchant-list,dynamic-listing');
        if ($post instanceof WP_Query) {
            $backendService->addScoringMerchantsToQueueForScoring(
                array_values(array_unique(array_map(fn (WP_Post $post) => get_post_meta($post->ID, 'merchant_id', true), $post->posts)))
            );
            return;
        }

        $backendService->addScoringMerchantsToQueueForScoring($post);
    }
}

$productHook = new ProductHook();
