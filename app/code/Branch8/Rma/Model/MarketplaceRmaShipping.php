<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Model;

use Branch8\Rma\Api\Data\MarketplaceRmaShippingInterface;
use Magento\Framework\Model\AbstractModel;

class MarketplaceRmaShipping extends AbstractModel implements MarketplaceRmaShippingInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Branch8\Rma\Model\ResourceModel\MarketplaceRmaShipping::class);
    }

    /**
     * @inheritDoc
     */
    public function getMarketplacermashippingId()
    {
        return $this->getData(self::MARKETPLACERMASHIPPING_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMarketplacermashippingId($marketplacermashippingId)
    {
        return $this->setData(self::MARKETPLACERMASHIPPING_ID, $marketplacermashippingId);
    }

    /**
     * @inheritDoc
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setParentId($parentId)
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * @inheritDoc
     */
    public function getShippingNumber()
    {
        return $this->getData(self::SHIPPING_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setShippingNumber($shippingNumber)
    {
        return $this->setData(self::SHIPPING_NUMBER, $shippingNumber);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
}

