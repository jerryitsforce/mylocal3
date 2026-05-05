<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model\AdminOrder;

use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Quote\Model\Quote\Address\CustomAttributeListInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Create extends \Magento\Sales\Model\AdminOrder\Create
{
    private \Webkul\Mpsplitorder\Helper\Data $mpSplitOrderHelper;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Sales\Model\Config $salesConfig
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param LoggerInterface $logger
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Sales\Model\AdminOrder\Product\Quote\Initializer $quoteInitializer
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Customer\Api\Data\AddressInterfaceFactory $addressFactory
     * @param \Magento\Customer\Model\Metadata\FormFactory $metadataFormFactory
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\AdminOrder\EmailSender $emailSender
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param Item\Updater $quoteItemUpdater
     * @param \Magento\Framework\DataObject\Factory $objectFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Customer\Api\AccountManagementInterface $accountManagement
     * @param \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerFactory
     * @param \Magento\Customer\Model\Customer\Mapper $customerMapper
     * @param \Magento\Quote\Api\CartManagementInterface $quoteManagement
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Sales\Api\OrderManagementInterface $orderManagement
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Webkul\Mpsplitorder\Helper\Data $mpSplitOrderHelper
     * @param array $data
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param ExtensibleDataObjectConverter|null $dataObjectConverter
     * @param StoreManagerInterface|null $storeManager
     * @param CustomAttributeListInterface|null $customAttributeList
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface                 $objectManager,
        \Magento\Framework\Event\ManagerInterface                 $eventManager,
        \Magento\Framework\Registry                               $coreRegistry,
        \Magento\Sales\Model\Config                               $salesConfig,
        \Magento\Backend\Model\Session\Quote                      $quoteSession,
        \Psr\Log\LoggerInterface                                  $logger,
        \Magento\Framework\DataObject\Copy                        $objectCopyService,
        \Magento\Framework\Message\ManagerInterface               $messageManager,
        \Magento\Sales\Model\AdminOrder\Product\Quote\Initializer $quoteInitializer,
        \Magento\Customer\Api\CustomerRepositoryInterface         $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface          $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory        $addressFactory,
        \Magento\Customer\Model\Metadata\FormFactory              $metadataFormFactory,
        \Magento\Customer\Api\GroupRepositoryInterface            $groupRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface        $scopeConfig,
        \Magento\Sales\Model\AdminOrder\EmailSender               $emailSender,
        \Magento\CatalogInventory\Api\StockRegistryInterface      $stockRegistry,
        \Magento\Quote\Model\Quote\Item\Updater                   $quoteItemUpdater,
        \Magento\Framework\DataObject\Factory                     $objectFactory,
        \Magento\Quote\Api\CartRepositoryInterface                $quoteRepository,
        \Magento\Customer\Api\AccountManagementInterface          $accountManagement,
        \Magento\Customer\Api\Data\CustomerInterfaceFactory       $customerFactory,
        \Magento\Customer\Model\Customer\Mapper                   $customerMapper,
        \Magento\Quote\Api\CartManagementInterface                $quoteManagement,
        \Magento\Framework\Api\DataObjectHelper                   $dataObjectHelper,
        \Magento\Sales\Api\OrderManagementInterface               $orderManagement,
        \Magento\Quote\Model\QuoteFactory                         $quoteFactory,
        \Webkul\Mpsplitorder\Helper\Data                          $mpSplitOrderHelper,
        array                                                     $data = [],
        \Magento\Framework\Serialize\Serializer\Json              $serializer = null,
        ExtensibleDataObjectConverter                             $dataObjectConverter = null,
        StoreManagerInterface                                     $storeManager = null,
        CustomAttributeListInterface                              $customAttributeList = null
    )
    {
        parent::__construct(
            $objectManager,
            $eventManager,
            $coreRegistry,
            $salesConfig,
            $quoteSession,
            $logger,
            $objectCopyService,
            $messageManager,
            $quoteInitializer,
            $customerRepository,
            $addressRepository,
            $addressFactory,
            $metadataFormFactory,
            $groupRepository,
            $scopeConfig,
            $emailSender,
            $stockRegistry,
            $quoteItemUpdater,
            $objectFactory,
            $quoteRepository,
            $accountManagement,
            $customerFactory,
            $customerMapper,
            $quoteManagement,
            $dataObjectHelper,
            $orderManagement,
            $quoteFactory,
            $data,
            $serializer,
            $dataObjectConverter,
            $storeManager,
            $customAttributeList
        );
        $this->mpSplitOrderHelper = $mpSplitOrderHelper;
    }

    /**
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|Order|object|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createOrder()
    {
        $currentAction = (string)$this->getSession()->getCurrentAction();
        $isSuborderProcess = in_array(
            $currentAction,
            [
                'edit_sub_order',
                'reorder_sub_order'
            ]
        );
        if ($this->mpSplitOrderHelper->getIsActive() == 0
            || $isSuborderProcess
        ) {
            return parent::createOrder();
        }
        $this->_prepareCustomer();
        $this->_validate();
        $quote = $this->getQuote();
        $this->_prepareQuoteItems();
        $lastOrder = $this->quoteManagement->submitMasterQuote($quote);
        $this->removeTransferredItems();
        return $lastOrder;
    }

    /**
     * @return void
     */
    private function removeTransferredItems(): void
    {
        try {
            if (is_array($this->getSession()->getTransferredItems())) {
                foreach ($this->getSession()->getTransferredItems() as $from => $itemIds) {
                    foreach ($itemIds as $itemId) {
                        $this->removeItem($itemId, $from);
                    }
                }
                $this->recollectCart();
            }
        } catch (\Throwable $exception) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_WebkulMpsplitorder', 'exceptionlog')) {
                $this->_logger->error($exception);
            }
        }
    }
}
