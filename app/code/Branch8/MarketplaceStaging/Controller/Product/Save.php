<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketplaceStaging\Controller\Product;

use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Helper\Data as ReportHelper;
use Branch8\Report\Model\Source\UserType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Staging\Controller\Result\JsonFactory;
use Magento\Staging\Model\Entity\Update\Save as StagingUpdateSave;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Save replace product save controller for update creation
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * Entity request identifier
     */
    const ENTITY_IDENTIFIER = 'id';

    /**
     * Entity name
     */
    const ENTITY_NAME = 'catalog_product';

    /**
     * @var StagingUpdateSave
     */
    protected $stagingUpdateSave;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helperData;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    protected $resourceConnection;

    /**
     * @var ReportHelper
     */
    private ReportHelper $reportHelper;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var ProductChangeLogInterfaceFactory
     */
    private ProductChangeLogInterfaceFactory $productChangeLogFactory;

    /**
     * @var ProductChangeLogRepositoryInterface
     */
    private ProductChangeLogRepositoryInterface $productChangeLogRepository;

    /**
     * Constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param StagingUpdateSave $stagingUpdateSave
     * @param StoreManagerInterface $storeManager
     * @param \Webkul\Marketplace\Helper\Data $helperData
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param ReportHelper $reportHelper
     * @param ProductRepositoryInterface $productRepository
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        StagingUpdateSave $stagingUpdateSave,
        StoreManagerInterface $storeManager,
        \Webkul\Marketplace\Helper\Data $helperData,
        JsonFactory $jsonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ReportHelper $reportHelper,
        ProductRepositoryInterface $productRepository,
        ProductChangeLogInterfaceFactory $productChangeLogFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository
    ) {
        $this->stagingUpdateSave = $stagingUpdateSave;
        $this->storeManager = $storeManager;
        $this->helperData = $helperData;
        $this->jsonFactory = $jsonFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
        $this->reportHelper = $reportHelper;
        $this->productRepository = $productRepository;
        $this->productChangeLogFactory = $productChangeLogFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $isPartner = $this->helperData->isSeller();
        if ($isPartner == 1) {
            $data = $this->getRequest()->getPostValue();
            if (isset($data['product']['current_store_id'])) {
                $selectedStore = $this->storeManager->getStore((int)$data['product']['current_store_id']);
                $this->storeManager->setCurrentStore($selectedStore);
            }
            if (isset($data['set'])) {
                $data['product']['attribute_set_id'] = $data['set'];
            }
            if (isset($data['product']['use_config_is_returnable']) && $data['product']['use_config_is_returnable']) {
                unset($data['product']['use_config_is_returnable']);
//                unset($data['product']['is_returnable']);
            }

            $data['product'] = $this->adjustProductDataForNewProduct($data['product']);

            /**
             * Validate Preservation status
             */
            $sellerId = $this->helperData->getCustomerId();
            $conn = $this->resourceConnection->getConnection();
            $sellerPreservationStatusQuery = $conn->select()
                ->from(['mp_user' => 'marketplace_userdata'], ['preservation_status'])
                ->where('seller_id = ?', (int)$sellerId);
            $sellerPreservationStatus = $conn->fetchOne($sellerPreservationStatusQuery);
            $sellerPreservationStatusArr = explode(',', (string)$sellerPreservationStatus);
            $productType = $this->getRequest()->getPost('type');
            if(
                !(
                    $productType == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL ||
                    $productType == \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE ||
                    (
                        $productType == \Magento\GiftCard\Model\Catalog\Product\Type\Giftcard::TYPE_GIFTCARD &&
                        $data['product']['giftcard_type'] == \Magento\GiftCard\Model\Giftcard::TYPE_VIRTUAL
                    )
                )
                &&
                (
                    !isset($data['product']['preservation_status']) || !in_array($data['product']['preservation_status'], $sellerPreservationStatusArr)
                )
            ){
                $this->messageManager->addError(__('Your Preservation status value is not allowed, please contact Administrator.'));
                return $this->jsonFactory->create([], ['error' => 1]);
            }
            $productId = $this->getRequest()->getParam(static::ENTITY_IDENTIFIER);

            // Log seller actions on product
            try {
                $productBefore = $this->productRepository->getById($productId);
                $productLinks = $this->reportHelper->prepareProductLinks($productBefore);
                $productBeforeData = $productBefore->getData();
                $productBeforeData += $productLinks;
            } catch (\Exception $e) {
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'mplog')){
                    $this->helperData->logDataInLogger('Branch8_MarketplaceStaging ERROR: ' . $e->getMessage());
                }
                $productBeforeData = [];
                $productLinks = [];
            }

            $result = $this->stagingUpdateSave->execute(
                [
                    'entityId' => $productId,
                    'stagingData' => $this->getRequest()->getParam('staging'),
                    'entityData' => $data

                ]
            );

            // Log seller actions on product
            if ($result instanceof Json) {
                try {
                    $reflection = new \ReflectionClass($result);
                    $property = $reflection->getProperty('json');
                    $resultData = $property->getValue($result);
                } catch (\Exception $e) {
                    if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'mplog')){
                        $this->helperData->logDataInLogger('Branch8_MarketplaceStaging ERROR: ' . $e->getMessage());
                    }
                    return $result;
                }
                $json = $this->reportHelper->getSerializer()->unserialize($resultData);
                if (isset($json['error']) && false === $json['error']) {
                    $postData = ['stagingData' => $data['staging'] ?? null];
                    $postData['status'] = $data['status'] ?? null;
                    $postData += $data['product'];
                    if (!empty($data['links'])) {
                        $postData += $data['links'];
                    }

                    try {
                        $postData = $this->reportHelper->preparePostData($postData);
                        $productAfter = $this->productRepository->getById($productId);
                        $productAfterData = $productAfter->getData();
                        $productAfterData+= $productLinks;
                        list($beforeData, $afterData) = $this->reportHelper->getCommonOrderedSubset($productBeforeData, $productAfterData);
                        $beforeValues = $this->reportHelper->adjustProductData($beforeData);
                        $afterValues = $this->reportHelper->adjustProductData($afterData);

                        $productChangeLog = $this->productChangeLogFactory->create();
                        $productChangeLog->setProductId((int)$productId)
                            ->setAction('scheduleUpdate')
                            ->setPostData((string)$postData)
                            ->setBeforeValues($beforeValues)
                            ->setAfterValues($afterValues)
                            ->setUserType(UserType::TYPE_SELLER)
                            ->setUserId($sellerId);

                        $this->productChangeLogRepository->save($productChangeLog);
                    } catch (\Exception $e) {
                        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'mplog')){
                            $this->helperData->logDataInLogger('Branch8_MarketplaceStaging ERROR: ' . $e->getMessage());
                        }
                    }
                }
            }

            return $result;
        } else {
            return $this->jsonFactory->create(['ajaxExpired' => 1, 'ajaxRedirect' => $this->_url->getUrl('marketplace/account/becomeseller', ['_secure' => $this->getRequest()->isSecure()])]);
        }
    }

    public function adjustProductDataForNewProduct($productData)
    {

        if (isset($productData['options'])) {
            foreach ($productData['options'] as &$option) {
                $values = $option['values'] ?? '';
                if ($values) {
                    foreach ($values as $key =>  &$value) {
                        if(!isset($value['is_visible'])){
                            $value['is_visible'] = 0;
                        }
                        if(!isset($value['is_bought'])){
                            $value['is_bought'] = 0;
                        }
                        $values[$key] = $value;
                    }
                }
                $option['values'] = $values;
            }
        }

        return $productData;
    }
}
