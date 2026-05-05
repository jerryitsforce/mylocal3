<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model;

use Magento\Framework\Model\AbstractModel;

class LogisticsSettings extends AbstractModel
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'hotaiconnected_logistics_settings';

    /**
     * @var string
     */
    protected $_cacheTag = 'hotaiconnected_logistics_settings';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'hotaiconnected_logistics_settings';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\HotaiConnected\Logistics\Model\ResourceModel\LogisticsSettings::class);
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
     * Get Seller ID
     *
     * @return int
     */
    public function getSellerId()
    {
        return $this->getData('seller_id');
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
     * Get Logistics Company Name
     *
     * @return string
     */
    public function getLogisticsCompanyName()
    {
        return $this->getData('logistics_company_name');
    }

    /**
     * Get Auth Type
     *
     * @return string
     */
    public function getAuthType()
    {
        return $this->getData('auth_type');
    }

    /**
     * Get Auth Data
     *
     * @return string
     */
    public function getAuthData()
    {
        return $this->getData('auth_data');
    }

    /**
     * Get Is Active
     *
     * @return int
     */
    public function getIsActive()
    {
        return $this->getData('is_active');
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

    /**
     * Get Pickup Count
     *
     * @return int
     */
    public function getPickupCount()
    {
        return $this->getData('pickup_count');
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
     * Set Logistics Company ID
     *
     * @param string $companyId
     * @return $this
     */
    public function setLogisticsCompanyId($companyId)
    {
        return $this->setData('logistics_company_id', $companyId);
    }

    /**
     * Set Logistics Company Name
     *
     * @param string $companyName
     * @return $this
     */
    public function setLogisticsCompanyName($companyName)
    {
        return $this->setData('logistics_company_name', $companyName);
    }

    /**
     * Set Auth Type
     *
     * @param string $authType
     * @return $this
     */
    public function setAuthType($authType)
    {
        return $this->setData('auth_type', $authType);
    }

    /**
     * Set Auth Data
     *
     * @param string $authData
     * @return $this
     */
    public function setAuthData($authData)
    {
        return $this->setData('auth_data', $authData);
    }

    /**
     * Set Is Active
     *
     * @param int $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData('is_active', $isActive);
    }

    /**
     * Set Pickup Count
     *
     * @param int $count
     * @return $this
     */
    public function setPickupCount($count)
    {
        return $this->setData('pickup_count', $count);
    }
}
