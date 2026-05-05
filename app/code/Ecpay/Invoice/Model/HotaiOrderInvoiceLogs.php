<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Model;

use Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface;
use Magento\Framework\Model\AbstractModel;

class HotaiOrderInvoiceLogs extends AbstractModel implements HotaiOrderInvoiceLogsInterface
{
    /** 發票狀態(1: 開立, 2: 作廢, 3: 折讓, 4: 不開發票) */
    const PENDDING   = 0;
    const CREATED    = 1;
    const INVALID    = 2;
    const DISCOUNT   = 3;
    const NO_INVOICE = 4;
    /** 是否正逆流成  */
    const REVERSE    = 1;
    const NO_REVERSE = 0;

    protected $_eventPrefix = 'hotai_order_invoice_logs';
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Ecpay\Invoice\Model\ResourceModel\HotaiOrderInvoiceLogs::class);
    }

    /**
     * @inheritDoc
     */
    public function getHotaiOrderInvoiceLogsId()
    {
        return $this->getData(self::HOTAI_ORDER_INVOICE_LOGS_ID);
    }

    /**
     * @inheritDoc
     */
    public function setHotaiOrderInvoiceLogsId($hotaiOrderInvoiceLogsId)
    {
        return $this->setData(self::HOTAI_ORDER_INVOICE_LOGS_ID, $hotaiOrderInvoiceLogsId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceNumber()
    {
        return $this->getData(self::INVOICE_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceNumber($invoiceNumber)
    {
        return $this->setData(self::INVOICE_NUMBER, $invoiceNumber);
    }

    /**
     * @inheritDoc
     */
    public function getHotaiCheckoutNumber()
    {
        return $this->getData(self::HOTIA_CHECKOUT_NUMBER);
    }

    /**
     * @inheritDoc
     */
    public function setHotaiCheckoutNumber($hotaiCheckoutNumber)
    {
        return $this->setData(self::HOTIA_CHECKOUT_NUMBER, $hotaiCheckoutNumber);
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

    /**
     * @inheritDoc
     */
    public function getInvoiceCount()
    {
        return $this->getData(self::INVOICE_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceCount($invoiceCount)
    {
        return $this->setData(self::INVOICE_COUNT, $invoiceCount);
    }

    /**
     * @inheritDoc
     */
    public function getIsReverse()
    {
        return $this->getData(self::IS_REVERSE);
    }

    /**
     * @inheritDoc
     */
    public function setIsReverse($isReverse)
    {
        return $this->setData(self::IS_REVERSE, $isReverse);
    }

    /**
     * @inheritDoc
     */
    public function getIsCrossMonth()
    {
        return $this->getData(self::IS_CROSS_MONTH);
    }

    /**
     * @inheritDoc
     */
    public function setIsCrossMonth($isCrossMonth)
    {
        return $this->setData(self::IS_CROSS_MONTH, $isCrossMonth);
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
    public function getPointUsed()
    {
        return $this->getData(self::POINT_USED);
    }

    /**
     * @inheritDoc
     */
    public function setPointUsed($pointUsed)
    {
        return $this->setData(self::POINT_USED, $pointUsed);
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
}

