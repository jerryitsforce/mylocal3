<?php

namespace Branch8\MarketplaceProduct\Plugin\Product;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class AdminApprovalCustomFilter
{
    public $formDataSource = 'product_approval_management_listing_data_source';
    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resource;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * AdminApprovalCustomFilter constructor.
     *
     * @param CollectionFactory $collectionFactory
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory  $collectionFactory,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Add shop_title to collection
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetCollection(\Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider $subject, $result)
    {
        if($subject->getName() == $this->formDataSource)
        {
            $connection = $this->resource->getConnection();
            $marketplaceUserTable = $connection->getTableName('marketplace_userdata');
            
            // Join marketplace_userdata to get shop_title
            $result->getSelect()->joinLeft(
                ['marketplace_user' => $marketplaceUserTable],
                'main_table.seller_id = marketplace_user.seller_id',
                ['shop_title' => 'marketplace_user.shop_title']
            );
            
            // Debug: log the SQL
            $this->logger->info('Product Approval SQL: ' . $result->getSelect()->__toString());
            
            // Map shop_title to name field for filtering
            $result->addFilterToMap('name', 'marketplace_user.shop_title');
        }
        
        return $result;
    }

    /**
     * Replace name with shop_title in data
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetData(\Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider $subject, $result)
    {
        if($subject->getName() == $this->formDataSource)
        {
            if (isset($result['items'])) {
                foreach ($result['items'] as &$item) {
                    // Debug: log the data
                    $this->logger->info('Item data: ' . json_encode([
                        'name' => $item['name'] ?? 'not set',
                        'shop_title' => $item['shop_title'] ?? 'not set',
                        'seller_id' => $item['seller_id'] ?? 'not set'
                    ]));
                    
                    if (isset($item['shop_title']) && !empty($item['shop_title'])) {
                        $item['name'] = $item['shop_title'];
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * Custom filter for Admin product approval
     * @param $subject
     * @param $filter
     * @return void
     */
    public function beforeAddFilter(\Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider $subject, $filter){
        if($subject->getName() == $this->formDataSource)
        {
            if($filter->getField() == 'gross_profit'){
                $filter->setField('commission_percent');
                $grossProfitValue = $filter->getValue();
                if($grossProfitValue == \Branch8\MarketplaceProduct\Model\Config\Source\GrossProfit::GP_GTEQ_BGP){
                    $filter->setValue(new \Zend_Db_Expr('`at_partner`.`min_commission_rate`'));
                    $filter->setConditionType('gteq');
                }else if($grossProfitValue == \Branch8\MarketplaceProduct\Model\Config\Source\GrossProfit::GP_LT_BGP){
                    $filter->setValue(new \Zend_Db_Expr('`at_partner`.`min_commission_rate`'));
                    $filter->setConditionType('lt');
                }else if($grossProfitValue == \Branch8\MarketplaceProduct\Model\Config\Source\GrossProfit::GP_GTEQ_GPL){
                    $filter->setValue(new \Zend_Db_Expr('`at_partner`.`commission_rate`'));
                    $filter->setConditionType('gteq');
                }else if($grossProfitValue == \Branch8\MarketplaceProduct\Model\Config\Source\GrossProfit::GP_LT_GPL){
                    $filter->setValue(new \Zend_Db_Expr('`at_partner`.`commission_rate`'));
                    $filter->setConditionType('lt');
                }
            }

            if($filter->getField() == 'gross_profit_range'){
                $filter->setField('commission_percent');
            }

            if($filter->getField() == 'gross_profit_level'){
                $filter->setField('at_partner.commission_rate');
            }

            if($filter->getField() == 'special_price'){
                $filter->setField('at_special_price.value');
            }
            if($filter->getField() == 'change_summary'){
                $filter->setField('additional_information');
                $value = $filter->getValue();
                $value = str_replace('%', '', strtolower($value));
                $label = $this->getLabels();
                if (isset($label[$value])) {
                    $value = $label[$value];
                }
                $value = '%'.$value.'%';
                $filter->setValue($value);
            }
            if($filter->getField() == 'base_gross_profit'){
                $filter->setField('min_commission_rate');
            }

            // Map name filter to shop_title
            if($filter->getField() == 'name'){
                $filter->setField('marketplace_user.shop_title');
            }

//            if($filter->getField() == 'is_price_cost_change'){
//                $filter->setField('IF((COALESCE(`at_product_version`.`cost`, 0) <> COALESCE(`at_cost`.`value`, 0)) OR ((COALESCE(`at_product_version`.`price`, 0) <> COALESCE(`at_price`.`value`, 0))), 1, 0)');
//            }

        }
        return [$filter];
    }

    /**
     * Returns all labels.
     *
     * @return array
     */
    protected function getLabels(): array
    {
        $labels = [];
        $attrCollection = $this->collectionFactory->create()
            ->addFieldToSelect(['attribute_code', 'frontend_label']);
        foreach ($attrCollection as $attribute) {
            if (!$attribute->getFrontendLabel()) {
                continue;
            }
            $labels[strtolower($attribute->getFrontendLabel())] = $attribute->getAttributeCode();
        }
        $labels[strtolower(__('Options')->render())] = 'options';
        $labels[strtolower(__('Related Products')->render())] = 'related';
        $labels[strtolower(__('Image Gallery')->render())] = 'image_gallery';
        $labels[strtolower(__('Variations')->render())] = 'wk_manage_variation';

        return $labels;
    }

}