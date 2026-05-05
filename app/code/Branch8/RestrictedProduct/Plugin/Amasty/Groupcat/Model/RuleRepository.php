<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Plugin\Amasty\Groupcat\Model;

use Branch8\RestrictedProduct\Model\Indexer\RestrictedProduct;

class RuleRepository
{

    /** @var RestrictedProduct */
    protected $restrictedProduct;

    protected $ruleProcessor;

    protected $resource;
    protected $connection;

    /**
     * @var array
     */
    protected $productsToReindex = [];

    public function __construct(
        RestrictedProduct $restrictedProduct,
        \Amasty\Groupcat\Model\Rule\RuleProcessor $ruleProcessor,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->restrictedProduct = $restrictedProduct;
        $this->ruleProcessor = $ruleProcessor;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    public function afterSave(
        \Amasty\Groupcat\Model\RuleRepository $subject,
        $result
    ) {
        // Optimization: Mview handles the reindex on save via 'amasty_groupcat_rule' subscription.
        // The previous logic here was forcing synchronous reindex which could be slow and redundant.
        /*
        $matchingProductIds = $this->ruleProcessor->getMatchingProductIds($result);
        if(is_array($matchingProductIds) && count($matchingProductIds)){
            $allIds = [];
            foreach($matchingProductIds as $product_id => $value){
                $allIds[] = $product_id;
            }
            $this->restrictedProduct->executeList($allIds);
        }
        */
        return $result;
    }
    
    public function beforeDeleteById(
        \Amasty\Groupcat\Model\RuleRepository $subject,
        $ruleId
    ) {
        $this->collectProductsToReindex($ruleId);
        return [$ruleId];
    }
    
    public function afterDeleteById(
        \Amasty\Groupcat\Model\RuleRepository $subject,
        $result
    ) {
        $this->processReindex();
        return $result;
    }

    public function beforeDelete(
        \Amasty\Groupcat\Model\RuleRepository $subject,
        $rule
    ) {
        $this->collectProductsToReindex($rule->getId());
        return [$rule];
    }

    public function afterDelete(
        \Amasty\Groupcat\Model\RuleRepository $subject,
        $result
    ) {
        $this->processReindex();
        return $result;
    }

    private function collectProductsToReindex($ruleId) {
        try {
            // Use UNION to fetch unique product IDs efficiently directly from DB
            // This avoids large array operations in PHP for rules applied to categories with many products (50k+ SKUs)
            $select = $this->connection->select()->union([
                $this->connection->select()
                    ->from($this->resource->getTableName('amasty_groupcat_rule_product'), 'product_id')
                    ->where('rule_id = ?', $ruleId),
                $this->connection->select()
                    ->from(['rc' => $this->resource->getTableName('amasty_groupcat_rule_category')], [])
                    ->join(['ccp' => $this->resource->getTableName('catalog_category_product')], 'rc.category_id = ccp.category_id', ['product_id'])
                    ->where('rc.rule_id = ?', $ruleId)
            ]);

            $this->productsToReindex = $this->connection->fetchCol($select);
            
        } catch (\Exception $e) {
        }
    }
    
    private function processReindex() {
        if (!empty($this->productsToReindex)) {
             $this->restrictedProduct->executeList($this->productsToReindex);
             $this->productsToReindex = [];
        }
    }
}