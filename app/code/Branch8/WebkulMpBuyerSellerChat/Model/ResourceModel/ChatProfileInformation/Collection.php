<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatProfileInformation;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected $_eventPrefix = 'mp_chat_profile_collection';

    protected $_eventObject = 'mp_chat_profile_collection';
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation::class,
            \Branch8\WebkulMpBuyerSellerChat\Model\ResourceModel\ChatProfileInformation::class
        );
    }
}
