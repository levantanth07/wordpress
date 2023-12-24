<?php

namespace GDelivery\Libs\Helper;

class Balance
{
    public static function productDiscountAndTax($items, $selectedVouchers = [])
    {
        $products = [];
        $totalPrice = 0;
        $totalPriceWithoutDiscountItem = 0;
        $totalDiscountWithoutOnItem = 0;
        foreach ($items as $item) {
            $productRkCode = $item->rkCode;
            $productId = $item->productId;
            $discountOnItem = 0;

            if (!empty($selectedVouchers)) {
                foreach ($selectedVouchers as $voucher) {
                    $listProductChose = isset($voucher->listProductDiscountChosen) ? array_column($voucher->listProductDiscountChosen, 'productId') : false;
                    if (
                        (
                            $voucher->type == Voucher::TYPE_DISCOUNT_PERCENT_ON_ITEM
                            || $voucher->type == Voucher::TYPE_DISCOUNT_CASH_ON_ITEM
                        )
                        && isset($voucher->selectedForRkItem)
                        && count($voucher->selectedForRkItem) == 1
                        && in_array($productRkCode, $voucher->selectedForRkItem)
                    ) {
                        $discountOnItem = $voucher->denominationValue;
                    } elseif (
                        (
                            $voucher->type == Voucher::TYPE_DISCOUNT_PERCENT_ON_ITEM
                            || $voucher->type == Voucher::TYPE_DISCOUNT_CASH_ON_ITEM
                        )
                        && $listProductChose
                        && in_array($item->productId, $listProductChose)
                    ) {
                        foreach ($voucher->listProductDiscountChosen as $productChosen) {
                            if ($productChosen['rkCode'] == $productRkCode) {
                                $discountOnItem = $productChosen['discountForOneProduct'] * $productChosen['quantity'];
                            }
                        }
                    }
                }
            }

            $itemTotalPrice = ($item->salePrice ?: $item->regularPrice) * $item->quantity;
            $totalPriceWithoutDiscountItem += $itemTotalPrice - $discountOnItem;
            $totalPrice += $itemTotalPrice;
            $products[$item->lineItemId] = [
                'id' => $productId,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'totalPrice' => $itemTotalPrice,
                'productRkCode' => $productRkCode,
                'discountItem' => $discountOnItem,
                'taxRateValue' => $item->taxRateValue
            ];
        }

        if (!empty($selectedVouchers)) {
            foreach ($selectedVouchers as $voucher) {
                if (
                    in_array(
                        $voucher->type,
                        [
                            Voucher::TYPE_DISCOUNT_CASH,
                            Voucher::TYPE_DISCOUNT_PERCENT
                        ]
                    )
                ) {
                    $totalDiscountWithoutOnItem += $voucher->denominationValue;
                }
            }
        }

        $totalDiscount = 0;
        foreach ($products as $key => $product) {
            // tỉ trọng giảm giá
            $discountRate = $totalPrice ? ($product['totalPrice'] / $totalPrice) : 0;

            $discount = $product['discountItem'] + ($totalDiscountWithoutOnItem * $discountRate);
            $products[$key]['discount'] = round($discount);

            $products[$key]['tax'] = ($product['totalPrice'] - $discount) * $product['taxRateValue'];
            $totalDiscount += round($discount);
        }
        return $products;
    }
}