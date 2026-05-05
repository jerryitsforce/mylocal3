<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatForParentOrder\Model;

use Branch8\WebkulMpBuyerSellerChat\Model\GeneralConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Config Class
 */
class ConfigData
{
    const XML_PATH_SHOW_CHAT_LINKS= 'buyer_seller_chat/parent_order/contact_links/enable';

    const XML_PATH_HIDDEN_STATUS_ORDER = 'buyer_seller_chat/parent_order/contact_links/hide_for_order_statues';

    private ScopeConfigInterface $scopeConfig;
    private GeneralConfig $generalConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        GeneralConfig $generalConfig,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->generalConfig = $generalConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get Hidden Order Statuses
     * @return array|string[]
     */
    public function getHiddenOrderStatus()
    {
        $statues = (string)$this->scopeConfig->getValue(self::XML_PATH_HIDDEN_STATUS_ORDER);
        if ($statues) {
            return explode(',', $statues);
        }
        return [];
    }


}
