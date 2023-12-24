<?php

use Abstraction\Object\Message;
use GDelivery\Libs\Helper\Response;
use \Abstraction\Object\Result;
use GDelivery\Libs\Helper\Cart;
use GDelivery\Libs\Helper\Helper;
use GDelivery\Libs\Helper\Category;

class AjaxCart extends \Abstraction\Core\AAjaxHook {

    public function __construct()
    {
        parent::__construct();

        add_action("wp_ajax_apply_voucher", [$this, "applyVoucher"]);
        add_action("wp_ajax_nopriv_apply_voucher", [$this, "mustLogin"]);

        add_action("wp_ajax_add_product_to_cart", [$this, "addToCart"]);
        add_action("wp_ajax_nopriv_add_product_to_cart", [$this, "addToCart"]);

        add_action("wp_ajax_check_product_in_cart", [$this, "checkProductCart"]);
        add_action("wp_ajax_nopriv_check_product_in_cart", [$this, "checkProductCart"]);

        add_action("wp_ajax_load_tgs_wallet", [$this, "loadTGSWallet"]);
        add_action("wp_ajax_nopriv_load_tgs_wallet", [$this, "mustLogin"]);

        add_action("wp_ajax_redirect_force", [$this, "redirectForceProduct"]);
        add_action("wp_ajax_nopriv_redirect_force", [$this, "redirectForceProduct"]);

        add_action("wp_ajax_add_and_redirect_detail_product", [$this, "addAndRedirectDetailProduct"]);
        add_action("wp_ajax_nopriv_add_and_redirect_detail_product", [$this, "addAndRedirectDetailProduct"]);

        add_action("wp_ajax_remove_cart_item", [$this, "removeItem"]);
        add_action("wp_ajax_nopriv_remove_cart_item", [$this, "removeItem"]);

        add_action("wp_ajax_list_products_in_cart", [$this, "listProductInCart"]);
        add_action("wp_ajax_nopriv_list_products_in_cart", [$this, "listProductInCart"]);

        add_action("wp_ajax_reload_cart", [$this, "reloadCart"]);
        add_action("wp_ajax_nopriv_reload_cart", [$this, "mustLogin"]);

        add_action("wp_ajax_set_selected_address", [$this, "setSelectedAddress"]);
        add_action("wp_ajax_nopriv_reload_cart", [$this, "mustLogin"]);
    }

