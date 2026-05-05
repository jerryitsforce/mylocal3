<?php

namespace Branch8\Customer\Model;

use Magento\Framework\Model\AbstractModel;

class PendingEmployee extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Branch8\Customer\Model\ResourceModel\PendingEmployee::class);
    }

    /**
     * Get entity id
     *
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->getData('entity_id');
    }

    /**
     * Set entity id
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData('entity_id', $entityId);
    }

    /**
     * Get cellphone
     *
     * @return string|null
     */
    public function getCellphone()
    {
        return $this->getData('cellphone');
    }

    /**
     * Set cellphone
     *
     * @param string $cellphone
     * @return $this
     */
    public function setCellphone($cellphone)
    {
        return $this->setData('cellphone', $cellphone);
    }


    /**
     * Get organization identity
     *
     * @return string|null
     */
    public function getOrganizationIdentity()
    {
        return $this->getData('organizationIdentity');
    }

    /**
     * Set organization identity
     *
     * @param string $organizationIdentity
     * @return $this
     */
    public function setOrganizationIdentity($organizationIdentity)
    {
        return $this->setData('organizationIdentity', $organizationIdentity);
    }

    /**
     * Get category identity
     *
     * @return string|null
     */
    public function getCategoryIdentity()
    {
        return $this->getData('categoryIdentity');
    }

    /**
     * Set category identity
     *
     * @param string $categoryIdentity
     * @return $this
     */
    public function setCategoryIdentity($categoryIdentity)
    {
        return $this->setData('categoryIdentity', $categoryIdentity);
    }

    /**
     * Get is enabled
     *
     * @return bool
     */
    public function getIsEnabled()
    {
        return (bool) $this->getData('isEnabled');
    }

    /**
     * Set is enabled
     *
     * @param bool $isEnabled
     * @return $this
     */
    public function setIsEnabled($isEnabled)
    {
        return $this->setData('isEnabled', (bool) $isEnabled);
    }


    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData('created_at');
    }

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData('updated_at');
    }

    /**
     * Set updated at
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData('updated_at', $updatedAt);
    }

}