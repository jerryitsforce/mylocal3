<?php

namespace Branch8\Marketplace\Controller\Order;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Customer\Model\Url as CustomerUrl;
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
use HotaiConnected\Logistics\Api\PickupServiceInterface;
use Webkul\SellerSubAccount\Helper\Data as SubAcountHelperData;
use Branch8\Marketplace\Helper\Shipment as ShipmentHelper;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class PickupNumber extends \Webkul\Marketplace\Controller\Order
{
    /**
     * @var HelperData
     */
    protected $sellerHelper;

    /**
     * @var PickupServiceInterface
     */
    protected $pickupService;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;
    private SubAcountHelperData $subAccountHelper;

    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;

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
     * @param PickupServiceInterface $pickupService
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param ShipmentHelper $shipmentHelper
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
        PickupServiceInterface $pickupService,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        ShipmentHelper $shipmentHelper,
        SubAcountHelperData $subaccountHelper,
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
            $customerUrl, $date, $fileFactory, $creditmemoPdf, $invoicePdf,
            $mpOrdersModel, $invoiceCollection, $invoiceManagement, $productModel, $mpSellerModel,
            $logger, $invoiceService, $resultJsonFactory, $resultRawFactory
        );
        $this->sellerHelper = $sellerHelper;
        $this->pickupService = $pickupService;
        $this->subAccountHelper = $subaccountHelper;
        $this->formKeyValidator = $formKeyValidator;
        $this->shipmentHelper = $shipmentHelper;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
    }

    /**
     * @return int
     */
    private function getSellerId()
    {
        $sellerId = (int)$this->_customerSession->getCustomerId();
        $subAccount = $this->subAccountHelper->getCurrentSubAccount();
        if ($subAccount->getId()) {
            $sellerId = (int)$subAccount->getSellerId();
        }
        return $sellerId;
    }
    /**
     * Execute logistics pickup number request
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $jsonResult = $this->resultJsonFactory->create();
        // Validate form key
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $jsonResult->setData([
                'error' => 1,
                'msg' => 'Please login your seller account.'
            ]);
        }

        // Check if user is seller
        $isPartner = $this->sellerHelper->isSeller();
        if (!$isPartner) {
            return $jsonResult->setData([
                'success' => 0,
                'msg' => __('Please login seller account.')
            ]);
        }

        try {
            // Get and validate order
            $order = $this->_initOrder();
            if (!$order) {
                return $jsonResult->setData([
                    'success' => 0,
                    'msg' => __('Invalid order.')
                ]);
            }

            // Get request parameters
            $request = $this->getRequest();
            $carrier = $request->getParam('carrier');
            $orderItems = $request->getParam('order_items');

            // Validate carrier
            if (empty($carrier)) {
                return $jsonResult->setData([
                    'success' => 0,
                    'msg' => __('Please select a logistics carrier.')
                ]);
            }

            // Validate order items
            if (empty($orderItems) || !is_array($orderItems)) {
                return $jsonResult->setData([
                    'success' => 0,
                    'msg' => __('Please select at least one order item.')
                ]);
            }

            // Get seller ID
            $sellerId = $this->getSellerId();

            // Call pickup service
            $response = $this->pickupService->createPickup(
                $order->getId(),
                $sellerId,
                $carrier,
                array_values($orderItems)
            );

            if ($response->getSuccess()) {
                // Reload popup with updated data
                $layout = $this->_resultPageFactory->create()->getLayout();
                $orderUpdated = $this->_orderRepository->get($order->getId());
                $block = $layout->createBlock(\Branch8\Marketplace\Block\Order\Items::class, 'order_item')
                    ->setData('order', $orderUpdated)
                    ->setTemplate('order/items.phtml');
                $resultHtml = $block->toHtml();

                return $jsonResult->setData([
                    'success' => 1,
                    'msg' => $response->getMessage(),
                    'html' => $resultHtml
                ]);
            } else {
                $errorMsg = $response->getErrorMessage();

                // Fallback: 找不到物流設定 → 改用 Create Shipment 流程
                if (strpos($errorMsg, '找不到物流設定') !== false) {
                    return $this->fallbackCreateShipment($order, $request, $jsonResult);
                }

                return $jsonResult->setData([
                    'success' => 0,
                    'msg' => $errorMsg ?: $response->getMessage()
                ]);
            }

        } catch (\Exception $exception) {
            $this->marketplaceLogger->logException('PickupNumber', $exception, [
                'order_id' => $this->getRequest()->getParam('id'),
                'data' => $this->getRequest()->getParams(),
            ]);

            return $jsonResult->setData([
                'success' => 0,
                'msg' => __('An error occurred while creating pickup number: %1', $exception->getMessage())
            ]);
        }
    }

    /**
     * Fallback: create Magento shipment when logistics settings not found
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Controller\Result\Json $jsonResult
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function fallbackCreateShipment($order, $request, $jsonResult)
    {
        $trackingNumber = $request->getParam('tracking_number_all');
        if (empty(trim($trackingNumber ?? ''))) {
            return $jsonResult->setData([
                'success' => 0,
                'msg' => __('找不到物流設定，請輸入追蹤編號後再試一次。')
            ]);
        }

        $carrierAll = $request->getParam('carrier_all', 'custom');
        $carrierTitleAll = $request->getParam('carrier_title_all', $request->getParam('carrier', ''));
        $orderItems = $request->getParam('order_items', []);

        // Convert items format: order_items[] -> items[id] = id
        $items = [];
        foreach ($orderItems as $itemId) {
            $items[$itemId] = $itemId;
        }

        $data = [
            'carrier_all' => $carrierAll,
            'carrier_title_all' => $carrierTitleAll,
            'tracking_number_all' => $trackingNumber,
            'items' => $items
        ];

        if (!$this->shipmentHelper->validateDataForShipment($data)) {
            return $jsonResult->setData([
                'success' => 0,
                'msg' => __('出貨資料不完整，請確認所有欄位已填寫。')
            ]);
        }

        $result = $this->shipmentHelper->createShipment($order, $data);

        if ($result['success']) {
            $layout = $this->_resultPageFactory->create()->getLayout();
            $orderUpdated = $this->_orderRepository->get($order->getId());
            $block = $layout->createBlock(\Branch8\Marketplace\Block\Order\Items::class, 'order_item')
                ->setData('order', $orderUpdated)
                ->setTemplate('order/items.phtml');
            $resultHtml = $block->toHtml();

            return $jsonResult->setData([
                'success' => 1,
                'msg' => __('已自動建立出貨單（未串接物流商）'),
                'html' => $resultHtml
            ]);
        }

        return $jsonResult->setData([
            'success' => 0,
            'msg' => $result['message'] ?? __('建立出貨單失敗，請重試。')
        ]);
    }
}
