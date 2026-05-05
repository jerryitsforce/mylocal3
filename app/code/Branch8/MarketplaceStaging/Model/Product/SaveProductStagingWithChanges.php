<?php

declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Model\Product;

use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceStaging\Api\ProductVersionDataRepositoryInterface;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Staging\Model\Entity\Update\Save as StagingUpdateSave;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Model\Product;
use Magento\User\Model\UserFactory;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class SaveProductStagingWithChanges
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var ProductVersionDataRepositoryInterface
     */
    private ProductVersionDataRepositoryInterface $productVersionDataRepository;

    /**
     * @var StagingUpdateSave
     */
    protected $stagingUpdateSave;

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    protected $userFactory;

    protected $timezone;

    /**
     * SaveProductStagingWithChanges constructor.
     *
     * @param SerializerInterface $serializer
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param ProductVersionDataRepositoryInterface $productVersionDataRepository
     * @param StagingUpdateSave $stagingUpdateSave
     * @param DateTime $dateTime
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        SerializerInterface $serializer,
        ProductVersionCollectionFactory $productVersionCollectionFactory,
        ProductVersionRepositoryInterface $productVersionRepository,
        ProductVersionDataRepositoryInterface $productVersionDataRepository,
        StagingUpdateSave $stagingUpdateSave,
        DateTime $dateTime,
        RequestInterface $request,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        UserFactory $userFactory,
        TimezoneInterface $timezone,
    ) {
        $this->serializer = $serializer;
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->productVersionRepository = $productVersionRepository;
        $this->productVersionDataRepository = $productVersionDataRepository;
        $this->stagingUpdateSave = $stagingUpdateSave;
        $this->dateTime = $dateTime;
        $this->request = $request;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->userFactory = $userFactory;
        $this->timezone = $timezone;
    }

    /**
     * Apply changed data to product schedule.
     *
     * @param int $productId
     * @param int $status
     * @param int $reviewerId
     * @return DataObject
     *
     * @throws LocalizedException
     */
    public function execute(
        int              $productId,
        int              $status,
        int              $reviewerId,
        int              $flowStatus,
        string           $reviewerStage,
        bool             $isImport = false
    ): DataObject
    {
        try {
            $result = new DataObject();
            $count = 0;
            $todayTimestamp = $this->dateTime->gmtTimestamp();
            $productVersionList = $this->productVersionCollectionFactory->create();
            $productVersionList->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('status', Product::STATUS_PENDING);
            if ($productVersionList->getSize() > 0) {
                foreach ($productVersionList as $productVersion) {
                    try {
                        $productVersionData = $this->productVersionDataRepository->get($productVersion->getId());
                    } catch (NoSuchEntityException $e) {
                        continue;
                    }
                    $data = $this->serializer->unserialize($productVersionData->getInformation());
                    // Check if the end time is in the past
                    if (isset($data['staging']['end_time'])
                        && $data['staging']['end_time']
                        && strtotime($data['staging']['end_time']) <= $todayTimestamp
                    ) {
                        $productVersion->setStatus(Product::STATUS_DENIED);
                        $productVersion->setReviewerId($reviewerId);
                        $productVersion = $this->setApprovalData($flowStatus, $reviewerId, $reviewerStage, $productVersion);
                        $this->productVersionRepository->save($productVersion);
                        $count++;
                        continue;
                    }
                    // Set start time to 5 minutes from now if it is in the past
                    if (isset($data['staging']['start_time'])
                        && $data['staging']['start_time']
                        && strtotime($data['staging']['start_time']) < $todayTimestamp
                    ) {
                        $data['staging']['start_time'] = date('Y-m-d H:i:s', strtotime('+5 minutes', $todayTimestamp));
                    }
                    $entityId = $data['id'];
                    $stagingData = $data['staging'];
                    foreach ($data as $k => $v) {
                        $this->request->setPostValue($k, $v);
                    }
                    // Set the current store
                    if (isset($data['product']['current_store_id'])) {
                        $selectedStore = $this->storeManager->getStore((int)$data['product']['current_store_id']);
                        $this->storeManager->setCurrentStore($selectedStore);
                    }
                    if ($isImport) {
                        $data['created_from'] = CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED;
                    }
                    $this->stagingUpdateSave->execute(
                        [
                            'entityId' => $entityId,
                            'stagingData' => $stagingData,
                            'entityData' => $data

                        ]
                    );
                    $productVersion->setStatus($status);
                    $productVersion->setReviewerId($reviewerId);

                    $productVersion = $this->setApprovalData($flowStatus, $reviewerId, $reviewerStage, $productVersion);
                    $this->productVersionRepository->save($productVersion);
                    $count++;
                }
            }


            $result->setData('status', !$count);
            return $result;
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'exceptionlog')){
                $this->logger->error($e->getMessage());
            }
            throw new LocalizedException(__('Cannot save the product changes: %1', $e->getMessage()));
        }
    }

    protected function setApprovalData($flowStatus, $reviewerId, $reviewerStage, $productVersion){
        $productVersion->setApprovalFflowStatus($flowStatus);
        $adminUser = $this->userFactory->create()->load($reviewerId);

        $userLogInfor = [
            'user_id' => $adminUser->getId(),
            'user_name' => $adminUser->getUsername(),
            'user_role' => $adminUser->getRole()->getRoleName(),
            'user_stage' => $reviewerStage,
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

        return $productVersion;
    }

}
