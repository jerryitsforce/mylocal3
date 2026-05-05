<?php

namespace Branch8\Customer\Plugin;

use Magenest\NotificationBox\Helper\Helper;
use Magenest\NotificationBox\Model\CustomerNotification;
use Magenest\NotificationBox\Model\ResourceModel\CustomerNotification\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class Notification{
    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var Helper
     */
    protected $helper;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param JsonFactory $resultJsonFactory
     * @param Helper $helper
     * @param CollectionFactory $collectionFactory
     * @param HelperData $subAccountHelper
     */
    public function __construct(
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        JsonFactory $resultJsonFactory,
        Helper $helper,
        CollectionFactory $collectionFactory,
        HelperData $subAccountHelper
    ){
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->collectionFactory = $collectionFactory;
        $this->subAccountHelper = $subAccountHelper;
    }


    public function aroundExecute($subject, $process){
        $data['customerNotLogin'] = true;
        $result = $this->resultJsonFactory->create();
        if($customerId = $this->helper->getCustomerId()){
            if(!($this->b8CustomerHelper->isSeller() || $this->b8CustomerHelper->isWaitForSeller() || $this->subAccountHelper->isSubAccount())){
                unset($data['customerNotLogin']);
            }
            // $data['allNotification'] = $subject->getAllCustomerNotification($customerId);
            /**
             * Now on FE, do not show the list on header, only show total, so do not need to get this
             */
            $data['allNotification'] = [];
            $data['unreadNotification'] = $this->getUnreadNotification($customerId);
        }
        return $result->setData($data);
    }

    private function getUnreadNotification($customerId){
        return $this->collectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status',CustomerNotification::STATUS_UNREAD)
            ->getSize();
    }
}