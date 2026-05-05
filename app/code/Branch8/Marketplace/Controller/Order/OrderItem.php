<?php

namespace Branch8\Marketplace\Controller\Order;

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

class OrderItem extends \Webkul\Marketplace\Controller\Order{
    /**
     * @var HelperData
     */
    protected $sellerHelper;

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
     * @param HelperData $sellerHelper
     * @param InvoiceService|null $invoiceService
     * @param JsonFactory|null $resultJsonFactory
     * @param RawFactory|null $resultRawFactory
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
        HelperData $sellerHelper,
        InvoiceService $invoiceService = null,
        JsonFactory $resultJsonFactory = null,
        RawFactory $resultRawFactory = null
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_invoiceSender = $invoiceSender;
        $this->_shipmentSender = $shipmentSender;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_shipment = $shipment;
        $this->_creditmemoSender = $creditmemoSender;
        $this->_creditmemoRepository = $creditmemoRepository;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_invoiceRepository = $invoiceRepository;
        $this->_stockConfiguration = $stockConfiguration;
        $this->_orderRepository = $orderRepository;
        $this->_orderManagement = $orderManagement;
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $resultPageFactory;
        $this->orderHelper = $orderHelper;
        $this->notificationHelper = $notificationHelper;
        $this->helper = $helper;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->saleslistFactory = $saleslistFactory;
        $this->customerUrl = $customerUrl;
        $this->date = $date;
        $this->fileFactory = $fileFactory;
        $this->creditmemoPdf = $creditmemoPdf;
        $this->invoicePdf = $invoicePdf;
        $this->mpOrdersModel = $mpOrdersModel;
        $this->invoiceCollection = $invoiceCollection;
        $this->invoiceManagement = $invoiceManagement;
        $this->productModel = $productModel;
        $this->mpSellerModel = $mpSellerModel;
        $this->logger = $logger;
        $this->invoiceService = $invoiceService ?:
            \Magento\Framework\App\ObjectManager::getInstance()->create(InvoiceService::class);
        $this->resultJsonFactory = $resultJsonFactory ?:
            \Magento\Framework\App\ObjectManager::getInstance()->create(JsonFactory::class);
        $this->resultRawFactory = $resultRawFactory ?:
            \Magento\Framework\App\ObjectManager::getInstance()->create(RawFactory::class);
        parent::__construct(
            $context, $resultPageFactory, $invoiceSender, $shipmentSender, $shipmentFactory,
            $shipment, $creditmemoSender, $creditmemoRepository, $creditmemoFactory, $invoiceRepository,
            $stockConfiguration, $orderRepository, $orderManagement, $coreRegistry, $customerSession,
            $orderHelper, $notificationHelper, $helper, $creditmemoManagement, $saleslistFactory,
            $customerUrl, $date, $fileFactory,$creditmemoPdf, $invoicePdf,
            $mpOrdersModel, $invoiceCollection, $invoiceManagement, $productModel, $mpSellerModel,
            $logger, $invoiceService, $resultJsonFactory, $resultRawFactory
        );
        $this->sellerHelper = $sellerHelper;
    }

    public function execute(){
        $isPartner = $this->sellerHelper->isSeller();
        if(!$isPartner){
            return $this->resultRedirectFactory->create()->setPath(
                '/',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }

        $order = $this->_initOrder();
        if(!$order){
            return $this->resultRedirectFactory->create()->setPath('/');
        }
        $layout = $this->_resultPageFactory->create()->getLayout();
        $block = $layout->createBlock(\Branch8\Marketplace\Block\Order\Items::class, 'order_item')
            ->setData('order', $order)
            ->setTemplate('order/items.phtml');
        $resultHtml = $block->toHtml();
        return $this->resultJsonFactory->create()->setData([
            'html' => $resultHtml,
            'increment_id' => $order->getIncrementId()
        ]);
    }
}