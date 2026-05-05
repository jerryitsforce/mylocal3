<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel;
/**
 *
 */
class MessageData extends \Webkul\MpBuyerSellerChat\Model\ResourceModel\Message
{
    /**
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageData $object
     * @param $uniqueId
     * @return \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageData
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByUniqueID(
        \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageData $object,
        string                                                      $uniqueId
    )
    {
        $select = $this->getConnection()->select()->from(
            $this->getMainTable(),
            '*'
        )->where('unique_id = ?', $uniqueId);
        $row = $this->getConnection()->fetchRow($select);
        if ($row) {
            $object->setData($row);
        }
        return $object;
    }
}
