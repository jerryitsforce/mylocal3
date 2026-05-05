<?php
declare(strict_types=1);

namespace Branch8\MarketplaceProductImport\Model;

use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\App\ResourceConnection;

class FormatBundleProduct
{
    /**
     * Delimiter before product option value.
     */
    public const BEFORE_OPTION_VALUE_DELIMITER = ',';

    public const PAIR_VALUE_SEPARATOR = '=';

    /**
     * Fixed value.
     */
    public const VALUE_FIXED = 'fixed';

    public const SELECTION_PRICE_TYPE_FIXED = 0;

    public const SELECTION_PRICE_TYPE_PERCENT = 1;

    /**
     * Array of cached options.
     *
     * @var array
     */
    protected $_cachedOptions = [];

    /**
     * Array of cached skus.
     *
     * @var array
     */
    protected $_cachedSkus = [];

    /**
     * Mapping array between cached skus and products.
     *
     * @var array
     */
    protected $_cachedSkuToProducts = [];

    /**
     * Array of queries selecting cached options.
     *
     * @var array
     */
    protected $_cachedOptionSelectQuery = [];

    /**
     * Column names that holds values with particular meaning.
     *
     * @var string[]
     */
    protected $_specialAttributes = [
        'price_type',
        'weight_type',
        'sku_type',
    ];

    /**
     * Bundle field mapping for bundle product with selection.
     *
     * @var array
     */
    protected $_bundleFieldMapping = [
        'is_default' => 'default',
        'selection_price_value' => 'price',
        'selection_qty' => 'default_qty',
    ];

    /**
     * Custom fields mapping for bundle product.
     *
     * @var array
     */
    protected $_customFieldsMapping = [
        'bundle_price_type' => 'price_type',
        'bundle_shipment_type' => 'shipment_type',
        'bundle_price_view' => 'price_view',
        'bundle_weight_type' => 'weight_type',
        'bundle_sku_type' => 'sku_type',
    ];

    /**
     * Option type mapping for bundle product.
     *
     * @var array
     */
    protected $_optionTypeMapping = [
        'dropdown' => 'select',
        'radiobutton' => 'radio',
        'checkbox' => 'checkbox',
        'multiselect' => 'multi',
    ];

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->_resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * Parse the row data.
     *
     * @param array $wholeData
     * @param array $rowData
     *
     * @return array
     */
    public function execute($wholeData, $rowData)
    {
        if (empty($rowData['bundle_values'])) {
            $wholeData['product']['type_id'] = 'simple';
        } else {
            $wholeData['product']['type_id'] = 'bundle';
            $wholeData['affect_bundle_product_selections'] = 1;
        }
        if (isset($rowData['bundle_price_type']) && $rowData['bundle_price_type'] == 'Dynamic') {
            $wholeData['product']['price'] = isset($rowData['price']) && $rowData['price'] ? $rowData['price'] : '0.00';
            $wholeData['product']['price_type'] = 0;
        } else {
            $wholeData['product']['price_type'] = 1;
        }
        foreach ($this->_customFieldsMapping as $key => $value) {
            if (isset($rowData[$key])) {
                $data = trim($rowData[$key]);
                if ($data && (strtolower($data) == 'dynamic' || strtolower($data) == 'together' || strtolower($data) == 'price range')) {
                    $data = 0;
                } elseif ($data && (strtolower($data) == 'fixed' || strtolower($data) == 'separately' || strtolower($data) == 'as low as')) {
                    $data = 1;
                }
                $wholeData['product'][$value] = $data;
            }
        }
        $productId = $wholeData['row_id'] ?? null;
        $this->parseSelections($rowData, $rowData['sku'], $productId);
        if (!empty($this->_cachedOptions)) {
            $this->retrieveProductsByCachedSkus();
            $this->populateExistingOptions();
            foreach ($this->_cachedOptions as $k => $options) {
                $index = 0;
                foreach ($options as $key => $option) {
                    if (isset($option['position'])) {
                        $index = $option['position'];
                    }
                    if ($tmpArray = $this->populateOptionTemplate($option, $index)) {
                        $wholeData['bundle_options'][$index] = $tmpArray;
                        $this->_cachedOptions[$k][$key]['index'] = $index;
                        $index++;
                    }
                }
            }
            foreach ($this->_cachedOptions as $k => $options) {
                foreach ($options as $key => $option) {
                    $index = 0;
                    foreach ($option['selections'] as $selection) {
                        $parentIndex = $this->_cachedOptions[$k][$key]['index'];
                        if (isset($selection['position'])) {
                            $index = $selection['position'];
                        }
                        if ($tmpArray = $this->populateSelectionTemplate(
                            $selection,
                            $option['option_id'] ?? null,
                            $index
                        )) {
                            $wholeData['bundle_selections'][$parentIndex][$index] = $tmpArray;
                            $index++;
                        }
                    }
                }
            }
            $this->_cachedOptions = [];
            $this->_cachedSkus = [];
            $this->_cachedSkuToProducts = [];
        }
        return $wholeData;
    }

