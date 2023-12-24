<?php

class UpdateProductJob
{

	/**
	 * @var mixed
	 */
	public $masterProduct;

    /**
	 * @var int
	 */
	public $merchantProductId;

	/**
	 * UpdateProductJob constructor.
	 *
	 * @param int $masterProductId
     * @param int $merchantProductId
	 */
	public function __construct($masterProduct, $merchantProductId) {
		$this->masterProduct = $masterProduct;
        $this->merchantProductId = $merchantProductId;
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
        $masterProduct = $this->masterProduct;
        $merchantProductId = $this->merchantProductId;
        if (is_int($this->masterProduct)) {
            $masterProduct = wc_get_product($this->masterProduct);
        }
        $masterProductId = $masterProduct->get_id();
        $merchantProduct = wc_get_product($merchantProductId);
        $excludeCustomFields = ['product_master_id', 'merchant_id', 'merchant_list', 'is_master_product', 'variations_mapping'];
        foreach ($excludeCustomFields as $customFieldKey) { // add _{key} for advanced custom fields
            $excludeCustomFields[] = '_' . $customFieldKey; 
        }
        $fieldsData = get_post_meta($masterProductId);
        foreach ($fieldsData as $fieldKey => $fieldData) {
            if (in_array($fieldKey, $excludeCustomFields)) {
                continue;
            }
            $merchantProduct->update_meta_data($fieldKey, maybe_unserialize($fieldData[0]));
        }

        $merchantProduct->set_name($masterProduct->get_name());
        $merchantProduct->set_image_id($masterProduct->get_image_id());
        $merchantProduct->set_gallery_image_ids($masterProduct->get_gallery_image_ids());
        $merchantProduct->set_status($masterProduct->get_status());
        $merchantProduct->set_regular_price($masterProduct->get_regular_price());
        $merchantProduct->set_sale_price($masterProduct->get_sale_price());
        $merchantProduct->set_date_on_sale_from($masterProduct->get_date_on_sale_from());
        $merchantProduct->set_date_on_sale_to($masterProduct->get_date_on_sale_to());
        $merchantProduct->set_description($masterProduct->get_description());
        $merchantProduct->set_short_description($masterProduct->get_short_description());
        $merchantProduct->set_sku($masterProduct->get_sku());
        $merchantProduct->set_manage_stock($masterProduct->get_manage_stock());
        $merchantProduct->set_stock_quantity($masterProduct->get_stock_quantity());
        $merchantProduct->set_backorders($masterProduct->get_backorders());
        $merchantProduct->set_stock_status($masterProduct->get_stock_status());
        $merchantProduct->set_catalog_visibility($masterProduct->get_catalog_visibility());
        $merchantProduct->set_tax_status($masterProduct->get_tax_status());
        $merchantProduct->set_tax_class($masterProduct->get_tax_class());
        $merchantProduct->set_sold_individually($masterProduct->is_sold_individually());
        $merchantProduct->set_attributes($masterProduct->get_attributes());

        if ($masterProduct->is_type('variable')) {
            $variationsMapping = json_decode(get_field('variations_mapping', $merchantProductId), true);
            $masterProductVariations = $masterProduct->get_children();
            foreach ($masterProductVariations as $masterProductVariationId) {
                $metaDatas = get_post_meta($masterProductVariationId);
                if (isset($variationsMapping[$masterProductVariationId])) { // Update variation meta data
                    $merchantVariationId = $variationsMapping[$masterProductVariationId];
                    foreach ($metaDatas as $metaKey => $metaData) {
                        update_post_meta($merchantVariationId, $metaKey, maybe_unserialize($metaData[0]));
                    }
                } else { // create new variation and meta data  
                    $masterProductVariation = wc_get_product($masterProductVariationId);
                    $newVariation = clone $masterProductVariation;
                    $newVariation->set_id(0);
                    $newVariation->set_parent_id($merchantProductId);
                    $newVariation->save();
                    $newVariationId = $newVariation->get_id();
                    foreach ($metaDatas as $metaKey => $metaData) {
                        update_post_meta($newVariationId, $metaKey, maybe_unserialize($metaData[0]));
                    }
                    //Update variations mapping
                    $variationsMapping[$masterProductVariationId] = $newVariationId;
                    $merchantProduct->update_meta_data("variations_mapping", json_encode($variationsMapping));
                }
            }
        }
        $merchantProduct->save();
        $this->updateProductTaxonomy($masterProductId, $merchantProductId);
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