<?php
namespace Branch8\GiftToFriend\Block\GiftBox;

class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Branch8\GiftToFriend\Helper\Confirm
     */
    protected $giftHelperConfirm;

    /**
     * @var \Branch8\GiftToFriend\Helper\Data
     */
    protected $giftHelper;

    /**
     * @var \Branch8\GiftToFriend\Model\SecurityChecker\Config
     */
    protected $securityConfig;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Branch8\GiftToFriend\Helper\Confirm $giftHelperConfirm
     * @var \Branch8\GiftToFriend\Helper\Data $giftHelper
     * @param \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Branch8\GiftToFriend\Helper\Confirm $giftHelperConfirm,
        \Branch8\GiftToFriend\Helper\Data $giftHelper,
        \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->giftHelperConfirm = $giftHelperConfirm;
        $this->giftHelper = $giftHelper;
        $this->securityConfig = $securityConfig;
    }

    public function getConfirmActionUrl() {
        return $this->getUrl('gift-order/giftbox/giftformsubmit');
    }

    public function getGiftBoxListingUrl() {
        return $this->getUrl('gift-order/giftbox/listing');
    }

    public function getSendSMSActionUrl() {
        return $this->getUrl('gift-order/giftbox/sendcode');
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
        if(!$order) {
            return $this->getUrl('gift-order/giftbox/service');
        }
        return $this->getUrl('gift-order/giftbox/service', ['id' => $order->getId()]);
    }

    public function getMaxSmsRequests() {
       return $this->securityConfig->getMaxNumberSmsRequests();
    }

    public function getLimitationTimePeriod() {
        $timePreiodConfig = $this->securityConfig->getLimitationTimePeriod();

        if($timePreiodConfig < 60){
            $timePreiod = __('%1秒', $timePreiodConfig);
        }else if($timePreiodConfig >= 60 && $timePreiodConfig < 60*60){
            $timePreiod = __('%1分', ceil($timePreiodConfig/60));
        }else{
            $timePreiod = __('%1小時', ceil($timePreiodConfig/(60*60)));
        }
        return $timePreiod;
    }

    public function getSmsLifetime() {
        return $this->giftHelperConfirm->getSmsLifetime();
    }
}
