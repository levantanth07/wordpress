<?php

namespace GDelivery\Libs\Helper;

use Abstraction\Object\Result;

class Cart {

    /**
     * Calculate total tax of cart
     *
     * @param \WC_Cart $cart
     * @param float $discount
     * @return float Tax total.
     */
    public static function taxTotal($cart = null, $selectedVouchers = [])
    {
        if (!$cart) {
            $cart = WC()->cart;
        }

        // get voucher apply on item
        if (!$selectedVouchers) {
            $selectedVouchers = Helper::getSelectedVouchers();
        }

        $totalDiscountAmount = 0;
        $listProductIdHasDiscount = [];
        $listDiscountOnItemVouchers = [];
        if ($selectedVouchers) {
            foreach ($selectedVouchers as $one) {
                if (in_array($one->type, [3, 5])) {
                    // discount on item
                    $listProductIdHasDiscount[] = $one->selectedForProductId;
                    $listDiscountOnItemVouchers[$one->selectedForProductId] = $one;
                } elseif (in_array($one->type, [2, 4])) {
                    // discount on order
                    $totalDiscountAmount += $one->denominationValue;
                } else {
                    // type = 1; cash voucher
                }
            }
        }

        $totalTax = 0;
        foreach ($cart->get_cart_contents() as $item) {
            // get product info
            $productId = $item['product_id'];
            $product = Product::getProductInfo($productId);
            if ($product->salePrice) {
                $productPrice = $product->salePrice;
            } else {
                $productPrice = $product->regularPrice;
            }

            if (in_array($productId, $listProductIdHasDiscount)) {
                if ($item['quantity'] == 1) {
                    if ($productPrice - $listDiscountOnItemVouchers[$productId]->denominationValue > 0) {
                        $totalTax += ($productPrice - $listDiscountOnItemVouchers[$productId]->denominationValue) * $product->taxRateValue;
                    } else {
                        // on case discount on whole item amout
                    }
                } else {
                    // quantity > 1
                    // first product count for discount voucher
                    $totalTax += ($productPrice - $listDiscountOnItemVouchers[$productId]->denominationValue) * $product->taxRateValue;

                    // other products
                    // Get total price after discount of product.
                    $otherProductSubTotal = $item['line_subtotal'] - $productPrice;
                    $otherProductTotalPrice = $otherProductSubTotal - $totalDiscountAmount;

                    if ($otherProductTotalPrice <= 0) {
                        $totalDiscountAmount = $totalDiscountAmount - $otherProductSubTotal; // this item amount = 0; the remaining discount amount for next item
                    } else {
                        $totalDiscountAmount = 0; // all discount amount for this item

                        $totalTax += $otherProductTotalPrice * $product->taxRateValue;
                    }
                }
            } else {
                // Get total price after discount of product.
                $linePrice = $item['line_subtotal'] - $totalDiscountAmount;

                if ($linePrice <= 0) {
                    $totalDiscountAmount = $totalDiscountAmount - $item['line_subtotal']; // this item amount = 0; the remaining discount amount for next item
                } else {
                    $totalDiscountAmount = 0; // all discount amount for this item

                    $totalTax += $linePrice * $product->taxRateValue;
                }
            }
        }

        return $totalTax;
    }

    /**
     * Get category of cart
     *
     * @return mixed|null
     */
    public static function getCategoryOfCart()
    {
        $currentCategory = null;
        if (WC()->cart->get_cart_contents_count() > 0) {
            $cart = WC()->cart->get_cart_contents();
            $productId = 0;
            foreach ($cart as $item) {
                $productId = $item['product_id'];
                break;
            }

            $categories = wp_get_post_terms($productId, 'product_cat');
            foreach ($categories as $category) {
                if ($category->parent != 0) {
                    $currentCategory = $category;
                    break;
                }
            }
        }

        if ($currentCategory) {
            Helper::setCurrentCategory($currentCategory);
        }

        return $currentCategory;
    }

