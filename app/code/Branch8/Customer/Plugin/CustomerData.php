<?php

namespace Branch8\Customer\Plugin;

use Magento\Customer\CustomerData\Customer;

class CustomerData{

    const DEFAULT_MASKED_CHAR = '○';

    protected $customerSession;

    protected $_groupRepository;

    protected $_storeManager;
    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Branch8\Customer\Helper\Data $b8CustomerHelper
    ){
        $this->customerSession = $customerSession;
        $this->_groupRepository = $groupRepository;
        $this->_storeManager = $storeManager;
        $this->b8CustomerHelper = $b8CustomerHelper;
    }
    
    public function afterGetSectionData(Customer $subject, array $result): array{
        if($this->customerSession->getCustomerId()){
            $groupId = $this->customerSession->getCustomerGroupId();
            $group = $this->_groupRepository->getById($groupId);
            $mediaUrl = $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            if((string)$group->getExtensionAttributes()->getIcon() != ''){
                $result['icon'] = $mediaUrl.$group->getExtensionAttributes()->getIcon();
            }else{
                $result['icon'] = '';
            }

            $result['label'] = $group->getExtensionAttributes()->getLabel();

            $customer = $this->customerSession->getCustomer();
            if(!empty($customer->getData('nickname'))){
                $result['fullname'] = $customer->getData('nickname');
            } else {
                $result['fullname'] = $this->maskedInfo($result['fullname']);
            }
        }
        return $result;
    }

    /**
    *
    * @param string
    * @param string
    * @param int
    * @return string
    */
    private function maskedInfo(string $info, $maskedChar = self::DEFAULT_MASKED_CHAR)
    {
        if (empty($info)) {
            return '';
        }

        $info = trim($info);
        $infoLength = mb_strlen($info, 'UTF-8');

        if ($infoLength <= 1) {
            return $info;
        }

        $maskedPart = str_repeat($maskedChar, 2);
        $unmaskedPart = mb_substr($info, -1, 1, 'UTF-8');

        return $maskedPart . $unmaskedPart . ' ';
    }
}