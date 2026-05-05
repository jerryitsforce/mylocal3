<?php

namespace Branch8\MarketplaceStaging\Block\CustomOption;

use Magento\Catalog\Model\Product;
use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;
use Magento\Framework\View\Element\Template;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Magento\Framework\App\ResourceConnection;

class StagingOptions extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketplaceStaging::catalog/product/edit/staging_options.phtml';

    /**
     * @var StagingLocator
     */
    protected $locator;

    /**
     * @var VariationsFactory
     */
    protected $variationsFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resource;

    /**
     * Option constructor.
     * @param Template\Context $context
     * @param StagingLocator $locator
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        StagingLocator $locator,
        VariationsFactory $variationsFactory,
        ResourceConnection $resource,
        array $data = []
    ) {
        $this->locator = $locator;
        $this->variationsFactory = $variationsFactory;
        $this->resource = $resource;
        parent::__construct($context, $data);
    }

    /**
     * @return Template
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'add_button',
            \Branch8\MarketplaceStaging\Block\Widget\Button::class,
            ['label' => __('Add New Option'), 'class' => 'add', 'id' => 'staging_add_new_defined_option']
        );

        $this->addChild(
            'add_wkvariations_button',
            \Branch8\MarketplaceStaging\Block\Widget\Button::class,
            ['label' => __('Manage Variations'), 'class' => 'add', 'id' => 'add_wkvariations_button', 'data-index' => 'wkvariations']
        );

        $this->addChild('options_box', Options\StagingOption::class);

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getAddWKButtonHtml()
    {
        return $this->getChildHtml('add_wkvariations_button');
    }
    
    /**
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    /**
     * Default will call html from this function
     * @return string
     */
    public function getOptionsBoxHtml()
    {
        return $this->getChildHtml('options_box');
    }

    /**
     * Get Product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->locator->getProduct();
    }

    /**
     * Retreive Product Type
     *
     * @return string
     */
    public function getProductType()
    {
        $productId = $this->getRequest()->getParam('id');
        if ($productId) {
            $product = $this->getProduct();
            $productType = $product->getTypeId();
        } else {
            $productType = $this->getRequest()->getParam('type');
        }
        return $productType;
    }

    /**
     * Check isOWSIProduct or not
     *
     * @return int
     */
    public function isOWSIProduct()
    {
        $row_ids = $this->getRowId($this->getProduct());
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
