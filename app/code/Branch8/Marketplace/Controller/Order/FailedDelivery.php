<?php

namespace Branch8\Marketplace\Controller\Order;

use Branch8\Marketplace\Model\Actions\OrderFailedDeliveryHandle;
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
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class FailedDelivery extends \Webkul\Marketplace\Controller\Order
{

    protected $parenOrderManagement;

    protected $orderFailedDeliveryHandle;
    private MarketplaceLogger $marketplaceLogger;
    public function __construct(
        Context                                          $context,
        PageFactory                                      $resultPageFactory,
        InvoiceSender                                    $invoiceSender,
        ShipmentSender                                   $shipmentSender,
        ShipmentFactory                                  $shipmentFactory,
        Shipment                                         $shipment,
        CreditmemoSender                                 $creditmemoSender,
        CreditmemoRepositoryInterface                    $creditmemoRepository,
        CreditmemoFactory                                $creditmemoFactory,
        \Magento\Sales\Api\InvoiceRepositoryInterface    $invoiceRepository,
        StockConfigurationInterface                      $stockConfiguration,
        OrderRepositoryInterface                         $orderRepository,
        OrderManagementInterface                         $orderManagement,
        \Magento\Framework\Registry                      $coreRegistry,
        \Magento\Customer\Model\Session                  $customerSession,
        \Webkul\Marketplace\Helper\Orders                $orderHelper,
        NotificationHelper                               $notificationHelper,
        HelperData                                       $helper,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement,
        SaleslistFactory                                 $saleslistFactory,
        CustomerUrl                                      $customerUrl,
        DateTime                                         $date,
        FileFactory                                      $fileFactory,
        \Webkul\Marketplace\Model\Order\Pdf\Creditmemo   $creditmemoPdf,
        \Webkul\Marketplace\Model\Order\Pdf\Invoice      $invoicePdf,
        MpOrdersModel                                    $mpOrdersModel,
        InvoiceCollection                                $invoiceCollection,
        \Magento\Sales\Api\InvoiceManagementInterface    $invoiceManagement,
        \Magento\Catalog\Model\ProductFactory            $productModel,
        MpSellerModel                                    $mpSellerModel,
        \Psr\Log\LoggerInterface                         $logger,
        ParenOrderManagement                             $parenOrderManagement,
        OrderFailedDeliveryHandle                        $orderFailedDeliveryHandle,
        MarketplaceLogger                                $marketplaceLogger = null,
        InvoiceService                                   $invoiceService = null,
        JsonFactory                                      $resultJsonFactory = null,
        RawFactory                                       $resultRawFactory = null,
    )
    {
        parent::__construct(
            $context, $resultPageFactory, $invoiceSender, $shipmentSender, $shipmentFactory,
            $shipment, $creditmemoSender, $creditmemoRepository, $creditmemoFactory, $invoiceRepository,
            $stockConfiguration, $orderRepository, $orderManagement, $coreRegistry, $customerSession,
            $orderHelper, $notificationHelper, $helper, $creditmemoManagement, $saleslistFactory,
            $customerUrl, $date, $fileFactory, $creditmemoPdf, $invoicePdf,
            $mpOrdersModel, $invoiceCollection, $invoiceManagement, $productModel, $mpSellerModel,
            $logger, $invoiceService, $resultJsonFactory, $resultRawFactory
        );
        $this->orderFailedDeliveryHandle=$orderFailedDeliveryHandle;
        $this->parenOrderManagement = $parenOrderManagement;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
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
                    $this->orderFailedDeliveryHandle->execute($order);

                    $this->messageManager->addSuccess(
                        __('The order has been updated to failed delivery.')
                    );
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->marketplaceLogger->logException('FailedDelivery', $e, [
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
