<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\Grid;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    /**
     * @inheritdoc
     */
    protected function _initSelect(): void
    {
        parent::_initSelect();

        $select = $this->getSelect();

        $productTable = $this->getTable('catalog_product_entity');
        $select->joinLeft(
            $productTable,
            'main_table.product_id = ' . $productTable . '.entity_id',
            ['sku']
        );

        // START: select the reviewer name (admin name)
        $userTable = $this->getTable('admin_user');
        $select->joinLeft(
            $userTable,
            'main_table.reviewer_id = ' . $userTable . '.user_id',
            [
                'reviewer_name' => new Expression('CONCAT(' . $userTable . '.firstname, " ", ' . $userTable . '.lastname)')
            ]
        );
        // END: select the reviewer name (admin name)

        // START: select the reviewer role (admin role)
        $roleTable = $this->getTable('authorization_role');
        $roleSubQuery = $this->getConnection()->select()
            ->from(['ar' => $roleTable], [
                'user_id',
                new Expression('GROUP_CONCAT(DISTINCT rp.role_name) AS reviewer_role'),
                new Expression('GROUP_CONCAT(DISTINCT rp.role_id) AS reviewer_stage')
            ])
            ->joinLeft(
                ['rp' => $roleTable],
                'rp.role_id = ar.parent_id',
                []
            )
            ->group('ar.user_id');

        $select->joinLeft(
            ['roles' => new Expression('(' . $roleSubQuery . ')')],
            'roles.user_id = main_table.reviewer_id',
            ['reviewer_role', 'reviewer_stage']
        );
        // END: select the reviewer role (admin role)
        /** Hide auto approve record(Update exclude fields) */
        $select->where('(main_table.status <> 1 OR (reviewer_id <> 0 AND reviewer_id is not null))');
        /** Hide Disapprove record if no reviewer infor(Admin update when seller submitted) */
        $select->where('(main_table.status <> 2 OR (reviewer_id <> 0 AND reviewer_id is not null))');
        $this->addFilterToMap('reviewer_name', 'main_table.reviewer_id');
        $this->addFilterToMap('updated_at', 'main_table.updated_at');
    }

    /**
     * @inheritdoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'status') {
            $field = 'main_table.status';
        }

        return parent::addFieldToFilter($field, $condition);
    }
}
