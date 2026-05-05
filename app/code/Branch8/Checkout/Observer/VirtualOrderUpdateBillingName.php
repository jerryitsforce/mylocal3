<?php

namespace Branch8\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Webkul\Mpsplitorder\Model\Mpsplitorder;
use Branch8\GiftToFriend\Model\Config\Source\AddressType;

class VirtualOrderUpdateBillingName implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    private LoggerInterface $logger;

    /**
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ){
        $this->customerRepository = $customerRepository;
        $this->resourceConnection  = $resourceConnection;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        /**
         * @var $dataObject Mpsplitorder
         * Todo:
         * We can move this action into cron process to reduce the checkout time
         */
        $dataObject = $observer->getData('data_object');
        $masterQuote = $observer->getData('quote');

        try {
            if ($masterQuote->isVirtual()) {
                $conn = $this->resourceConnection->getConnection();
                try {
                    $suborders = explode(',', $dataObject->getData('order_ids'));
                } catch (\Exception $exception) {
                    $suborders = [];
                }
                if(
                    !$masterQuote->getData('is_gift_order') || 
                    ($masterQuote->getData('is_gift_order') && $masterQuote->getData('gift_address_type') == AddressType::RECIPIENT_INPUT_ADDRESS)
                ){
                    
                   
                    $customerId = $masterQuote->getCustomerId();
                    $customer = $this->customerRepository->getById($customerId);
                    $customerFirstname = $customer->getFirstname();
                    $telephone = $customer->getCustomAttribute('phone_number')->getValue();
                    foreach ($suborders as $subOrderId) {
                        /**
                         * Update firstname of billing address for virtual order
                         */
                        $sqlOrderBilling = 'update sales_order_address set firstname="' . $customerFirstname . '", lastname="", telephone="'.$telephone.'" where parent_id=' . $subOrderId;
                        $conn->query($sqlOrderBilling);
                        $sqlOrderGrid = 'update sales_order_grid set billing_name = "' . $customerFirstname . '", billing_phone="'.$telephone.'" where entity_id=' . $subOrderId;
                        $conn->query($sqlOrderGrid);
                    }
                    /** Update for Parent order */
                    
                    $sqlParentOrderBilling = 'update sales_parent_order_address set firstname="' . $customerFirstname . '", lastname="", telephone="'.$telephone.'" where parent_order_id=' . $dataObject->getId();
                    $conn->query($sqlParentOrderBilling);
                    $sqlParentOrderGrid = 'update sales_parent_order_grid set billing_name = "' . $customerFirstname . '" where entity_id=' . $dataObject->getId();
                    $conn->query($sqlParentOrderGrid);
                }
                if(
                    $masterQuote->getData('is_gift_order') && 
                    $masterQuote->getData('gift_address_type') == AddressType::BUYER_INPUT_ADDRESS
                ){
                    $billingAddress = $masterQuote->getBillingAddress();
                    $addrFirstname = $billingAddress->getFirstname();
                    $addrPhone = $billingAddress->getTelephone();
                    foreach ($suborders as $subOrderId) {
                        $sqlOrderBilling = 'update sales_order_address set firstname="' . $addrFirstname . '", lastname="", telephone="'.$addrPhone.'" where parent_id=' . $subOrderId;
                        $conn->query($sqlOrderBilling);
                        $sqlOrderGrid = 'update sales_order_grid set billing_name = "' . $addrFirstname . '", billing_phone="'.$addrPhone.'" where entity_id=' . $subOrderId;
                        $conn->query($sqlOrderGrid);
                    }
                    $sqlParentOrderBilling = 'update sales_parent_order_address set firstname="' . $addrFirstname . '", lastname="", telephone="'.$addrPhone.'" where parent_order_id=' . $dataObject->getId();
                    $conn->query($sqlParentOrderBilling);
                    
                    $sqlParentOrderGrid = 'update sales_parent_order_grid set billing_name = "' . $addrFirstname . '" where entity_id=' . $dataObject->getId();
                    $conn->query($sqlParentOrderGrid);
                    
                }
            }
        }catch (\Exception $e){
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'exceptionlog')){
                $this->logger->critical("Branch8\Checkout\Observer\VirtualOrderUpdateBillingName::execute".$e->getMessage());
                $this->logger->critical($e->getTraceAsString());
            }
        }
    }

}
