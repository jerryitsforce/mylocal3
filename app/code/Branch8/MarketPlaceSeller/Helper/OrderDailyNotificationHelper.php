<?php

namespace Branch8\MarketPlaceSeller\Helper;

use Branch8\MarketPlaceSeller\Logger\Logger;
use Magento\Customer\Model\CustomerFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\ItemFactory;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Store\Model\ScopeInterface;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory as SalesListCollection;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\XlsxFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Webkul\Marketplace\Model\SellerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Branch8\Smtp\Helper\EmailBuilder;
use Webkul\Marketplace\Helper\Orders as HelperOrders;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory;
use Branch8\Rma\Helper\Config\StatusLabel;
use Magento\Framework\App\State;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ResourceConnection;
use Webkul\SellerSubAccount\Observer\SellerStatusChange;
use Ecpay\General\Helper\Services\Common\OrderService;
use Amasty\Rolepermissions\Helper\Data as RoleHelperData;

class OrderDailyNotificationHelper
{
    const ENABLE = 'email_notification/order_daily_notification_to_seller/enable';
    const EMAIL_TEMPLATE = 'email_notification/order_daily_notification_to_seller/template';
    const COPY_TO = 'email_notification/order_daily_notification_to_seller/copy_to';
    const COPY_METHOD = 'email_notification/order_daily_notification_to_seller/copy_method';
    const ENGLISH_HEADER_ENABLE = 'email_notification/order_daily_notification_to_seller/english_header';
    const FLOW_STATUS = 'email_notification/order_daily_notification_to_seller/flow_status';
    const FILE_NAME_PREFIX = 'email_notification/order_daily_notification_to_seller/file_prefix';

    const HK_HEADER = [
        "項次",
        "特約商名稱",
        "訂單成立日期",
        "訂單編號",
        "訂單狀態",
        "退換貨狀態",
        "商品編號(系統回押物流單用)",
        "廠商貨號(廠商撿貨用)",
        "主SKU",
        "Option SKU",
        "Variation SKU",
        "規格標題 | 規格名稱",
        "商品名稱",
        "數量",
        "成本價",
        "售價",
        "行小計(售價*數量)",
        "總折抵點數",
        "總刷卡金額",
        "付款狀態",
        "購買人Email",
        "訂貨人姓名",
        "訂貨人電話",
        "收件人姓名",
        "收件人電話",
        "配送方式",
        "超商店號",
        "超商門市名稱",
        "配送地址",
        "訂單備註說明",
        "退貨申請日",
        "退貨單號",
        "退換貨原因",
        "退貨地址",
        "退貨超商店號",
        "退貨超商門市名稱",
    ];

    const HEADER_COLOR = 'ffffff';
    const BACKGROUND_COLOR = '595959';

    protected $spreedSheet = null;

    protected $pendingPaymentStatus = [
        'pending_payment',
        'ecpay_pending_payment',
        'pending',
        'pending_paypal'
    ];

    protected Logger $logger;

    protected OrderRepository $orderRepository;

    protected SalesListCollection $salesListCollectionFactory;
    protected OrderFactory $orderFactory;
    protected ItemFactory $itemFactory;

    protected ScopeConfigInterface $scopeConfig;
    protected StateInterface $inlineTranslation;

    protected TransportBuilder $transportBuilder;

    protected StoreManagerInterface $storeManager;

    protected Spreadsheet $spreadsheet;

    protected XlsxFactory $xlsx;

    protected DirectoryList $directoryList;

    protected TimezoneInterface $timezone;
    protected SellerFactory $sellerFactory;

    protected CustomerRepositoryInterface $customerRepository;
    protected EmailBuilder $emailBuilder;

    protected HelperOrders $helperOrder;

    protected CollectionFactory $rmaDetailCollectionFactory;

    protected StatusLabel $statusLabel;

    protected State $appState;

    protected UrlInterface $url;

    protected ProductFactory $productFactory;
    protected ResourceConnection $resourceConnection;

    protected SellerStatusChange $sellerStatusChange;

    protected CustomerFactory $customerFactory;

    protected OrderService $orderService;

    protected RoleHelperData $roleHelperData;

    protected $template;

    protected $initItemCollection;

