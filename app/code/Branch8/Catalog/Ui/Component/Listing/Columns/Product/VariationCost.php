<?php

declare(strict_types=1);

namespace Branch8\Catalog\Ui\Component\Listing\Columns\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Psr\Log\LoggerInterface;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

class VariationCost extends Column
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    protected VariationsFactory $variationsFactory;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var array
     */
    private array $label = [];

    /**
     * SummaryChange constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param VariationsFactory $variationsFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        CollectionFactory  $collectionFactory,
        LoggerInterface    $logger,
        VariationsFactory   $variationsFactory,
        ProductRepositoryInterface    $productRepository,
        ScopeConfigInterface $scopeConfig,
        array              $components = [],
        array              $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->variationsFactory = $variationsFactory;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function prepare()
    {
        if ($this->scopeConfig->isSetFlag('branch8_catalog/product_approval_setting/hide_variation_price')) {
            $this->_data['config']['componentDisabled'] = true;
        }
        parent::prepare();
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])
            || $this->scopeConfig->isSetFlag('branch8_catalog/product_approval_setting/hide_variation_price')) {
            return $dataSource;
        }

        if (empty($this->label)) {
            $this->label = $this->getLabels();
        }

        $fieldName = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            try {
                $variantValue = [];
                $variations = [];

                // Check for pre-loaded variations
                if (isset($item['branch8_variations'])) {
                    $variations = $item['branch8_variations'];
                } else {
                    // Fallback to individual query
                    $rowId = $item['row_id'] ?? $item['mage_pro_row_id'] ?? null;
                    if ($rowId) {
                        $collection = $this->variationsFactory->create()
                            ->getCollection()
                            ->addFieldToFilter("product_id", $rowId);
                        foreach ($collection as $variant) {
                            $variations[] = $variant->getData();
                        }
                    }
                }

                if (!empty($variations)) {
                    foreach ($variations as $variant) {
                        $cost = $variant['cost'] ?? '';
                        // Format: comb : cost
                        $variantValue[] = sprintf(
                            '<span><strong style="white-space: nowrap;">%s</strong> : <span>%s</span></span>',
                            $variant['comb'],
                            $cost
                        );
                    }
                    if (!empty($variantValue)) {
                        $item[$fieldName] = '<div style="width: 100px;">'.implode('<br>', $variantValue).'</div>';
                    } else {
                        $item[$fieldName] = '';
                    }
                } else {
                    $item[$fieldName] = '';
                }
            } catch (\Exception $e) {
                $item[$fieldName] = '';
            }
        }

        return $dataSource;
    }

    /**
     * Returns all labels.
     *
     * @return array
     */
    private function getLabels(): array
    {
        $labels = [];
        $attrCollection = $this->collectionFactory->create()
            ->addFieldToSelect(['attribute_code', 'frontend_label']);
        foreach ($attrCollection as $attribute) {
            $labels[$attribute->getAttributeCode()] = $attribute->getFrontendLabel();
        }
        $labels['options'] = __('Options');
        $labels['related'] = __('Related Products');
        $labels['image_gallery'] = __('Image Gallery');
        $labels['wk_manage_variation'] = __('Variations');

        return $labels;
    }
}
