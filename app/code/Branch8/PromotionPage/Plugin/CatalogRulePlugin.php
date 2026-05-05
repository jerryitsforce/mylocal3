<?php
namespace Branch8\PromotionPage\Plugin;

use Magento\AsynchronousOperations\Api\Data\OperationInterface;
use Magento\CatalogRule\Api\Data\RuleInterface;
use Magento\CatalogRule\Model\CatalogRuleRepository;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\PromotionPage\Helper\Logger;

class CatalogRulePlugin
{
    const PATH_ROOT_CATEGORY = '1/2/';

    protected $categoryFactory;
    protected $scopeConfig;
    protected $publisher;
    protected RuleFactory $ruleFactory;

    /**
     * @var \Magento\Framework\Bulk\BulkManagementInterface
     */
    private $bulkManagement;

    /**
     * @var \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory
     */
    private $operationFactory;

    /**
     * @var \Magento\Framework\DataObject\IdentityGeneratorInterface
     */
    private $identityService;

    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    private $serializer;

    /**
     * @var \Magento\Authorization\Model\UserContextInterface
     */
    private $userContext;

    //protected $batchIdsUpdated = [];
    private Logger $logger;
    private StoreManagerInterface $storeManager;

    /**
     * @param CategoryFactory $categoryFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param PublisherInterface $publisher
     * @param RuleFactory $ruleFactory
     * @param \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement
     * @param \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operartionFactory
     * @param \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Magento\Authorization\Model\UserContextInterface $userContext
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        ScopeConfigInterface $scopeConfig,
        PublisherInterface $publisher,
        RuleFactory $ruleFactory,
        \Magento\Framework\Bulk\BulkManagementInterface $bulkManagement,
        \Magento\AsynchronousOperations\Api\Data\OperationInterfaceFactory $operartionFactory,
        \Magento\Framework\DataObject\IdentityGeneratorInterface $identityService,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Magento\Authorization\Model\UserContextInterface $userContext,
        Logger $logger,
        StoreManagerInterface $storeManager
    ) {
        $this->categoryFactory  = $categoryFactory;
        $this->scopeConfig      = $scopeConfig;
        $this->publisher        = $publisher;
        $this->ruleFactory      = $ruleFactory;
        $this->bulkManagement   = $bulkManagement;
        $this->operationFactory = $operartionFactory;
        $this->identityService  = $identityService;
        $this->serializer       = $serializer;
        $this->userContext      = $userContext;
        $this->logger           = $logger;
        $this->storeManager     = $storeManager;
    }

    public function afterSave(CatalogRuleRepository $subject, RuleInterface $result, RuleInterface $rule): RuleInterface
    {
        if ($rule->getIsPromotion()) {
            $this->createOrUpdateCategory($rule, 'promotion');
        }
        if ($rule->getIsVip()) {
            $this->createOrUpdateCategory($rule, 'vip');
        }
        return $result;
    }

    private function createOrUpdateCategory(RuleInterface $rule, string $type)
    {
        $categoryIdConfigPath = $type === 'vip' ?
        'promotion_page/promotion_categories/vip_category' :
        'promotion_page/promotion_categories/root_category';

        $categoryAttribute = $type === 'vip' ?
        'catalog_price_rule_vip_id' :
        'catalog_price_rule_id';

        $rootCategoryId = $this->scopeConfig->getValue($categoryIdConfigPath, ScopeInterface::SCOPE_STORE);

        if (! $rootCategoryId) {
            return;
        }

        $category         = $this->categoryFactory->create();
        $existingCategory = $category->getCollection()
            ->addAttributeToFilter($categoryAttribute, $rule->getId())
            ->getFirstItem();

        if ($existingCategory->getId()) {
            $this->assignProductsToCategory($rule, $existingCategory, $type === 'vip');
            // If the category already exists, no need to create it again.
            return;
        }

        $urlKey = $type === 'vip' ? 'vip-label' . $rule->getId() : null;

        $category->setPath(self::PATH_ROOT_CATEGORY . $rootCategoryId);
        $category->setName($rule->getName());
        $category->setCategoryCode($rule->getName());
        $category->setIsActive(true);
        $category->setData($categoryAttribute, $rule->getId());
        if ($type === 'vip') {
            $category->setUrlKey($urlKey);
        }
        $category->save();

        $this->assignProductsToCategory($rule, $category, $type === 'vip');
    }

    private function assignProductsToCategory(RuleInterface $rule, $category, bool $isVip = false)
    {
        $productIds = $rule->getMatchingProductIds();

        $batchSize  = 5000; // Adjust this based on your needs
        $batches    = array_chunk($productIds, $batchSize, true);
        $conditions = $rule->getConditions();
        $isNew      = $rule->isObjectNew() ? "new" : "old";
        $this->logger->debug("=======================================================");
        $this->logger->debug("Branch8\PromotionPage\Plugin\CatalogRulePlugin::assignProductsToCategory");
        $this->logger->debug("Rule Type:" . $isNew);
        $this->logger->debug("Total Products Found: " . count($productIds));
        $this->logger->debug("Conditions:" . is_object($conditions) ? json_encode($conditions->toArray()) : 'null');

        foreach ($batches as $batch) {
            $productIdsBatch = array_keys($batch);
            $message         = json_encode([
                'category_id' => $category->getId(),
                'product_ids' => $productIdsBatch,
                'store_id'    => 0,
                'date'        => date('Y-m-d H:i:s'),
            ]);
            $queueName = $isVip ? 'branch8.vip.product.assign' : 'branch8.catalog.rule.product.assign';
            $this->publisher->publish($queueName, $message);
            /*if ($isVip) {
                $bulkUuid = $this->identityService->generateId();
                $bulkDescription = __('Update sort for ' . count($productIdsBatch) . ' vip products');
                $operations[] = $this->makeOperation(
                    'product_action_attribute.update',
                    ['vip_price_priority' => 1],
                    0,
                    $productIdsBatch,
                    $bulkUuid
                );
                $this->bulkManagement->scheduleBulk(
                    $bulkUuid,
                    $operations,
                    $bulkDescription,
                    $this->userContext->getUserId()
                );
            }*/
        }
    }

    /*public function beforeDelete(CatalogRuleRepository $subject, RuleInterface $rule)
    {
        if ($rule->getIsVip()) {
            $productIds = $rule->getMatchingProductIds();
            $productIds = array_keys($productIds);
            $catalogRuleCollection = $this->ruleFactory->create()->getCollection()
                ->addFieldToFilter('is_vip', 1)
                ->addFieldToFilter('rule_id', ['neq' => $rule->getId()]);
            foreach ($catalogRuleCollection as $catalogRule) {
                $compareProductIds = $catalogRule->getMatchingProductIds();
                $compareProductIds = array_keys($compareProductIds);
                $productIds = array_diff($productIds, $compareProductIds);
            }
            $this->batchIdsUpdated = $productIds;
        }
    }*/

    public function afterDelete(CatalogRuleRepository $subject, $result, RuleInterface $rule)
    {
        if ($rule->getIsPromotion()) {
            $this->deleteCategory($rule, 'promotion');
        }
        if ($rule->getIsVip()) {
            $this->deleteCategory($rule, 'vip');
            /*if (!empty($this->batchIdsUpdated)) {
                $bulkUuid = $this->identityService->generateId();
                $bulkDescription = __('Update sort for ' . count($this->batchIdsUpdated) . ' vip products');
                $operations[] = $this->makeOperation(
                    'product_action_attribute.update',
                    ['vip_price_priority' => 0],
                    0,
                    $this->batchIdsUpdated,
                    $bulkUuid
                );
                $this->bulkManagement->scheduleBulk(
                    $bulkUuid,
                    $operations,
                    $bulkDescription,
                    $this->userContext->getUserId()
                );
            }*/
        }
        return $result;
    }

    private function deleteCategory(RuleInterface $rule, string $type)
    {
        $categoryAttribute = $type === 'vip' ? 'catalog_price_rule_vip_id' : 'catalog_price_rule_id';

        $categoryCollection = $this->categoryFactory->create()->getCollection()
            ->addAttributeToFilter($categoryAttribute, $rule->getId());

        foreach ($categoryCollection as $category) {
            try {
                $category->delete();
            } catch (\Exception $e) {
                // Handle exception or log it
            }
        }
    }

    /**
     * Make asynchronous operation
     *
     * @param string $queue
     * @param array $dataToUpdate
     * @param int $storeId
     * @param array $productIds
     * @param int $bulkUuid
     *
     * @return OperationInterface
     */
    private function makeOperation(
        $queue,
        $dataToUpdate,
        $storeId,
        $productIds,
        $bulkUuid
    ): OperationInterface {
        $dataToEncode = [
            'product_ids' => $productIds,
            'store_id'    => $storeId,
            'attributes'  => $dataToUpdate,
        ];
        $data = [
            'data' => [
                'bulk_uuid'       => $bulkUuid,
                'topic_name'      => $queue,
                'serialized_data' => $this->serializer->serialize($dataToEncode),
                'status'          => \Magento\Framework\Bulk\OperationInterface::STATUS_TYPE_OPEN,
            ],
        ];

        return $this->operationFactory->create($data);
    }
}