    public function __construct(
        Logger                      $logger,
        OrderRepository             $orderRepository,
        OrderFactory                $orderFactory,
        SalesListCollection         $salesListCollectionFactory,
        State                       $appState,
        TransportBuilder            $transportBuilder,
        ScopeConfigInterface        $scope,
        StoreManagerInterface       $storeManager,
        Spreadsheet                 $spreadsheet,
        XlsxFactory                 $xlsx,
        DirectoryList               $directoryList,
        TimezoneInterface           $timezone,
        SellerFactory               $sellerFactory,
        CustomerRepositoryInterface $customerRepository,
        EmailBuilder                $emailBuilder,
        HelperOrders                $helperOrder,
        ItemFactory                 $itemFactory,
        CollectionFactory           $rmaDetailCollectionFactory,
        StatusLabel                 $statusLabel,
        UrlInterface                $url,
        ProductFactory              $productFactory,
        SellerStatusChange          $sellerStatusChange,
        CustomerFactory             $customerFactory,
        OrderService                $orderService,
        RoleHelperData              $roleHelperData,
        ResourceConnection          $resourceConnection
    )
    {
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->itemFactory = $itemFactory;
        $this->salesListCollectionFactory = $salesListCollectionFactory;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scope;
        $this->storeManager = $storeManager;
        $this->spreadsheet = $spreadsheet;
        $this->xlsx = $xlsx;
        $this->directoryList = $directoryList;
        $this->timezone = $timezone;
        $this->sellerFactory = $sellerFactory;
        $this->customerRepository = $customerRepository;
        $this->emailBuilder = $emailBuilder;
        $this->helperOrder = $helperOrder;
        $this->rmaDetailCollectionFactory = $rmaDetailCollectionFactory;
        $this->statusLabel = $statusLabel;
        $this->appState = $appState;
        $this->url = $url;
        $this->productFactory = $productFactory;
        $this->sellerStatusChange = $sellerStatusChange;
        $this->customerFactory = $customerFactory;
        $this->orderService = $orderService;
        $this->roleHelperData = $roleHelperData;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param null $sellerId
     * @param bool $withBuildData
     * @return array|void
     */
    public function getOrderDataForExcel($sellerId = null, bool $withBuildData = true)
    {
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
            $this->logger->info('-----------------------------Cron Job Order daily notification to seller: Start ----------------------------------');
        }
        // Check if the feature is enabled
        $isEnabled = $this->scopeConfig->getValue(
            self::ENABLE,
            ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->info('-----------------------------Cron Job Order daily notification to seller: END due to this function is disable ----------------------------------');
            }
            return;
        }
        $itemCollection = $this->initItemCollection($sellerId);