    /**
     * Parse selections.
     *
     * @param array $rowData
     * @param mixed $sku
     * @param mixed $productId
     *
     * @return array
     */
    protected function parseSelections($rowData, $sku, $productId)
    {
        if (empty($rowData['bundle_values'])) {
            return [];
        }

        $selections = explode(
            Product::PSEUDO_MULTI_LINE_SEPARATOR,
            $rowData['bundle_values']
        );
        if ($productId) $sku = $productId;
        foreach ($selections as $selection) {
            $values = explode(self::BEFORE_OPTION_VALUE_DELIMITER, $selection);
            $option = $this->parseOption($values);
            if (isset($option['sku']) && isset($option['name'])) {
                if (isset($this->_cachedOptions[$sku][$option['name']]['sku']) && $this->_cachedOptions[$sku][$option['name']]['sku'] == $option['sku']) {
                    continue;
                }
                if (!isset($this->_cachedOptions[$sku])) {
                    $this->_cachedOptions[$sku] = [];
                }
                $this->_cachedSkus[] = $option['sku'];
                if (!isset($this->_cachedOptions[$sku][$option['name']])) {
                    $this->_cachedOptions[$sku][$option['name']] = [];
                    $this->_cachedOptions[$sku][$option['name']] = $option;
                    $this->_cachedOptions[$sku][$option['name']]['selections'] = [];
                }
                $this->_cachedOptions[$sku][$option['name']]['sku'] = $option['sku'];
                $this->_cachedOptions[$sku][$option['name']]['selections'][] = $option;
                if ($productId) $this->_cachedOptionSelectQuery[] = [(int)$productId, $option['name']];
            }
        }
        return $selections;
    }

    /**
     * Parse the option.
     *
     * @param array $values
     *
     * @return array
     */
    protected function parseOption($values)
    {
        $option = [];
        foreach ($values as $keyValue) {
            $keyValue = $keyValue ? trim($keyValue) : '';
            $pos = strpos($keyValue, self::PAIR_VALUE_SEPARATOR);
            if ($pos !== false) {
                $key = substr($keyValue, 0, $pos);
                $value = substr($keyValue, $pos + 1);
                if ($key == 'type') {
                    if (isset($this->_optionTypeMapping[$value])) {
                        $value = $this->_optionTypeMapping[$value];
                    }
                }
                $option[$key] = $value;
            }
        }
        return $option;
    }

