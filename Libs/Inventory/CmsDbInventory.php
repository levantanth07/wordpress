<?php
namespace GDelivery\Libs\Inventory;

use Abstraction\Object\Message;
use Abstraction\Object\Result;

class CmsDbInventory implements IInventory {

    public function checkStock($productsInfo)
    {
        $res = new Result();
        $stockDetail = $outOfStock = [];
        foreach ($productsInfo as $productItem) {
            $product = wc_get_product($productItem['id']);
            $remainingQuantity = intval($product->get_stock_quantity());
            $productItem['remainingQuantity'] = $remainingQuantity;
            $productItem['enoughStock'] = true;
            if ($remainingQuantity - intval($productItem['quantity']) < 0) {
                $productItem['enoughStock'] = false;
                $outOfStock[] = $productItem;
            }
            $stockDetail[] = $productItem;
        }
        $stockStatus = new \stdClass();
        $stockStatus->enoughStock = empty($outOfStock) ? true : false;
        $stockStatus->stockDetail = $stockDetail;
        
        $res->messageCode = Message::SUCCESS;
        $res->result = $stockStatus;
        return $res;
    }

    public function setStock($productsInfo)
    {
        $res = new Result();
        foreach ($productsInfo as $productItem) {
            $product = wc_get_product($productItem['id']);
            $product->set_stock_quantity($productItem['quantity']);
            $product->save();
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Cập nhật thành công';
        return $res;
    }

    public function decreaseStock($productsInfo)
    {
        $res = new Result();
        $recheckStock = $this->checkStock($productsInfo);
        if ($recheckStock->messageCode != Message::SUCCESS || $recheckStock->result->enoughStock != true) {
            $res->messageCode = Message::GENERAL_ERROR;
            $res->message = 'Tồn tại sản phẩm không đủ số lượng trong kho';
            $res->result = $recheckStock->result;
            return $res;
        }
        foreach ($productsInfo as $productItem) {
            $product = wc_get_product($productItem['id']);
            $remainingQuantity = intval($product->get_stock_quantity());
            $newStockQuantity = $remainingQuantity - intval($productItem['quantity']);
            $product->set_stock_quantity($newStockQuantity);
            $product->save();
        }
        $res->messageCode = Message::SUCCESS;
        return $res;
    }

    public function increaseStock($productsInfo)
    {
        $res = new Result();
        foreach ($productsInfo as $productItem) {
            $product = wc_get_product($productItem['id']);
            $remainingQuantity = intval($product->get_stock_quantity());
            $newStockQuantity = $remainingQuantity + intval($productItem['quantity']);
            $product->set_stock_quantity($newStockQuantity);
            $product->save();
        }
        $res->messageCode = Message::SUCCESS;
        return $res;
    }
}