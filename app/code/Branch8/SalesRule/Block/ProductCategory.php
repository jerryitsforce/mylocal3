<?php

namespace Branch8\SalesRule\Block;

use Magento\Backend\Block\Widget\Context;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Framework\Registry;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;

class ProductCategory extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Registry
     */
    public $registry;

    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $collectionFactory;

    /**
     * @var CategoryModel
     */
    public $category;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    public $categoryHelper;

    protected const ASSIGN_CATEGORY_TEMPLATE = 'Branch8_SalesRule::cart_price/categories.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CategoryCollectionFactory $collectionFactory
     * @param CategoryModel $category
     * @param CategoryHelper $categoryHelper
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context                   $context,
        Registry                  $registry,
        CategoryCollectionFactory $collectionFactory,
        CategoryModel             $category,
        CategoryHelper            $categoryHelper,
        array                     $data = [],
        ?SecureHtmlRenderer       $secureRenderer = null
    )
    {
        $this->coreRegistry = $registry;
        $this->collectionFactory = $collectionFactory;
        $this->category = $category;
        $this->categoryHelper = $categoryHelper;
        parent::__construct($context, $data, $secureRenderer);
    }

    /**
     * Set template to itself.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::ASSIGN_CATEGORY_TEMPLATE);
        }

        return $this;
    }

    public function getJsFormObject()
    {
        return $this->getData('js_form_object');
    }

    /**
     * @return array $category;
     */
    public function getSelectedCategory()
    {
        $selectedCategory = $this->getRequest()->getPost('selected', []);

        return $selectedCategory;
    }


    public function getCategoriesTree()
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('position', 'asc');

        $categoryById = [
            CategoryModel::TREE_ROOT_ID => [
                'value' => CategoryModel::TREE_ROOT_ID,
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['value' => $categoryId];
                }
            }

            $categoryById[$category->getId()]['is_active'] = 1;
            $categoryById[$category->getId()]['label'] = $category->getName();
            $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
        }

        return $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'];
    }
}