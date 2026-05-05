<?php

declare(strict_types=1);

namespace Branch8\Catalog\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class RoundCostBeforeSave implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var  $product Product */
        $product = $observer->getEvent()->getProduct();
        $cost = $product->getCost();

        if (!empty($cost) && (int)$cost != $cost) {
            $product->setData('cost', round((float)$cost));
        }

        return $this;
    }
}
