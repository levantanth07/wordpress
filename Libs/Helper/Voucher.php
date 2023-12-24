<?php
namespace GDelivery\Libs\Helper;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;
use GDelivery\Libs\InternalAffiliateService;
use GDelivery\Libs\PaymentHubService;

class Voucher {

    const TYPE_CASH = 1; // voucher equal case
    const TYPE_DISCOUNT_CASH = 2; // coupon, discount amount on order
    const TYPE_DISCOUNT_CASH_ON_ITEM = 3; // coupon, discount amount on item
    const TYPE_DISCOUNT_PERCENT = 4; // discount percent on order
    const TYPE_DISCOUNT_PERCENT_ON_ITEM = 5; // discount percent on item
    const TYPE_GIFT = 6;

    private static function validateGPPVoucher($voucherCode, $orderTotal)
    {
        $res = new Result();

        // I. check with internal G-delivery;
        $listProcessingVouchers = get_option('processing_clm_vouchers', []);
        if (!in_array($voucherCode, $listProcessingVouchers)) {
            // II. Check with payment hub
            // payment hub service
            $paymentHubService = new \GDelivery\Libs\PaymentHubService();

            $selectedRestaurant = \GDelivery\Libs\Helper\Helper::getSelectedRestaurant();

            $restaurantGDeliveryCode = 0;
            if ($selectedRestaurant->restaurant->regionName == 'Ha Noi') {
                $restaurantGDeliveryCode = Config::PAYMENT_HUB_TGS_RESTAURANT_CODE;
            } elseif ($selectedRestaurant->restaurant->regionName == 'Ho Chi Minh') {
                $restaurantGDeliveryCode = Config::PAYMENT_HUB_TGS_RESTAURANT_CODE_HCM;
            }

            $doCheck = $paymentHubService->checkVoucher($voucherCode, $restaurantGDeliveryCode, 14);
            if ($doCheck->messageCode == Message::SUCCESS) {
                // III. check 50% total price

                $voucherObject = $doCheck->result;

                if ($orderTotal->totalDiscount + $voucherObject->denominationValue <= $orderTotal->totalPrice/2) {
                    $res->messageCode = Message::SUCCESS;
                    $res->message = 'Voucher khả dụng';
                    $res->result = $voucherObject;
                } else {
                    $res->messageCode = Message::GENERAL_ERROR;
                    $res->message = 'Voucher không khả dụng để sử dụng: Tổng giá trị giảm giá không được phép vượt quá 50% giá trị tiền hàng';
                }
            } else {
                $res->messageCode = Message::GENERAL_ERROR;
                $res->message = $doCheck->message;
            }
        } else {
            $res->message = 'Voucher đang được sử dụng ở một hóa đơn khác. Nếu đây là một sự nhầm lẫn, vui lòng liên hệ bộ phận Chăm Sóc Khách Hàng để được hỗ trợ. Xin cảm ơn';
            $res->messageCode = Message::GENERAL_ERROR;
        }

        return $res;
    }

    /**
     * Idea is: recalculate and check current selected vouchers and cart items
     * so, just check with total price and total discount with this
     */
    public static function revalidateVouchersInCart()
    {
        // get list selected voucher first
        $selectedVouchersInCart = \GDelivery\Libs\Helper\Helper::getSelectedVouchers() ? \GDelivery\Libs\Helper\Helper::getSelectedVouchers() : [];

        // cart total
        $cartTotal = \GDelivery\Libs\Helper\Helper::calculateCartTotals();
        // todo check discount
        $cartTotal->totalDiscount = 0; // reset discount as cart without discount
        $cartTotal->totalPrice = $cartTotal->totalPrice - $cartTotal->shipping->price;

        $selectedVouchers = [];
        foreach ($selectedVouchersInCart as $one) {
            if (isset($one->selectedForProductId)) {
                $selectedItem = $one->selectedForProductId;
            } else {
                $selectedItem = null;
            }

            $checkVoucher = self::validateVoucher($one->code, $cartTotal, $selectedVouchers, $one->partnerId, $selectedItem);
            if ($checkVoucher->messageCode == Message::SUCCESS) {
                $selectedVouchers[] = $checkVoucher->result;

                if ($one->type != 1) {
                    $cartTotal->totalDiscount += $one->denominationValue;
                }
            } else {

            }
        }

        Helper::setSelectedVouchers($selectedVouchers);
    }

