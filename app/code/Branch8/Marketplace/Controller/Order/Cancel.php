<?php

namespace Branch8\Marketplace\Controller\Order;

use Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement;
use Branch8\HotaiCore\Model\Order\Status;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\Service\InvoiceService;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Helper\Notification as NotificationHelper;
use Webkul\Marketplace\Model\OrdersFactory as MpOrdersModel;
use Webkul\Marketplace\Model\SaleslistFactory;
use Webkul\Marketplace\Model\SellerFactory as MpSellerModel;

class Cancel extends \Webkul\Marketplace\Controller\Order{

    protected $parenOrderManagement;
    private OrderRepositoryInterface $orderRepository;
    private \Branch8\Marketplace\Service\MarketplaceLogger $marketplaceLogger;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param InvoiceSender $invoiceSender
     * @param ShipmentSender $shipmentSender
     * @param ShipmentFactory $shipmentFactory
     * @param Shipment $shipment
     * @param CreditmemoSender $creditmemoSender
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderManagementInterface $orderManagement
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Webkul\Marketplace\Helper\Orders $orderHelper
     * @param NotificationHelper $notificationHelper
     * @param HelperData $helper
     * @param \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement
     * @param SaleslistFactory $saleslistFactory
     * @param CustomerUrl $customerUrl
     * @param DateTime $date
     * @param FileFactory $fileFactory
     * @param \Webkul\Marketplace\Model\Order\Pdf\Creditmemo $creditmemoPdf
     * @param \Webkul\Marketplace\Model\Order\Pdf\Invoice $invoicePdf
     * @param MpOrdersModel $mpOrdersModel
     * @param InvoiceCollection $invoiceCollection
     * @param \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement
     * @param \Magento\Catalog\Model\ProductFactory $productModel
     * @param MpSellerModel $mpSellerModel
     * @param \Psr\Log\LoggerInterface $logger
     * @param InvoiceService|null $invoiceService
     * @param JsonFactory|null $resultJsonFactory
     * @param RawFactory|null $resultRawFactory
     * @param ParenOrderManagement $parenOrderManagement
     * @param \Branch8\Marketplace\Service\MarketplaceLogger|null $marketplaceLogger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        InvoiceSender $invoiceSender,
        ShipmentSender $shipmentSender,
        ShipmentFactory $shipmentFactory,
        Shipment $shipment,
        CreditmemoSender $creditmemoSender,
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        StockConfigurationInterface $stockConfiguration,
        OrderRepositoryInterface $orderRepository,
        OrderManagementInterface $orderManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Webkul\Marketplace\Helper\Orders $orderHelper,
        NotificationHelper $notificationHelper,
        HelperData $helper,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement,
        SaleslistFactory $saleslistFactory,
        CustomerUrl $customerUrl,
        DateTime $date,
        FileFactory $fileFactory,
        \Webkul\Marketplace\Model\Order\Pdf\Creditmemo $creditmemoPdf,
        \Webkul\Marketplace\Model\Order\Pdf\Invoice $invoicePdf,
        MpOrdersModel $mpOrdersModel,
        InvoiceCollection $invoiceCollection,
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagement,
        \Magento\Catalog\Model\ProductFactory $productModel,
        MpSellerModel $mpSellerModel,
        \Psr\Log\LoggerInterface $logger,
        InvoiceService $invoiceService = null,
        JsonFactory $resultJsonFactory = null,
        RawFactory $resultRawFactory = null,
        ParenOrderManagement $parenOrderManagement,
        \Branch8\Marketplace\Service\MarketplaceLogger $marketplaceLogger = null
    )
    {
        parent::__construct(
            $context, $resultPageFactory, $invoiceSender, $shipmentSender, $shipmentFactory,
            $shipment, $creditmemoSender, $creditmemoRepository, $creditmemoFactory, $invoiceRepository,
            $stockConfiguration, $orderRepository, $orderManagement, $coreRegistry, $customerSession,
            $orderHelper, $notificationHelper, $helper, $creditmemoManagement, $saleslistFactory,
            $customerUrl, $date, $fileFactory,$creditmemoPdf, $invoicePdf,
            $mpOrdersModel, $invoiceCollection, $invoiceManagement, $productModel, $mpSellerModel,
            $logger, $invoiceService, $resultJsonFactory, $resultRawFactory
        );
        $this->parenOrderManagement = $parenOrderManagement;
        $this->orderRepository = $orderRepository;
        $this->marketplaceLogger = $marketplaceLogger
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(\Branch8\Marketplace\Service\MarketplaceLogger::class);
    }

    /**
     * Default customer account page.
     *
     * @return \Magento\Framework\Controller\Result\RedirectFactory
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            if ($order = $this->_initOrder()) {
                try {
                    $sellerId = $this->_customerSession->getCustomerId();
                    $flag = $this->orderHelper->cancelorder($order, $sellerId);
                    if ($flag) {
                        
                        if(!$order->getData('is_paid')) {
                            $order->cancel();
                            $this->orderRepository->save($order);
                        } else {
                            $this->_eventManager->dispatch(
                                'sales_order_cancel_paied_order',
                                ['order' => $order]
                            );
                        }
                        $reason = $this->getRequest()->getParam('reason');
                        $reasonDescription = $this->getRequest()->getParam('reason_description');
                        $comment = $reason . ': ' . $reasonDescription;
                        $this->parenOrderManagement->setComment($comment);
                        $this->parenOrderManagement->updateSubOrderStatus(
                            $order, Status::STATUS_CANCELED
                        );

                        $status = is_null($order->getEcpayInvoiceCustomerIdentifier()) 
                        ? Status::STATUS_CANCELED : Status::STATUS_FINANCIAL_REVIEW;
                        
                        foreach ($order->getItemsCollection() as $item) {
                            $item->setFlowStatus($status);
                            $item->save();
                        }

                        $paidCanceledStatus = \Webkul\Marketplace\Model\Saleslist::PAID_STATUS_CANCELED;
                        $paymentCode = '';
                        $paymentMethod = '';
                        if ($order->getPayment()) {
                            $paymentCode = $order->getPayment()->getMethod();
                        }
                        $orderId = $this->getRequest()->getParam('id');

                        $this->updateSellerOrderStatus($orderId, $sellerId, $paidCanceledStatus, $paymentCode);

                        $trackingcoll = $this->mpOrdersModel->create()
                        ->getCollection()
                        ->addFieldToFilter(
                            'order_id',
                            $orderId
                        )
                        ->addFieldToFilter(
                            'seller_id',
                            $sellerId
                        );
                        foreach ($trackingcoll as $tracking) {
                            $tracking->setTrackingNumber('canceled');
                            $tracking->setCarrierName('canceled');
                            $tracking->setIsCanceled(1);
                            $tracking->setOrderStatus('canceled');
                            $tracking->save();
                        }
                        $this->messageManager->addSuccess(
                            __('The order has been cancelled.')
                        );
                        $this->_eventManager->dispatch(
                            'mp_order_cancel_after',
                            ['seller_id' => $sellerId, 'order' => $order]
                        );
                    } else {
                        $this->messageManager->addError(
                            __('You are not permitted to cancel this order.')
                        );

                        return $this->resultRedirectFactory->create()->setPath(
                            '*/*/history',
                            ['_secure' => $this->getRequest()->isSecure()]
                        );
                    }
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->marketplaceLogger->logException('Cancel', $e, [
                        'order_id' => $order->getId(),
                    ]);
                    $this->messageManager->addError(
                        __('We can\'t send the email order right now.')
                    );
                }

                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/view',
                    [
                        'id' => $order->getEntityId(),
                        '_secure' => $this->getRequest()->isSecure(),
                    ]
                );
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    '*/*/history',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }

    /**
     * Update Seller Order Status.
     *
     * @param int $orderId
     * @param int $sellerId
     * @param float $paidCanceledStatus
     * @param string $paymentCode
     * @return void
     */
    public function updateSellerOrderStatus($orderId, $sellerId, $paidCanceledStatus, $paymentCode)
    {
        $collection = $this->saleslistFactory->create()
        ->getCollection()
        ->addFieldToFilter(
            'order_id',
            ['eq' => $orderId]
        )
        ->addFieldToFilter(
            'seller_id',
            ['eq' => $sellerId]
        );
        foreach ($collection as $saleproduct) {
            $saleproduct->setCpprostatus(
                $paidCanceledStatus
            );
            $saleproduct->setPaidStatus(
                $paidCanceledStatus
            );
            if ($paymentCode == 'mpcashondelivery') {
                $saleproduct->setCollectCodStatus(
                    $paidCanceledStatus
                );
                $saleproduct->setAdminPayStatus(
                    $paidCanceledStatus
                );
            }
            $saleproduct->save();
        }
    }

}