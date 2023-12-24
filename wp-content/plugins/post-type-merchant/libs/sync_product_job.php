<?php

require_once 'update_product_job.php';
require_once 'duplicate_product_job.php';

class SyncProductJob
{

	/**
	 * @var int
	 */
	public $masterProductId;

	/**
	 * DuplicateProductJob constructor.
	 *
     * @param int $masterProductId
	 */
	public function __construct($masterProductId) {
		$this->masterProductId = $masterProductId;
	}

	/**
	 * Handle job logic.
	 */
	public function handle() {
        $isMasterProduct = get_field('is_master_product', $this->masterProductId) ?? false;
        if (!$isMasterProduct) {
            return;
        }
        $listMerchant = get_field('merchant_list', $this->masterProductId);
        if (empty($listMerchant)) {
            return;
        }
        $masterProduct = wc_get_product($this->masterProductId);
        foreach ($listMerchant as $key => $merchant) {
            $merchantId = $merchant->ID;
            $args = array(
                'post_type' => 'product',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'product_master_id',
                        'value' => $this->masterProductId,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'merchant_id',
                        'value' => $merchantId,
                        'compare' => '='
                    )
                )
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $merchantProductId = get_the_ID();
                    (new UpdateProductJob($masterProduct, $merchantProductId))->handle();

                    $provinceIdCMS = get_field('province_id', $merchantId);
                    $provinceId = get_field('booking_province_id', $provinceIdCMS);
                    update_post_meta($merchantProductId, 'province_ids', serialize($provinceId));
                }
                wp_reset_postdata();
            } else {
                (new DuplicateProductJob($masterProduct, $merchantId))->handle();
            }
        }
	}

}