    /**
     * @param int  $productId
     * @param int  $quantity
     * @param bool $addAsNew
     *
     * @return Result
     */
    public static function addProductToCart($productId, $quantity = 1, $addAsNew = true)
    {
        // result
        $res = new Result();

        // Check add product other brand
        $newCategory = Category::getCurrentCategoryFromProductId($productId);
        $newCategoryId = $newCategory->term_id;
        $currentCartContent = WC()->cart->get_cart();
        $currentCategoryId = $currentCartContent ? Category::getCurrentCategoryFromProductId(array_values($currentCartContent)[0]['product_id'])->term_id : 0;
        if ($newCategoryId != $currentCategoryId) {
            WC()->cart->empty_cart();
            \GDelivery\Libs\Helper\Helper::setSelectedRestaurant(null);
            \GDelivery\Libs\Helper\Helper::setCurrentCategory($newCategory);
            $doAddToCart = WC()->cart->add_to_cart($productId, $quantity);

            $res->messageCode = 1;
            $res->message = 'Thành công';
            $res->result = $doAddToCart;

            return $res;
        }

        // get current
        if ($productId !== null && $quantity !== null) {
            // check mini cart total amount to add to cart
            $checkCartTotal = \GDelivery\Libs\Helper\Product::validateMinimumAddToCart($productId, $quantity);
            if ($checkCartTotal->messageCode == \Abstraction\Object\Message::SUCCESS) {
                // check validate quantity
                $checkQuantity = \GDelivery\Libs\Helper\Product::validateQuantityToAddToCart($productId, $quantity);

                if ($checkQuantity->messageCode == \Abstraction\Object\Message::SUCCESS) {
                    try {
                        $existingInCart = false;
                        $doAddToCart = false;
                        $currentQuantity = 0;

                        // update existing cart
                        foreach (WC()->cart->get_cart() as $itemKey => $item) {

                            $product = $item['data'];
                            $itemProductId = $item['product_id'];
                            if ($product->is_type('variation'))  {
                                $itemProductId = $product->get_id();
                            }
                            if ($itemProductId == $productId) {
                                $currentQuantity = (int) $item['quantity'];
                                $existingInCart = true;
                                $itemKey = $item['key'];

                                if ($addAsNew) {
                                    $doAddToCart = WC()->cart->set_quantity($itemKey, $quantity);
                                } else {
                                    $doAddToCart = WC()->cart->set_quantity($itemKey, ($currentQuantity + $quantity));
                                }
                                break;
                            }
                        }

                        if (!$existingInCart) {
                            $doAddToCart = WC()->cart->add_to_cart($productId, $quantity);
                        }

                        // re-calculate voucher if has
                        $selectedVouchers = \GDelivery\Libs\Helper\Helper::getSelectedVouchers();
                        if ($selectedVouchers) {
                            \GDelivery\Libs\Helper\Voucher::revalidateVouchersInCart();
                        }

                        $res->messageCode = 1;
                        $res->message = 'Thành công';
                        $res->result = $doAddToCart;

                        \GDelivery\Libs\Helper\Address::updateSelectedProvince();
                        Address::updateSelectedAddress();
                    } catch (\Exception $e) {
                        $res->messageCode = 0;
                        $res->message = $e->getMessage();
                    }
                } else {
                    $res->messageCode = 0;
                    $res->message = $checkQuantity->message;
                }
            } else {
                $res->messageCode = 0;
                $res->message = 'Giá trị đơn hàng cần tối thiểu '.number_format($checkCartTotal->result).'đ để đặt món này.';

                // if in cart, remove it
                foreach (WC()->cart->get_cart() as $itemKey => $item) {
                    if ($item['product_id'] == $productId) {
                        WC()->cart->set_quantity($item['key'], 0);
                    }
                }
            }
        } else {
            $res->messageCode = 0;
            $res->message = 'Cần truyền đầy đủ thông tin sản phẩm';
        }

        return $res;
    }

    /**
     * @param $productId
     *
     * @return bool
     */
    public static function isProductInCart($productId)
    {
        $existingInCart = false;

        foreach (WC()->cart->get_cart() as $itemKey => $item) {
            $product = $item['data'];
            $itemProductId = $item['product_id'];

            if ($product->is_type('variation')) {
                $itemProductId = $product->get_id();
            }

            if ($itemProductId == $productId) {
                $existingInCart = true;
                break;
            }
        }

        if ($existingInCart) {
            return true;
        } else {
            return false;
        }
    }

} // end class
