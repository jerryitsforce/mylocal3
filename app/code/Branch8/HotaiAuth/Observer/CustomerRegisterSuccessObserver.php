<?php

namespace Branch8\HotaiAuth\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @param  \Magento\Customer\Model\CustomerFactory  $customerFactory
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory
    )
    {
        $this->customerFactory = $customerFactory;
    }

    /**
     * @param  Observer  $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
//        $data = $observer['account_controller'];
//        try {
//            $paramData = $data->getRequest()->getParams();
//            if (!empty($paramData['is_seller']) && !empty($paramData['profileurl']) && $paramData['is_seller'] == 1) {
//                $customer = $this->customerFactory->create()->load($observer->getCustomer()->getId());
//                $customerDataModel = $customer->getDataModel();
//                $customerDataModel->setCustomAttribute('platform', 'seller');
//                $customer->updateData($customerDataModel);
//                $customer->save();
//            }
//        }catch (\Exception $exception){
//
//        }
    }
}
