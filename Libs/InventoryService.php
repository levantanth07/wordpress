<?php
namespace GDelivery\Libs;

use Abstraction\Object\Message;
use Abstraction\Object\Result;
use GDelivery\Libs\Inventory\Inventory;
use GDelivery\Libs\Inventory\CmsDbInventory;
use GDelivery\Libs\Inventory\RedisInventory;

class InventoryService {

    const LIST_INVENTORY_TYPE = ['RedisInventory', 'CmsDbInventory'];

    /**
     *  @param array $products
     *  @return Result
     */
    public function checkStock($products)
    {
        $inventory = new Inventory(new RedisInventory());
        $checkStock = $inventory->checkStock($products);
        if ($checkStock->messageCode == Message::SUCCESS && false == $checkStock->result->enoughStock) {
            $inventory->setInventory(new CmsDbInventory());
            $checkStock = $inventory->checkStock($products);
            if ($checkStock->messageCode == Message::SUCCESS) {
                $inventory->setInventory(new RedisInventory());
                $cmsDbStock = array_map(
                    fn ($product) => ['id' => $product['id'], 'quantity' => $product['remainingQuantity']], 
                    $checkStock->result->stockDetail
                );
                $inventory->setStock($cmsDbStock);
            }
        }
        return $checkStock;
    }

    /**
     *  @param array $products
     *  @return Result
     */
    public function decreaseStock($products)
    {
        $inventory = null;
        $namespace = '\\GDelivery\\Libs\\Inventory\\';
        foreach (self::LIST_INVENTORY_TYPE as $inventoryType) {
            $inventoryTypeClass = $namespace . $inventoryType;
            if ($inventory == null) {
                $inventory = new Inventory(new $inventoryTypeClass());
            } else {
                $inventory->setInventory(new $inventoryTypeClass());
            }
            $inventory->decreaseStock($products);
        }
        $res = new Result();
        $res->messageCode = Message::SUCCESS;
        return $res;
    }

    /**
     *  @param array $products
     *  @return Result
     */
    public function increaseStock($products)
    {
        $inventory = null;
        $namespace = '\\GDelivery\\Libs\\Inventory\\';
        foreach (self::LIST_INVENTORY_TYPE as $inventoryType) {
            $inventoryTypeClass = $namespace . $inventoryType;
            if ($inventory == null) {
                $inventory = new Inventory(new $inventoryTypeClass());
            } else {
                $inventory->setInventory(new $inventoryTypeClass());
            }
            $inventory->increaseStock($products);
        }
        $res = new Result();
        $res->messageCode = Message::SUCCESS;
        return $res;
    }

    /**
     * @param \WC_Product[] $products
     * @return void
     */
    public static function syncProductInventory($products)
    {
        $inventory = new RedisInventory();
        foreach($products as $product) {
            $productInventory = [];
            if ($product->is_type('variable')) {
                $variationIds = $product->get_children();
                if (!empty($variationIds)) {
                    foreach ($variationIds as $variationId) {
                        $variation = wc_get_product($variationId);
                        $inventoryItem = [];
                        $inventoryItem['id'] = $variationId;
                        $inventoryItem['quantity'] = $variation->get_stock_quantity();
                        $productInventory[] = $inventoryItem;
                    }
                }
            }
            $productInventory[] = [
                'id' => $product->get_id(),
                'quantity' => $product->get_stock_quantity()
            ];
            $inventory->setStock($productInventory);
        }
    }

}