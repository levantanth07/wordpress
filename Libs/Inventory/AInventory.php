<?php
namespace GDelivery\Libs\Inventory;

abstract class AInventory {

    protected IInventory $inventory;

    public function __construct(IInventory $inventory)
	{
        $this->setInventory($inventory);
	}

    public function setInventory(IInventory $inventory)
    {
        $this->inventory = $inventory;
    }

    public function getInventory() 
    {
        return $this->inventory;
    }

    abstract public function checkStock($productsInfo);

    abstract public function setStock($productsInfo);

    abstract public function decreaseStock($productsInfo);

    abstract public function increaseStock($productsInfo);

}