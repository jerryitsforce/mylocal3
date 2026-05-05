<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Logistics Waybill Model
 */
class LogisticsWaybill extends AbstractModel
{
    /**
     * Status constants
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\HotaiConnected\Logistics\Model\ResourceModel\LogisticsWaybill::class);
    }

    /**
     * Get ID
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData('id');
    }

    /**
     * Get Sales Order Item ID
     *
     * @return int
     */
    public function getSalesOrderItemId()
    {
        return $this->getData('sales_order_item_id');
    }

    /**
     * Set Sales Order Item ID
     *
     * @param int $salesOrderItemId
     * @return $this
     */
    public function setSalesOrderItemId($salesOrderItemId)
    {
        return $this->setData('sales_order_item_id', $salesOrderItemId);
    }

    /**
     * Get Seller ID
     *
     * @return int
     */
    public function getSellerId()
    {
        return $this->getData('seller_id');
    }

    /**
     * Set Seller ID
     *
     * @param int $sellerId
     * @return $this
     */
    public function setSellerId($sellerId)
    {
        return $this->setData('seller_id', $sellerId);
    }

    /**
     * Get Logistics Company ID
     *
     * @return string
     */
    public function getLogisticsCompanyId()
    {
        return $this->getData('logistics_company_id');
    }

    /**
     * Set Logistics Company ID
     *
     * @param string $logisticsCompanyId
     * @return $this
     */
    public function setLogisticsCompanyId($logisticsCompanyId)
    {
        return $this->setData('logistics_company_id', $logisticsCompanyId);
    }

    /**
     * Get Logistics Settings ID
     *
     * @return int|null
     */
    public function getLogisticsSettingsId()
    {
        return $this->getData('logistics_settings_id');
    }

    /**
     * Set Logistics Settings ID
     *
     * @param int|null $logisticsSettingsId
     * @return $this
     */
    public function setLogisticsSettingsId($logisticsSettingsId)
    {
        return $this->setData('logistics_settings_id', $logisticsSettingsId);
    }

    /**
     * Get Waybill Number
     *
     * @return string|null
     */
    public function getWaybillNumber()
    {
        return $this->getData('waybill_number');
    }

    /**
     * Set Waybill Number
     *
     * @param string $waybillNumber
     * @return $this
     */
    public function setWaybillNumber($waybillNumber)
    {
        return $this->setData('waybill_number', $waybillNumber);
    }

    /**
     * Get Tracking Number
     *
     * @return string|null
     */
    public function getTrackingNumber()
    {
        return $this->getData('tracking_number');
    }

    /**
     * Set Tracking Number
     *
     * @param string $trackingNumber
     * @return $this
     */
    public function setTrackingNumber($trackingNumber)
    {
        return $this->setData('tracking_number', $trackingNumber);
    }

    /**
     * Get Image
     *
     * @return string|null
     */
    public function getImage()
    {
        return $this->getData('image');
    }

    /**
     * Set Image
     *
     * @param string $image
     * @return $this
     */
    public function setImage($image)
    {
        return $this->setData('image', $image);
    }

    /**
     * Get Status (JSON format)
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Set Status (JSON format)
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * Get Status Data as array
     *
     * @return array|null
     */
    public function getStatusData()
    {
        $status = $this->getStatus();
        if (!$status) {
            return null;
        }

        $decoded = json_decode($status, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Get Status Code from JSON
     *
     * @return string|null
     */
    public function getStatusCode()
    {
        $statusData = $this->getStatusData();
        return $statusData['code'] ?? null;
    }

    /**
     * Get Status Message from JSON
     *
     * @return string|null
     */
    public function getStatusMessage()
    {
        $statusData = $this->getStatusData();
        return $statusData['message'] ?? null;
    }

    /**
     * Check if waybill is successful
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->getStatusCode() === self::STATUS_SUCCESS;
    }

    /**
     * Get Memo
     *
     * @return string|null
     */
    public function getMemo()
    {
        return $this->getData('memo');
    }

    /**
     * Set Memo
     *
     * @param string $memo
     * @return $this
     */
    public function setMemo($memo)
    {
        return $this->setData('memo', $memo);
    }

    /**
     * Get Created At
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Get Updated At
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }
}
