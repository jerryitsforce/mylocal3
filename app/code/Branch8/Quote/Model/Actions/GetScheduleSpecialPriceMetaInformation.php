<?php
declare(strict_types=1);

namespace Branch8\Quote\Model\Actions;

use Magento\Catalog\Model\Product;
use Branch8\Quote\Model\Actions\SearchResultFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;

class GetScheduleSpecialPriceMetaInformation
{
    const ATTRIBUTE_CODE = 'special_price';
    private \Magento\Staging\Model\ResourceModel\Update\CollectionFactory $updateCollectionFactory;

    private \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory;

    private SearchResultFactory $searchResultFactory;
    private ResourceConnection $resourceConnection;
    private Config $config;

    /**
     * @param \Magento\Staging\Model\ResourceModel\Update\CollectionFactory $updateCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Forix\TrackingSpecialPrice\Model\Actions\SearchResultFactory $searchResultFactory
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     */
    public function __construct(
        \Magento\Staging\Model\ResourceModel\Update\CollectionFactory  $updateCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        SearchResultFactory                                            $searchResultFactory,
        ResourceConnection                                             $resourceConnection,
        Config                                                         $config
    )
    {
        $this->collectionFactory = $productCollectionFactory;
        $this->updateCollectionFactory = $updateCollectionFactory;
        $this->searchResultFactory = $searchResultFactory;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
    }

    /**
     *
     * @param Product $product
     * @return array|null[]
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(Product $product)
    {
        /**
         * 1. find rollback ID
         * 2. get rollback id for this product:
         *    2.1 if has roll back id => next version will be origin version
         *    2.2 if hs no roll back id => current speical price
         */
        $connection = $this->resourceConnection->getConnection();
        /**
         * @var $searchResult \Forix\TrackingSpecialPrice\Model\Actions\SearchResult
         */
        $rowId = $specialPrice = $beginDate = $endDate = null;
        if (!$product->getSpecialPrice()) {
            return [
                $rowId,
                $specialPrice,
                $beginDate,
                $endDate
            ];
        }
        try {
            $eav = $this->config->getAttribute('catalog_product', self::ATTRIBUTE_CODE);
        } catch (\Exception $exception) {
            return [
                $rowId,
                $specialPrice,
                $beginDate,
                $endDate
            ];
        }
        if (!$eav->getAttributeId()) {
            return [
                $rowId,
                $specialPrice,
                $beginDate,
                $endDate
            ];
        }
        $attributeId = $eav->getAttributeId();
        $entityId = $product->getId();
        $createdIn = $product->getCreatedIn();// current schedule
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter('entity_id', $entityId);
        $collection->getSelect()->join('staging_update', 'staging_update.id=' . $createdIn)
            ->where('rollback_id IS NOT NULl');
        if (!$collection->getSize()) {
            return [
                $rowId,
                $specialPrice,
                $beginDate,
                $endDate
            ];
        }
        $rollBackId = $collection->load()->getFirstItem()->getData('rollback_id');
        $priceTable = $connection->getTableName('catalog_product_entity_decimal');
        $maintable = $connection->getTableName('catalog_product_entity');
        $sql = 'SELECT `' . $priceTable . '`.`value` AS `special_price`';
        $sql .= 'FROM `' . $maintable . '` AS `main_table` ';
        $sql .= 'INNER JOIN `' . $priceTable . '` ON main_table.row_id=catalog_product_entity_decimal.row_id ';
        $sql .= 'WHERE ((created_in = ' . $rollBackId . ') AND (attribute_id = ' . $attributeId . ')) LIMIT 1 ;';
        $result = (float)$connection->query($sql)->fetchColumn(0);
        if (!$result) {
            return [
                $product->getRowId(),
                $product->getSpecialPrice(),
                date('Y-m-d H:i:s', $product->getCreatedIn()),
                null,
            ];
        }
        return [
            $product->getRowId(),
            $result,
            date('Y-m-d H:i:s', (int)$product->getCreatedIn()),
            date('Y-m-d H:i:s', (int)$product->getUpdatedIn()),
        ];
    }
}
