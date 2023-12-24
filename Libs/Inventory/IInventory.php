<?php
namespace GDelivery\Libs\Inventory;

interface IInventory {

    public function checkStock($productsInfo);

    public function setStock($productsInfo);

    public function decreaseStock($productsInfo);

    public function increaseStock($productsInfo);
}