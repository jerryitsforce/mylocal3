<?php
namespace Branch8\GiftToFriend\Block\Confirmation;

class ConfirmSuccess extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Branch8\Customer\Helper\Info
     */
    protected $infoHelper;

    /**
     * @var \Branch8\GiftToFriend\Helper\Data
     */
    protected $giftHelper;
     

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Branch8\Customer\Helper\Info $infoHelper
     * @param \Branch8\GiftToFriend\Helper\Data $giftHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Branch8\Customer\Helper\Info $infoHelper,
        \Branch8\GiftToFriend\Helper\Data $giftHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->infoHelper = $infoHelper;
        $this->giftHelper = $giftHelper;
    }

    public function getOrderDetail()
    {
        if ($this->getData('orderDetail')) {
            return $this->getData('orderDetail');
        }
        return null;
    }

    public function getOrder()
    {
        if ($this->getData('parentOrder')) {
            return $this->getData('parentOrder');
        }
        return null;
    }

    public function getCustomerServiceUrl() {
       return $this->getUrl('helpdesk/ticket');
    }

    public function getFAQUrl() {
        $faqCategoryId = $this->giftHelper->getFAQCatConfig();
        if($faqCategoryId && $faqCategoryId !== '') {
            return $this->getUrl('faq',['_query' => ['tabid' => $faqCategoryId]]);
        }
        return $this->getUrl('faq');
    }

    public function getContactServiceUrl($order) {
        return $this->getUrl('gift-order/giftbox/service', ['id' => $order->getId()]);
    }

    /**
     * @param ParentOrder $order
     * @return mixed
     */
    public function renderBillingInfo($order, $type = '')
    {
        $address = $order->getBillingAddress();
        if (!$address) {
            return '';
        }

        if($type === 'name') {
            return $this->infoHelper->getOAuthName('', $address->getFirstName()?? '');
        } else if($type === 'phone') {
            return $this->infoHelper->getOAuthPhone($address->getTelephone()?? '');
        } else if($type === 'street') {
            return $this->infoHelper->getHomeDeliveryAdress($address?? '');
        } else {
           return $address;
        }
    }

    public function isVirtual($order) {
        return $order ? $order->isVirtual() : false;
    }

}
