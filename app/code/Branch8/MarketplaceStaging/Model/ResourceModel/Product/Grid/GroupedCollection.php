<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_MpGroupedProduct
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceStaging\Model\ResourceModel\Product\Grid;

use Webkul\Marketplace\Model\ResourceModel\Product\Grid\Collection as ProductCollection;
use Magento\Framework\Api\Search\SearchResultInterface;

/**
 * Class Collection
 * Collection for displaying grid of Bundle Product selection
 */
class GroupedCollection extends ProductCollection implements SearchResultInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var string
     */
    protected $_eventPrefix;

    /**
     * @var string
     */
    protected $_eventObject;

    /**
     * Initialize constructor
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute
     * @param [type] $mainTable
     * @param [type] $eventPrefix
     * @param [type] $eventObject
     * @param [type] $resourceModel
     * @param [type] $model
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute,
        $mainTable,
        $eventPrefix,
        $eventObject,
        $resourceModel,
        $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $mainTable,
            $eventPrefix,
            $eventObject,
            $resourceModel,
            $model,
            $connection,
            $resource
        );
        $this->_customerSession = $customerSession;
        $this->_eventPrefix = $eventPrefix;
        $this->_eventObject = $eventObject;
        $this->_init($model, $resourceModel);
        $this->setMainTable($mainTable);
    }

   /**
    * Add filter to Map
    */

    protected function _initSelect()
    {
        $this->addFilterToMap("product_price", "cped.value");
        parent::_initSelect();
    }
    /**
     * Join store relation table if there is store filter
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        parent::_renderFiltersBefore();
        $customerId = $this->_customerSession->getCustomer()->getId();
        $catalogProductEntity = $this->getTable('catalog_product_entity');
        $this->getSelect()->join(
            $catalogProductEntity.' as cpe',
            'main_table.mage_pro_row_id = cpe.row_id',
            ['type_id'=>'type_id', 'sku' => 'sku']
        );

        $this->addFieldToFilter('seller_id', ['eq'=> $customerId])
            ->addFieldToFilter(
                'type_id',
                [
                  ['eq'=> 'simple'],
                  ['eq'=> 'virtual'],
                  ['eq' => 'downloadable']

                ]
            );
        $this->getSelect()->reset(\Zend_Db_Select::GROUP);
        $this->getSelect()->group(['mage_pro_row_id','mageproduct_id']);
    }
}
