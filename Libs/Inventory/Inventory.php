<?php
namespace GDelivery\Libs\Inventory;

class Inventory extends AInventory {

    public function checkStock($productsInfo)
    {
        return $this->inventory->checkStock($productsInfo);
    }

    public function setStock($productsInfo)
    {
        return $this->inventory->setStock($productsInfo);
    }

    public function decreaseStock($productsInfo)
    {
        return $this->inventory->decreaseStock($productsInfo);
    }

    public function increaseStock($productsInfo)
    {
        return $this->inventory->increaseStock($productsInfo);
    }
}