<?php

namespace Branch8\MarketPlaceSeller\Cron;

use Branch8\MarketPlaceSeller\Logger\Logger;
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
use Webkul\SellerSubAccount\Observer\SellerStatusChange;

class OrderDailyNotification
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
        "訂單成立日期",
        "訂單編號",
        "訂單狀態",
        "商品編號",
        "廠商貨號",
        "規格標題 | 規格名稱",
        "商品名稱",
        "數量",
        "售價",
        "付款狀態",
        "訂貨人姓名",
        "訂貨人電話",
        "收件人姓名",
        "收件人電話",
        "配送方式",
        "超商店號",
        "超商門市名稱",
        "配送地址",
        "訂單備註說明",
        "退換貨狀態",
        "退貨申請日",
        "退貨單號",
        "1.0 訂單編號",
    ];
    const EN_HEADER = [
        "ID",
        "Purchase Date",
        "order number",
        "order status",
        "SKU",
        "Original SKU",
        "Specification name",
        "item name",
        "Quantity",
        "Selling Price",
        "Payment status",
        "Billing  name",
        "Billing phone number",
        "shipping name",
        "shipping phone number",
        "shipping method",
        "C&C shop number",
        "C&C shop name",
        "shipping address",
        "Note",
        "RMA status",
        "Return application date",
        "Return order number",
        "1.0 Order Number"
    ];

    protected $pendingPaymentStatus = [
        'pending_payment',
        'ecpay_pending_payment',
        'pending',
        'pending_paypal'
        ];

    protected $subSellerAllowPermission = [
        'marketplace/order/history',
        'marketplace/order/view',
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

    protected SellerStatusChange $sellerStatusChange;

    protected $template;

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
        SellerStatusChange          $sellerStatusChange
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
    }

    /**
     * @throws RtnException
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function execute()
    {
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
            $this->logger->info('-----------------------------Cron Job Order daily notification to seller: Start ----------------------------------');
        }
        // Check if the feature is enabled
        $isEnabled = $this->scopeConfig->getValue(
            self::ENABLE,
            ScopeInterface::SCOPE_STORE
        );

        if (!$isEnabled) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->info('-----------------------------Cron Job Order daily notification to seller: END due to this function is disable ----------------------------------');
            }
            return;
        }

        $flowStatus = $this->getFlowStatus();
        $yesterday = $this->timezone->date()->modify('-1 day');

        // Convert to UTC (since DB stores in UTC)
        $from = $this->timezone->convertConfigTimeToUtc($yesterday->format('Y-m-d 00:00:00'));
        $to = $this->timezone->convertConfigTimeToUtc($yesterday->format('Y-m-d 23:59:59'));

        $sellerCollection = $this->sellerFactory->create()->getCollection();
        $sellerCollection->getSelect()->join(
            ['cgf' => 'customer_grid_flat'],
            'main_table.seller_id = cgf.entity_id',
            [
                'name' => 'name',
                'email' => 'email'
            ]
        )->where('main_table.store_id = 0');
        $sellerCollection->getSelect()->joinLeft(
            ['flagTable' => 'marketplace_sellerflags'],
            'main_table.seller_id = flagTable.seller_id',
            [
                'flagcount' => 'count(flagTable.entity_id)'
            ]
        )->group("main_table.seller_id");

        foreach ($sellerCollection as $seller) {
            $sellerId = $seller->getData('seller_id');
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->info("Seller: " . $sellerId . " : start");
            }
            $itemCollection = $this->itemFactory->create()->getCollection();
            $itemCollection->getSelect()->join(['salelist' => 'marketplace_saleslist'],
                'main_table.item_id = salelist.order_item_id',
                []);
            $itemCollection->addFieldToFilter('main_table.flow_status',['in' => $flowStatus])
                ->addFieldToFilter('main_table.created_at',['gteq' => $from])
                ->addFieldToFilter('main_table.created_at',['lteq' => $to])
                ->addFieldToFilter('salelist.seller_id', $sellerId);
            if ($itemCollection->getSize() > 0) {
                $this->appState->emulateAreaCode(
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    [$this, 'sendNotificationToSeller'],
                    [$seller]
                );
            }
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->info("Seller: " . $sellerId . " : end");
            }
        }
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
            $this->logger->info('-----------------------------Cron Job Order daily notification to seller: End ----------------------------------');
        }
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
                $specialName[] = $option['label']. " | ". $option['value'];
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
        $rmaDetailItem = $this->rmaDetailCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('product_id', $productId)->getFirstItem();
        if ($rmaDetailItem->getId()) {
            $rmaStatusTitle = $this->statusLabel->getCustomerRmaStatusTitle($rmaDetailItem->getData('status'));
                return [$rmaDetailItem->getData('created_date'), $rmaDetailItem->getId(), $rmaStatusTitle];
        }
        return [null,null,null];

    }

    public function sendNotificationToSeller($seller)
    {
        $sellerId = $seller->getData('seller_id');
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
            $this->logger->info('Start sending a notification to seller ID ' . $sellerId);
        }
        if (!$seller->getId()) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->error('The seller ID ' . $sellerId . " does not exist.");
            }
            return false;
        }
        if (!$seller->getData('email')) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->error('The seller ID ' . $sellerId . " does not have contact email. Seller data detail is " . json_encode($seller->getData()));
            }
            return false;
        }

        $sellerEmail = $seller->getData('email');
        try {
            $customer = $this->customerRepository->get($sellerEmail);
            $sellerName = $customer->getFirstname();
        } catch (\Exception $e) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->critical($e);
                $this->logger->error("This seller " . $sellerId . " is not associated with any customer.");
            }
            return false;
        }


        $emailTemplateVariables = [];
        $emailTemplateVariables['seller_name'] = $sellerName;
        $emailTemplateVariables['base_url'] = $this->url->getBaseUrl().'marketplace/account/dashboard';
        $receivers[] = $sellerEmail;
        $subAccounts = $this->sellerStatusChange->getSubAccountsList($sellerId) ?? [];

        foreach ($subAccounts as $subAccount) {
            $subAccountId = $subAccount->getData('customer_id');
            $value = explode(",", $subAccount->getPermissionType());
            $exists = false;

            foreach ($this->subSellerAllowPermission as $permission) {
                if (in_array($permission, $value)) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                try {
                    $subAccountModel = $this->customerRepository->getById($subAccountId);
                    $receivers[] = $subAccountModel->getEmail();
                } catch (\Exception $e) {
                    if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                        $this->logger->error("This sub account " . $subAccountId . ' is not exist.');
                        $this->logger->critical($e);
                    }
                }
            }
        }

        // Set the sender information (can use default Magento senders or custom)
        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'email' => $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];

        $result = $this->emailBuilder->sendEmail(self::EMAIL_TEMPLATE, $emailTemplateVariables, $sender, $receivers, self::COPY_TO, self::COPY_METHOD);
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
            $this->logger->info('Finished sending a notification to seller ID ' . $sellerId . " with status is " . $result ? "success" : "failed");
        }
        return $result;
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
     * @return array|string[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRmaStatus()
    {
        $rmaStatus = $this->getConfigValue(self::RMA_STATUS, $this->getStore()->getStoreId());
        return $rmaStatus ? explode(',', $rmaStatus) : [];
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
     * @param $invoiceCompanyNo
     * @param $data
     * @param $fileDirectoryPath
     * @param $fileName
     * @return false|string[]
     */
    public function setFileContent($invoiceCompanyNo, $data, $fileDirectoryPath, $fileName)
    {
        $password = [];
        $content[] = self::HK_HEADER;
        if ($this->getConfigValue(self::ENGLISH_HEADER_ENABLE)) {
            $content[] = self::EN_HEADER;
        }
        $content = array_merge($content, $data);
        if (!is_dir($fileDirectoryPath)) {
            mkdir($fileDirectoryPath, 0777, true);
        }
        $excelFileName = $fileName . ".xlsx";
        $filePath = $fileDirectoryPath . $excelFileName;
        $zipFileName = $fileName . ".zip";
        $zipFilePath = $fileDirectoryPath . $zipFileName;
//        $sheet = $this->spreadsheet->getActiveSheet();
        if($this->spreadsheet->getSheetCount() > 0){
            $this->spreadsheet->removeSheetByIndex(0);
        }
        $sheet = $this->spreadsheet->createSheet(0);
        $sheet->fromArray($content, NULL, 'A1');
        $this->xlsx->create([$sheet])->save($filePath);
        $password = $this->generatePassword($invoiceCompanyNo);
        echo system('cd '.$fileDirectoryPath.' && zip -P '.$password.' '.$zipFileName.' '.$excelFileName);

        /*$zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        $zip->addFile($filePath, basename($filePath));

        // Use AES-256 encryption (Windows-compatible)
        $zip->setEncryptionName(basename($filePath), \ZipArchive::EM_AES_256, $password);*/

        /*if (!$zip->setEncryptionName($excelFileName, \ZipArchive::EM_AES_256, $password)) {
            $zip->close();
            return false; // Encryption failed
        }
        $zip->close();*/
        return ["path" => $zipFilePath, "file_name" => $zipFileName];
    }


    protected function generatePassword($invoiceCompanyNo)
    {
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
