<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Helper;

use Branch8\Smtp\Helper\EmailBuilder;
use Magento\Framework\Mail\Template\TransportBuilder;
use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\Product\SaveProductWithChanges;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Branch8\MarketplaceStaging\Model\Product\SaveProductStagingWithChanges;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\Store;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Model\Product;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\XlsxFactory;
use Magento\User\Model\UserFactory;

class ProductApproval
{
    protected const PRODUCT_FILE_NAME_ENABLE = 'marketplace/branch8_dealer_approval/enable';
    protected const PRODUCT_FILE_NAME_PREFIX = 'marketplace/branch8_dealer_approval/file_prefix';
    protected const RECEIVERS = 'marketplace/branch8_dealer_approval/receiver';
    protected const EMAIL_TEMPLATE = 'marketplace/branch8_dealer_approval/template';

    protected const PRODUCT_FILE_HEADER = [
        'product_name',
        'SKU',
        'seller_name',
        'apply_time'
    ];

    /**
     * @var AdminSession
     */
    protected AdminSession $adminSession;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @var SerializerInterface
     */
    protected SerializerInterface $serializer;

    /**
     * @var ProductRepositoryInterface
     */
    protected ProductRepositoryInterface $productRepository;

    /**
     * @var MarketplaceProductManagement
     */
    protected MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var SaveProductWithChanges
     */
    protected SaveProductWithChanges $saveProductWithChanges;

    /**
     * @var ProductVersionRepositoryInterface
     */
    protected ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var GetProductLogEntryByProductId
     */
    protected GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * @var SaveProductStagingWithChanges
     */
    private SaveProductStagingWithChanges $saveProductStagingWithChanges;

    /**
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    protected DirectoryList $directoryList;

    protected TimezoneInterface $timezone;

    protected XlsxFactory $xlsx;
    protected Spreadsheet $spreadsheet;
    protected ScopeConfigInterface $scopeConfig;

    protected StateInterface $inlineTranslation;

    protected TransportBuilder $transportBuilder;

    protected EmailBuilder $emailBuilder;

    protected $userFactory;


    /**
     * Approve constructor.
     *
     * @param Registry $registry
     * @param DateTime $dateTime
     * @param SerializerInterface $serializer
     * @param ProductRepositoryInterface $productRepository
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param SaveProductWithChanges $saveProductWithChanges
     * @param SaveProductStagingWithChanges $saveProductStagingWithChanges
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param LoggerInterface $logger
     * @param DirectoryList $directoryList
     * @param TimezoneInterface $timezone
     * @param XlsxFactory $xlsx
     * @param Spreadsheet $spreadsheet
     * @param ScopeConfigInterface $scopeConfig
     * @param StateInterface $inlineTranslation
     * @param TransportBuilder $transportBuilder
     * @param EmailBuilder $emailBuilder
     * @param UserFactory $userFactory
     * @param AdminSession|null $adminSession
     */
    public function __construct(
        Registry                          $registry,
        DateTime                          $dateTime,
        SerializerInterface               $serializer,
        ProductRepositoryInterface        $productRepository,
        MarketplaceProductManagement      $marketplaceProductManagement,
        SaveProductWithChanges            $saveProductWithChanges,
        SaveProductStagingWithChanges     $saveProductStagingWithChanges,
        ProductVersionRepositoryInterface $productVersionRepository,
        ProductVersionCollectionFactory   $productVersionCollectionFactory,
        GetProductLogEntryByProductId     $getProductLogEntryByProductId,
        LoggerInterface                   $logger,
        DirectoryList                     $directoryList,
        TimezoneInterface                 $timezone,
        XlsxFactory                       $xlsx,
        Spreadsheet                       $spreadsheet,
        ScopeConfigInterface              $scopeConfig,
        StateInterface                    $inlineTranslation,
        TransportBuilder                  $transportBuilder,
        EmailBuilder                      $emailBuilder,
        UserFactory                       $userFactory,
        AdminSession                      $adminSession = null
    ) {
        $this->registry = $registry;
        $this->dateTime = $dateTime;
        $this->serializer = $serializer;
        $this->productRepository = $productRepository;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->saveProductWithChanges = $saveProductWithChanges;
        $this->saveProductStagingWithChanges = $saveProductStagingWithChanges;
        $this->productVersionRepository = $productVersionRepository;
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->timezone = $timezone;
        $this->spreadsheet = $spreadsheet;
        $this->xlsx = $xlsx;
        $this->scopeConfig = $scopeConfig;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->emailBuilder = $emailBuilder;
        $this->adminSession = $adminSession ?? ObjectManager::getInstance()->get(AdminSession::class);
        $this->userFactory = $userFactory;
    }

