<?php

namespace Branch8\CustomNotification\Model\OneId;

use Magento\Framework\App\ResourceConnection;

class ValidateOneIdExist implements ValidateInterface
{
    private ResourceConnection $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $oneId
     * @return true
     */
    public function validate(string $oneId)
    {
        $select = $this->resourceConnection->getConnection()
            ->select()->from('customer_entity')->where('member_seq = ?', $oneId);
        $row = $this->resourceConnection->getConnection()->fetchOne($select);
        return (bool)$row;
    }

    /**
     * @param string $oneId
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getMessageError(string $oneId)
    {
        return __('OneID not exist "%1"', $oneId);
    }
}
