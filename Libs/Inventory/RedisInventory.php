<?php
namespace GDelivery\Libs\Inventory;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Config;

class RedisInventory implements IInventory {

    const PRODUCT_STOCK_KEY = 'ecom-cms:product:{productId}:stock';

    private $redisClient;

    public function __construct()
    {
        $this->redisClient = new \Predis\Client([
            'scheme' => 'tcp',
            'host' => Config::REDIS_HOST,
            'port' => Config::REDIS_PORT,
            'database' => Config::REDIS_DB_INDEX,
            'password' => Config::REDIS_PASS,
        ]);
    }

    private function getProductStockKey($productId)
    {
        return str_replace('{productId}', $productId, self::PRODUCT_STOCK_KEY);
    }

    public function checkStock($productsInfo)
    {
        $res = new Result();
        $stockDetail = $outOfStock = [];
        foreach ($productsInfo as $productItem) {
            $keyName = $this->getProductStockKey($productItem['id']);
            $stockValue = intval($this->redisClient->get($keyName));
            $productItem['remainingQuantity'] = $stockValue;
            $productItem['enoughStock'] = true;
            if ($stockValue - intval($productItem['quantity']) < 0) {
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
            $this->redisClient->set(
                $this->getProductStockKey($productItem['id']), 
                intval($productItem['quantity'])
            );
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
            $productQuantity = intval($productItem['quantity']);
            $keyName = $this->getProductStockKey($productItem['id']);
            $remainingQuantity = intval($this->redisClient->get($keyName));
            $newStockQuantity = $remainingQuantity - $productQuantity;
            $this->redisClient->set(
                $keyName, 
                $newStockQuantity
            );
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Cập nhật thành công';
        return $res;
    }

    public function increaseStock($productsInfo)
    {
        $res = new Result();
        foreach ($productsInfo as $productItem) {
            $productQuantity = intval($productItem['quantity']);
            $keyName = $this->getProductStockKey($productItem['id']);
            $remainingQuantity = intval($this->redisClient->get($keyName));
            $newStockQuantity = $remainingQuantity + $productQuantity;
            $this->redisClient->set(
                $keyName, 
                $newStockQuantity
            );
        }
        $res->messageCode = Message::SUCCESS;
        $res->message = 'Cập nhật thành công';
        return $res;
    }
}