    public static function validateVoucher($voucherCode, $orderTotal, $selectedVouchers, $partnerId = null, $selectedItem = null)
    {
        $res = new Result();

        $selectedRestaurant = \GDelivery\Libs\Helper\Helper::getSelectedRestaurant();

        if (!$selectedRestaurant) {
            $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
            $res->message = 'Vui lòng chọn nhà hàng phục vụ đơn hàng này và thử lại. Xin cảm ơn.';
        } else {
            // detect vendor voucher
            if ($partnerId === null) {
                if (preg_match("/^(999|998)(\d{9})$/", $voucherCode)) {
                    // giftpop
                    $partnerId = 3;
                } elseif (preg_match("/^\d{10}$/", $voucherCode)) {
                    // got it
                    $partnerId = 1;
                } elseif (preg_match("/^(DT)(\d{10})$/", $voucherCode)) {
                    // deal today
                    $partnerId = 4;
                } elseif (
                    preg_match("/^(UB)(\d{6})$/", $voucherCode)
                    || preg_match("/^(UB)(\d{9})$/", $voucherCode)
                ) {
                    // urbox
                    $partnerId = 6;
                } elseif (preg_match("/^(UTOP)(\w{12})$/", $voucherCode)) {
                    // utop
                    $partnerId = 17;
                } else {
                    // golden gate
                    $partnerId = 8;
                }
            }

            // in case voucher GPP
            if ($partnerId == 14) {
                return self::validateGPPVoucher($voucherCode, $orderTotal);
            }

            // list voucher before tax without partner voucher
            $beforeTaxVouchers = [];
            $afterTaxVouchers = [];
            foreach ($selectedVouchers as $one) {
                if ($one->type == 1) {
                    if ($one->partnerId == 8 || $one->partnerId == 999) {
                        $afterTaxVouchers[] = $one;
                    }
                } else {
                    if ($one->partnerId == 8 || $one->partnerId == 999) {
                        $beforeTaxVouchers[] = $one;
                    }
                }
            }

            // payment hub service
            $paymentHubService = new \GDelivery\Libs\PaymentHubService();

            // internal affiliate service
            $internalAffiliateService = new InternalAffiliateService();

            $restaurantGDeliveryCode = 0;
            if ($selectedRestaurant->restaurant->regionName == 'Ha Noi') {
                $restaurantGDeliveryCode = Config::PAYMENT_HUB_TGS_RESTAURANT_CODE;
            } elseif ($selectedRestaurant->restaurant->regionName == 'Ho Chi Minh') {
                $restaurantGDeliveryCode = Config::PAYMENT_HUB_TGS_RESTAURANT_CODE_HCM;
            }

            // check voucher code
            $isOkToCheck = false;
            $voucherObject = null;

            // check as internal affiliate
            $doCheckInternalAffiliate = $internalAffiliateService->checkReferralCode($voucherCode, $selectedRestaurant->restaurant);
            if ($doCheckInternalAffiliate->messageCode == Message::SUCCESS) {
                $isOkToCheck = true;
                $voucherObject = $doCheckInternalAffiliate->result;
            }

            // check as voucher
            if (!$isOkToCheck) {
                // check with gdelivery
                $doCheckVoucherWithGDelivery = $paymentHubService->checkVoucher(
                    $voucherCode,
                    $restaurantGDeliveryCode,
                    $partnerId
                );

                if ($doCheckVoucherWithGDelivery->messageCode == Message::SUCCESS) {
                    // check with selected restaurant
                    $doCheckVoucher = $paymentHubService->checkVoucher(
                        $voucherCode,
                        $selectedRestaurant->restaurant->code,
                        $partnerId
                    );
                    if ($doCheckVoucher->messageCode == \Abstraction\Object\Message::SUCCESS) {
                        $isOkToCheck = true;
                        $voucherObject = $doCheckVoucher->result;
                    } else {
                        $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                        $res->message = $doCheckVoucher->message;
                    }
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = $doCheckVoucherWithGDelivery->message;
                }
            }


            // if ok to check
            if ($isOkToCheck) {
                $validToUse = 1;
                $invalidToUseMessage = '';

                if ($partnerId == 8 || $partnerId == 999) {
                    // just check condition in case Golden Gate Voucher
                    // check in campaign
                    $existingInListAfterTaxVoucher = false;
                    foreach ($selectedVouchers as $one) {
                        if ($voucherObject->partnerId == 999 && $one->partnerId == 999) {
                            $validToUse = 0;
                            $invalidToUseMessage = 'KHÔNG được phép áp dụng song song các mã giới thiệu. Bạn ĐÃ áp dụng mã giới thiệu '.$one->code.' vào đơn hàng.';
                            break;
                        }

                        if ($one->type != 1 && $one->campaign->id == $voucherObject->campaign->id) {
                            $validToUse = 0;
                            $invalidToUseMessage = 'Voucher không khả dụng để sử dụng, bạn ĐÃ áp dụng chương trình "'.$voucherObject->campaign->name.'" vào đơn hàng.';
                            break;
                        }

                        if ($one->type == 1 && $one->campaign->id == $voucherObject->campaign->id) {
                            $existingInListAfterTaxVoucher = true;
                        }
                    }

                    // check parallel voucher conditions
                    if ($validToUse == 1) {
                        if ($voucherObject->conditions) {
                            $minBillValue = 0;
                            $hasParallelBeforeTaxCondition = false;
                            $hasParallelAfterTaxCondition = false;
                            foreach ($voucherObject->conditions as $one) {
                                switch ($one->key) {
                                    case 'min_bill_value':
                                        $minBillValue = $one->value;
                                        if ($orderTotal->totalPrice < $one->value) {
                                            $validToUse = 0;
                                            $invalidToUseMessage = 'Giá trị hóa đơn tối thiểu được áp dụng là '.number_format((float)$one->value).'đ';
                                        }
                                        break;
                                    case 'is_accumulated':
                                        if ($one->value === true) {
                                            if (!$minBillValue) {
                                                $validToUse = 0;
                                                $invalidToUseMessage = 'Voucher không hợp lệ với điều kiện lũy kế';
                                            } else {
                                                // just allow accumulated in case discount amount before tax apple for order
                                                if ($voucherObject->type == 2) {
                                                    $voucherValue = $voucherObject->denominationValue;

                                                    $x = (int) ($orderTotal->totalPrice/$minBillValue);
                                                    $voucherObject->denominationValue = $voucherValue * $x;
                                                }
                                            }
                                        }
                                        break;
                                    case 'parallel_apply_with_before_tax_campaign':
                                        $hasParallelBeforeTaxCondition = true;
                                        if ($beforeTaxVouchers) {
                                            if ($one->value) {
                                                foreach ($beforeTaxVouchers as $beforeTaxVoucher) {
                                                    if (!in_array($beforeTaxVoucher->campaign->id, $one->value)) {
                                                        $validToUse = 0;
                                                        $invalidToUseMessage = 'Voucher không được áp dụng song song với chương trình khác trước thuế khác.';
                                                        break; // break for $beforeTaxVouchers
                                                    }
                                                }
                                            } else {
                                                // there is no config for parallel, not apply
                                                $validToUse = 0;
                                                $invalidToUseMessage = 'Voucher không được áp dụng song song với chương trình trước thuế khác ';
                                            }
                                        }
                                        break;
                                    case 'parallel_apply_with_after_tax_campaign':
                                        $hasParallelAfterTaxCondition = true;
                                        if ($afterTaxVouchers) {
                                            if (!$existingInListAfterTaxVoucher) {
                                                if ($one->value) {
                                                    foreach ($afterTaxVouchers as $afterTaxVoucher) {
                                                        if (!in_array($afterTaxVoucher->campaign->id, $one->value)) {
                                                            $validToUse = 0;
                                                            $invalidToUseMessage = 'Voucher không được áp dụng song song với chương trình sau thuế khác.';
                                                            break; // break for $afterTaxVouchers
                                                        }
                                                    }
                                                } else {
                                                    // there is no config for parallel, not apply
                                                    $validToUse = 0;
                                                    $invalidToUseMessage = 'Voucher không được áp dụng song song với chương trình sau thuế khác ';
                                                }
                                            }
                                        }
                                        break;
                                    default:
                                }

                                if (!$validToUse) {
                                    // break foreach conditions
                                    break;
                                }
                            } // end foreach conditions

                            // in case there is no parallel_apply_with_after_tax_campaign; parallel_apply_with_before_tax_campaign
                            if ($beforeTaxVouchers) {
                                if (!$hasParallelBeforeTaxCondition) {
                                    $validToUse = 0;
                                    $invalidToUseMessage = 'Voucher không được cấu hình áp dụng song song với chương trình trước thuế khác';
                                }
                            }

                            if ($afterTaxVouchers) {
                                if (!$hasParallelAfterTaxCondition) {
                                    if ($existingInListAfterTaxVoucher) {

                                    } else {
                                        $validToUse = 0;
                                        $invalidToUseMessage = 'Voucher không được cấu hình áp dụng song song với chương trình sau thế khác';
                                    }
                                }

                                if ($existingInListAfterTaxVoucher) {

                                }
                            }
                        } else {
                            if ($beforeTaxVouchers || $afterTaxVouchers) {
                                // there is no config for parallel, just apply
                                $validToUse = 0;
                                $invalidToUseMessage = 'Voucher không được cấu hình điều kiện áp dụng song song với chương trình khác';
                            }
                        }
                    } // end if check conditions

                    // check voucher type
                    if ($validToUse == 1) {
                        switch ($voucherObject->type) {
                            case 3: // discount amount before tax on item
                            case 5: // discount percent before tax on item
                                if (\count($voucherObject->applyForRkItemCodes) > 0) {
                                    if ($selectedItem) {
                                        $product = Product::getProductInfo($selectedItem);

                                        if ($voucherObject->type == 5) {
                                            if ($product->salePrice) {
                                                $price = $product->salePrice;
                                            } else {
                                                $price = $product->regularPrice;
                                            }
                                            $voucherObject->denominationValue = $price * $voucherObject->denominationValue/100;
                                        }

                                        $voucherObject->selectedForRkItem = $product->rkCode;
                                        $voucherObject->selectedForProductId = $product->id;
                                    } else {
                                        if (\count($voucherObject->applyForRkItemCodes) == 1) {
                                            // search product
                                            $products = Product::searchProductByRkCode(
                                                $voucherObject->applyForRkItemCodes[0],
                                                Helper::getCurrentCategory()->term_id
                                            );
                                            if (isset($products[0])) {

                                                if ($voucherObject->type == 5) {
                                                    if ($products[0]->salePrice) {
                                                        $price = $products[0]->salePrice;
                                                    } else {
                                                        $price = $products[0]->regularPrice;
                                                    }
                                                    $voucherObject->denominationValue = $price * $voucherObject->denominationValue/100;
                                                }

                                                $voucherObject->selectedForRkItem = $voucherObject->applyForRkItemCodes[0];
                                                $voucherObject->selectedForProductId = $products[0]->id;
                                            } else {
                                                $validToUse = 0;
                                                $invalidToUseMessage = "Voucher chưa khả dụng với ".Helper::getCurrentCategory()->name.". (3.1)";
                                            }
                                        } else {
                                            $validToUse = 2;
                                            $invalidToUseMessage = 'Vui lòng chọn món được ưu đãi.';

                                            // list product
                                            $listProducts = [];
                                            foreach ($voucherObject->applyForRkItemCodes as $one) {
                                                $searchProducts = Product::searchProductByRkCode(
                                                    $one,
                                                    Helper::getCurrentCategory()->term_id
                                                );

                                                if (isset($searchProducts[0])) {
                                                    $listProducts[] = $searchProducts[0];
                                                }
                                            }

                                            // define new property for list apply item
                                            $voucherObject->applyForItems = $listProducts;
                                        }
                                    }
                                } else {
                                    $validToUse = 0;
                                    $invalidToUseMessage = 'Voucher chưa khả dụng! Vui lòng thử lại sau. (3)';
                                }
                                break;
                            case 4: // discount percent before tax on order
                                $voucherObject->denominationValue = ($orderTotal->totalPrice - $orderTotal->totalDiscount) * $voucherObject->denominationValue/100;
                                if ($voucherObject->denominationValue < 0) {
                                    $voucherObject->denominationValue = 0;
                                }
                                break;
                            case 1: // discount amount after tax
                            case 2: // discount amount before tax on order
                            default:
                                // do nothing
                        } // end check voucher type
                    }

                    // check 50% giá trị hóa đơn
                    if ($validToUse && $voucherObject->type != 1) {
                        if ($orderTotal->totalDiscount + $voucherObject->denominationValue > $orderTotal->totalPrice/2) {
                            $validToUse = 0;
                            $invalidToUseMessage = 'Voucher không khả dụng để sử dụng: Tổng giá trị giảm giá không được phép vượt quá 50% giá trị tiền hàng';
                        }
                    }
                } else {
                    // the other is cash voucher, always is ok
                }

                // if everything ok
                if ($validToUse == 1) {
                    // if everything ok and product not in cart yet, auto add to cart
                    if (isset($voucherObject->selectedForProductId) && !Cart::isProductInCart($voucherObject->selectedForProductId)) {
                        Cart::addProductToCart($voucherObject->selectedForProductId);
                    }

                    // return
                    $res->messageCode = \Abstraction\Object\Message::SUCCESS;
                    $res->message = 'Thành công';
                    $res->result = $voucherObject;
                } elseif ($validToUse == 2) {
                    $res->messageCode = \Abstraction\Object\Message::NOT_VALID_DATA;
                    $res->message = $invalidToUseMessage;
                    $res->result = $voucherObject;
                } else {
                    $res->messageCode = \Abstraction\Object\Message::GENERAL_ERROR;
                    $res->message = $invalidToUseMessage;
                }
            } // end if isOkToCheck
        }

        return $res;
    }

    public static function validateInternalAffiliateCode()
    {
        
    }

} // end class
