<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message as Message;
use Abstraction\Object\Result;
use GDelivery\Libs\BookingService;

class Province {
    
    public static function checkProvinceAvailable($provinceId)
    {
        $res = new Result();
        if (in_array($provinceId, [43, 27, 12])) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = "<p style='text-align: center'>G-Delivery tạm dừng dịch vụ theo chỉ thị phòng chống dịch của thủ tướng chính phủ. <br />Vui lòng liên hệ số điện thoại 02473003077 để được hỗ trợ. <br />Xin lỗi Quý khách vì sự bất tiện này.</p>";
        } else {
            $res->messageCode = Message::SUCCESS;
        }

        return $res;
    }

    /**
     * Get list province from booking service
     *
     * @return Result
     */
    public static function getListProvince(): Result
    {
        $bookingService = new BookingService();

        return $bookingService->getProvinces();
    }

    /**
     * Get province from product id
     *
     * @param $productId
     * @return mixed|null
     */
    public static function getProvinceFromProductId($productId) {
        $product = wc_get_product($productId);

        if ($product->is_type( 'variation' )) {
            $productId = $product->get_parent_id();
        }

        $categories = wp_get_post_terms($productId, 'product_cat');
        $currentCategory = null;
        foreach ($categories as $category) {
            if ($category->parent == 0) {
                $currentCategory = $category;
                break;
            }
        }

        return $currentCategory;
    }

} // end class
