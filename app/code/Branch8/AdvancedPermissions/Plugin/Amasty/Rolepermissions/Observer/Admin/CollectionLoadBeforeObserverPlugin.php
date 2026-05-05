<?php

namespace Branch8\AdvancedPermissions\Plugin\Amasty\Rolepermissions\Observer\Admin;

use Amasty\Rolepermissions\Helper\Data;
use Magento\Catalog\Model\Category;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\Registry;
use Magento\Store\Model\Store;
use Magento\Store\Model\Website;

class CollectionLoadBeforeObserverPlugin
{
    /**
     * @var Store
     */
    private Store $store;

    /**
     * @param Store $store
     */
    public function __construct(
        Store $store
    )
    {
        $this->store = $store;
    }

    /**
     * @param $suject
     * @param $process
     * @param $collection
     * @param $rule
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundAddCategoryFilter($subject, $process, $collection, \Amasty\Rolepermissions\Model\Rule $rule)
    {
        if ($collection instanceof \Magento\Catalog\Model\ResourceModel\Category\Collection) {
            $ruleCategories = $rule->getCategories();
            if ($ruleCategories) {
                $collection->getSelect()->join(
                    ['amrule_cat' => 'amasty_amrolepermissions_rule_category'],
                    'amrule_cat.category_id = e.entity_id',
                    []
                )->where('amrule_cat.rule_id IN (?)', $rule->getId());
            } else {
                $rootCategories = [];
                /** Hide categories from another store */
                if ($rule->getScopeWebsites() || $rule->getScopeStoreviews()) {
                    $storeIds = $rule->getScopeStoreviews();
                    foreach ($storeIds as $storeId) {
                        /** @var \Magento\Store\Model\Store $store */
                        $store = $this->store->load($storeId);
                        if ($categoryRoot = $store->getRootCategoryId()) {
                            $rootCategories[] = $categoryRoot;
                        }
                    }
                }

                if ($rootCategories) {
                    $rootCategories = array_unique($rootCategories);
                    $allRootCategoryIds = $this->getRootCategoryIds($collection);
                    $deniedCategories = array_diff($allRootCategoryIds, $rootCategories);

                    if ($deniedCategories) {
                        $collection->getSelect()
                            ->where('e.entity_id NOT IN (?)', $deniedCategories);

                        foreach ($deniedCategories as $id) {
                            $collection->getSelect()->where('e.path NOT LIKE ?', '%/' . $id . '/%');
                        }
                    }
                }
            }
        }
    }
    /**
     * Get all root categories id
     *
     * @param $collection
     *
     * @return array
     */
    private function getRootCategoryIds($collection)
    {
        $connection = $collection->getConnection();
        $select = $connection->select()->from(
            $collection->getMainTable(),
            'entity_id'
        )->where('parent_id = ?', Category::TREE_ROOT_ID);

        return $connection->fetchCol($select);
    }

}
