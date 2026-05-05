<?php
declare(strict_types=1);

namespace Branch8\ShippingSubsidy\Model\Data;

use Branch8\ShippingSubsidy\Api\Data\ShippingSubsidyInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class ShippingSubsidy extends AbstractSimpleObject implements ShippingSubsidyInterface
{
    public function getEnabled(): ?bool
    {
        return $this->_get('enabled');
    }

    public function setEnabled(?bool $enabled): self
    {
        return $this->setData('enabled', $enabled);
    }

    public function getMode(): ?string
    {
        return $this->_get('mode');
    }

    public function setMode(?string $mode): self
    {
        return $this->setData('mode', $mode);
    }

    public function getHomeDelivery(): ?float
    {
        return $this->_get('home_delivery');
    }

    public function setHomeDelivery(?float $amount): self
    {
        return $this->setData('home_delivery', $amount);
    }

    public function getStorePickup(): ?float
    {
        return $this->_get('store_pickup');
    }

    public function setStorePickup(?float $amount): self
    {
        return $this->setData('store_pickup', $amount);
    }
}
