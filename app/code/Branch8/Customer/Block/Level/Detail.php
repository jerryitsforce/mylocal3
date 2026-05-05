<?php

namespace Branch8\Customer\Block\Level;

use Branch8\Customer\Helper\Data;
use Magento\Customer\Helper\View;

class Detail extends \Branch8\Customer\Block\Account\Header
{
    private Data $configHelper;
    private $vipHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $session,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail\CollectionFactory $parentOrderCollectionFactory,
        View $customerViewHelper,
        \Branch8\HotaiPoint\Helper\Api $apiHelper,
        Data $configHelper,
        \Branch8\PromotionPage\Helper\Data $vipHelper,
        \Magento\Framework\App\ResponseFactory $responseFactory,
        array $data = []
    ) {
        $this->configHelper = $configHelper;
        $this->vipHelper = $vipHelper;
        parent::__construct($context, $session, $groupCollectionFactory, $orderCollectionFactory, $timeZone, 
            $parentOrderCollectionFactory, $customerViewHelper, $apiHelper, $responseFactory, $data);
    }

    protected $groups = [];

    public function getCustomer(){
        return $this->_session->getCustomer();
    }

    public function getGroup($groupId){
        if(isset($this->groups[$groupId])){
            return $this->groups[$groupId];
        }
        $this->groups[$groupId] = $this->_groupCollectionFactory->create()
            ->addFieldToFilter('customer_group_id', $groupId)
            ->getFirstItem();

        return $this->groups[$groupId];
    }

    public function getGroupData($customerGroupId)
    {
        $group = $this->getGroup($customerGroupId);

        if (!$group->getCustomerGroupId()) {
            $data = [
                'groupName' => '',
                'groupLabel' => '',
                'groupIcon' => ''
            ];
        } else {
            $mediaUrl = $this->_storeManager->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $data = [
                'groupName' => $group->getData('customer_group_code'),
                'groupLabel' => $group->getData('label'),
                'groupIcon' => $mediaUrl.$group->getData('icon')
            ];
        }
        return $data;
    }


    public function getNextLevel(){
        $customer = $this->getCustomer();
        $currentLevel = $this->getGroup($customer->getGroupId());
        $nextLevelData = $this->getGroup($currentLevel->getData('nxt_level'));
        if($nextLevelData->getCustomerGroupId()){
            return $this->getNextLevelInfor(
                $customer->getId(),
                $nextLevelData->getCustomerGroupId(),
            );
        }

        return [];
    }

    public function getMembershipLevelDescription()
    {
        $organization = $this->getGroup($this->getCustomer()->getGroupId())->getData('organization');
        $config = $this->configHelper->getMembershipLevelDescription($organization);
        foreach ($config as &$item) {
            $customerGroup = $item['customer_group'];
            $item['group_data'] = $this->getGroupData($customerGroup);
            $conditionText = '';

            $customerGroup = $this->getGroup($customerGroup);
            if ($customerGroup->getCustomerGroupId()) {

                $conditions = $customerGroup->getData('conditions');
                $conditions = $conditions ? json_decode($conditions, true): [];
                if (empty($conditions)) {
                    $conditionText = empty($customerGroup->getData('prev_level')) ? __('預設會員等級') : "";
                } else {
                    $conditionsArray = [];
                    if(!empty($conditions['condition_num_orders'])) {
                        $conditionsArray[] = __('消費滿%1筆', $conditions['condition_num_orders']);
                    }

                    if(!empty($conditions['condition_total_value'])) {
                        $conditionsArray[] = __('滿%1元', $conditions['condition_total_value']);
                    }
                    $combineText = '';
                    if (count($conditionsArray) > 1) {
                        $combineText = $conditions['condition_combine'] = 1 ? __('或')  : __('且');
                    }

                    $conditionText = implode($combineText, $conditionsArray);
                }
            }
            $item['condition_text'] = $conditionText;
        }

        return $config;
    }

    public function getMemberLevelInstructionHtml()
    {
        $blockIdentify = $this->configHelper->getMemberLevelInstructionBlockIdentify();
        return $blockIdentify
            ? $this->getLayout()
                ->createBlock('Magento\Cms\Block\Block')
                ->setBlockId($blockIdentify)
                ->toHtml()
            : '';
    }

    public function getVipCategoryUrl(){
        return $this->vipHelper->getFirstVipCategoryUrl();
    }
}
