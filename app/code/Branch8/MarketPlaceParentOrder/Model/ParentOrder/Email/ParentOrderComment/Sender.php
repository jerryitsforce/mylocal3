<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderComment;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Area;
use Magento\Framework\DataObject;

class Sender extends ParentOrder\Email\AbstractNotifySender implements SenderInterface
{
    /**
     * @param ParentOrder $parentOrder
     * @param string $comment
     * @return mixed
     */
    public function send(ParentOrder $parentOrder, string $comment = '')
    {
        $customerName = $this->customerNickname->getCustomerNicknameByCustomerId($parentOrder->getDetail()->getCustomerId());
        $this->identityContainer->setStore($parentOrder->getStore());
        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($parentOrder->getDetail()->getCustomerEmail());
        $this->appEmulation->startEnvironmentEmulation(
            $parentOrder->getDetail()->getStoreId(),
            Area::AREA_FRONTEND, true
        );
        $transport = [
            'order' => $parentOrder,
            'comment' => $comment,
            'detail' => $parentOrder->getDetail(),
            'billing' => $parentOrder->getBillingAddress(),
            'store' => $parentOrder->getStore(),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($parentOrder),
            'formattedBillingAddress' => $this->getFormattedBillingAddress($parentOrder),
            'order_data' => [
                'customer_name' => $customerName,
                'frontend_status_label' => $parentOrder->getDetail()->getFrontendStatusLabel()
            ]
        ];
        $transportObject = new DataObject($transport);
        $this->appEmulation->stopEnvironmentEmulation();

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_parent_order_comment_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());
        return $this->checkAndSend($parentOrder);
    }
}
