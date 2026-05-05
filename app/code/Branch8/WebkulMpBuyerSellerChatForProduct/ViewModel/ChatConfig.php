<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatForProduct\ViewModel;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatRole;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatStatus;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

/**
 * Chat Config Model
 */
class ChatConfig implements ArgumentInterface
{
    private Json $json;
    private \Webkul\Marketplace\Helper\Data $helper;
    private LoggerInterface $logger;
    private GetOrCreateChatProfile $getOrCreateChatProfile;

    /**
     * @param \Webkul\Marketplace\Helper\Data $helper
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetOrCreateChatProfile          $getOrCreateChatProfile,
        \Webkul\Marketplace\Helper\Data $helper,
        Json                            $json,
        LoggerInterface                 $logger
    )
    {
        $this->helper = $helper;
        $this->json = $json;
        $this->logger = $logger;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
    }

    public function getSellerProfile()
    {

    }

    /**
     * @param Product $product
     * @return bool|string
     */
    public function getChatData(Product $product)
    {
        try {
            $sellerId = $this->helper->getSellerIdByProductId($product->getId());
            $chatProfile = $this->getOrCreateChatProfile->execute(
                (int)$sellerId,
                ChatProfileEntity::CUSTOMER,
                ChatRole::SELLER
            );
            $data = [
                'seller' => [
                    'sellerId' => $sellerId,
                    'uniqueId' => $chatProfile->getUniqueId()
                ]
            ];
        } catch (\Exception $exception) {
            $this->logger->critical('ERROR WHEN GET SELLER:' . $exception->getMessage());
            return false;
        }
        return $this->json->serialize($data);
    }

    /**
     * @return bool|string
     */
    public function getChatLinkWidgetConfig()
    {
        return $this->json->serialize([
            'pattern' => '[data-role=\'chat-with-seller\']'
        ]);
    }
}

