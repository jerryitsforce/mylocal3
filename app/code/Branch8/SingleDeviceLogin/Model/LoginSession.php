<?php

namespace Branch8\SingleDeviceLogin\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\AbstractModel;

class LoginSession extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(ResourceModel\LoginSession::class);
    }

    /**
     * @param $customerId
     * @param $deviceType
     * @return LoginSession
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByCustomerIdAndDeviceType($customerId, $deviceType)
    {
        $select = $this->getResource()->getConnection()->select();
        $select->from($this->getResource()->getMainTable())
            ->where('customer_id=?', $customerId)
            ->where('device_type=?', $deviceType);
        $data = $this->getResource()->getConnection()->fetchRow($select);
        if ($data) {
            $this->setData($data);
        }
        return $this;
    }


}
