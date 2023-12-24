<?php

class DuplicateProductJob
{

	/**
	 * @var WC_Product
	 */
	public $masterProduct;

    /**
	 * @var int
	 */
	public $merchantId;

	/**
	 * DuplicateProductJob constructor.
	 *
	 * @param WC_Product $masterProduct The master product.
     * @param int $merchantId
	 */
	public function __construct($masterProduct, $merchantId) {
		$this->masterProduct = $masterProduct;
        $this->merchantId = $merchantId;
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
        $masterProduct = $this->masterProduct;
        $masterProductId = $masterProduct->get_id();
        $merchantId = $this->merchantId;
        $masterProductClone = clone $masterProduct;
        $masterProductClone->update_meta_data("merchant_list", []);
        $masterProductClone->update_meta_data("product_master_id", $masterProductId);
        $masterProductClone->update_meta_data("merchant_id", $merchantId);
        $masterProductClone->update_meta_data("is_master_product", 0);
        $duplicatedProduct = (new WC_Admin_Duplicate_Product)->product_duplicate($masterProductClone);
        $duplicatedProduct->update_meta_data("variations_mapping", json_encode($this->mappingVariationIds($masterProduct->get_children(), $duplicatedProduct->get_children())));
        $duplicatedProduct->set_name($masterProduct->get_name());
        $duplicatedProduct->set_status($masterProduct->get_status());
        $duplicatedProduct->save();
        $this->updateProductTaxonomy($masterProductId, $duplicatedProduct->get_id());

        $provinceIdCMS = get_field('province_id', $merchantId);
        $provinceId = get_field('booking_province_id', $provinceIdCMS);
        update_post_meta($duplicatedProduct->get_id(), 'province_ids', serialize($provinceId));
	}

    private function mappingVariationIds($masterProductVariations, $merchantProductVariations)
    {
        $arrayMapping = [];
        foreach ($merchantProductVariations as $key => $variationId) {
            $arrayMapping[$masterProductVariations[$key]] = $variationId;
        }
        return $arrayMapping;
    }

    private function updateProductTaxonomy($sourceProductId, $duplicateProductId)
    {
        $listTaxonomy = ['product_modifier_category', 'product_group', 'product_meat_type', 'product_apply_for_brand'];
        foreach ($listTaxonomy as $taxonomy) {
            $terms = get_the_terms($sourceProductId, $taxonomy);
            if (!is_wp_error($terms)) {
                wp_set_object_terms($duplicateProductId, wp_list_pluck($terms, 'term_id'), $taxonomy);
            }
        }
    }

}