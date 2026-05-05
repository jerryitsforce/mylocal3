<?php

namespace Branch8\MagentoVisualMerchandiser\Model\Queue;

use Branch8\MagentoVisualMerchandiser\Model\Action\GetProductMatchRules;
use Branch8\MagentoVisualMerchandiser\Model\Action\GetProductSmartCategories;
use Branch8\MagentoVisualMerchandiser\Model\Config;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\ResourceConnection;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

class CalculateMatchRulesProductConsumer
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var Config
     */
    private Config $config;
    /**
     * @var GetProductMatchRules
     */
    private GetProductMatchRules $getProductMatchingRules;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    /**
     * @var GetProductSmartCategories
     */
    private GetProductSmartCategories $getProductSmartCategories;
    /**
     * @var ProductRepository
     */
    private ProductRepository $productRepository;
    /**
     * @var \Magento\Catalog\Model\CategoryLinkRepository
     */
    private \Magento\Catalog\Model\CategoryLinkRepository $categoryLinkRepository;

    /**
     * @param GetProductMatchRules $getProductMatchRules
     * @param GetProductSmartCategories $getProductSmartCategories
     * @param ProductRepository $productRepository
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Catalog\Model\CategoryLinkRepository $categoryLinkRepository
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetProductMatchRules                          $getProductMatchRules,
        GetProductSmartCategories                     $getProductSmartCategories,
        ProductRepository                             $productRepository,
        ResourceConnection                            $resourceConnection,
        \Magento\Catalog\Model\CategoryLinkRepository $categoryLinkRepository,
        Config                                        $config,
        LoggerInterface                               $logger
    )
    {
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
        $this->getProductMatchingRules = $getProductMatchRules;
        $this->getProductSmartCategories = $getProductSmartCategories;
        $this->logger = $logger;
        $this->categoryLinkRepository = $categoryLinkRepository;
    }

    /**
     * @param $jsonData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($jsonData)
    {
        if (!$this->config->enabled()) {
            return;
        }
        try {
            $data = @json_decode($jsonData, TRUE);
            if (empty($data) || empty($data['product_id'])) {
                return;
            }
            $product = $this->productRepository->getById($data['product_id']);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return;
        }
        $sku = $product->getSku();
        $rules = $this->getProductMatchingRules->get((int)$data['product_id']);
        $currentSmartCategories = $this->getProductSmartCategories->get($product);
        $this->logger->info(sprintf('CalculateMatchRulesProductConsumer:%s-%s', $data['product_id'], json_encode($rules)));
        $insertData = [];
        $matchCategories = [];
        if (count($rules) > 0) {
            foreach ($rules as $info) {
                $matchCategories[] = $info['category_id'];
                $insertData[] = ['vm_rule_id' => $info['rule_id'], 'status' => \Branch8\MagentoVisualMerchandiser\Model\RuleIndex::STATUS_PENDING];
            }
        }
        $unMatchCategories = array_unique(array_diff($currentSmartCategories, $matchCategories));
        if ($insertData) {
            $this->resourceConnection->getConnection()->insertOnDuplicate('visual_merchandiser_rule_index', $insertData, ['status']);
        }
        if ($unMatchCategories) {
            foreach ($unMatchCategories as $categoryId) {
                try {
                    $this->categoryLinkRepository->deleteByIds((int)$categoryId, $sku);
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                }
            }
        }
    }
}
