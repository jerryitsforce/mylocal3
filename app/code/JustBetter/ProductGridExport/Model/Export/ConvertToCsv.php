<?php

namespace JustBetter\ProductGridExport\Model\Export;


use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider;

class ConvertToCsv extends \Magento\Ui\Model\Export\ConvertToCsv
{

    protected $collection;

    /**
     *
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $mpHelper;

    /**
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $attribute;

    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    protected ProductCustomOptionRepositoryInterface $customOptionRepository;


    /**
     * @param  Filesystem  $filesystem
     * @param  Filter  $filter
     * @param  \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory  $collection
     * @param  \Webkul\Marketplace\Helper\Data  $mpHelper
     * @param  \Magento\Eav\Model\ResourceModel\Entity\Attribute  $attribute
     * @param  MetadataProvider  $metadataProvider
     * @param int $pageSize
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        \Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory $collection,
        \Webkul\Marketplace\Helper\Data $mpHelper,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $attribute,
        MetadataProvider $metadataProvider,
        ProductCustomOptionRepositoryInterface $customOptionRepository,
        int $pageSize = 200
    ) {
        $this->collection = $collection;
        $this->mpHelper = $mpHelper;
        $this->attribute = $attribute;
        $this->customOptionRepository = $customOptionRepository;
        parent::__construct($filesystem, $filter, $metadataProvider, $pageSize);
    }

    /**
     * Returns CSV file
     *
     * @return array
     * @throws LocalizedException
     */
    public function getCsvFile($isBackend = false)
    {
        $component = $this->filter->getComponent();
        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();
        $dataProvider = $component->getContext()->getDataProvider();
        $ids = $dataProvider->getSearchResult()->getAllIds();
        // Generate unique filename (non-security usage)
        $name = bin2hex(random_bytes(16));
        $file = 'export/' . $name . '.csv';
        $columns = $this->getColumns();

        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        $stream->writeCsv($columns);

        $collection = $this->prepareCollection($isBackend, $ids);
        $items = $collection->getItems();

        foreach ($items as $item) {
            $itemData = $this->prepareItemData($item);
            $stream->writeCsv($itemData);
        }

        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true
        ];
    }

    /**
     * @return string[]
     */
    private function getColumns()
    {
        return [
            'Invoice Company No', 'Invoice Company Name', 'Company Name', 'Register Account',
            'entity', 'product name', 'option title', 'option name', 'option sku', 'product type',
            'price', 'Special Price', 'cost', 'Point:Money Config Type',
            'shipping method', 'Status', 'Review Status', 'Qty', 'Safe Qty', 'SKU',
            'Start Sale Date-Virtual Ticket', 'End Sale Date-Virtual Ticket',
            'Start Redemption Date-Virtual Ticket', 'End Redemption Date-Virtual Ticket'
        ];
    }

    /**
     * @return \Webkul\Marketplace\Model\ResourceModel\Product\Collection
     */
    private function prepareCollection($isBackend = false, $ids = [])
    {
        $collection = $this->collection->create();
        $sellerId = $this->mpHelper->getCustomerId();
        $storeId = 1;
        $eavAttribute = $this->attribute;

        $attributeIds = $this->getAttributeIds($eavAttribute);
        $tables = $this->getTables($collection);

        $this->applyJoinsAndFilters($collection, $sellerId, $storeId, $attributeIds, $tables, $isBackend, $ids);

        $flattenedCollection = [];
        $uniqueIndex = 1; // Counter for generating unique IDs

        foreach ($collection as $item) {
            try {
                if(!$item->getSku()){
                    continue;
                }
                $customOptions = $this->customOptionRepository->getList($item->getSku());

                if (empty($customOptions)) {
                    // No custom options, add the original item
                    $item->setId($item->getId() . '-base-' . $uniqueIndex); // Assign a unique ID
                    $flattenedCollection[] = $item;
                    $uniqueIndex++;
                    continue;
                }

                foreach ($customOptions as $customOption) {
                    if (in_array($customOption->getType(), ['drop_down', 'multiple', 'checkbox', 'radio'])) {
                        foreach ($customOption->getValues() as $value) {
                            $itemClone = clone $item;
                            $itemClone->setId($item->getId() . '-opt-' . $uniqueIndex); // Assign a unique ID
                            $itemClone->setData('option_title', $customOption->getTitle());
                            $itemClone->setData('option_value', $value->getTitle());
                            $itemClone->setData('option_price', $value->getPrice());
                            $itemClone->setData('option_price_type', $value->getPriceType());
                            $itemClone->setData('option_sku', $value->getSku());
                            $flattenedCollection[] = $itemClone;
                            $uniqueIndex++;
                        }
                    } else {
                        $itemClone = clone $item;
                        $itemClone->setId($item->getId() . '-opt-' . $uniqueIndex); // Assign a unique ID
                        $itemClone->setData('option_title', $customOption->getTitle());
                        $itemClone->setData('option_value', $customOption->getPrice());
                        $itemClone->setData('option_price_type', $customOption->getPriceType());
                        $itemClone->setData('option_sku', $customOption->getSku());
                        $flattenedCollection[] = $itemClone;
                        $uniqueIndex++;
                    }
                }
            }catch (\Exception $e) {
                continue;
            }

        }

        $collection->clear(); // Clear the original collection
        foreach ($flattenedCollection as $item) {
            $collection->addItem($item); // Add transformed items to the collection
        }

        return $collection;
    }



    /**
     * @param $eavAttribute
     *
     * @return array
     */
    private function getAttributeIds($eavAttribute)
    {
        return [
            'price' => $eavAttribute->getIdByCode("catalog_product", "price"),
            'special_price' => $eavAttribute->getIdByCode("catalog_product", "special_price"),
            'cost' => $eavAttribute->getIdByCode("catalog_product", "cost"),
            'point_money_config_type' => $eavAttribute->getIdByCode("catalog_product", "point_money_config_type"),
            'shipping_method' => $eavAttribute->getIdByCode("catalog_product", "shipping_method"),
            'status' => $eavAttribute->getIdByCode("catalog_product", "status"),
            'name' => $eavAttribute->getIdByCode("catalog_product", "name"),
            'limit_purchased_start_time' => $eavAttribute->getIdByCode("catalog_product", "limit_purchased_start_time"),
            'limit_purchased_end_time' => $eavAttribute->getIdByCode("catalog_product", "limit_purchased_end_time"),
        ];
    }

    private function getTables($collection)
    {
        return [
            'catalog_product_entity' => $collection->getTable('catalog_product_entity'),
            'catalog_product_entity_decimal' => $collection->getTable('catalog_product_entity_decimal'),
            'catalog_product_entity_int' => $collection->getTable('catalog_product_entity_int'),
            'catalog_product_entity_varchar' => $collection->getTable('catalog_product_entity_varchar'),
            'catalog_product_entity_text' => $collection->getTable('catalog_product_entity_text'),
            'catalog_product_entity_datetime' => $collection->getTable('catalog_product_entity_datetime'),
            'cataloginventory_stock_item' => $collection->getTable('cataloginventory_stock_item'),
            'marketplace_userdata' => $collection->getTable('marketplace_userdata'),
        ];
    }

    private function applyJoinsAndFilters($collection, $sellerId, $storeId, $attributeIds, $tables, $isBackend = false, $ids = [])
    {
        $connection = $collection->getConnection();

        if($isBackend){
            $collection->getSelect()->joinLeft(
                ['cpe' => $tables['catalog_product_entity']],
                'main_table.mage_pro_row_id = cpe.row_id',
                ['product_type' => 'type_id', 'sku']
            );
        }else{
            $collection->getSelect()->joinLeft(
                ['cpe' => $tables['catalog_product_entity']],
                'main_table.mage_pro_row_id = cpe.row_id',
                ['product_type' => 'type_id', 'sku']
            )->where("main_table.seller_id = ?", $sellerId);
        }

        $collection->getSelect()->joinLeft(
            ['mu' => $tables['marketplace_userdata']],
            'main_table.seller_id = mu.seller_id',
            ['invoice_company_no', 'invoice_company_name','company_name','register_account']
        );

        $this->joinProductAttributes($collection, $storeId, $attributeIds, $tables, $connection);
        $collection->getSelect()->joinLeft(
            ['csi' => $tables['cataloginventory_stock_item']],
            'main_table.mageproduct_id = csi.product_id',
            ["qty" => "qty"]
        )->where("csi.website_id = 0 OR csi.website_id = 1");

        if (!empty($ids)) {
            $collection->addFieldToFilter('main_table.entity_id', ['in' => $ids]);
        }

        $collection->getSelect()->group('main_table.mageproduct_id');
    }

    private function joinProductAttributes($collection, $storeId, $attributeIds, $tables, $connection)
    {
        $collection->getSelect()->joinLeft(
            $tables['catalog_product_entity_varchar'].' as cpev',
            'main_table.mage_pro_row_id = cpev.row_id',
            ["product_name" => "value"]
        )->where(
            "cpev.store_id = ?  AND  cpev.attribute_id = ".$attributeIds['name'],
            $connection->getIfNullSql('cpev.store_id='.$storeId, 'cpev.store_id=0')
        );

        $collection->getSelect()->joinLeft(
            ['cped_price' => $tables['catalog_product_entity_decimal']],
            'main_table.mage_pro_row_id = cped_price.row_id AND cped_price.store_id = 0 AND cped_price.attribute_id = ' . $attributeIds['price'],
            ['price' => 'cped_price.value']
        );

        $collection->getSelect()->joinLeft(
            ['cped_special_price' => $tables['catalog_product_entity_decimal']],
            'main_table.mage_pro_row_id = cped_special_price.row_id AND cped_special_price.store_id = 0 AND cped_special_price.attribute_id = ' . $attributeIds['special_price'],
            ['special_price' => 'cped_special_price.value']
        );

        $collection->getSelect()->joinLeft(
            ['cped_cost' => $tables['catalog_product_entity_decimal']],
            'main_table.mage_pro_row_id = cped_cost.row_id AND cped_cost.store_id = 0 AND cped_cost.attribute_id = ' . $attributeIds['cost'],
            ['cost' => 'cped_cost.value']
        );

        $collection->getSelect()->joinLeft(
            $tables['catalog_product_entity_int'].' as cpei',
            'main_table.mage_pro_row_id = cpei.row_id',
            ["point_money_config_type" => "value"]
        )->where(
            'cpei.store_id = ?  AND cpei.attribute_id = '.$attributeIds['point_money_config_type'],
            $connection->getIfNullSql('cpei.store_id='.$storeId, 'cpei.store_id=0')
        );

        $collection->getSelect()->joinLeft(
            $tables['catalog_product_entity_varchar'].' as cpev1',
            'main_table.mage_pro_row_id = cpev1.row_id AND  cpev1.attribute_id ='.$attributeIds['shipping_method'],
            ["shipping_method" => "value"]
        );

        $collection->getSelect()->joinLeft(
            $tables['catalog_product_entity_int'].' as cpei1',
            'main_table.mage_pro_row_id = cpei1.row_id',
            ["product_status" => "value"]
        )->where(
            'cpei1.store_id = ?  AND cpei1.attribute_id = '.$attributeIds['status'],
            $connection->getIfNullSql('cpei1.store_id='.$storeId, 'cpei1.store_id=0')
        );

        $collection->getSelect()->joinLeft(
            ['cpei_start_time' => $tables['catalog_product_entity_datetime']],
            'main_table.mage_pro_row_id = cpei_start_time.row_id AND cpei_start_time.store_id = 0 AND cpei_start_time.attribute_id = ' . $attributeIds['limit_purchased_start_time'],
            ['start_sale_date_virtual_ticket' => 'cpei_start_time.value']
        );

        $collection->getSelect()->joinLeft(
            ['cpei_end_time' => $tables['catalog_product_entity_datetime']],
            'main_table.mage_pro_row_id = cpei_end_time.row_id AND cpei_end_time.store_id = 0 AND cpei_end_time.attribute_id = ' . $attributeIds['limit_purchased_end_time'],
            ['end_sale_date_virtual_ticket' => 'cpei_end_time.value']
        );


        $optionSubquery = $collection->getConnection()->select()
            ->from(
                ['cpo' => $collection->getTable('catalog_product_option')],
                ['product_id']
            )
            ->joinLeft(
                ['cpot' => $collection->getTable('catalog_product_option_title')],
                'cpo.option_id = cpot.option_id',
                ['custom_option_name' => 'GROUP_CONCAT(DISTINCT cpot.title SEPARATOR ", ")']
            )
            ->joinLeft(
                ['cpotv' => $collection->getTable('catalog_product_option_type_value')],
                'cpo.option_id = cpotv.option_id',
                []
            )
            ->joinLeft(
                ['cpott' => $collection->getTable('catalog_product_option_type_title')],
                'cpotv.option_type_id = cpott.option_type_id',
                ['custom_option_values' => 'GROUP_CONCAT(DISTINCT cpott.title SEPARATOR ", ")']
            )
            ->group('cpo.product_id');
        $collection->getSelect()->joinLeft(
            ['option_data' => $optionSubquery],
            'main_table.entity_id = option_data.product_id',
            ['custom_option_name', 'custom_option_values']
        );
    }

    private function prepareItemData($item)
    {
        return [
            $item->getData('invoice_company_no'),
            $item->getData('invoice_company_name'),
            $item->getData('company_name'),
            $item->getData('register_account'),
            $item->getData('mageproduct_id'),
            $item->getData('product_name'),
            $item->getData('option_title'),
            $item->getData('option_value'),
            $item->getData('option_sku'),
            $item->getData('product_type'),
            $item->getData('price'),
            $item->getData('special_price'),
            $item->getData('cost'),
            $item->getData('point_money_config_type'),
            $item->getData('shipping_method'),
            $item->getData('product_status'),
            $item->getData('review_status'),
            $item->getData('qty'),
            $item->getData('qty'),
            $item->getData('sku'),
            $item->getData('start_sale_date_virtual_ticket'),
            $item->getData('end_sale_date_virtual_ticket'),
            $item->getData('start_redemption_date_virtual_ticket'),
            $item->getData('end_redemption_date_virtual_ticket'),
        ];
    }
}