    public function loadTGSWallet()
    {
        $res = new \Abstraction\Object\Result();
        if (wp_verify_nonce($_REQUEST['beHonest'], 'load_tgs_wallet')) {
            $currentCategory = \GDelivery\Libs\Helper\Helper::getCurrentCategory();
            $brandId = get_field('product_category_brand_id', 'product_cat_'.$currentCategory->term_id);

            $customer = \GDelivery\Libs\Helper\User::currentCustomerInfo();
            $tgsService = new \GDelivery\Libs\TGSService();
            $getVouchers = $tgsService->customerWallet($customer->customerAuthentication->token);
            if ($getVouchers->messageCode == \Abstraction\Object\Message::SUCCESS) {
                // selected voucher in cart
                $selectedVouchers = \GDelivery\Libs\Helper\Helper::getSelectedVouchers();
                $selectedVoucherCode = [];
                if ($selectedVouchers) {
                    foreach ($selectedVouchers as $one) {
                        $selectedVoucherCode[] = $one->code;
                    }
                }
                // in process selected voucher
                $processingSelectedVouchers = get_option('processing_clm_vouchers');

                $promotions = $getVouchers->result;

                $returnArr = [];
                foreach ($promotions as $promotion) {
                    foreach ($promotion->gifts as $item) {
                        if (
                            $item->status === 'available'
                            && in_array($item->type, [0, 1, 2])
                            && !in_array($item->seriNo, $selectedVoucherCode)
                            && !in_array($item->seriNo, $processingSelectedVouchers)
                        ) {
                            if ($item->type == 1) {
                                $partnerId = 14; // clm voucher
                            } else {
                                $partnerId = 8; // golden gate voucher
                            }

                            if ($brandId == 32 || $brandId == 17) {
                                // dont allow yutang to use coupon GPP from TGS
                            } else {
                                $temp = new \stdClass();
                                $temp->promotionThumbnail = $promotion->promotionThumbnail;
                                $temp->promotionTitle = $promotion->promotionTitle;
                                $temp->expiryDate = $item->expiryDate;
                                $temp->serialNo = $item->serialNo;
                                $temp->partnerId = $partnerId;

                                $returnArr[] = $temp;
                                unset($temp);
                            }
                        }
                    }
                }

                if ($returnArr) {
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $returnArr;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::NOT_FOUND;
                    $res->message = 'Bạn chưa có ưu đãi nào';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::MISSING_PARAMS;
                $res->message = 'Bạn không có mã vouchers nào.';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::MISSING_PARAMS;
            $res->message = 'Vui lòng làm mới lại trang và thử lại.';
        }
        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function applyVoucher()
    {
        $res = new \Abstraction\Object\Result();
        if (wp_verify_nonce($_REQUEST['beHonest'], 'apply_voucher')) {
            if (isset($_REQUEST['voucherCode'])) {
                // check partner if has
                if (isset($_REQUEST['partnerId']) && $_REQUEST['partnerId']) {
                    $partnerId = $_REQUEST['partnerId'];
                } else {
                    $partnerId = null;
                }

                if (isset($_REQUEST['productId']) && $_REQUEST['productId']) {
                    $productId = $_REQUEST['productId'];
                } else {
                    $productId = null;
                }

                // voucher code
                $voucherCode = $_REQUEST['voucherCode'];

                $cartTotal = \GDelivery\Libs\Helper\Helper::calculateCartTotals();

                $cartTotal->totalPrice = $cartTotal->totalPrice - $cartTotal->shipping->price;

                $selectedVouchers = \GDelivery\Libs\Helper\Helper::getSelectedVouchers();

                // check existing in list
                $existing = false;
                foreach ($selectedVouchers as $one) {
                    if ($one->code == $voucherCode) {
                        $existing = true;
                        break;
                    }
                }

                if (!$existing) {
                    $checkVoucher = \GDelivery\Libs\Helper\Voucher::validateVoucher($voucherCode, $cartTotal, $selectedVouchers, $partnerId, $productId);

                    if ($checkVoucher->messageCode == \Abstraction\Object\Message::SUCCESS) {
                        // re-assign selected voucher
                        $selectedVouchers[] = $checkVoucher->result;
                        \GDelivery\Libs\Helper\Helper::setSelectedVouchers($selectedVouchers);

                        // return
                        $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                        $res->message = $checkVoucher->message;
                        $res->result = $checkVoucher->result;
                    } elseif ($checkVoucher->messageCode == Message::NOT_VALID_DATA) {
                        $res->messageCode = \Abstraction\Object\Message::NOT_VALID_DATA;
                        $res->message = $checkVoucher->message;
                        $res->result = $checkVoucher->result;
                    } else {
                        $res->messageCode = \Abstraction\Object\Message::MISSING_PARAMS;
                        $res->message = $checkVoucher->message;
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = 'Bạn đã áp dụng voucher này vào giỏ hàng';
                }
            } else {
                $res->messageCode = \Abstraction\Object\Message::MISSING_PARAMS;
                $res->message = 'Vui lòng nhập mã voucher hợp lệ.';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::MISSING_PARAMS;
            $res->message = 'Be honest, play fair :)';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    /**
     * Ajax add product to cart
     */
    public function addToCart()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['beHonest'], $_REQUEST['productId'], $_REQUEST['quantity'])) {
            if (
                true
                //wp_verify_nonce($_REQUEST['beHonest'], "add_product_to_cart")
            ) {
                $productId = $_REQUEST['productId'];
                $quantity = $_REQUEST['quantity'];
                $addAsNew = isset($_POST['addAsNew']) ? $_POST['addAsNew'] : false;

                $res = Cart::addProductToCart($productId, $quantity, $addAsNew);
            } else {
                $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                $res->message = 'Lỗi khi kiểm tra bảo mật, vui lòng tải lại trang và thử lại.';
            }
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Cần truyền đầy đủ tham số';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function checkProductCart()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['beHonest'], $_REQUEST['productId'])) {

            $productId = $_REQUEST['productId'];
            $productFactory = new WC_Product_Factory();
            $product = $productFactory->get_product($productId);

            if ($product) {
                $cartItem = \GDelivery\Libs\Helper\Helper::productInCart($productId);
                if ($cartItem) {
                    $cart = [
                        'key' => $cartItem['key'],
                        'quantity' => $cartItem ? $cartItem['quantity'] : 0,
                        'totalPrice' => (float)($cartItem['quantity'] * $product->get_price()),
                        'textTotalPrice' => number_format($cartItem['quantity'] * $product->get_price()) . '₫',
                        'totalRegularPrice' => (float)($cartItem['quantity'] * $product->get_regular_price()),
                        'textTotalRegularPrice' => number_format($cartItem['quantity'] * $product->get_regular_price()) . '₫',
                        'totalSalePrice' => (float)($cartItem['quantity'] * $product->get_sale_price()),
                        'textTotalSalePrice' => number_format($cartItem['quantity'] * $product->get_sale_price()) . '₫',
                    ];
                    $res->messageCode = 1;
                    $res->message = 'Thành công';
                    $res->result = $cart;
                } else {
                    $res->messageCode = 0;
                    $res->message = 'Không tìm thấy sản phẩm trong giỏ hàng';
                }
            } else {
                $res->messageCode = 0;
                $res->message = 'Không tìm thấy thông tin sản phẩm';
            }

        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Cần truyền đầy đủ tham số';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    /**
     * Clear cart and add new a product
     */
    public function redirectForceProduct() {
        $res = new Result();
        try {
            WC()->cart->empty_cart();
            $productId = $_POST['productId'];
            $currentCategory = Category::getCurrentCategoryFromProductId($productId);
            Helper::setCurrentCategory($currentCategory);

            $res = Cart::addProductToCart($productId);
            Helper::setFlagRedirectFromHome();
        } catch (Exception $e) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Vui lòng thử lại';
        }

        Response::returnJson($res);
        die;
    }

    public function addAndRedirectDetailProduct() {
        $res = new Result();
        $productId = $_POST['productId'];

        if (isset($_POST['categoryId'])) {
            $categoryId = $_POST['categoryId'];
            $res = Category::checkSelectedBrand($res, $categoryId);
        } else {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Hmm, bạn chưa chọn thương hiệu';
        }

        if ($res->messageCode == Message::SUCCESS) {
            $currentCategory = Category::getCurrentCategoryFromProductId($productId);
            Helper::setCurrentCategory($currentCategory);
            Helper::setFlagRedirectFromHome();

            $res = Cart::addProductToCart($productId, 1, false);
        }

        Response::returnJson($res);
        die;
    }

    public function removeItem()
    {
        $res = new \Abstraction\Object\Result();

        $itemKey = isset($_POST['itemKey']) ? $_POST['itemKey'] : null;

        if ($itemKey) {
            try {
                $item = WC()->cart->get_cart_item($itemKey);
                /** @var WC_Product_Simple $productObj */
                $productObj = $item['data'];
                $rkCode = $productObj->get_meta('product_rk_code');

                $doRemove = WC()->cart->remove_cart_item($itemKey);
                if ($doRemove) {
                    // re-calculate voucher if has
                    $selectedVouchers = \GDelivery\Libs\Helper\Helper::getSelectedVouchers();
                    if ($selectedVouchers) {
                        // check voucher for this item
                        foreach ($selectedVouchers as $key => $value) {
                            if ($value->selectedForRkItem == $rkCode) {
                                unset($selectedVouchers[$key]);
                            }
                        }
                        Helper::setSelectedVouchers($selectedVouchers);

                        // recalculate voucher
                        \GDelivery\Libs\Helper\Voucher::revalidateVouchersInCart();
                    }

                    $res->messageCode = 1;
                    $res->message = 'Thành công';
                } else {
                    $res->messageCode = 0;
                    $res->message = 'Lỗi khi xóa sản phẩm khỏi giỏ hàng.';
                }
            } catch (\Exception $e) {
                $res->messageCode = 0;
                $res->message = $e->getMessage();
            }
        } else {
            $res->messageCode = 0;
            $res->message = 'Cần truyền đầy đủ thông tin sản phẩm';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function listProductInCart()
    {
        $res = new Result();

        $cartObject = WC()->cart;

        $arrProducts = [];
        $totalPrice = 0;
        foreach ( $cartObject->get_cart() as $cart_item_key => $cart_item ) :
            $_product = $cart_item['data'];
            $variationNames = '';
            if( $_product->is_type('variation') ){

                $attributes = $_product->get_attributes();
                if( $attributes ){
                    foreach ($attributes as $key => $value) {
                        $attrName = wc_attribute_label($key);
                        $attrValue = get_term_by('slug', $value, $key)->name;
                        $variationNames .= ' '.$attrName. ' ' . $attrValue;
                    }
                }
            }
            // Only display if allowed
            if ( ! apply_filters('woocommerce_widget_cart_item_visible', true, $cart_item, $cart_item_key ) || ! $_product->exists() || $cart_item['quantity'] == 0 ) {
                continue;
            }
            $temp = $cart_item;
            $checkMinimumCart = \GDelivery\Libs\Helper\Product::validateMinimumAddToCart($_product->get_ID(), (int) $cart_item['quantity']);

            if ($checkMinimumCart->messageCode == \Abstraction\Object\Message::SUCCESS) {
                $temp['product'] = [
                    'id' => $_product->get_ID(),
                    'name' => $_product->get_title(),
                    'price' => $_product->get_price(),
                    'textPrice' => number_format((float) $_product->get_price()),
                    'textTotalPrice' => number_format($cart_item['quantity'] * ((float) $_product->get_price())),
                    'regularPrice' => (float) $_product->get_regular_price(),
                    'textTotalRegular' => number_format($cart_item['quantity'] * ((float) $_product->get_regular_price())),
                    'textRegularPrice' => number_format((float) $_product->get_regular_price()),
                    'salePrice' => (float) $_product->get_sale_price(),
                    'textSalePrice' => number_format((float) $_product->get_sale_price()),
                    'textTotalSale' => number_format($cart_item['quantity'] * ((float) $_product->get_sale_price())),
                    'variationNames' => $variationNames
                ];
                $totalPrice += $cart_item['quantity'] * $_product->get_price();
                $arrProducts[] = $temp;
            } else {
                WC()->cart->set_quantity($cart_item['key'], 0);
            }
        endforeach;

        if ($arrProducts) {
            $cartObject = WC()->cart;

            // calculate shipping fee
            if(isset($_REQUEST['calculateShippingFee'])) {
                $calculateShippingFee = $_REQUEST['calculateShippingFee'];
            } else {
                $calculateShippingFee = 0;
            }

            $options = [];
            if ($calculateShippingFee == 3) {
                // calculate with payment method is COD
                $options['recalculateShippingFee'] = true;
                $options['paymentMethod'] = 'COD';
            } elseif ($calculateShippingFee == 2) {
                $options['recalculateShippingFee'] = true;
            } elseif ($calculateShippingFee == 1) {

            } else {

            }

            // delivery info
            $deliveryInfo = \GDelivery\Libs\Helper\Helper::getDeliveryInfo();
            if (isset($deliveryInfo->pickupAtRestaurant)) {
                $options['pickupAtRestaurant'] = $deliveryInfo->pickupAtRestaurant;
            }

            // in case for customer, always use shippingVendor = restaurant
            if (get_option('google_map_service_address') == 'goong_address') {
                $options['shippingVendor'] = 'restaurant';
            } else {
                $options['shippingVendor'] = 'grab_express';
            }

            $totals = \GDelivery\Libs\Helper\Helper::calculateCartTotals($cartObject, $options);

            $res->messageCode = 1;
            $res->message = 'Thành công';
            $res->result = [
                'products' => $arrProducts,
                'totalQuantity' => $cartObject->get_cart_contents_count(),

                'selectedVouchers' => \GDelivery\Libs\Helper\Helper::getSelectedVouchers(),

                'totalPrice' => $totals->totalPrice,

                //'discount' => $cartObject->get_coupon_discount_totals(),

                'totalDiscount' => $totals->totalDiscount,

                'totalTax' => $totals->totalTax,

                'shippingFee' => (float) $totals->shippingFee,

                'shipping' => $totals->shipping,

                'total' => (float) $totals->total,

                'totalPaySum' => (float) $totals->totalPaySum,

                'totals' => $totals
            ];
        } else {
            $res->messageCode = 0;
            $res->message = 'Chưa có sản phẩm trong giỏ hàng';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function reloadCart()
    {
        $res = new Result();
        $cart = WC()->cart;
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Không có lỗi';

        if ($cart->is_empty()) {
            // Return continue.
            $res->messageCode = Message::SUCCESS;
            $res->message = 'Không có sản phẩm để kiểm tra';

            \GDelivery\Libs\Helper\Response::returnJson($res);
            die;
        }
        $user = wp_get_current_user();
        $args = [
            'customer_id' => $user->ID,
            'date_created' => date_i18n('Y-m-d')
        ];
        $listOrders = wc_get_orders($args);
        if ($listOrders) {
            $arrQuantity = \GDelivery\Libs\Helper\Order::getListQuantityOfProductOrder($listOrders);
        }

        $arrListProductLimit = [];
        foreach ($cart->get_cart() as $key=>$item) {
            $product = $item['data'];
            $itemProductId = $item['product_id'];
            $isVariation = $product->is_type('variation');
            $productName = $product->get_title();
            if ($isVariation)  {
                $itemProductId = $item['variation_id'];
                $product = (new WC_Product_Factory())->get_product($itemProductId);
                $variationName = \GDelivery\Libs\Helper\Product::getAttributes($product);
                $productName .= " - " . $variationName;
            }

            $limitQuantity = (int) get_field('product_limited_quantity_per_day', $itemProductId);
            // Nếu limit = 0 thì sẽ không giới hạn chọn món.
            if ($limitQuantity >= 1) {
                // Check sản phẩm quá limit sẽ bị remove và lưu lại tên sản phẩm.
                $maxQuantity = $limitQuantity;
                $quantity = (int) $item['quantity'];

                if (isset($arrQuantity[$itemProductId])) {
                    $maxQuantity = $limitQuantity - $arrQuantity[$itemProductId];
                }

                if ($maxQuantity == 0 || $quantity > $maxQuantity) {
                    $arrListProductLimit[] = $productName;
                    WC()->cart->remove_cart_item($key);
                }
            }
        }

        if (!empty($arrListProductLimit)) {
            $res->messageCode = Message::GENERAL_ERROR;
            $listLimitTitle = implode(',', $arrListProductLimit);
            $res->message = "Giỏ hàng đang chọn có món <strong>{$listLimitTitle}</strong> bị vượt quá giới hạn mua trong ngày. Hệ thống sẽ thực hiện xóa món khỏi giỏ hàng.";
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

    public function setSelectedAddress()
    {
        $res = new \Abstraction\Object\Result();

        if (isset($_REQUEST['deliveryAddress'])) {
            $selectedAddress = Helper::getSelectedAddress();
            if (!$selectedAddress) {
                $selectedAddress = new \stdClass();
            }

            $textDeliveryAddress = $_REQUEST['deliveryAddress'];

            $selectedAddress->districtId = isset($_REQUEST['districtId']) ? $_REQUEST['districtId'] : '';
            $selectedAddress->districtName = isset($_REQUEST['districtName']) ? $_REQUEST['districtName'] : '';
            $selectedAddress->provinceId = isset($_REQUEST['provinceId']) ? $_REQUEST['provinceId'] : '';
            $selectedAddress->provinceName = isset($_REQUEST['provinceName']) ? $_REQUEST['provinceName'] : '';
            $selectedAddress->longitude = isset($_REQUEST['longitude']) ? $_REQUEST['longitude'] : null;
            $selectedAddress->latitude = isset($_REQUEST['latitude']) ? $_REQUEST['latitude'] : null;
            $selectedAddress->ggPlaceId = isset($_REQUEST['ggPlaceId']) ? $_REQUEST['ggPlaceId'] : null;

            // get ward info if need
            if (isset($_REQUEST['wardId']) && $_REQUEST['wardId'] > 0 && $_REQUEST['wardId'] < 999999) {
                $selectedAddress->addressLine1 = $selectedAddress->address = $textDeliveryAddress;

                $selectedAddress->wardId = $_REQUEST['wardId'];
                $selectedAddress->wardName = isset($_REQUEST['wardName']) ? $_REQUEST['wardName'] : '';

                $bookingService = new \GDelivery\Libs\BookingService();
                $wardInfo = $bookingService->getWard($selectedAddress->wardId)->result;
                $selectedAddress->longitude = $wardInfo->longitude;
                $selectedAddress->latitude = $wardInfo->latitude;
            } else {
                // in case use Google map address
                $selectedAddress->wardId = null;

                $addressParams = explode(',', $textDeliveryAddress);

                if (\count($addressParams) == 6) {
                    // in case: "Tầng 5 TTTM Hà Nội Centerpoint, 27 Lê Văn Lương, Nhân Chính, Thanh Xuân, Hà Nội, Vietnam"
                    $selectedAddress->addressLine1 = $selectedAddress->address = trim($addressParams[0]).', '.trim($addressParams[1]);
                    $selectedAddress->wardName = trim($addressParams[2]);
                } elseif (\count($addressParams) == 5) {
                    // in case: "315 Trường Chinh, Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                    $selectedAddress->addressLine1 = trim($addressParams[0]);
                    $selectedAddress->wardName = $selectedAddress->address = trim($addressParams[1]);
                } elseif (\count($addressParams) == 4) {
                    // in case: "Khương Thượng, Đống Đa, Hà Nội, Vietnam"
                    $selectedAddress->addressLine1 = $selectedAddress->address = trim($addressParams[0]);
                    $selectedAddress->wardName = '';
                } else {
                    $selectedAddress->addressLine1 = $textDeliveryAddress;
                    $selectedAddress->wardName = '';
                }
            }

            Helper::setSelectedAddress($selectedAddress);

            $res->messageCode = \Abstraction\Object\Message::SUCCESS;
            $res->message = 'Success';
            $res->result = $selectedAddress;
        } else {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Cần truyền đầy đủ tham số';
        }

        \GDelivery\Libs\Helper\Response::returnJson($res);
        die;
    }

} // end class

// init
$ajaxCart = new AjaxCart();