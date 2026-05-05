<?php

namespace Branch8\OptionsWithStockAndImages\Ui\DataProvider\Product\Form\Modifier;

use Branch8\Catalog\Model\ResourceModel\ProductCopyTracking;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions as CustomOptionsModifier;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Element\DataType\Number;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Element\Hidden;
use Magento\Ui\Component\Form\Element\Input;
use Magento\Ui\Component\Form\Field;
use Webkul\OptionsWithStockAndImages\Helper\Data;
use Magento\Catalog\Helper\Data as catalogHelper;
use Magento\Catalog\Model\Product\Type;
use Magento\Ui\Component\Container;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Magento\Framework\App\ResourceConnection;

class CustomOptions extends AbstractModifier
{
    protected $meta = [];

    /**
     * @var LocatorInterface
     * @since 101.0.0
     */
    protected $locator;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     * @since 101.0.0
     */
    protected $storeManager;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    public $catalogHelper;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var ArrayManager
     * @since 101.0.0
     */
    protected $arrayManager;

    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var ProductCopyTracking
     */
    private ProductCopyTracking $productCopyTracking;

    /**
     * Constructor.
     *
     * @param Data $helper
     * @param catalogHelper $catalogHelper
     * @param LocatorInterface $locator
     * @param StockRegistryInterface $stockRegistry
     * @param ArrayManager $arrayManager
     * @param StoreManagerInterface $storeManager
     * @param VariationsFactory $variationsFactory
     * @param ResourceConnection $resourceConnection
     * @param ProductCopyTracking $productCopyTracking
     */
    public function __construct(
        Data                   $helper,
        catalogHelper          $catalogHelper,
        LocatorInterface       $locator,
        StockRegistryInterface $stockRegistry,
        ArrayManager           $arrayManager,
        StoreManagerInterface  $storeManager,
        VariationsFactory      $variationsFactory,
        ResourceConnection     $resourceConnection,
        ProductCopyTracking    $productCopyTracking
    )
    {
        $this->helper = $helper;
        $this->catalogHelper = $catalogHelper;
        $this->locator = $locator;
        $this->stockRegistry = $stockRegistry;
        $this->arrayManager = $arrayManager;
        $this->storeManager = $storeManager;
        $this->variationsFactory = $variationsFactory;
        $this->resource = $resourceConnection;
        $this->productCopyTracking = $productCopyTracking;
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data)
    {
        $productId = (int)$this->locator->getProduct()->getId();
        if (!$this->productCopyTracking->isTracked($productId)) {
            return $data;
        }

        return array_replace_recursive(
            $data,
            [$productId => [self::DATA_SOURCE_DEFAULT => [ProductInterface::SKU => '']]]
        );
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $this->meta = $meta;
        $this->addCustomOptionsFields();
        return $this->meta;
    }

    protected function addCustomOptionsFields()
    {
        $groupCustomOptionsName = CustomOptionsModifier::GROUP_CUSTOM_OPTIONS_NAME;
        $optionContainerName = CustomOptionsModifier::CONTAINER_OPTION;
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['arguments']['data']['config']['component'] = 'Branch8_OptionsWithStockAndImages/js/dynamic-rows/new-record';
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['arguments']['data']['config']['component'] = 'Branch8_OptionsWithStockAndImages/js/dynamic-rows/record';
   /*     $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'][CustomOptionsModifier::FIELD_TITLE_NAME]
        ['arguments']['data']['config']['validation'] = ['variation-fields-required' => true];*/
        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'][CustomOptionsModifier::FIELD_SKU_NAME]
        ['arguments']['data']['config']['validation'] = [
            'required-entry' => true,
            'sku-unique' => true
        ];

        $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
        [$optionContainerName]['children']['values']['children']['record']['children'] = array_replace_recursive(
            $this->meta[$groupCustomOptionsName]['children']['options']['children']['record']['children']
            [$optionContainerName]['children']['values']['children']['record']['children'],
            $this->getOptionsValueFields()
        );

        $this->meta[$groupCustomOptionsName]['children']['wk_input_validation'] = $this->getWKVariantValadition();

        if ($this->helper->isEnable()) {

            // Removing the Buttons if the product is bundle having dynamicPricing

            $dynamicPricing = false;
            $isCatalogStaging = false;
            $product = $this->catalogHelper->getProduct();
            if (!$product) {
                $isCatalogStaging = true;
                $product = $this->locator->getProduct();
            }
            if ($product) {
                if (!empty($product->getSku())) {
                    $productId = (int)$product->getId();
                    if (!$this->productCopyTracking->isTracked($productId)) {
                        $fieldSku = 'sku';
                        $pathFieldSku = $this->arrayManager->findPath($fieldSku, $this->meta, null, 'children');
                        if ($pathFieldSku) {
                            $this->meta = $this->arrayManager->merge(
                                $pathFieldSku . static::META_CONFIG_PATH,
                                $this->meta,
                                [
                                    'disabled' => true
                                ]
                            );
                        }
                    }
                }

                $priceType = $product->getPriceType();
                if ($priceType !== null && $priceType == 0) {
                    $dynamicPricing = true;
                }
                if (!($product->getTypeId() == Type::TYPE_BUNDLE) && !$dynamicPricing) {
                    $this->meta[$groupCustomOptionsName]['children']['container_header']['children'] = array_replace_recursive(
                        $this->meta[$groupCustomOptionsName]['children']['container_header']['children'],
                        $this->getWKButton()
                    );
                }

                // Remove wkswatches button
                unset($this->meta[$groupCustomOptionsName]['children']['container_header']['children']['wkswatches']);
                if ($isCatalogStaging) {
                    unset($this->meta[$groupCustomOptionsName]['children']['container_header']['children']['wkvariations']);
                }

                $salableQtyCode = 'salable_qty';
                $pathFieldSalableQtyCode = $this->arrayManager->findPath($salableQtyCode, $this->meta, null, 'children');
                if ($pathFieldSalableQtyCode) {
                    $this->meta = $this->arrayManager->merge(
                        $pathFieldSalableQtyCode . static::META_CONFIG_PATH,
                        $this->meta,
                        [
                            'validation' => [
                                'validate-number' => true,
                                'validate-digits' => true
                            ]
                        ]
                    );
                }

                $fieldCode = 'quantity_and_stock_status_qty';
                $pathField = $this->arrayManager->findPath($fieldCode, $this->meta, null, 'children');
                if ($pathField) {
                    $this->meta = $this->arrayManager->merge(
                        $pathField . static::META_CONFIG_PATH,
                        $this->meta,
                        [
                            'disabled' => true,
                            'additionalClasses' => 'disabled'
                        ]
                    );
                }

                $stockItem = $this->stockRegistry->getStockItem($product->getId());
                if ($stockItem && $stockItem->getItemId() && !$stockItem->getManageStock()) {
                    if ($pathFieldSalableQtyCode) {
                        $this->meta = $this->arrayManager->merge(
                            $pathFieldSalableQtyCode . static::META_CONFIG_PATH,
                            $this->meta,
                            [
                                'disabled' => true,
                                'validation' => [
                                    'validate-number' => true,
                                    'validate-digits' => true,
                                    'validate-greater-than-zero' => true
                                ]
                            ]
                        );
                    }
                }

                if ($isCatalogStaging && $this->isOWSIProduct($product)) {
                    $customOption = $this->arrayManager->findPath(CustomOptionsModifier::GROUP_CUSTOM_OPTIONS_NAME, $this->meta, null, 'children');
                    if ($customOption) {
                        $this->meta = $this->arrayManager->merge(
                            $customOption . static::META_CONFIG_PATH,
                            $this->meta,
                            [
                                'visible' => false
                            ]
                        );
                    }
                }

            }
        }
    }