    /**
     * Populate the option template.
     *
     * @param array $option
     * @param int $index
     * @return array
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function populateOptionTemplate($option, $index = null)
    {
        $populatedOption = [
            'option_id' => null,
            'required' => isset($option['required']) ? $option['required'] : 1,
            'delete' => null,
            'position' => ($index === null ? 0 : $index),
            'type' => isset($option['type']) ? $option['type'] : 'select',
            'title' => $option['name'],
        ];
        if (isset($option['option_id'])) {
            $populatedOption['option_id'] = $option['option_id'];
        }
        return $populatedOption;
    }

    /**
     * Populate the option value template.
     *
     * @param array $selection
     * @param int $optionId
     * @param int $parentId
     * @param int $index
     * @return array|bool
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function populateSelectionTemplate($selection, $optionId, $index)
    {
        if (!isset($selection['parent_product_id'])) {
            if (!isset($this->_cachedSkuToProducts[$selection['sku']])) {
                return false;
            }
            $productId = $this->_cachedSkuToProducts[$selection['sku']];
        } else {
            $productId = $selection['product_id'];
        }

        $populatedSelection = [
            'selection_id' => null,
            'option_id' => $optionId ?? null,
            'product_id' => (int)$productId,
            'delete' => null,
            'position' => (int)$index,
            'is_default' => (isset($selection['default']) && $selection['default']) ? 1 : 0,
            'selection_price_type' => (isset($selection['price_type']) && $selection['price_type'] == self::VALUE_FIXED)
                ? self::SELECTION_PRICE_TYPE_FIXED : self::SELECTION_PRICE_TYPE_PERCENT,
            'selection_price_value' => (isset($selection['price'])) ? (float)$selection['price'] : 0.0,
            'selection_qty' => (isset($selection['default_qty'])) ? (float)$selection['default_qty'] : 1.0,
            'selection_can_change_qty' => isset($selection['can_change_qty'])
                ? ($selection['can_change_qty'] ? 1 : 0) : 1,
        ];
        if (isset($selection['selection_id'])) {
            $populatedSelection['selection_id'] = $selection['selection_id'];
        }
        return $populatedSelection;
    }

    /**
     * Populates existing options.
     *
     */
    protected function populateExistingOptions()
    {
        if (empty($this->_cachedOptionSelectQuery)) {
            return $this;
        }
        $select = $this->connection->select()->from(
            ['bo' => $this->_resource->getTableName('catalog_product_bundle_option')],
            ['option_id', 'parent_id', 'required', 'position', 'type']
        )->joinLeft(
            ['bov' => $this->_resource->getTableName('catalog_product_bundle_option_value')],
            'bo.option_id = bov.option_id',
            ['value_id', 'title']
        );
        $orWhere = false;
        foreach ($this->_cachedOptionSelectQuery as $item) {
            if ($orWhere) {
                $select->orWhere('parent_id = ' . $item[0] . ' AND title = ?', $item[1]);
            } else {
                $select->where('parent_id = ' . $item[0] . ' AND title = ?', $item[1]);
                $orWhere = true;
            }
        }

        $existingOptions = $this->connection->fetchAssoc($select);
        foreach ($existingOptions as $optionId => $option) {
            $this->_cachedOptions[$option['parent_id']][$option['title']]['option_id'] = $optionId;
            foreach ($option as $key => $value) {
                if (!isset($this->_cachedOptions[$option['parent_id']][$option['title']][$key])) {
                    $this->_cachedOptions[$option['parent_id']][$option['title']][$key] = $value;
                }
            }
        }
        $this->populateExistingSelections($existingOptions);
    }

    /**
     * Populate existing selections.
     *
     * @param array $existingOptions
     *
     * @return \Magento\CatalogImportExport\Model\Import\Product\Type\AbstractType
     */
    protected function populateExistingSelections($existingOptions)
    {
        //@codingStandardsIgnoreStart
        $existingSelections = $this->connection->fetchAll(
            $this->connection->select()->from(
                $this->_resource->getTableName('catalog_product_bundle_selection')
            )->where(
                'option_id IN (?)',
                array_keys($existingOptions)
            )
        );
        foreach ($existingSelections as $existingSelection) {
            $optionTitle = $existingOptions[$existingSelection['option_id']]['title'];
            if (isset($this->_cachedOptions[$existingSelection['parent_product_id']][$optionTitle]['selections'])) {
                $cachedOptionsSelections = $this->_cachedOptions[$existingSelection['parent_product_id']][$optionTitle]['selections'];
                foreach ($cachedOptionsSelections as $selectIndex => $selection) {
                    $productId = $this->_cachedSkuToProducts[$selection['sku']];
                    if ($productId == $existingSelection['product_id']) {
                        foreach (array_keys($existingSelection) as $origKey) {
                            $key = $this->_bundleFieldMapping[$origKey] ?? $origKey;
                            if (
                                !isset($this->_cachedOptions[$existingSelection['parent_product_id']][$optionTitle]['selections'][$selectIndex][$key])
                            ) {
                                $this->_cachedOptions[$existingSelection['parent_product_id']][$optionTitle]['selections'][$selectIndex][$key] =
                                    $existingSelection[$origKey];
                            }
                        }
                        break;
                    }
                }
            }
        }
        // @codingStandardsIgnoreEnd
    }

    /**
     * Retrieve mapping between skus and products.
     *
     */
    protected function retrieveProductsByCachedSkus()
    {
        $this->_cachedSkuToProducts = $this->connection->fetchPairs(
            $this->connection->select()->from(
                $this->_resource->getTableName('catalog_product_entity'),
                ['sku', 'entity_id']
            )->where(
                'sku IN (?)',
                $this->_cachedSkus
            )
        );
    }

}
