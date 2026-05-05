<?php

namespace Branch8\WebkulMpBuyerSellerChatCustomerUi\Block\Customer\AbstractChatBlock;
class MiniChat extends \Branch8\WebkulMpBuyerSellerChatCustomerUi\Block\Customer\AbstractChatBlock
{
    /**
     * @return bool|string
     */
    public function getJsLayout()
    {
        if(!$customerId = $this->customerSessionFactory->create()->getCustomerId()){
            return '';
        }

        $jsonJsLayout = parent::getJsLayout();
        $jsonConfig = $this->serializerJson->unserialize($jsonJsLayout);
        $path = 'components/miniChat';
        return $this->serializerJson->serialize(
            $this->arrayManager->merge($path, $jsonConfig, $this->getConfig())
        );
    }
}