        return ($withBuildData) ? $this->buildData($itemCollection) : $itemCollection->getSize();
    }

    public function initItemCollection($sellerId = null)
    {
        if ($this->initItemCollection) {
            return $this->initItemCollection;
        }
        $flowStatus = $this->getFlowStatus();

        $itemCollection = $this->itemFactory->create()->getCollection();
        $itemCollection->getSelect()->join(['salelist' => 'marketplace_saleslist'],
            'main_table.item_id = salelist.order_item_id',
            []);
        $itemCollection->addFieldToFilter('main_table.flow_status', ['in' => $flowStatus]);
        if ($sellerId) {
            $itemCollection->addFieldToFilter('salelist.seller_id', $sellerId);
        } else {
            $rule = $this->roleHelperData->currentRule();
            if (!empty($rule) && $rule->getSellers()) {
                $itemCollection->addFieldToFilter('salelist.seller_id', ['in' => $rule->getSellers()]);
            }
        }
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
            $this->logger->info("SQL: " . $itemCollection->getSelectSql());
        }
        return $this->initItemCollection = $itemCollection;
    }

    /**
     * @param $itemCollection
     * @return array
     */
    public function buildData($itemCollection): array
    {
        $orderData = [];
        $index = 0;
        try {
            $listOrderId = [];
            $listProductId = [];
            $listOrder = [];
            $listProduct = [];
            foreach ($itemCollection as $item) {
                $listOrderId[] = $item->getData('order_id');
                $listProductId[] = $item->getData('product_id');
            }
            $listOrderId = array_unique($listOrderId);
            $listProductId = array_unique($listProductId);
            if (!empty($listOrderId)) {
                $orderQuery = $this->orderFactory->create()->getCollection()
                    ->addFieldToFilter('entity_id', ['in' => $listOrderId]);
                foreach ($orderQuery as $order) {
                    $listOrder[$order->getId()] = $order;
                }
            }
            if (!empty($listProductId)) {
                $connection = $this->resourceConnection->getConnection();
                $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
                $query = $connection->select()
                    ->from($productTable)
                    ->where('entity_id IN (?)', $listProductId);
                $productRows = $connection->fetchAll($query);
                foreach ($productRows as $productRow) {
                    $listProduct[$productRow['entity_id']] = $productRow;
                }
            }
            foreach ($itemCollection as $item) {
                $sellerId = $item->getData('seller_id');
                $flowStatus = $item->getData('flow_status');
                try {
                    $customer = $this->customerRepository->getById($sellerId);
                    $sellerName = $customer->getFirstname() . " " . $customer->getLastname();
                } catch (\Exception $e) {
                    $sellerName = '';
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                        $this->logger->error('Seller ID ' . $sellerId . ' does not exist.');
                    }
                }
                $index += 1;
                $orderId = $item->getData('order_id');
                if (isset($listOrder[$orderId])) {
                    $orderModel = $listOrder[$orderId];
                } else {
                    $this->logger->error("The seller " . $item->getData('seller_id') . " linked to an order which does not exist. " . $item->getData('order_id'));
                    continue;
                }
                /*try {
                    $orderModel = $this->orderFactory->create()->load($orderId);
                } catch (\Exception $e) {
                    $this->logger->error("The seller " . $item->getData('seller_id') . " linked to an order which does not exist. " . $item->getData('order_id'));
                    $this->logger->critical($e);
                    continue;
                }*/

                if (strpos($orderModel->getIncrementId(), 'hotai_order_') !== false) {
                    continue;
                }

                $billingAddress = $orderModel->getBillingAddress();
                $shippingAddress = $orderModel->getShippingAddress();
                $paymentStatus = !in_array($orderModel->getStatus(), $this->pendingPaymentStatus) ? "已付款" : null;
                list($rmaApplicationDate, $rmaOrderId, $rmaStatus, $rmaDetail) = $this->getRmaData($orderId, $item->getProductId());

                if (!$item->getParentItem()) {
                    $specialNameStr = $this->getSpecificationName($item);
                    $finalShippingAddress = null;
                    if ($shippingAddress) {
                        if ($orderModel->getShippingMethod() == 'hotai_711_hotai_711') {
                            $finalShippingAddress = $shippingAddress->getPostcode() . $shippingAddress->getStreet()[0];
                        } else {
                            $finalShippingAddress = $shippingAddress->getPostcode() . $shippingAddress->getRegion() . $shippingAddress->getCity() . $shippingAddress->getStreet()[0];
                        }
                    }
                    $originSku = $this->processColumnData($item->getData());
                    $originSku = $item->getData('variation_sku') ?: ($item->getData('option_sku') ?: $originSku);
                    // Direct SQL to fetch product row
                    /*$connection = $this->resourceConnection->getConnection();
                    $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
                    $productRow = $connection->fetchRow(
                        $connection->select()->from($productTable)->where('entity_id = ?', $item->getData('product_id'))
                    );*/
                    $mainSku = $listProduct[$item->getData('product_id')]['sku'] ?? null;
                    $ccShopNumber =  $billingAddress->getData('cvs_store_code');
                    $ccShopName =  $billingAddress->getData('cvs_store_name');
                    if ($orderModel->getShippingMethod() == 'hotai_delivery_hotai_delivery') {
                        $ccShopName = null;
                        $ccShopNumber = null;
                    }
                    $orderData[] = [
                        'index' => $index,
                        'seller_name' => $sellerName,
                        'purchased_data' => $this->timezone->date($orderModel->getCreatedAt())->format('Y-m-d H:i:s'),
                        'order_number' => $orderModel->getIncrementId(),
                        'order_status' => $orderModel->getStatusLabel() ?? $orderModel->getStatus(),
                        'rma_status' => __($rmaStatus),
                        'sku' => $item->getSku(),
                        'original_sku' => $originSku,
                        'main_sku' => $mainSku,
                        'option_sku' => $item->getData('option_sku'),
                        'variation_sku' => $item->getData('variation_sku'),
                        'specification_name' => $specialNameStr,
                        'item_name' => $item->getName(),
                        'qty' => $item->getQtyOrdered(),
                        'cost_price' => $this->formatPrice($item->getData('base_cost'), $flowStatus),
                        'selling_price' => $this->formatPrice($item->getPriceInclTax(), $flowStatus),
                        'sub_total' => $this->formatPrice($item->getQtyOrdered() * $item->getPriceInclTax(), $flowStatus),
                        'total_redeem_point' => $this->formatPrice($item->getData('row_total_point_used'), $flowStatus),
                        'payment_amount' => $this->formatPrice((int)$item->getData('row_total_incl_tax') - (int)$item->getData('row_total_point_used') - (int)$item->getData('discount_amount'), $flowStatus),
                        'payment_status' => $paymentStatus,
                        'consumer_email' => $orderModel->getCustomerEmail(),
                        'billing_name' => $billingAddress->getFirstname(),
                        'billing_phone_number' => $billingAddress->getTelephone(),
                        'shipping_name' => $shippingAddress?->getFirstname(),
                        'shipping_phone_number' => $shippingAddress?->getTelephone(),
                        'shipping_method' => $orderModel->getShippingDescription(),
                        'c&c_shop_number' => $ccShopNumber,
                        'c&c_shop_name' => $ccShopName,
                        'shipping_address' => $finalShippingAddress,
                        'Note' => $orderModel->getData('order_note'),
                        'return_date' => $rmaApplicationDate,
                        'return_order_number' => $rmaOrderId,
                        'return_reason' => $rmaDetail->getData('rma_reason'),
                        'return_address' => $rmaDetail->getData('rma_address'),
                        'return_cvs_id' => $orderModel->getEcpayLogisticCvsStoreId(),
                        'return_cvs_name' => $orderModel->getEcpayLogisticCvsStoreName(),
                    ];
                }
            }
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->critical($e);
            }
        }
        return $orderData;
    }

    public function formatPrice($price, $flowStatus) {
        if (!empty($price) && $price != 0 && $flowStatus != \Branch8\HotaiCore\Model\Order\Status::STATUS_TALLYING) {
            return "(" . (int)$price . ")";
        }
        return $price;
    }

    /**
     * @param $item
     * @return string|null
     */
    public function getSpecificationName($item)
    {
        $productOptionsData = $this->helperOrder->getProductOptions(
            $item->getProductOptions()
        );
        $specialName = [];
        if (isset($productOptionsData['options'])) {
            foreach ($productOptionsData['options'] as $option) {
                $specialName[] = $option['label'] . " | " . $option['value'];
            }
        }
        return implode(', ', $specialName);

    }

    /**
     * @param $orderId
     * @param $productId
     * @return array
     */
    public function getRmaData($orderId, $productId)
    {
        $rmaDetailItemCollection = $this->rmaDetailCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId);
        $rmaDetailItemCollection->getSelect()->where('FIND_IN_SET(?, product_id)', $productId);
        $rmaDetailItem = $rmaDetailItemCollection->getFirstItem();

        if ($rmaDetailItem->getId()) {
            $rmaStatusTitle = $this->statusLabel->getCustomerRmaStatusTitle($rmaDetailItem->getData('status'));
            return [
                $rmaDetailItem->getData('created_date'),
                $rmaDetailItem->getId(),
                $rmaStatusTitle,
                $rmaDetailItem
            ];
        }
        return [null, null, null, $rmaDetailItem];

    }


    /**
     * @return array|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getFlowStatus()
    {
        $orderStatus = $this->getConfigValue(self::FLOW_STATUS, $this->getStore()->getStoreId());
        return $orderStatus ? explode(',', $orderStatus) : [];
    }

    /**
     * @param $path
     * @param $storeId
     * @return mixed
     */
    protected function getConfigValue($path, $storeId = null): mixed
    {
        if (!$storeId) {
            $storeId = $this->getStore()->getStoreId();
        }
        return $this->scopeConfig->getValue(
            $path,
            'stores',
            $storeId
        );
    }

    /**
     * @param $sellerId
     * @return string[]
     */
    public function getFileName(): array
    {
        $prefix = $this->getConfigValue(self::FILE_NAME_PREFIX);
        $datetime = $this->timezone->date()->format('Ymd_His');
        $fileName = $prefix . "_" . $datetime . '.xlsx';
        $zipFileName = $prefix . "_" . $datetime . '.zip';
        return [$fileName, $zipFileName];
    }


    /**
     * @param $sellerId
     * @return string
     */
    public function generatePassword($sellerId): string
    {
        $seller = $this->sellerFactory->create()->getCollection()
                    ->addFieldToFilter('seller_id',$sellerId)->getFirstItem();
        $invoiceCompanyNo = trim((string)$seller->getData('invoice_company_no'));
        $prefix = 'oD';
        $monthDate = $this->timezone->date()->format('md');
        return $prefix . $monthDate . substr(trim($invoiceCompanyNo), -4);
    }

    /**
     * @return \Magento\Store\Api\Data\StoreInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStore()
    {
        return $this->storeManager->getStore();
    }

    public function processColumnData(array $item = [])
    {
        $replacePrefix = false;

        $vendorSku = '';
        if (!empty($item['variation_sku'])) {
            $vendorSku = $item['variation_sku'];
        } elseif (!empty($item['option_sku'])) {
            $vendorSku = $item['option_sku'];
        } elseif (!empty($item['origin_sku'])) {
            $vendorSku = $item['origin_sku'];
            $replacePrefix = true;
        } else {
            $replacePrefix = true;
            $vendorSku =  $item['sku'];
        }

        return $replacePrefix ? preg_replace("/^HOTAI[^-]*-/", "", (string)$vendorSku) : $vendorSku;
    }
}
