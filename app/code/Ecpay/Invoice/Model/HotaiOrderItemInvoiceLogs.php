<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Model;

use Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface;
use Magento\Framework\Model\AbstractModel;

class HotaiOrderItemInvoiceLogs extends AbstractModel implements HotaiOrderItemInvoiceLogsInterface
{
    const TYPE_ITEM     = 'item';
    const TYPE_POINT    = 'point';
    const TYPE_SHIPPING = 'shipping';
    const TYPE_DISCOUNT = 'discount';
    const TYPE_FEE      = 'fee';

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Ecpay\Invoice\Model\ResourceModel\HotaiOrderItemInvoiceLogs::class);
    }

    /**
     * @inheritDoc
     */
    public function getHotaiOrderItemInvoiceLogsId()
    {
        return $this->getData(self::HOTAI_ORDER_ITEM_INVOICE_LOGS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setHotaiOrderItemInvoiceLogsId($hotaiOrderItemInvoiceLogsId)
    {
        return $this->setData(self::HOTAI_ORDER_ITEM_INVOICE_LOGS_ID, $hotaiOrderItemInvoiceLogsId);
    }

    /**
     * @inheritDoc
     */
    public function getHotaiOrderInvoiceLogId()
    {
        return $this->getData(self::HOTAI_ORDER_INVOICE_LOG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setHotaiOrderInvoiceLogId($hotaiOrderInvoiceLogId)
    {
        return $this->setData(self::HOTAI_ORDER_INVOICE_LOG_ID, $hotaiOrderInvoiceLogId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderItemId()
    {
        return $this->getData(self::ORDER_ITEM_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderItemId($orderItemId)
    {
        return $this->setData(self::ORDER_ITEM_ID, $orderItemId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderItemName()
    {
        return $this->getData(self::ORDER_ITEM_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setOrderItemName($orderItemName)
    {
        return $this->setData(self::ORDER_ITEM_NAME, $orderItemName);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * @inheritDoc
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * @inheritDoc
     */
    public function getIncludeTax()
    {
        return $this->getData(self::INCLUDE_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setIncludeTax($includeTax)
    {
        return $this->setData(self::INCLUDE_TAX, $includeTax);
    }

    /**
     * @inheritDoc
     */
    public function getExcludeTax()
    {
        return $this->getData(self::EXCLUDE_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setExcludeTax($excludeTax)
    {
        return $this->setData(self::EXCLUDE_TAX, $excludeTax);
    }

    /**
     * @inheritDoc
     */
    public function getTax()
    {
        return $this->getData(self::TAX);
    }

    /**
     * @inheritDoc
     */
    public function setTax($tax)
    {
        return $this->setData(self::TAX, $tax);
    }

    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * @inheritDoc
     */
    public function getExportReport()
    {
        return $this->getData(self::EXPORT_REPORT);
    }

    /**
     * @inheritDoc
     */
    public function setExportReport($exportReport)
    {
        return $this->setData(self::EXPORT_REPORT, $exportReport);
    }

    /**
     * @inheritDoc
     */
    public function getDiscountAmountIncludeTax()
    {
        return $this->getData(self::DISCOUNT_AMOUNT_INCLUDE_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setDiscountAmountIncludeTax($discountAmountIncludeTax)
    {
        return $this->setData(self::DISCOUNT_AMOUNT_INCLUDE_TAX, $discountAmountIncludeTax);
    }

    /**
     * @inheritDoc
     */
    public function getDiscountAmountExcludeTax()
    {
        return $this->getData(self::DISCOUNT_AMOUNT_EXCLUDE_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setDiscountAmountExcludeTax($discountAmountExcludeTax)
    {
        return $this->setData(self::DISCOUNT_AMOUNT_EXCLUDE_TAX, $discountAmountExcludeTax);
    }

    /**
     * @inheritDoc
     */
    public function getDiscountAmountTax()
    {
        return $this->getData(self::DISCOUNT_AMOUNT_TAX);
    }

    /**
     * @inheritDoc
     */
    public function setDiscountAmountTax($discountAmountTax)
    {
        return $this->setData(self::DISCOUNT_AMOUNT_TAX, $discountAmountTax);
    }
}