    /**
     * Handle approval based on log entry.
     *
     * @param int $productId
     * @param int $sellerId
     *
     * @return mixed
     *
     * @throws LocalizedException
     */
    public function handleApprovalBasedOnLogEntry(int $productId, int $sellerId, int $reviewerId, $gridNamespace): mixed
    {
        // $reviewerId = '';
        $userUpdated = '';
        $flagCheck = false;
        $logEntry = $this->getProductLogEntryByProductId->execute($productId);
        $logEntryId = false;
        if (isset($logEntry['id']) && $logEntry['id']) {
            $flagCheck = true;
            $logEntryId = $logEntry['id'];
            if (isset($logEntry['is_new_product']) && $logEntry['is_new_product']) {
                $allLogEntry = $this->getProductLogEntryByProductId->executeAll($productId, true);
                if (count($allLogEntry) < 1) {
                    $flagCheck = false;
                }
            }
        }
        if (isset($logEntry['user_updated']) && $logEntry['user_updated']) {
            $userUpdated = $logEntry['user_updated'];
        }
        $this->logger->debug('FLagCheck:' . $flagCheck);
        $this->logger->debug('logEntry:');
        $this->logger->info(json_encode($logEntry));

        if ($flagCheck) {
            try {
                // $reviewerId = $logEntry['reviewer_id'] ?? '';
                $catalogProduct = $this->productRepository->getById($productId);
                $this->registry->unregister('current_product_links_before_approve');
                $this->registry->register('current_product_links_before_approve', $catalogProduct->getProductLinks() ?: false);
                if ($logEntry['created_from'] != CreatedFrom::CREATED_FROM_SCHEDULE && $logEntry['created_from'] != CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                    $this->saveProductWithChanges->execute($catalogProduct, $sellerId, null, true);

                    // START: Update child products of a configurable product in marketplace product table
                    $additionalInfo = $logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION];
                    $changedData = $this->serializer->unserialize((string)$additionalInfo);
                    if (isset($changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS])) {
                        $beforeChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['before'] ?? [];
                        $afterChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['after'] ?? [];
                        foreach (array_diff($afterChildIds, $beforeChildIds) as $childId) {
                            try {
                                $childProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $childId);
                                $childProduct->setData('status', Product::STATUS_ENABLED);
                                $childProduct->setData('updated_at', $this->dateTime->gmtDate());
                                $this->marketplaceProductManagement->save($childProduct);
                            } catch (NoSuchEntityException $e) {
                                continue;
                            }
                        }
                    }
                    // END: Update child products of a configurable product in marketplace product table.
                    if (isset($logEntry['is_new_product']) && $logEntry['is_new_product']) {
                        $catalogProduct = $this->productRepository->getById($productId);
                        $catalogProduct->setStatus(Status::STATUS_ENABLED);
                        $catalogProduct->setStoreId(Store::DEFAULT_STORE_ID);
                        $this->productRepository->save($catalogProduct);
                        $isNewProduct = true;
                    }

                    $this->updateProductVersion($logEntryId, $reviewerId, $gridNamespace);
                } else {
                    $isImport = false;
                    if ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
                        $isImport = true;
                    }
                    $reviewerStage = '';
                    $flowStatus = 1;
                    if($gridNamespace == 'marketplacectrl_products_list'){
                        $reviewerStage = 'Curator';
                        $flowStatus = ApprovalFlowStatus::APPROVAL_GRANTED_FINAL_APPROVE;
                    }else if($gridNamespace == 'marketplacectrl_manager_products_list'){
                        $reviewerStage = 'Manager';
                        $flowStatus = ApprovalFlowStatus::APPROVAL_GRANTED_FINAL_APPROVE;
                    }
                    $this->saveProductStagingWithChanges->execute($productId, Product::STATUS_ENABLED, $reviewerId, $flowStatus, $reviewerStage, $isImport);
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                throw new LocalizedException(__('An error occurred while approving product.' . $e->getMessage()));
            }
        } else {
            $catalogProduct = $this->productRepository->getById($productId);
            $catalogProduct->setStatus(Status::STATUS_ENABLED);
            $catalogProduct->setStoreId(Store::DEFAULT_STORE_ID);
            $this->productRepository->save($catalogProduct);
            if (isset($logEntry['status']) && $logEntry['status'] == Product::STATUS_PENDING) {
                $this->updateProductVersion($logEntryId, $reviewerId, $gridNamespace);
            }
            if (isset($logEntry['is_new_product']) && $logEntry['is_new_product']) {
                $isNewProduct = true;
            }
        }
        return [$userUpdated, $isNewProduct ?? false];
    }

    protected function updateProductVersion($logEntryId, $reviewerId, $gridNamespace, $status = Product::STATUS_ENABLED)
    {
        if (!$logEntryId) {
            return;
        }
        $productVersion = $this->productVersionRepository->getById((int)$logEntryId);
        $productVersion->setStatus($status);

        $userStage = '';
        if($gridNamespace == 'marketplacectrl_products_list'){
            $userStage = 'Curator';
        }else if($gridNamespace == 'marketplacectrl_manager_products_list'){
            $userStage = 'Manager';
        }

        $adminUser = $this->userFactory->create()->load($reviewerId);
        $userId = $adminUser->getId();
        $userName = $adminUser->getUsername();
        $userRole = $adminUser->getRole()->getRoleName();
        $userLogInfor = [
            'user_id' => $userId,
            'user_name' => $userName,
            'user_role' => $userRole,
            'user_stage' => $userStage,
            'status_updated' => 'Approved',
            'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
        ];
        $approvalLog = $productVersion->getApprovalLog();
        if($approvalLog){
            $newApprovalLog = json_decode($approvalLog, true);
        }
        $newApprovalLog[] = $userLogInfor;
        $newApprovalLogStr = json_encode($newApprovalLog);
        $productVersion->setApprovalLog($newApprovalLogStr);

        $productVersion->setReviewerId((int)$reviewerId);

        $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::APPROVAL_GRANTED_FINAL_APPROVE);
        $this->productVersionRepository->save($productVersion);
    }

    /**
     * Handle disapprove based on log entry.
     *
     * @param int $productId
     *
     * @return array
     *
     * @throws LocalizedException
     */
    public function handleDisapproveBasedOnLogEntry(int $productId, string $reviewStage, int $isDealer = 0): array
    {
        $reviewerId = '';
        $logEntry = $this->getProductLogEntryByProductId->execute($productId);
        $result = false;

        if (!empty($logEntry['id'])) {
            try {
                $reviewerId = $logEntry['reviewer_id'] ?? '';
                // For case created from Seller, Schedule and Import
                if ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SELLER) {
                    // START: Update child products of a configurable product in marketplace product table
                    $additionalInfo = (string)$logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION];
                    $changedData = $this->serializer->unserialize($additionalInfo);
                    if (isset($changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS])) {
                        $beforeChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['before'] ?? [];
                        $afterChildIds = $changedData[BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS]['after'] ?? [];
                        foreach (array_diff($afterChildIds, $beforeChildIds) as $childId) {
                            try {
                                $product = $this->productRepository->getById($childId);
                                $this->productRepository->delete($product);
                            } catch (NoSuchEntityException $e) {
                                continue;
                            }
                        }
                    }
                    // END: Update child products of a configurable product in marketplace product table

                    $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                    $productVersion->setStatus(Product::STATUS_DISABLED);

                    if ($this->adminSession->isLoggedIn()) {
                        $adminUser = $this->adminSession->getUser();
                        $productVersion->setReviewerId((int)$adminUser->getId());
                        $productVersion = $this->handleApprovalLogBasedOnLogEntry($adminUser, $reviewStage, $productVersion);
                    }
                    $productVersion->setDealerProcessed($isDealer);
                    $this->productVersionRepository->save($productVersion);
                    $result = true;
                } elseif ($logEntry['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE) {
                    $adminUserId = 0;
                    if ($this->adminSession->isLoggedIn()) {
                        $adminUser = $this->adminSession->getUser();
                        $adminUserId = (int)$adminUser->getId();
                    }
                    $productVersionList = $this->productVersionCollectionFactory->create();
                    $productVersionList->addFieldToFilter('product_id', $productId)
                        ->addFieldToFilter('main_table.status', Product::STATUS_PENDING);
                    if ($productVersionList->getSize() > 0) {
                        foreach ($productVersionList as $productVersion) {
                            $productVersion->setStatus(Product::STATUS_DISABLED);
                            $productVersion->setReviewerId($adminUserId);
                            $productVersion->setDealerProcessed($isDealer);
                            if ($this->adminSession->isLoggedIn()) {
                                $productVersion = $this->handleApprovalLogBasedOnLogEntry($adminUser, $reviewStage, $productVersion);
                            }
                            $this->productVersionRepository->save($productVersion);
                        }
                    }
                } else {
                    $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                    $productVersion->setStatus(Product::STATUS_DISABLED);

                    if ($this->adminSession->isLoggedIn()) {
                        $adminUser = $this->adminSession->getUser();
                        $productVersion->setReviewerId((int)$adminUser->getId());
                        $productVersion = $this->handleApprovalLogBasedOnLogEntry($adminUser, $reviewStage, $productVersion);
                    }
                    $productVersion->setDealerProcessed($isDealer);
                    $this->productVersionRepository->save($productVersion);
                }
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
                throw new LocalizedException(__('An error occurred while denying product.'));
            }
        } else {
            $result = true;
        }
        return ['success' => $result, 'reviewer_id' => $reviewerId];
    }

    protected function handleApprovalLogBasedOnLogEntry($adminUser, $reviewStage, $productVersion){
        $userLogInfor = [
            'user_id' => $adminUser->getId(),
            'user_name' => $adminUser->getUsername(),
            'user_role' => $adminUser->getRole()->getRoleName(),
            'user_stage' => $reviewStage,
            'status_updated' => 'Disapproved',
            'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
        ];
        $approvalLog = $productVersion->getApprovalLog();
        if($approvalLog){
            $newApprovalLog = json_decode($approvalLog, true);
        }
        $newApprovalLog[] = $userLogInfor;
        $newApprovalLogStr = json_encode($newApprovalLog);
        $productVersion->setApprovalLog($newApprovalLogStr);
        $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::APPROVAL_REJECTED);

        return $productVersion;
    }

    public function handleEmailToAdminWhenDealerApprove($emailVariables, $data)
    {
        $this->logger->info("Starting send email to Admin");
        $enable = $this->scopeConfig->getValue(self::PRODUCT_FILE_NAME_ENABLE);
        if (!$enable) {
            return;
        }
        $prefix = $this->scopeConfig->getValue(self::PRODUCT_FILE_NAME_PREFIX);
        $datetime = $this->timezone->date()->format('Ymd_His');
        $fileName = $prefix . "_" . $datetime;
        $fileDirectoryPath = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR)
            . "/dealer_approval_product/";
        $attachment = $this->setFileContent($data, $fileDirectoryPath, $fileName);
        // Set the sender information (can use default Magento senders or custom)
        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'email' => $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];

        $receiversConfig = $this->scopeConfig->getValue(self::RECEIVERS);
        $receivers = $receiversConfig ? explode(';', trim($receiversConfig)) : [];

        $this->emailBuilder->sendEmail(self::EMAIL_TEMPLATE, $emailVariables, $sender, $receivers, $attachment);

        $this->logger->info("Finished sending email to Admin");
    }

    public function setFileContent($data, $fileDirectoryPath, $fileName)
    {
        $content[] = self::PRODUCT_FILE_HEADER;
        foreach ($data as $item) {
            $content[] = array_values($item);
        }
        if (!is_dir($fileDirectoryPath)) {
            mkdir($fileDirectoryPath, 0777, true);
        }
        $excelFileName = $fileName . ".xlsx";
        $filePath = $fileDirectoryPath . $excelFileName;
        if ($this->spreadsheet->getSheetCount() > 0) {
            $this->spreadsheet->removeSheetByIndex(0);
        }
        $sheet = $this->spreadsheet->createSheet(0);
        $sheet->fromArray($content, NULL, 'A1');
        $this->xlsx->create([$sheet])->save($filePath);
        return ["path" => $filePath, "file_name" => $excelFileName];
    }

    public function curatorApproveProcessNegativeGrossProfit($logEntryId){
        if (!$logEntryId) {
            return;
        }
        $productVersion = $this->productVersionRepository->getById((int)$logEntryId);
        $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::MANAGER_PENDING_APPROVAL);
        if ($this->adminSession->isLoggedIn()) {
            $adminUser = $this->adminSession->getUser();
            $productVersion->setReviewerId((int)$adminUser->getId());
            $userLogInfor = [
                'user_id' => $adminUser->getId(),
                'user_name' => $adminUser->getUsername(),
                'user_role' => $adminUser->getRole()->getRoleName(),
                'user_stage' => 'Curator',
                'status_updated' => 'Approved',
                'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
            ];
            $approvalLog = $productVersion->getApprovalLog();
            if($approvalLog){
                $newApprovalLog = json_decode($approvalLog, true);
            }
            $newApprovalLog[] = $userLogInfor;
            $newApprovalLogStr = json_encode($newApprovalLog);
            $productVersion->setApprovalLog($newApprovalLogStr);
        }
        $this->productVersionRepository->save($productVersion);
    }

    public function massApproveProcessNegativeGrossProfit($logEntryId, $reviewUserInfor){
        if (!$logEntryId) {
            return;
        }
        $productVersion = $this->productVersionRepository->getById((int)$logEntryId);
        $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::MANAGER_PENDING_APPROVAL);
        $productVersion->setReviewerId((int)$reviewUserInfor['id']);

        $userLogInfor = [
            'user_id' => $reviewUserInfor['id'],
            'user_name' => $reviewUserInfor['name'],
            'user_role' => $reviewUserInfor['role_name'],
            'user_stage' => $reviewUserInfor['user_stage'],
            'status_updated' => 'Approved',
            'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
        ];
        $approvalLog = $productVersion->getApprovalLog();
        if($approvalLog){
            $newApprovalLog = json_decode($approvalLog, true);
        }
        $newApprovalLog[] = $userLogInfor;
        $newApprovalLogStr = json_encode($newApprovalLog);
        $productVersion->setApprovalLog($newApprovalLogStr);

        $this->productVersionRepository->save($productVersion);
    }

    public function curatorDisapproveProcessNegativeGrossProfit($productVersion){
        $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::APPROVAL_REJECTED);
        if ($this->adminSession->isLoggedIn()) {
            $adminUser = $this->adminSession->getUser();
            $productVersion->setReviewerId((int)$adminUser->getId());
            $userLogInfor = [
                'user_id' => $adminUser->getId(),
                'user_name' => $adminUser->getUsername(),
                'user_role' => $adminUser->getRole()->getRoleName(),
                'user_stage' => 'Curator',
                'status_updated' => 'Disapproved',
                'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
            ];
            $approvalLog = $productVersion->getApprovalLog();
            if($approvalLog){
                $newApprovalLog = json_decode($approvalLog, true);
            }
            $newApprovalLog[] = $userLogInfor;
            $newApprovalLogStr = json_encode($newApprovalLog);
            $productVersion->setApprovalLog($newApprovalLogStr);
        }

        return $productVersion;
    }
    /**
     * Handle Approve based on log entry.
     *
     * @param int $productId
     *
     * @return \Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface
     *
     */
    public function dealerApproveProcess($productVersion){
        $adminUserId = 0;
        if ($this->adminSession->isLoggedIn()) {
            $adminUser = $this->adminSession->getUser();
            $adminUserId = (int)$adminUser->getId();

            $userLogInfor = [
                'user_id' => $adminUser->getId(),
                'user_name' => $adminUser->getUsername(),
                'user_role' => $adminUser->getRole()->getRoleName(),
                'user_stage' => 'Dealer',
                'status_updated' => 'Approved',
                'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
            ];
            $approvalLog = $productVersion->getApprovalLog();
            if($approvalLog){
                $newApprovalLog = json_decode($approvalLog, true);
            }
            $newApprovalLog[] = $userLogInfor;
            $newApprovalLogStr = json_encode($newApprovalLog);
            $productVersion->setApprovalLog($newApprovalLogStr);
        }
        $productVersion->setReviewerId($adminUserId);
        $productVersion->setDealerProcessed(1);

        $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::CURATOR_PENDING_APPROVAL);
        return $productVersion;
    }

    public function dealerDisapproveProcess($productVersion){
        $adminUserId = 0;
        if ($this->adminSession->isLoggedIn()) {
            $adminUser = $this->adminSession->getUser();
            $adminUserId = (int)$adminUser->getId();

            $userLogInfor = [
                'user_id' => $adminUser->getId(),
                'user_name' => $adminUser->getUsername(),
                'user_role' => $adminUser->getRole()->getRoleName(),
                'user_stage' => 'Dealer',
                'status_updated' => 'Disapproved',
                'created_at' => $this->timezone->date()->format('M d, Y h:i:s A')
            ];
            $approvalLog = $productVersion->getApprovalLog();
            if($approvalLog){
                $newApprovalLog = json_decode($approvalLog, true);
            }
            $newApprovalLog[] = $userLogInfor;
            $newApprovalLogStr = json_encode($newApprovalLog);
            $productVersion->setApprovalLog($newApprovalLogStr);
        }
        $productVersion->setReviewerId($adminUserId);
        $productVersion->setDealerProcessed(1);


        $productVersion->setApprovalFlowStatus(ApprovalFlowStatus::APPROVAL_REJECTED);
        return $productVersion;
    }

    public function isVariantionNegative($logData){
        $additionalData = $logData['additional_information'];
        $res = false;
        try{
            $additionalDataArr = json_decode($additionalData, true);
            if(isset($additionalDataArr['wk_manage_variation'])){
                foreach($additionalDataArr['wk_manage_variation']['after'] as $variant){
                    if((int)$variant['commission_percent'] < 0){
                        $res = true;
                    }
                }
            }
        }catch(\Exception $e){
            $res = false;
        }
        return $res;
    }
}
