<?php
namespace Branch8\OptionsWithStockAndImages\Plugin\SaaS;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class PriceProviderPlugin
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var \Magento\Customer\Api\GroupRepositoryInterface
     */
    protected $groupRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * cache for group code hash to id
     */
    protected $groupHashToId = [];

    /**
     * @param ResourceConnection $resource
     * @param \Magento\Customer\Api\GroupRepositoryInterface $groupRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resource,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->groupRepository = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Intercept get method to modify prices
     *
     * @param \Magento\ProductPriceDataExporter\Model\Provider\ProductPrice $subject
     * @param array $result
     * @return array
     */
    public function afterGet($subject, $result)
    {
        if (empty($result)) {
            return $result;
        }

        try {
            $this->initGroupHashes();

            $productIds = [];
            foreach ($result as $item) {
                if (isset($item['productId'])) {
                    $productIds[$item['productId']] = $item['productId'];
                }
            }

            if (empty($productIds)) {
                return $result;
            }

            $prices = $this->getBranch8Prices($productIds);

            foreach ($result as $key => $item) {
                if (!isset($item['productId']) || !isset($item['customerGroupCode'])) {
                    continue;
                }

                $productId = $item['productId'];
                $groupHash = $item['customerGroupCode'];

                $groupId = 0;
                if ($groupHash === '0') {
                    $groupId = 0;
                } elseif (isset($this->groupHashToId[$groupHash])) {
                    $groupId = $this->groupHashToId[$groupHash];
                } else {
                    continue;
                }

                if (isset($prices[$productId][$groupId])) {
                    $minPrice = $prices[$productId][$groupId];

                    if (isset($item['discounts']) && is_array($item['discounts'])) {
                        $updatedDiscounts = [];
                        $catalogRuleFound = false;
                        foreach ($item['discounts'] as $discount) {
                            if (isset($discount['code']) && $discount['code'] === 'catalog_rule') {
                                $discount['price'] = (float)$minPrice;
                                $catalogRuleFound = true;
                            }
                            $updatedDiscounts[] = $discount;
                        }

                        if (!$catalogRuleFound) {
                             $updatedDiscounts[] = [
                                 'code' => 'catalog_rule',
                                 'price' => (float)$minPrice
                             ];
                        }

                        $result[$key]['discounts'] = $updatedDiscounts;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $result;
    }

    protected function initGroupHashes()
    {
        if (!empty($this->groupHashToId)) {
            return;
        }
        $groups = $this->groupRepository->getList($this->searchCriteriaBuilder->create())->getItems();
        foreach ($groups as $group) {
            $id = $group->getId();
            // Deterministic hash for customer group mapping (non-security usage)
            $hash = hash('sha256', (string)$id);
            $this->groupHashToId[$hash] = $id;
        }
    }

    protected function getBranch8Prices($productIds)
    {
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName('branch8_variations_price_index');

        $select = $connection->select()
            ->from($tableName, [
                'product_id',
                'customer_group_id',
                'min_price' => new \Zend_Db_Expr('MIN(final_price)')
            ])
            ->where('product_id IN (?)', $productIds)
            ->group(['product_id', 'customer_group_id']);

        $rows = $connection->fetchAll($select);

        $data = [];
        foreach ($rows as $row) {
            $data[$row['product_id']][$row['customer_group_id']] = $row['min_price'];
        }

        return $data;
    }
}
