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
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class CreateShipment extends \Webkul\Marketplace\Controller\Order{
    /**
     * @var HelperData
     */
    protected $sellerHelper;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory
     */
    protected $shipmentItemFactory;
    /**
     * @var \Branch8\Marketplace\Helper\Import
     */
    protected $marketplaceImportHelper;

    protected $shipmentHelper;

    protected $formKeyValidator;

    /**
     * @var MarketplaceLogger
     */
    private MarketplaceLogger $marketplaceLogger;

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
     * @param MarketplaceLogger|null $marketplaceLogger
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
        \Magento\Sales\Model\ResourceModel\Order\Shipment\Item\CollectionFactory $shipmentItemFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Branch8\Marketplace\Helper\Import $marketplaceImportHelper,
        \Branch8\Marketplace\Helper\Shipment $shipmentHelper,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        MarketplaceLogger $marketplaceLogger = null,
        InvoiceService $invoiceService = null,
        JsonFactory $resultJsonFactory = null,
        RawFactory $resultRawFactory = null
    ) {
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
        $this->orderFactory = $orderFactory;
        $this->shipmentItemFactory = $shipmentItemFactory;
        $this->marketplaceImportHelper = $marketplaceImportHelper;
        $this->shipmentHelper = $shipmentHelper;
        $this->formKeyValidator  = $formKeyValidator;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute(){
        $jsonResult = $this->resultJsonFactory->create();
        if(!$this->formKeyValidator->validate($this->getRequest())){
            return $jsonResult->setData([
                'error' => 1,
                'msg' => 'Please login your seller account.'
            ]);
        }

        $isPartner = $this->sellerHelper->isSeller();
        if(!$isPartner){
            $returnData = [
                'success' => 0,
                'msg' => __('Please login seller account.')
            ];
            return $jsonResult->setData($returnData);;
        }
        try {
            $order = $this->_initOrder();
            if (!$order) {
                $returnData = [
                    'success' => 0,
                    'msg' => __('Invalid order.')
                ];
                return $jsonResult->setData($returnData);
            }
            $data = $this->getRequest()->getParams();
            if(!$this->shipmentHelper->validateDataForShipment($data)){
                return $jsonResult->setData([
                    'success' => 0,
                    'msg' => __('Fail to create shipment, please check your input data.')
                ]);
            }
            $shipment = $this->shipmentHelper->createShipment($order, $data);
            if($shipment['success']){
                //load order item popup
                $layout = $this->_resultPageFactory->create()->getLayout();
                $orderUpdated = $this->_orderRepository->get($order->getId());
                $block = $layout->createBlock(\Branch8\Marketplace\Block\Order\Items::class, 'order_item')
                    ->setData('order', $orderUpdated)
                    ->setTemplate('order/items.phtml');
                $resultHtml = $block->toHtml();
                $returnData = [
                    'success' => 1,
                    'msg' => $resultHtml
                ];
            }else{
                $returnData = [
                    'success' => 0,
                    'msg' => __('Fail to create shipment, please check your input data.')
                ];
            }
        }catch (\Exception $exception){
            $returnData = [
                'success' => 0
            ];
            $this->marketplaceLogger->logException('CreateShipment', $exception, [
                'order_id' => $this->getRequest()->getParam('id'),
                'data' => $this->getRequest()->getParams(),
            ]);
        }
        return $jsonResult->setData($returnData);
    }
}