    /**
     * @return array[]
     */
    private function getWKVariantValadition()
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'title' => __('Validate variantion form'),
                        'componentType' => Field::NAME,
                        'formElement' => Hidden::NAME,
                        'dataScope' => 'wk_input_validation',
                        'dataType' => Number::NAME,
                        'visible' => true,
                        'sortOrder' => 100,
                        'validation' => [
                            'variation-fields-required' => true
                        ]
                    ],
                ],
            ],
        ];
    }

    protected function getWKButton()
    {
        return [
            "wkvariations" => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'title' => __('Manage Variations'),
                            'formElement' => Container::NAME,
                            'componentType' => Container::NAME,
                            'component' => 'Magento_Ui/js/form/components/button',
                            'sortOrder' => 100,
                            'actions' => [
                                [
                                    'targetName' => '${ $.ns }.${ $.ns }.' . CustomOptionsModifier::
                                        GROUP_CUSTOM_OPTIONS_NAME
                                        . '.' . CustomOptionsModifier::GRID_OPTIONS_NAME,
                                    'actionName' => '',
                                ]
                            ]
                        ],
                    ],
                ],
            ]
        ];
    }

    protected function getOptionsValueFields()
    {
        $fields['is_bought'] = $this->getSelectIsBoughtField(55);
        $fields['is_visible'] = $this->getSelectIsVisibleField(56);

        return $fields;
    }

    protected function getSelectIsVisibleField($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Is Visible'),
                        'componentType' => Field::NAME,
                        'formElement' => Checkbox::NAME,
                        'dataScope' => 'is_visible',
                        'dataType' => Number::NAME,
                        'sortOrder' => $sortOrder,
                        'value' => '1',
                        'valueMap' => [
                            'true' => '1',
                            'false' => '0'
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getSelectIsBoughtField($sortOrder)
    {
        return [
            'arguments' => [
                'data' => [
                    'config' => [
                        'componentType' => Field::NAME,
                        'formElement' => Hidden::NAME,
                        'dataScope' => 'is_bought',
                        'dataType' => Number::NAME,
                        'visible' => false,
                        'sortOrder' => $sortOrder
                    ],
                ],
            ],
        ];
    }

    /**
     * Check isOWSIProduct or not
     *
     * @return int
     */
    protected function isOWSIProduct($product)
    {
        $row_ids = $this->getRowId($product);
        $count = 0;
        if (count($row_ids)) {
            $collection = $this->variationsFactory->create()
                ->getCollection()
                ->addFieldToFilter("product_id", ['in' => $row_ids]);
            $count = $collection->getSize();
        }
        return $count;
    }

    protected function getRowId($product)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_entity');
        $where = $connection->quoteInto('entity_id = ?', $product->getId());
        return $connection->fetchCol("SELECT `{$table}`.`row_id` FROM `{$table}` WHERE {$where}");
    }
}
