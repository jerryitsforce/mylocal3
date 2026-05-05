<?php

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

class GetCcAddress
{
    const XML_PATH_CC_SUBACCOUNT = 'buyer_seller_chat/notification/cc_sub_accounts';
    private \Webkul\SellerSubAccount\Helper\Data $subAccountHelper;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    private ScopeConfigInterface $scopeConfig;


    /**
     * @param \Webkul\SellerSubAccount\Helper\Data $subAccountHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Webkul\SellerSubAccount\Helper\Data $subAccountHelper,
        ResourceConnection                   $resourceConnection,
        ScopeConfigInterface                 $scopeConfig
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $resourceConnection;
        $this->subAccountHelper = $subAccountHelper;
    }

    /**
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation $chatProfileInformation
     * @return array
     */
    public function get(\Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\ChatProfileInformation $chatProfileInformation)
    {
        $sellerId = (int)$chatProfileInformation->getObjectId();
        $isSeller = $this->subAccountHelper->isSeller($sellerId);
        $enable = $this->scopeConfig->getValue(self::XML_PATH_CC_SUBACCOUNT);
        $records = [];
        if ($enable && $chatProfileInformation->getRegisteredAs() !== ChatRole::SELLER || !$isSeller) {
            return $records;
        }
        if ($rows = $this->getSellerAllSubAccounts($sellerId)) {
            $records = $rows;
        }
        return $records;
    }

    /**
     * @param $sellerId
     * @return array
     */
    private function getSellerAllSubAccounts($sellerId)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('marketplace_sub_accounts as msa', [])
            ->join('customer_entity as c', 'msa.customer_id=c.entity_id',
                [
                    'email' => 'email',
                    'name' => new \Zend_Db_Expr('CONCAT (COALESCE(`c`.`firstname`,"")," ",COALESCE(`c`.`lastname`,""))')
                ]
            )->where('msa.seller_id = ? ', $sellerId)
            ->where('status = ?', 1)
            ->where('FIND_IN_SET(\'seller_chat\', permission_type)');
        return $this->resourceConnection->getConnection()->fetchAll($select);
    }
}
