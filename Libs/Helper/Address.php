<?php
namespace GDelivery\Libs\Helper;

class Address {

    public static function updateSelectedProvince()
    {
        $listItems = WC()->cart->get_cart();
        $firstItem = reset($listItems);
        if ($firstItem) {
            $productId = $firstItem['product_id'];
            $categories = get_the_terms($productId, 'product_cat');
            foreach ($categories as $category) {
                if (!isset($currentCategory) && $category->parent != 0) {
                    $currentCategory = $category;
                }
                if (!isset($parentCategory) && $category->parent == 0) {
                    $parentCategory = $category;
                }
            }
        }

        if (isset($currentCategory) && isset($parentCategory)) {
            $currentProvinceId = get_field('product_category_province_id', 'product_cat_' . $currentCategory->term_id);
            if (!isset($_SESSION['selectedProvince']) ||
                $_SESSION['selectedProvince']->id != $currentProvinceId) {

                $bookingService = new \GDelivery\Libs\BookingService();
                $getProvince = $bookingService->getProvince($currentProvinceId);
                if ($getProvince->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    $currentProvince = $getProvince->result;
                    // set selected province
                    \GDelivery\Libs\Helper\Helper::setSelectedProvince($currentProvince);
                }
            }
        }
    }

    public static function updateSelectedAddress()
    {
        $currentCustomer = \GDelivery\Libs\Helper\User::currentCustomerInfo();
        if ($currentCustomer) {
            $currentSelectedProvince = Helper::getSelectedProvince();

            // TGS service
            $tgsService = new \GDelivery\Libs\TGSService($currentCustomer->customerAuthentication);
            // booking service
            $bookingService = new \GDelivery\Libs\BookingService();

            $listCustomerAddress = $tgsService->getAddresses();
            if ($listCustomerAddress->messageCode == \Abstraction\Object\Message::SUCCESS) {
                $customerAddress = $listCustomerAddress->result;

                // set default address
                $defaultCustomerAddress = null;
                // firstly get default
                foreach ($customerAddress as $one) {
                    if ($one->isDefault) {
                        $defaultCustomerAddress = $one;
                        break;
                    }
                }

                // secondly check default and the other address if needed
                if ($defaultCustomerAddress->provinceId != $currentSelectedProvince->id) {
                    foreach ($customerAddress as $one) {
                        // get first address in province is default
                        if ($one->provinceId == $currentSelectedProvince->id) {
                            $selectedAddress = $one;
                            break;
                        }
                    }
                } else {
                    $selectedAddress = $defaultCustomerAddress;
                }

                // thirdly, set more info
                if (!isset($selectedAddress->longitude, $selectedAddress->latitude)) {
                    if (get_option('google_map_service_address') == 'goong_address') {
                        $defaultProvinceInfo = $bookingService->getProvince($selectedAddress->provinceId)->result;
                        $defaultDistrictInfo = $bookingService->getDistrict($selectedAddress->districtId)->result ?: null;
                        $defaultWardInfo = $bookingService->getWard($selectedAddress->wardId)->result ?: null;

                        // set long/lat by ward
                        if ($defaultWardInfo) {
                            $selectedAddress->address = $selectedAddress->addressLine1;
                            $selectedAddress->longitude = $defaultWardInfo->longitude;
                            $selectedAddress->latitude = $defaultWardInfo->latitude;
                        }
                        $selectedAddress->wardId = $defaultWardInfo->id;
                        // set info
                        $selectedAddress->wardId = $defaultWardInfo->id;
                        $selectedAddress->provinceId = $defaultProvinceInfo->id;
                        $selectedAddress->districtId = $defaultDistrictInfo->id;
                    }
                }

                \GDelivery\Libs\Helper\Helper::setSelectedAddress($selectedAddress);
            }
        }
    }

} // end class
