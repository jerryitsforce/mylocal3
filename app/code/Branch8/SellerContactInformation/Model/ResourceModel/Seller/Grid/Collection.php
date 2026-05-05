<?php

namespace Branch8\SellerContactInformation\Model\ResourceModel\Seller\Grid;

/**
 * Class Collection
 * Collection for displaying grid of marketplace seller
 */
class Collection extends \Webkul\Marketplace\Model\ResourceModel\Seller\Grid\Collection
{

    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $request = $objectManager->get('Magento\Framework\App\Request\Http');
        $filters = $request->getParam("filters");
        $this->getSelect()->columns(['*','invoice_and_statement_email' => 'main_table.financial_liaison_email']);
        if (isset($filters['applicable_contracts_and_quotations'][0])) {
            if (count($filters['applicable_contracts_and_quotations']) > 1) {
                $this->getSelect()->orWhere("applicable_contracts_and_quotations IS NOT NULL OR applicable_contracts_and_quotations IS NULL");
            } else if(!empty($filters['applicable_contracts_and_quotations'][0])) {
                $this->getSelect()->orWhere("applicable_contracts_and_quotations IS NOT NULL AND applicable_contracts_and_quotations != ''");
            } else {
                $this->getSelect()->orWhere("applicable_contracts_and_quotations IS NULL OR applicable_contracts_and_quotations = ''");
            }
        }
        $partnerTable = $this->getTable('marketplace_saleperpartner');
        $this->getSelect()->joinLeft(
            $partnerTable.' as partnerTable',
            'main_table.seller_id = partnerTable.seller_id',
            [
                'commission_rate',
                'min_commission_rate',
            ]
        );
        parent::_renderFiltersBefore();
    }
}
