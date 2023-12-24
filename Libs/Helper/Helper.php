<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\BookingService;
use GGGGESKD\Abstraction\Object\Service;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GDelivery\Libs\Config;
use GDelivery\Libs\Location;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Helper {

    public static $merchantPostType = 'merchant';

    private $httpClient;

    private $redis;

    private $locationService;

    /**
     * @param \WP_Post $post
     * @param array|boolean $options
     *                      when pass true, get with booking restaurant
     *
     * @return \stdClass
     */
    public static function getMerchantInfo($post, $options = [])
    {
        $temp = new \stdClass();

        $temp->id = $post->ID;
        $temp->name = $post->post_title;
        $temp->status = $post->post_status;
        $temp->banner = get_the_post_thumbnail_url($post->ID, 'shop_catalog') ?: '';
        $temp->logo = get_field('merchant_logo', $temp->id) ? get_field('merchant_logo', $temp->id) : '';
        $temp->avatar = get_field('merchant_avatar', $temp->id) ?: '';
        $temp->merchantType = get_field('merchant_type', $temp->id);
        $temp->restaurantCode = get_field('restaurant_code', $temp->id);
        $temp->rkOrderCategoryCode = get_field('restaurant_order_category_code', $temp->id);
        $temp->rkWaiterCode = get_field('restaurant_waiter_code', $temp->id);
        $temp->rkTableCode = get_field('restaurant_table_code', $temp->id);
        $temp->allowCallNewOrder = get_field('merchant_allow_calling_new_order', $temp->id);
        $temp->allowEmailNewOrder = get_field('merchant_allow_send_mail_new_order', $temp->id);
        $temp->allowGrabExpress = get_field('merchant_allow_grab_express', $temp->id);
        $temp->allowCutleryTool = get_field('merchant_allow_cutlery_tool', $temp->id);
        $temp->isAllowedTgsWallet = get_field('isAllowedTgsWallet', $temp->id);
        // todo hardcode for icook in HCM @since 24/10/2021; added by toan.nguyenduc@ggg.com.vn
        $temp->rkOrderCategoryCodeForIcook = get_field('merchant_order_category_code_for_icook', $temp->id);

        // merchant partner
        $temp->merchantAddress = get_field('merchant_address', $temp->id);
        $temp->merchantTelephone = get_field('merchant_telephone', $temp->id);
        $temp->merchantLongitude = get_field('merchant_longitude', $temp->id);
        $temp->merchantLatitude = get_field('merchant_latitude', $temp->id);

        $temp->merchantOpenTime1 = get_field('merchant_open_time_1', $temp->id);
        $temp->merchantCloseTime1 = get_field('merchant_close_time_1', $temp->id);

        $temp->merchantOpenTime2 = get_field('merchant_open_time_2', $temp->id);
        $temp->merchantCloseTime2 = get_field('merchant_close_time_2', $temp->id);
        $temp->minimumTimeToServe = get_field('minimum_time_to_serve', $temp->id);

        $temp->sceneId = get_field('sceneId', $temp->id);
        $temp->provinceId = get_field('province_id', $temp->id);
        $temp->ratingPoint = get_field('rating_point', $temp->id);

        $temp->availableDays = get_field('merchant_available_days', $temp->id);

        // Brand
        $brand = get_field('brand_id', $temp->id);
        if ($brand) {
            $temp->brand = self::getBrandInfo($brand);
        }

        // Get concept
        $getConcepts = get_field('concept_id', $temp->id);
        $concepts = [];
        if ($getConcepts) {
            foreach ($getConcepts as $item) {
                $objConcept = new \stdClass();
                $objConcept->id = $item->ID;
                $objConcept->name = $item->post_title;
                $objConcept->logo = get_the_post_thumbnail_url($item->ID, 'shop_catalog');
                $concepts[] = $objConcept;
            }
            $temp->concepts = $concepts;
        } else {
            $temp->concepts = null;
        }

        // Get rating
        $argRatings = [
            'post_type' => 'rating',
            'showposts' => 999,
            'post_status' => 'publish'
        ];

        $argRatings['meta_query'] = [
            [
                'key' => 'merchant_id',
                'value' => $post->ID,
                'compare' => '=',
            ],
        ];
        $loopRating = new \WP_Query($argRatings);
        if ($loopRating->have_posts()) {
            $ratings = [];
            foreach ($loopRating->posts as $onePost) {
                $ratings[] = [
                    'name' => $onePost->post_title,
                    'avatar' => get_the_post_thumbnail_url($onePost->ID, 'shop_catalog'),
                    'phoneNumber' => get_field('phone_number', $onePost->ID),
                    'point' => get_field('point', $onePost->ID),
                    'comment' => get_field('comment', $onePost->ID),
                    'create_at' => get_field('create_at', $onePost->ID),
                ];
            }
            $temp->totalRating = $loopRating->found_posts;
            $temp->ratings = $ratings;
        } else {
            $temp->totalRating = 0;
            $temp->ratings = [];
        }

        $bookingService = new BookingService();
        if ($temp->merchantType && $options === true) {
            $getMerchantInfo = $bookingService->getRestaurant($temp->restaurantCode);
            if ($getMerchantInfo->messageCode == Message::SUCCESS) {
                $temp->restaurant = $getMerchantInfo->result;
            } else {
                $temp->restaurant = null;
            }
        } elseif ($temp->restaurantCode && is_array($options)) {
            $getMerchantInfo = $bookingService->getRestaurant($temp->restaurantCode, $options);
            if ($getMerchantInfo->messageCode == Message::SUCCESS) {
                $temp->restaurant = $getMerchantInfo->result;
            } else {
                $temp->restaurant = null;
            }
        } else {
            $temp->restaurant = null;
        }
        if ($temp->restaurant != null) {
            $temp->restaurant->time = self::calculateTimeFromDistance($temp->restaurant->distance);
        }

        return $temp;
    }

    /**
     * calculate time from distance
     *
     * @param float $distance (m)
     *
     * @return int $time (s)
     */
    public static function calculateTimeFromDistance($distance)
    {
        if (empty($distance) || $distance <= 0) {
            return 0;
        }
        $defaultSpeed = 6.5; // m/s ~ 25 km/h
        return ceil($distance/$defaultSpeed);
    }

    /**
     * return cart item or false
     *
     * @param      $productId
     * @param null $cart
     *
     * @return bool|mixed
     */
    public static function productInCart($productId, $cart = null)
    {
        if ($cart === null) {
            $cart = WC()->cart ? WC()->cart->get_cart() : [];
        }

        foreach ($cart as $oneItem) {
            $product = $oneItem['data'];
            if ($product->is_type('variation'))  {
                if ($product->get_id() == $productId) {
                    return $oneItem;
                }
            } else {
                if ($oneItem['product_id'] == $productId) {
                    return $oneItem;
                }
            }
        }

        return false;
    }

    public static function productUnitText($unit)
    {
        $text = [
            'gram' => 'gram',
            'ml' => 'ml',
            'pla' => 'Đĩa',
            'par' => 'Suất',
            'bot' => 'Chai',
            'fa' => 'Lần',
            'kg' => 'Kg',
            'pcs' => 'Cái',
            'lon' => 'Lon',
            'cup' => 'Ly',
            'box' => 'Hộp',
            'set' => 'Bộ',
            'l' => 'L',
            'pak' => 'Gói',
            'con' => 'Con',
            'qua' => 'Quả',
        ];

        if (isset($text[$unit])) {
            return $text[$unit];
        } else {
            return 'Chưa xác định';
        }
    }

    public static function allowGrabWebhookUpdateOrder($orderStatus, $grabStatus)
    {
        $allowOrderStatus = [
            Order::STATUS_TRANS_REQUESTED,
            Order::STATUS_TRANS_ACCEPTED,
            Order::STATUS_TRANS_REJECTED,
            Order::STATUS_TRANS_ALLOCATING,
            Order::STATUS_TRANS_DELIVERED,
            Order::STATUS_TRANS_GOING,
            Order::STATUS_TRANS_RETURNED
        ];

        return in_array($orderStatus, $allowOrderStatus);
    }

    public static function parseQueryUri()
    {
        // process current query uri
        $currentUrl = add_query_arg( NULL, NULL ) ;
        $queryUriString = isset(parse_url($currentUrl)['query']) ? parse_url($currentUrl)['query'] : '';
        $arrayQueryUriParam = [];
        parse_str($queryUriString, $arrayQueryUriParam);

        if ($queryUriString) {
            $temp = new \stdClass();
            $temp->string = $queryUriString;
            $temp->params = $arrayQueryUriParam;
        } else {
            $temp = null;
        }

        return $temp;
    }

    /**
     * @return null|\WP_Term
     */
    public static function getCurrentCategory()
    {
        if (isset($_SESSION['currentCategory'])) {
            return $_SESSION['currentCategory'];
        } else {
            return Cart::getCategoryOfCart();
        }
    }

    public static function setCurrentCategory($currentCategory)
    {
        $_SESSION['currentCategory'] = $currentCategory;
    }

    public static function countOnGoingOrders($currentUser = null)
    {
        if (!$currentUser) {
            $currentUser = wp_get_current_user();
        }

        $onGoingStatus = ['pending', 'processing', 'trans-requested', 'trans-delivered', 'trans-returned', 'trans-accepted', 'need-to-transfer'];

        $args = [
            'customer_id' => $currentUser->ID,
            'status' => $onGoingStatus,
            'return' => 'ids',
            'numberposts' => -1
        ];

        $customerOrders = wc_get_orders($args);

        if ($customerOrders) {
            return \count($customerOrders);
        } else {
            return 0;
        }
    }

    public static function setDeliveryInfo($deliveryInfo)
    {
        $_SESSION['deliveryInfo'] = $deliveryInfo;
    }

    public static function getDeliveryInfo()
    {
        if (isset($_SESSION['deliveryInfo'])) {
            return $_SESSION['deliveryInfo'];
        } else {
            return null;
        }
    }

    public static function setSelectedRestaurant($selectedRestaurant)
    {
        $_SESSION['selectedRestaurant'] = $selectedRestaurant;
    }

    public static function getSelectedRestaurant()
    {
        if (isset($_SESSION['selectedRestaurant'])) {
            return $_SESSION['selectedRestaurant'];
        } else {
            return null;
        }
    }

    public static function setSelectedAddress($selectedAddress)
    {
        $_SESSION['selectedAddress'] = $selectedAddress;
    }

    public static function getSelectedAddress()
    {
        if (isset($_SESSION['selectedAddress'])) {
            return $_SESSION['selectedAddress'];
        } else {
            return null;
        }
    }

    public static function setSelectedProvince($selectedProvince)
    {
        $_SESSION['selectedProvince'] = $selectedProvince;
    }

    public static function getSelectedProvince()
    {
        if (WC()->cart->get_cart_contents_count() > 0) {
            Address::updateSelectedProvince();
        }

        return $_SESSION['selectedProvince'] ?? null;
    }

    public static function setTempShippingFee($fee = null)
    {
        if ($fee === null || $fee === 0) {
            $temp = new \stdClass();
            $temp->price = 0;
            $temp->tax = 0;
            $temp->total = 0;

            $_SESSION['tempShippingFee'] = $temp;
        } else {
            $_SESSION['tempShippingFee'] = $fee;
        }
    }

    public static function getTempShippingFee()
    {
        if (isset($_SESSION['tempShippingFee'])) {
            return $_SESSION['tempShippingFee'];
        } else {
            return null;
        }
    }

    public static function setSelectedVouchers($vouchers)
    {
        $_SESSION['selectedVouchers'] = $vouchers;
    }

    public static function getSelectedVouchers()
    {
        if (isset($_SESSION['selectedVouchers'])) {
            return $_SESSION['selectedVouchers'];
        } else {
            return null;
        }
    }

    public static function setPickupAtRestaurant($pickup)
    {
        $_SESSION['pickupAtRestaurant'] = $pickup;
    }

    public static function getPickupAtRestaurant()
    {
        if (isset($_SESSION['pickupAtRestaurant'])) {
            return $_SESSION['pickupAtRestaurant'];
        } else {
            return 0;
        }
    }

    public static function setFlagRedirectFromHome()
    {
        $_SESSION['redirectFromHome'] = true;
    }

    public static function getFlagRedirectFromHome()
    {
        if (isset($_SESSION['redirectFromHome'])) {
            return $_SESSION['redirectFromHome'];
        } else {
            return false;
        }
    }

    /**
     * idea is: order price without shipping fee;
     * after calculate shipping fee (with extra if has);
     * plus shipping fee (price; tax) to total
     *
     * @param null  $cart
     * @param array $options
     *
     * @return \stdClass
     */
    public static function calculateCartTotals($cart = null, $options = [])
    {
        // cart
        if ($cart === null) {
            $cart = WC()->cart;
        }

        $currentCategory = Helper::getCurrentCategory();
        $categoryId = $currentCategory->term_id;
        $brandId = get_field('product_category_brand_id', 'product_cat_' . $categoryId);

        // selected vouchers
        $selectedVouchers = \GDelivery\Libs\Helper\Helper::getSelectedVouchers();
        $totalDiscount = 0;
        $totalCashVoucher = 0;
        if ($selectedVouchers) {
            foreach ($selectedVouchers as $selectedVoucher) {
                if ($selectedVoucher->type == 1) {
                    $totalCashVoucher += $selectedVoucher->denominationValue;
                } else {
                    $totalDiscount += $selectedVoucher->denominationValue;
                }
            }
        }

        // total
        $totals = new \stdClass();

        $baseTotalPrice = (float) $cart->get_subtotal(); // base on real item first
        $totalPrice = $baseTotalPrice;
        $totalTax = Cart::taxTotal($cart, $selectedVouchers);

        $totalShippingFee = 0;
        if ($cart->get_subtotal()) {

            if (isset($options['pickupAtRestaurant']) && $options['pickupAtRestaurant'] == 1) {
                \GDelivery\Libs\Helper\Helper::setTempShippingFee(0);
            } elseif (isset($options['recalculateShippingFee']) && $options['recalculateShippingFee'] === true) {
                if (isset($options['shippingVendor'])) {
                    $calculateShippingFee = self::calculateShippingFee2(
                        self::getSelectedAddress(),
                        self::getSelectedRestaurant()->restaurant,
                        [
                            'shippingVendor' => $options['shippingVendor'],
                            'brandId' => $brandId
                        ]
                    );
                } else {
                    $calculateShippingFee = self::calculateShippingFee(
                        self::getSelectedAddress(),
                        self::getSelectedRestaurant()->restaurant,
                        [
                            'brandId' => $brandId
                        ]
                    );
                }

                if ($calculateShippingFee->messageCode === Message::SUCCESS) {
                    $totalShippingFee = $calculateShippingFee->result->total;
                    \GDelivery\Libs\Helper\Helper::setTempShippingFee($calculateShippingFee->result);
                } else {
                    $totalShippingFee = 0;
                }
            } else {
                $totalShippingFee = self::getTempShippingFee() ? self::getTempShippingFee()->total : 0;
            }
        } else {
            $totalShippingFee = 0;
        }

        // check shipping fee
        $totals->shippingFee = $totalShippingFee;
        if ($totalShippingFee > 0) {
            $shippingObject = self::getTempShippingFee();
            $totalPrice += $shippingObject->price; // plus shipping price
            $totalTax += $shippingObject->tax; // plus shipping tax.
        } else {
            \GDelivery\Libs\Helper\Helper::setTempShippingFee(0);
            $shippingObject = self::getTempShippingFee();
        }

        $tempTotal = $totalPrice - $totalDiscount + $totalTax;

        // check in case COD and restaurant shipping via Grab Express, recalculate shipping fee
        if (
            self::getSelectedRestaurant()
            && self::getSelectedRestaurant()->allowGrabExpress == 1
            && isset($options['paymentMethod'], $options['shippingVendor'])
            && $options['paymentMethod'] == 'COD'
            && $options['shippingVendor'] == 'grab_express'
        ) {
            if ($cart->get_subtotal()) {
                // re-define/calculate shipping fee and total, point is < 995k --> plus 5k; > 995k --> plus 8k for grab case
                if ($tempTotal < 995000) {
                    $extraCodFee = 5000;
                } elseif ($tempTotal >= 950000 && $tempTotal <= 2000000) {
                    $extraCodFee = 8000;
                } else {
                    $extraCodFee = 0;
                }

                // calculate extra shipping fee
                $category = self::getCurrentCategory();
                $brandId = get_field('product_category_brand_id', 'product_cat_' . $category->term_id);
                $shippingTax = self::getShippingTax($brandId);
                $extraPrice = ceil($extraCodFee/(1 + $shippingTax));
                $extraTax = ceil($extraPrice * $shippingTax);
                $extraFee = $extraPrice + $extraTax;

                // Update total tax.
                $totalTax += $extraTax; // plus extra tax.

                // re-calculate and assign to total
                $totalPrice = $baseTotalPrice + $shippingObject->price + $extraPrice;
                $tempTotal = $totalPrice - $totalDiscount + $totalTax;

                // set shipping fee to session
                $temp = self::getTempShippingFee();
                $temp->price += $extraPrice;
                $temp->tax += $extraTax;
                $temp->total += $extraFee;
                \GDelivery\Libs\Helper\Helper::setTempShippingFee($temp);
            }
        } // end if re-calculate

        // shipping object
        $totals->shipping = self::getTempShippingFee();

        if ($tempTotal < 0) {
            $tempTotal = 0;
        }

        // total bill value
        $totals->total = $tempTotal;
        $totals->totalPrice = $totalPrice;
        $totals->totalPriceWithoutShipping = $totalPrice - $totals->shipping->price;
        $totals->totalTax = $totalTax;
        $totals->totalDiscount = $totalDiscount;
        $totals->totalCashVoucher = $totalCashVoucher;

        // pay sum
        $totals->totalPaySum = $totals->total - $totalCashVoucher;
        if ($totals->totalPaySum < 0) {
            $totals->totalPaySum = 0;
        }

        return $totals;
    }

    public static function clearDataAfterCheckout()
    {
        // clear cart
        WC()->cart->empty_cart();

        // clear all session and data
        unset(
            $_SESSION['selectedAddress'],
            $_SESSION['selectedRestaurant'],
            $_SESSION['tempShippingFee'],
            $_SESSION['currentCategory'],
            $_SESSION['selectedVouchers'],
            $_SESSION['deliveryInfo'],
            $_SESSION['selectedProvince']
        );

        // clear utm data
        setcookie("utm_source", "", time() - 3600);
        setcookie("utm_medium", "", time() - 3600);
        setcookie("utm_campaign", "", time() - 3600);
        setcookie("utm_content", "", time() - 3600);
        setcookie("utm_location", "", time() - 3600);
        setcookie("utm_term", "", time() - 3600);
        setcookie("ggg_internal_affiliate", "", (time() - 86400), '/');

        // Todo check requirement
        unset($_COOKIE['mo_traffic_id']);
        setcookie('mo_traffic_id', null, -1, '/');
        unset($_COOKIE['mo_utm_source']);
        setcookie('mo_utm_source', null, -1, '/');
    }

    /***
     * @param \WC_Order $order
     * @param array $options
     *
     * @return \stdClass
     */
    public static function calculateOrderTotals($order, $options = [])
    {
        // selected vouchers
        $selectedVouchers = $order->get_meta('selected_vouchers');
        $totalDiscount = 0;
        $totalCashVoucher = 0;
        if ($selectedVouchers) {
            foreach ($selectedVouchers as $selectedVoucher) {
                if ($selectedVoucher->type == 1) {
                    $totalCashVoucher += $selectedVoucher->denominationValue;
                } else {
                    $totalDiscount += $selectedVoucher->denominationValue;
                }
            }
        }

        $totals = new \stdClass();
        $basePriceTotal = (float) $order->get_subtotal(); // base on real item
        $totalPrice = $basePriceTotal;

        $totalTax = Order::totalTax($order, $totalDiscount);
        $shippingObj = new \stdClass();

        $restaurant = $order->get_meta('restaurant_in_tgs');
        $customerAddress = $order->get_meta('customer_selected_address');

        $totalShippingFee = 0;
        /*if (isset($options['pickupAtRestaurant']) && $options['pickupAtRestaurant'] == 1) {
            $totalShippingFee = 0;
            $tempTotal = $totalPrice - $totalDiscount + $totalTax;

            // shipping object
            $shippingObj->price = 0;
            $shippingObj->tax = 0;
            $shippingObj->total = 0;
        } else {
            $categoryId = $order->get_meta('current_product_category_id');
            $brandId = get_field('product_category_brand_id', 'product_cat_' . $categoryId);
            $calculateShippingFee = self::calculateShippingFee2(
                $customerAddress,
                $restaurant->restaurant,
                [
                    'shippingVendor' => $options['shippingVendor'] ?? null,
                    'brandId' => $brandId
                ]
            );

            if ($calculateShippingFee->messageCode === Message::SUCCESS) {
                $shippingObj = $calculateShippingFee->result;
            } else {
                $shippingObj->price = 0;
                $shippingObj->tax = 0;
                $shippingObj->total = 0;
            }

            $totalPrice += $shippingObj->price;
            $totalTax += $shippingObj->tax;

            $tempTotal = $totalPrice - $totalDiscount + $totalTax;
        }*/

        $shippingObj->price = 0;
        $shippingObj->tax = 0;
        $shippingObj->total = 0;

        $totalPrice += $shippingObj->price;
        $totalTax += $shippingObj->tax;

        $tempTotal = $totalPrice - $totalDiscount + $totalTax;

        // check in case COD and restaurant shipping via Grab Express, recalculate shipping fee
        /*
        if (
            $restaurant
            && $restaurant->allowGrabExpress == 1
            &&isset($options['paymentMethod'], $options['shippingVendor'])
            && $options['paymentMethod'] == 'COD'
            && $options['shippingVendor'] == 'grab_express'
            && isset($options['pickupAtRestaurant'])
            && $options['pickupAtRestaurant'] != 1
        ) {
            // re-define/calculate shipping fee and total, point is < 995k --> plus 5k; > 995k --> plus 8k for grab case
            if ($tempTotal < 995000) {
                $extraCodFee = 5000;
            } elseif ($tempTotal >= 950000 && $tempTotal <= 2000000) {
                $extraCodFee = 8000;
            } else {
                $extraCodFee = 0;
            }

            $shippingTax = self::getShippingTax();
            // calculate extra shipping fee
            $extraTax = ceil($extraCodFee/(1 + $shippingTax) * $shippingTax);
            $extraPrice = ceil($extraCodFee/(1 + $shippingTax));
            $extraFee = $extraPrice + $extraTax;

            // Update total tax.
            $totalTax += $shippingObj->tax; // plus shipping tax.
            $totalTax += $extraTax; // plus extra tax.

            // re-calculate and assign to total
            $totalPrice = $totalPrice + $extraPrice;
            $tempTotal = $tempTotal + $extraFee;

            // set shipping fee to session
            $shippingObj->price += $extraPrice;
            $shippingObj->tax += $extraTax;
            $shippingObj->total += $extraFee;
        } // end if re-calculate
        */

        // shipping object
        $totals->shipping = $shippingObj;
        $totals->shippingFee = $shippingObj->total;

        $totals->totalPrice = $totalPrice;
        $totals->totalTax = $totalTax;
        $totals->totalDiscount = $totalDiscount;
        $totals->totalCashVoucher = $totalCashVoucher;
        $totals->totalPriceWithoutShipping = $totals->totalPrice - $totals->shipping->price;

        // total amount
        if ($tempTotal < 0) {
            $totals->total = 0;
        } else {
            $totals->total = $tempTotal;
        }

        // set tax is 0
        if ($totals->totalTax < 0) {
            $totals->totalTax = 0;
        }

        // total pay sum
        $totals->totalPaySum = $totals->total - $totalCashVoucher;
        if ($totals->totalPaySum < 0) {
            $totals->totalPaySum = 0;
        }

        return $totals;
    }

    /***
     * @param \WC_Order $order
     *
     * @return \stdClass
     */
    public static function orderTotals($order)
    {
        $totals = new \stdClass();

        // selected vouchers
        $order = wc_get_order($order->get_id());
        $selectedVouchers = $order->get_meta('selected_vouchers');
        $totalDiscount = 0;
        $totalCashVoucher = 0;
        if ($selectedVouchers) {
            foreach ($selectedVouchers as $selectedVoucher) {
                if ($selectedVoucher->type == 1) {
                    $totalCashVoucher += $selectedVoucher->denominationValue;
                } else {
                    $totalDiscount += $selectedVoucher->denominationValue;
                }
            }
        }

        $totals->totalPrice = (float) $order->get_meta('total_price');
        $totals->totalDiscount = $totalDiscount;
        $totals->totalTax = (float) $order->get_meta('total_tax');
        $totals->totalCashVoucher = $totalCashVoucher;

        // shipping object
        $shippingObj = new \stdClass();
        $shippingObj->price = (float) $order->get_meta('shipping_price');

        $items = $order->get_items();
        if($items) {
            $firstItem = array_shift($items);
            $productId = $firstItem->get_product_id();
            $category = Category::getCurrentCategoryFromProductId($productId);
            if ($category) {
                $brandId = get_field('product_category_brand_id', 'product_cat_' . $category->term_id);
                $shippingObj->tax = $shippingObj->price * self::getShippingTax($brandId);
            } else {
                $shippingObj->tax = $shippingObj->price * Config::SHIPPING_TAX;
            }
        } else {
            $shippingObj->tax = 0;
        }
        $shippingObj->total = (float) $order->get_shipping_total('number');
        $shippingObj->distance = (double) $order->get_shipping_total('shipping_distance');
        $totals->shipping = $shippingObj;
        // total amount
        $totals->total = $totals->totalPrice - $totalDiscount + (float) $totals->totalTax;
        $totals->totalPaySum = $totals->total - $totalCashVoucher;
        $totals->totalPriceWithoutShipping = $totals->totalPrice - $totals->shipping->price;

        if ($totals->totalPaySum < 0) {
            $totals->totalPaySum = 0;
        }

        return $totals;
    }


    public static function textRecentOrderTime($timestamp)
    {
        $current = time();
        $diff = $current - $timestamp;

        if ($diff < 60) {
            $textTime = '1 phút trước';
        } elseif ($diff >= 60 && $diff < 3600) {
            $textTime = floor($diff/60) .' phút trước';
        } elseif ($diff >= 3600 && $diff < (43200)) {
            $hours = floor($diff/3600);
            $mins = floor(($diff - ($hours * 3600))/60);

            $textTime = "{$hours} giờ {$mins} phút trước";
        } elseif ($diff >= (43200)) {
            $textTime = gmdate('d/m/y H:i', $timestamp + 7 * 3600);
        }

        return $textTime;
    }

    public static function textPaymentMethod($paymentMethod)
    {
        switch ($paymentMethod) {
            case 'COD' :
                $text = 'Thanh toán khi nhận hàng';
                break;
            case 'VNPAY' :
                $text = 'Thanh toán VnPay';
                break;
            case 'ZALOPAY' :
                $text = 'Thanh toán ZaloPay';
                break;
            case 'VINID' :
                $text = 'Thanh toán VinID';
                break;
            case 'SHOPEE_PAY':
                $text = 'Thanh toán ShopeePay';
                break;
            case 'VNPAY_BANK_ONLINE' :
            case 'VNPAY_BANK_ONLINE_INTERNATIONAL_CARD' :
                $text = 'Thẻ ngân hàng - Vnpay';
                break;
            case 'VNPT_EPAY_BANK_ONLINE' :
                $text = 'Thẻ ngân hàng - VNPT ePay';
                break;
            case Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME :
                $text = 'G-Business';
                break;
            default :
                $text = $paymentMethod;
        }

        return  $text;
    }

    public static function rkPaymentCode($paymentMethod)
    {
        switch ($paymentMethod) {
            case 'VNPAY' :
            case 'VNPAY_BANK_ONLINE' :
            case 'VNPAY_BANK_ONLINE_INTERNATIONAL_CARD' :
                $text = 991111;
                break;
            case 'ZALOPAY' :
                $text = 991112;
                break;
            case 'VINID' :
                $text = 991115;
                break;
            case 'MOMO' :
                $text = 991114;
                break;
            case 'SHOPEE_PAY' :
                $text = 991117;
                break;
            case 'VNPT_EPAY_BANK_ONLINE' :
                $text = 991118;
                break;
            case Config::PAYMENT_HUB_BIZ_ACCOUNT_WALLET_NAME :
                $text = Config::PAYMENT_HUB_BIZ_ACCOUNT_PAYMENT_CODE;
                break;
            default :
                $text = 1;
        }

        return  $text;
    }

    public static function getMerchantByCode($restaurantCode, $options = [])
    {
        $args = [
            'post_type' => self::$merchantPostType,
            'showposts' => 999,
            'meta_query' => [
                [
                    'key'     => 'restaurant_code',
                    'value'   => $restaurantCode,
                    'compare' => '='
                ],
            ]
        ];

        $getMerchant = new \WP_Query($args);

        $res = new Result();
        if ($getMerchant->have_posts()) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = self::getMerchantInfo($getMerchant->posts[0], $options);
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Không tồn tại nhà hàng';
        }

        return $res;
    }

    public static function getMerchant($id, $options = [])
    {
        $args = [
            'post_type' => self::$merchantPostType,
            'p' => $id,
        ];
        if (isset($options['post_status']) && $options['post_status']) {
            $args['post_status'] = [
                'publish',
                'pending',
                'draft',
                'auto-draft',
                'future',
                'private',
                'inherit',
                'trash'
            ];
        } else {
            $args['post_status'] = 'publish';
        }
        $getMerchant = new \WP_Query($args);

        $res = new Result();
        if ($getMerchant->have_posts()) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = self::getMerchantInfo($getMerchant->posts[0], $options);
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Không tồn tại nhà hàng';
        }

        return $res;
    }

    public static function getMerchantCategory($merchantId, $options = [])
    {
        $res = new Result();
        $getMerchantCategory = get_terms([
            'taxonomy' => 'merchant-category',
            'hide_empty' => true,
            'meta_query' => [
                [
                    'key' => 'merchant_id',
                    'value' => $merchantId
                ],
                [
                    'key' => 'is_active',
                    'value' => true
                ]
            ],
            'orderby' => 'meta_value_num',
            'meta_key' => 'order',
            'order' => 'ASC',
        ]);

        if ($getMerchantCategory) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';

            $arrCategory = [];
            $parentCategory = [];
            $childCategory = [];
            foreach ($getMerchantCategory as $category) {
                if ($category->parent) {
                    $childCategory[$category->parent][] = MerchantCategory::convertToStdClass($category);
                } else {
                    $parentCategory[] = MerchantCategory::convertToStdClass($category);
                }
            }
            foreach ($parentCategory as $category) {
                $tempCategory = $category;
                if (isset($childCategory[$category->id])) {
                    $tempCategory->child = $childCategory[$category->id];
                }
                $arrCategory[] = $tempCategory;
            }

            $res->result = $arrCategory;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Không tồn tại category';
        }

        return $res;
    }

    public static function getEComCategory($params)
    {
        $res = new Result();

        if (!isset($params['type'])) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Phải chọn loại danh mục';

            return $res;
        }

        $categoryType = get_term_by('slug', $params['type'], 'ecom-category');

        if ($categoryType) {
            $getEComCategory = get_terms([
                'taxonomy' => 'ecom-category',
                'hide_empty' => true,
                'meta_query' => [
                    [
                        'key' => 'is_active',
                        'value' => true
                    ]
                ],
                'orderby' => 'meta_value_num',
                'meta_key' => 'order',
                'order' => 'ASC',
                'parent' => $categoryType->term_id
            ]);

            if ($getEComCategory) {
                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';

                $arrCategory = [];
                foreach ($getEComCategory as $category) {
                    $arrCategory[] = EComCategory::convertToStdClass($category);
                }

                $res->result = $arrCategory;
            } else {
                $res->messageCode = Message::NOT_FOUND;
                $res->message = 'Không tồn tại category';
            }
        }




        return $res;
    }

    public static function getRestaurantsInCategory($categoryId = 0, $options = [])
    {
        $args = [
            'post_type' => self::$merchantPostType,
            'showposts' => 999,
            'post_status' => 'publish'
        ];

        if ($categoryId) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'product_cat',
                    'field' => 'id',
                    'terms' => $categoryId,
                ],
            ];
        }

        $arrRestaurants = [];
        $loop = new \WP_Query($args);

        $startTime = microtime(true);
        foreach ($loop->posts as $onePost) {
            $arrRestaurants[] = self::getMerchantInfo($onePost, $options);
        }
        wp_reset_query();

        $res = new Result();
        if ($arrRestaurants) {
            // sort
            usort(
                $arrRestaurants,
                function ($a, $b) {
                    if (isset($a->restaurant, $b->restaurant) && $a->restaurant && $a->restaurant) {
                        return $a->restaurant->distance > $b->restaurant->distance;
                    } else {
                        return false;
                    }
                }
            );
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $arrRestaurants;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Ko có dữ liệu nhà hàng';
        }

        return $res;
    }

    public static function getMerchantsInProvince($provinceId = 0, $options = [])
    {
        $perPage = $options['perPage'] ?? 8;
        $page = $options['page'] ?? 1;
        $args = [
            'post_type' => self::$merchantPostType,
            'posts_per_page' => $perPage,
            'paged' => $page,
            'post_status' => 'publish'
        ];
        $metaQuery = [];

        if ($provinceId) {
            $metaQuery[] = [
                'key' => 'province_id',
                'value' => $provinceId,
                'compare' => '=',
            ];
        }
        if (isset($options['merchantType'])) {
            $metaQuery[] = [
                'key' => 'merchant_type',
                'value' => $options['merchantType'],
                'compare' => '=',
            ];
        }
        if (isset($options['conceptIds'])) {
            $conceptIds = explode(',', $options['conceptIds']);
            if (count($conceptIds) > 1) {
                $conceptConditions = [
                    'relation' => 'OR',
                ];
                foreach ($conceptIds as $conceptId) {
                    $conceptConditions[] = [
                        'key' => 'concept_id',
                        'value' => $conceptId,
                        'compare' => 'LIKE',
                    ];
                }
                $metaQuery[] = $conceptConditions;
            } else {
                $metaQuery[] = [
                    'key' => 'concept_id',
                    'value' => $options['conceptIds'],
                    'compare' => 'LIKE',
                ];
            }

        }
        if (isset($options['brandId'])) {
            $metaQuery[] = [
                'key' => 'brand_id',
                'value' => $options['brandId'],
                'compare' => '=',
            ];
        }

        if (!empty($metaQuery)) {
            if (count($metaQuery) > 1) {
                array_unshift($metaQuery, ['relation' => 'AND']);
            }
            $args['meta_query'] = $metaQuery;
        }

        if (isset($options['filterType']) && $options['filterType'] === 'popular') {
            $args['meta_key'] = 'number_of_order';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        }
        $needSortDistance = isset($options['filterType'], $options['fromLatitude'], $options['fromLongitude']) && $options['filterType'] === 'nearest';
        if ($needSortDistance) {
            $args['posts_per_page'] = -1; // get all for distance sort
        }
        $arrMerchants = [];
        $loop = new \WP_Query($args);

        $startTime = microtime(true);
        foreach ($loop->posts as $onePost) {
            $arrMerchants[] = self::getMerchantInfo($onePost, $options);
        }
        wp_reset_query();

        $res = new Result();
        if ($arrMerchants) {
            if ($needSortDistance) {
                usort(
                    $arrMerchants,
                    function ($a, $b) {
                        $aDistance = isset($a->restaurant) && $a->restaurant ? $a->restaurant->distance : 999999999;
                        $bDistance = isset($b->restaurant) && $b->restaurant ? $b->restaurant->distance : 999999999;
                        if ($aDistance == $bDistance) {
                            return 0;
                        }
                        return $aDistance > $bDistance ? 1 : -1;
                    }
                );
            }
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $arrMerchants;
            $res->lastPage = $loop->max_num_pages;
            $res->currentPage = $page;
            $res->total = $loop->found_posts;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Ko có dữ liệu nhà hàng';
        }

        return $res;
    }

    public static function getNearestRestaurant($categoryId, $address)
    {
        $res = new Result();

        if ((is_string($address))) {
            // if pass string address
            // find with google map
            try {
                $locationService = new Location();
                $getGoogleAddress = $locationService->getGoogleMapAddress($address);
                if ($getGoogleAddress->messageCode == Message::SUCCESS) {
                    $selectedAddress = $getGoogleAddress->result[0];
                } else {
                    $selectedAddress = null;
                }
            } catch (\Exception $e) {
                $selectedAddress = null;
            }
        } else {
            $selectedAddress = $address;
        }

        $params['fromLongitude'] = $selectedAddress->longitude;
        $params['fromLatitude'] = $selectedAddress->latitude;

        // get allow restaurant in category
        $getRestaurantsInCategory = self::getRestaurantsInCategory($categoryId, $params);
        if ($getRestaurantsInCategory->messageCode == Message::SUCCESS) {
            // list restaurant in category
            $listRestaurants = $getRestaurantsInCategory->result;

            usort(
                $listRestaurants,
                function ($restaurantOne, $restaurantTwo) {
                    if (isset($restaurantOne->restaurant, $restaurantTwo->restaurant) && $restaurantOne->restaurant && $restaurantTwo->restaurant) {
                        return ($restaurantOne->restaurant->distance > $restaurantTwo->restaurant->distance);
                    } else {
                        return false;
                    }
                }
            );

            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $listRestaurants[0];
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Chưa khai báo nhà hàng';
        }

        return $res;
    }

    public static function calculateShippingFee($customerAddress, $restaurant, $options = [])
    {
        $logger = new Logger('grab-quote');
        $logger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/grab-quote-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
        $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $getRestaurantInfo = self::getRestaurant($restaurant->code);
        $dataGrabAuth = self::getGrabAuthentication();

        $res = new Result();
        $brandId = '';
        if (isset($options['brandId'])) {
            $brandId = $options['brandId'];
        }
        if ($getRestaurantInfo->result->allowGrabExpress == 1 && isset($dataGrabAuth)) {
            $startRequestTime = microtime(true);

            try {
                $ge = new \GGGGESKD\Service\GrabExpress($dataGrabAuth);

                $orderId = \uniqid();

                $service = new \GGGGESKD\Abstraction\Object\Service(
                    [
                        'type' => \GGGGESKD\Abstraction\Object\Service::TYPE_INSTANT
                    ]
                );

                // packages
                $package = new \GGGGESKD\Abstraction\Object\Package(
                    [
                        'name' => 'Vận chuyển đơn hàng cho khách hàng ',
                        'description' => 'Vận chuyển đơn hàng cho khách hàng ',
                        'price' => 0,
                        'height' => 0,
                        'width' => 0,
                        'depth' => 0,
                        'weight' => 0
                    ]
                );

                // sender - origin
                $sender = new \GGGGESKD\Abstraction\Object\People(
                    [
                        'address' => new \GGGGESKD\Abstraction\Object\Address(
                            [
                                'address' => $restaurant->address,
                                'longitude' => $restaurant->longitude,
                                'latitude' => $restaurant->latitude
                            ]
                        )
                    ]
                );

                // recipient - destination
                $recipient = new \GGGGESKD\Abstraction\Object\People(
                    [
                        'address' => new \GGGGESKD\Abstraction\Object\Address(
                            [
                                'address' => $customerAddress->address,
                                'longitude' => $customerAddress->longitude,
                                'latitude' => $customerAddress->latitude
                            ]
                        )
                    ]
                );

                // use package symfony/serializer
                $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

                // start log
                $logger->info("Request Quote: Service: ".json_encode($serializer->normalize($service))."; Package: ".json_encode($serializer->normalize([$package]))."; Sender: ".json_encode($serializer->normalize($sender))."; Recipient: ".json_encode($serializer->normalize($recipient)).";");

                // request
                $quoteData = $ge->quote($orderId, $service, [$package], $sender, $recipient);

                // response log
                $logger->info((microtime(true) - $startRequestTime)."||||Quote Data: Amount: {$quoteData->getAmount()}{$quoteData->getCurrency()->getSymbol()}; Distance: {$quoteData->getDistance()}m; quoteData: ".json_encode($serializer->normalize($quoteData)).";");

                $temp = new \stdClass();

                $extraAmount = 0;
                if (isset($options['extraPriceFee'])) {
                    $extraAmount = $options['extraPriceFee'];
                }

                // cause grab quote amount include tax, do need to re-calculate price/tax and total
                // for all number is rounded, recalculate tax first, round tax up
                // calculate price base on rounded tax
                // and total is rounded price + rounded tax
                $shippingTax = self::getShippingTax($brandId);
                $price = ceil(($quoteData->getAmount() / (1 + $shippingTax)) + $extraAmount);
                $temp->price = $price;
                $temp->tax = ceil($price * $shippingTax);
                $temp->total = $temp->price + $temp->tax;
                $temp->actualAmountIncludeTax = $quoteData->getAmount(); // also include actual amount include tax
                $temp->distance = $quoteData->getDistance();

                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $temp;
            } catch (\GGGGESKD\Exception\ApiRequestException $e) {
                if ($e->getResponse())
                {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to get quote: '.$e->getMessage().'|||| Response Body: '.$e->getResponse()->getBody()->getContents();
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to get quote: '.$e->getMessage().'|||| Response Body: '.$e->getRequest();
                }
                $logger->error((microtime(true) - $startRequestTime)."||||Request Quote; ApiException: {$e->getMessage()}");
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to get quote: '.$e->getMessage();
                $logger->error((microtime(true) - $startRequestTime)."||||Request Quote; Exception: {$e->getMessage()}");
            }
        } else {
            // calculate base on distance
            $locationService = new Location();

            // calculate distance
            $calculate = $locationService->vincentyGreatCircleDistance(
                $customerAddress->latitude,
                $customerAddress->longitude,
                $restaurant->latitude,
                $restaurant->longitude
            );

            if ($calculate->messageCode == Message::SUCCESS) {
                $distance = $calculate->result;
                $fee = 0;

                if ($restaurant->regionName == 'Ha Noi') {
                    if ($distance > 15000) {
                        $fee = 90000;
                    } else if ($distance > 14000) {
                        $fee = 85500;
                    } else if ($distance > 13000) {
                        $fee = 81000;
                    } else if ($distance > 12000) {
                        $fee = 76500;
                    } else if ($distance > 11000) {
                        $fee = 72000;
                    } else if ($distance > 10000) {
                        $fee = 67500;
                    } else if ($distance > 9000) {
                        $fee = 63000;
                    } else if ($distance > 8000) {
                        $fee = 58500;
                    } else if ($distance > 7000) {
                        $fee = 54000;
                    } else if ($distance > 6000) {
                        $fee = 49500;
                    } else if ($distance > 5000) {
                        $fee = 45000;
                    } else if ($distance > 4000) {
                        $fee = 40500;
                    } else if ($distance > 3000) {
                        $fee = 36000;
                    } else if ($distance > 2000) {
                        $fee = 31500;
                    } else if ($distance > 0) {
                        $fee = 27000;
                    }
                } elseif ($restaurant->regionName == 'Ho Chi Minh') {
                    $distance += ($distance * 0.4);

                    if ($distance > 15000) {
                        $fee = 80000;
                    } else if ($distance > 14000) {
                        $fee = 70000;
                    } else if ($distance > 13000) {
                        $fee = 70000;
                    } else if ($distance > 12000) {
                        $fee = 70000;
                    } else if ($distance > 11000) {
                        $fee = 65000;
                    } else if ($distance > 10000) {
                        $fee = 60000;
                    } else if ($distance > 9000) {
                        $fee = 55000;
                    } else if ($distance > 8000) {
                        $fee = 50000;
                    } else if ($distance > 7000) {
                        $fee = 45000;
                    } else if ($distance > 6000) {
                        $fee = 40000;
                    } else if ($distance > 5000) {
                        $fee = 35000;
                    } else if ($distance > 4000) {
                        $fee = 30000;
                    } else if ($distance > 3000) {
                        $fee = 20000;
                    } else if ($distance > 0) {
                        $fee = 20000;
                    }
                } else {
                    $fee = 0;
                }

                $temp = new \stdClass();
                $temp->price = $fee;
                $temp->tax = $fee * self::getShippingTax($brandId);
                $temp->total = $temp->price + $temp->tax;
                $temp->distance = $distance;

                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $temp;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $calculate->message;
            }
        }
        return $res;
    }

    public static function calculateShippingFee2($customerAddress, $restaurant, $option = [])
    {
        $getRestaurantInfo = self::getRestaurant($restaurant->code);
        $dataGrabAuth = self::getGrabAuthentication();

        $res = new Result();
        $brandId = '';
        if (isset($option['brandId'])) {
            $brandId = $option['brandId'];
        }
        if (
            $getRestaurantInfo->result
            && $getRestaurantInfo->result->allowGrabExpress == 1
            && isset($dataGrabAuth, $option['shippingVendor'])
            && $option['shippingVendor'] == 'grab_express'
        ) {
            $logger = new Logger('grab-quote');
            $logger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/grab-quote-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
            $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));

            $startRequestTime = microtime(true);

            try {
                $ge = new \GGGGESKD\Service\GrabExpress($dataGrabAuth);

                $orderId = \uniqid();

                $service = new \GGGGESKD\Abstraction\Object\Service(
                    [
                        'type' => \GGGGESKD\Abstraction\Object\Service::TYPE_INSTANT
                    ]
                );

                // packages
                $package = new \GGGGESKD\Abstraction\Object\Package(
                    [
                        'name' => 'Vận chuyển đơn hàng cho khách hàng ',
                        'description' => 'Vận chuyển đơn hàng cho khách hàng ',
                        'price' => 0,
                        'height' => 0,
                        'width' => 0,
                        'depth' => 0,
                        'weight' => 0
                    ]
                );

                // sender - origin
                $sender = new \GGGGESKD\Abstraction\Object\People(
                    [
                        'address' => new \GGGGESKD\Abstraction\Object\Address(
                            [
                                'address' => $restaurant->address,
                                'longitude' => $restaurant->longitude,
                                'latitude' => $restaurant->latitude
                            ]
                        )
                    ]
                );

                // recipient - destination
                $recipient = new \GGGGESKD\Abstraction\Object\People(
                    [
                        'address' => new \GGGGESKD\Abstraction\Object\Address(
                            [
                                'address' => isset($customerAddress->address) ? $customerAddress->address : $customerAddress->addressLine1,
                                'longitude' => $customerAddress->longitude,
                                'latitude' => $customerAddress->latitude
                            ]
                        )
                    ]
                );

                // use package symfony/serializer
                $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);

                // start log
                $logger->info("Request Quote: Service: ".json_encode($serializer->normalize($service))."; Package: ".json_encode($serializer->normalize([$package]))."; Sender: ".json_encode($serializer->normalize($sender))."; Recipient: ".json_encode($serializer->normalize($recipient)).";");

                // request
                $quoteData = $ge->quote($orderId, $service, [$package], $sender, $recipient);

                // response log
                $logger->info((microtime(true) - $startRequestTime)."||||Quote Data: Amount: {$quoteData->getAmount()}{$quoteData->getCurrency()->getSymbol()}; Distance: {$quoteData->getDistance()}m; quoteData: ".json_encode($serializer->normalize($quoteData)).";");

                $temp = new \stdClass();

                $extraAmount = 0;
                if (isset($option['extraPriceFee'])) {
                    $extraAmount = $option['extraPriceFee'];
                }

                // cause grab quote amount include tax, do need to re-calculate price/tax and total
                // for all number is rounded, recalculate tax first, round tax up
                // calculate price base on rounded tax
                // and total is rounded price + rounded tax
                $shippingTax = self::getShippingTax($brandId);
                $price = ceil(($quoteData->getAmount() / (1 + $shippingTax)) + $extraAmount);
                $temp->price = $price;
                $temp->tax = ceil($price * $shippingTax);
                $temp->total = $temp->price + $temp->tax;
                $temp->actualAmountIncludeTax = $quoteData->getAmount(); // also include actual amount include tax
                $temp->distance = $quoteData->getDistance();

                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $temp;
            } catch (\GGGGESKD\Exception\ApiRequestException $e) {
                if ($e->getResponse())
                {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to get quote: '.$e->getMessage().'|||| Response Body: '.$e->getResponse()->getBody()->getContents();
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Fail to get quote: '.$e->getMessage().'|||| Response Body: '.$e->getRequest();
                }
                $logger->error((microtime(true) - $startRequestTime)."||||Request Quote; ApiException: {$e->getMessage()}");
            } catch (\Exception $e) {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = 'Fail to get quote: '.$e->getMessage();
                $logger->error((microtime(true) - $startRequestTime)."||||Request Quote; Exception: {$e->getMessage()}");
            }
        } else {
            // calculate base on distance
            $locationService = new Location();

            // calculate distance
            $calculate = $locationService->vincentyGreatCircleDistance(
                $customerAddress->latitude,
                $customerAddress->longitude,
                $restaurant->latitude,
                $restaurant->longitude
            );

            if ($calculate->messageCode == Message::SUCCESS) {
                $distance = $calculate->result;
                $fee = 0;

                if ($restaurant->regionName == 'Ha Noi') {
                    if ($distance > 15000) {
                        $fee = 90000;
                    } else if ($distance > 14000) {
                        $fee = 85500;
                    } else if ($distance > 13000) {
                        $fee = 81000;
                    } else if ($distance > 12000) {
                        $fee = 76500;
                    } else if ($distance > 11000) {
                        $fee = 72000;
                    } else if ($distance > 10000) {
                        $fee = 67500;
                    } else if ($distance > 9000) {
                        $fee = 63000;
                    } else if ($distance > 8000) {
                        $fee = 58500;
                    } else if ($distance > 7000) {
                        $fee = 54000;
                    } else if ($distance > 6000) {
                        $fee = 49500;
                    } else if ($distance > 5000) {
                        $fee = 45000;
                    } else if ($distance > 4000) {
                        $fee = 40500;
                    } else if ($distance > 3000) {
                        $fee = 36000;
                    } else if ($distance > 2000) {
                        $fee = 31500;
                    } else if ($distance > 0) {
                        $fee = 27000;
                    }
                } elseif ($restaurant->regionName == 'Ho Chi Minh') {
                    $distance += ($distance * 0.4);

                    if ($distance > 15000) {
                        $fee = 80000;
                    } else if ($distance > 14000) {
                        $fee = 70000;
                    } else if ($distance > 13000) {
                        $fee = 70000;
                    } else if ($distance > 12000) {
                        $fee = 70000;
                    } else if ($distance > 11000) {
                        $fee = 65000;
                    } else if ($distance > 10000) {
                        $fee = 60000;
                    } else if ($distance > 9000) {
                        $fee = 55000;
                    } else if ($distance > 8000) {
                        $fee = 50000;
                    } else if ($distance > 7000) {
                        $fee = 45000;
                    } else if ($distance > 6000) {
                        $fee = 40000;
                    } else if ($distance > 5000) {
                        $fee = 35000;
                    } else if ($distance > 4000) {
                        $fee = 30000;
                    } else if ($distance > 3000) {
                        $fee = 20000;
                    } else if ($distance > 0) {
                        $fee = 20000;
                    }
                } else {
                    $fee = 0;
                }

                $temp = new \stdClass();
                $temp->price = $fee;
                $temp->tax = $fee * self::getShippingTax($brandId);
                $temp->total = $temp->price + $temp->tax;
                $temp->distance = $distance;

                $res->messageCode = Message::SUCCESS;
                $res->message = 'Thành công';
                $res->result = $temp;
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $calculate->message;
            }
        }
        return $res;
    }

    public static function correctProvinceName($provinceName)
    {
        $names = [
            'tp hồ chí minh' => 'Hồ Chí Minh',
            'hồ chí minh' => 'Hồ Chí Minh',
            'thành phố hồ chí minh' => 'Hồ Chí Minh',
            'hochiminh' => 'Hồ Chí Minh',
            'hanoi' => 'Hà Nội',
            'ha noi' => 'Hà Nội'
        ];

        if (isset($names[strtolower($provinceName)])) {
            return $names[strtolower($provinceName)];
        } else {
            return $provinceName;
        }
    }

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->redis = new \Predis\Client(
            [
                'scheme' => 'tcp',
                'host'   => Config::REDIS_HOST,
                'port'   => Config::REDIS_PORT,
                'password' => Config::REDIS_PASS
            ]
        );
        $this->locationService = new Location();
    }

    public static function getGrabAuthentication()
    {
        $startTime = microtime(true);

        $res = new \Abstraction\Object\Result();

        $dataGrabAuth = get_option('dataGrabAuth');

        if ($dataGrabAuth && $dataGrabAuth->getToken()->getExpiry() > time()) {
            $ge = $dataGrabAuth;
        } else {
            $logger = new Logger('grab-authentication');
            $logger->pushHandler(new StreamHandler(ABSPATH.'/logs/grab-express/grab-authentication-'.date_i18n('Y-m-d').'.log', Logger::DEBUG));
            $logger->setTimezone(new \DateTimeZone('Asia/Ho_Chi_Minh'));

            try {
                $auth = new \GGGGESKD\Service\Authentication(
                    \GDelivery\Libs\Config::GRAB_ENV,
                    [
                        'clientId' => \GDelivery\Libs\Config::GRAB_CLIENT_ID,
                        'clientSecret' => \GDelivery\Libs\Config::GRAB_CLIENT_SECRET,
                    ]
                );

                // start log
                $logger->info("Authentication:");

                // do auth
                $auth->auth();

                // result log
                $logger->info((microtime(true) - $startTime)."||||Authentication: {$auth->getToken()->getAccessToken()}");

                update_option('dataGrabAuth', $auth);

                $ge = $auth;
            } catch (\Exception $e) {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi gọi Grab Auth: '.$e->getMessage();
                $logger->error("Authentication: Error: {$res->messageCode}; Message: {$res->message};");
                $ge = null;
            }
        }

        return $ge;
    }

    public static function convertUnsignedVietnamese($str)
    {
        $coDau = array("à","á","ạ","ả","ã","â","ầ","ấ","ậ","ẩ","ẫ","ă","ằ","ắ"
        ,"ặ","ẳ","ẵ","è","é","ẹ","ẻ","ẽ","ê","ề","ế","ệ","ể","ễ","ì","í","ị","ỉ","ĩ",
            "ò","ó","ọ","ỏ","õ","ô","ồ","ố","ộ","ổ","ỗ","ơ"
        ,"ờ","ớ","ợ","ở","ỡ",
            "ù","ú","ụ","ủ","ũ","ư","ừ","ứ","ự","ử","ữ",
            "ỳ","ý","ỵ","ỷ","ỹ",
            "đ",
            "À","Á","Ạ","Ả","Ã","Â","Ầ","Ấ","Ậ","Ẩ","Ẫ","Ă"
        ,"Ằ","Ắ","Ặ","Ẳ","Ẵ",
            "È","É","Ẹ","Ẻ","Ẽ","Ê","Ề","Ế","Ệ","Ể","Ễ",
            "Ì","Í","Ị","Ỉ","Ĩ",
            "Ò","Ó","Ọ","Ỏ","Õ","Ô","Ồ","Ố","Ộ","Ổ","Ỗ","Ơ"
        ,"Ờ","Ớ","Ợ","Ở","Ỡ",
            "Ù","Ú","Ụ","Ủ","Ũ","Ư","Ừ","Ứ","Ự","Ử","Ữ",
            "Ỳ","Ý","Ỵ","Ỷ","Ỹ",
            "Đ","ê","ù","à");
        $khongDau = array("a","a","a","a","a","a","a","a","a","a","a"
        ,"a","a","a","a","a","a",
            "e","e","e","e","e","e","e","e","e","e","e",
            "i","i","i","i","i",
            "o","o","o","o","o","o","o","o","o","o","o","o"
        ,"o","o","o","o","o",
            "u","u","u","u","u","u","u","u","u","u","u",
            "y","y","y","y","y",
            "d",
            "A","A","A","A","A","A","A","A","A","A","A","A"
        ,"A","A","A","A","A",
            "E","E","E","E","E","E","E","E","E","E","E",
            "I","I","I","I","I",
            "O","O","O","O","O","O","O","O","O","O","O","O"
        ,"O","O","O","O","O",
            "U","U","U","U","U","U","U","U","U","U","U",
            "Y","Y","Y","Y","Y",
            "D","e","u","a");

        return str_replace($coDau,$khongDau,$str);
    } // end cover unsigned vietnamese

    // Config shippingTax
    public static function getShippingTax($brandId = '')
    {
        if ($brandId == Config::BRAND_IDS['icook']) {
            return (float) (get_option('tax_shipping_fee_icook') / 100);
        }

        return (float) (get_option('tax_shipping_fee') / 100);
    }

    public static function restaurantOpenTime($categoryId)
    {
        $openTime1 = get_field('product_category_open_time_1', 'product_cat_'.$categoryId) ?: '09:00';
        $openTime1Obj = date_i18n('Y-m-d').' '.$openTime1;
        $closeTime1 = get_field('product_category_close_time_1', 'product_cat_'.$categoryId) ?: '22:00';
        $closeTime1Obj = date_i18n('Y-m-d').' '.$closeTime1;
        $openTime2 = get_field('product_category_open_time_2', 'product_cat_'.$categoryId);
        $openTime2Obj = date_i18n('Y-m-d').' '.$openTime2;
        $closeTime2 = get_field('product_category_close_time_2', 'product_cat_'.$categoryId);
        $closeTime2Obj = date_i18n('Y-m-d').' '.$closeTime2;
        $now = date_i18n('Y-m-d H:i:s');

        $strOpenCloseTime = '';
        $classOpenCloseTime = '';
        $isOpen = true;
        if ($now < $openTime1Obj) {
            $strOpenCloseTime = 'Đóng - Mở: '.$openTime1;
            $classOpenCloseTime = 'close-time';
            $isOpen = false;
        } elseif ($now >= $openTime1Obj && $now <= $closeTime1Obj) {
            $strOpenCloseTime = 'Mở - Đóng: '.$closeTime1;
        } elseif ($now > $closeTime1Obj) {
            if ($openTime2 && $closeTime2) {
                if ($now < $openTime2Obj) {
                    $strOpenCloseTime = 'Đóng - Mở: '.$openTime2;
                    $classOpenCloseTime = 'close-time';
                    $isOpen = false;
                } elseif ($now >= $openTime2Obj && $now <= $closeTime2Obj) {
                    $strOpenCloseTime = 'Mở - Đóng: '.$closeTime2;
                } elseif ($now > $closeTime1Obj) {
                    $strOpenCloseTime = 'Đóng - Mở: '.$openTime1;
                    $classOpenCloseTime = 'close-time';
                    $isOpen = false;
                }
            } else {
                $strOpenCloseTime = 'Đóng - Mở: '.$openTime1;
                $classOpenCloseTime = 'close-time';
                $isOpen = false;
            }
        }
        return [
            'isOpen' => $isOpen,
            'isOpenLabel' => $strOpenCloseTime,
            'classOpenCloseTime' => $classOpenCloseTime
        ];
    }

    public static function getMetaValue($key = '', $type = 'post', $status = 'publish')
    {
        global $wpdb;

        if (empty($key)) {
            return;
        }

        $r = $wpdb->get_col($wpdb->prepare("
                SELECT pm.meta_value FROM {$wpdb->postmeta} pm
                LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE pm.meta_key = %s 
                AND pm.meta_value != ''
                AND p.post_status = %s 
                AND p.post_type = %s
                GROUP BY pm.meta_value
            ", $key, $status, $type));

        return $r;
    }

    public static function getBrands($params = [])
    {
        $args = [
            'post_type' => 'brand',
            'showposts' => 999,
            'post_status' => 'publish'
        ];

        if (
            isset($params['provinceId'])
            && $params['provinceId']
        ) {
            $args['meta_query'] = [
                [
                    'key' => 'province_id',
                    'value' => $params['provinceId'],
                    'compare' => 'LIKE',
                ],
            ];
        }

        if (isset($params['type']) && $params['type']) {
            $args['meta_query'][] = [
                [
                    'key' => 'type',
                    'value' => $params['type'],
                    'compare' => '=',
                ],
            ];
        }
        if (isset($params['sceneId']) && $params['sceneId']) {
            $args['meta_query'][] = [
                [
                    'key' => 'sceneId',
                    'value' => $params['sceneId'],
                    'compare' => 'LIKE',
                ],
            ];
        }

        $brands = [];
        $query = new \WP_Query($args);

        foreach ($query->posts as $post) {
            $brands[] = self::getBrandInfo($post);
        }
        wp_reset_query();

        $res = new Result();
        if ($brands) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $brands;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Ko có dữ liệu';
        }

        return $res;
    }

    public static function getBrand($id)
    {
        $args = [
            'post_type' => 'brand',
            'post_status'=> 'publish',
            'p' => $id,
        ];
        $getBrand = new \WP_Query($args);

        $res = new Result();
        if ($getBrand->have_posts()) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = self::getBrandInfo($getBrand->posts[0]);
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Không tồn tại';
        }

        return $res;
    }

    public static function getBrandInfo($post)
    {
        $obj = new \stdClass();
        $obj->id = $post->ID;
        $obj->name = $post->post_title;
        $obj->logo = get_the_post_thumbnail_url($post->ID, 'shop_catalog') ?: '';

        $card = get_field('card', $post->ID);
        $obj->card = ($card && isset($card['url'])) ? $card['url'] : '';
        $obj->provinceIds = get_field('province_id', $post->ID);
        return $obj;
    }

    public static function getProvinces()
    {
        $args = [
            'post_type' => 'province',
            'showposts' => 999,
            'post_status' => 'publish'
        ];

        $provinces = [];
        $query = new \WP_Query($args);

        foreach ($query->posts as $post) {
            $obj = new \stdClass();
            $obj->id = $post->ID;
            $obj->name = $post->post_title;
            $obj->province_id = get_field('booking_province_id', $post->ID);

            $provinces[] = $obj;
        }
        wp_reset_query();

        $res = new Result();
        if ($provinces) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $provinces;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Ko có dữ liệu';
        }

        return $res;
    }

    public static function getConcepts($params = [])
    {
        $args = [
            'post_type' => 'concept',
            'showposts' => 999,
            'post_status' => 'publish',
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ];

        $concepts = [];
        $query = new \WP_Query($args);

        foreach ($query->posts as $post) {
            $concept = new \stdClass();
            $concept->id = $post->ID;
            $concept->name = $post->post_title;
            //$concept->order = $post->menu_order;
            $concept->logo = get_the_post_thumbnail_url($post->ID, 'shop_catalog');

            $concepts[] = $concept;
        }
        wp_reset_query();

        $res = new Result();
        if ($concepts) {
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Thành công';
            $res->result = $concepts;
        } else {
            $res->messageCode = Message::NOT_FOUND;
            $res->message = 'Ko có dữ liệu';
        }

        return $res;
    }

    /**
     * @param $category
     * @param $level
     * @return int|mixed
     */
    public static function getLevel($category, $level = 0)
    {
        if ($category->parent == 0) {
            return $level;
        } else {
            $level++;
            $category = get_term($category->parent);
            return self::getLevel($category, $level);
        }
    }

    /**
     * @param $categories
     * @param $level
     * @return array
     */
    public static function getCategoryByLevel($categories, $level = 0)
    {
        $output = [];
        foreach ($categories as $category) {
            if ($level == self::getLevel($category)) {
                $output[] = $category;
            }
        }

        return $output;
    }

} // end class
