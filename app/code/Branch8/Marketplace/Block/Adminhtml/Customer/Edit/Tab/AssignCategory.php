<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Branch8\Marketplace\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Backend\Block\Widget\Context;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Registry;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Framework\View\Helper\SecureHtmlRenderer;
use Magento\Store\Model\ScopeInterface;
use Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab\Magento;
use Webkul\Marketplace\Model\SellerFactory;

class AssignCategory extends \Magento\Config\Block\System\Config\Form\Field
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

    /**
     * @var \Webkul\Marketplace\Model\SellerFactory
     */
    public $sellerFactory;

    protected const ASSIGN_CATEGORY_TEMPLATE = 'Branch8_Marketplace::customer/assign-category.phtml';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param CategoryCollectionFactory $collectionFactory
     * @param CategoryModel $category
     * @param CategoryHelper $categoryHelper
     * @param SellerFactory $sellerFactory
     * @param array $data
     * @param SecureHtmlRenderer|null $secureRenderer
     */
    public function __construct(
        Context                   $context,
        Registry                  $registry,
        CategoryCollectionFactory $collectionFactory,
        CategoryModel             $category,
        CategoryHelper            $categoryHelper,
        SellerFactory             $sellerFactory,
        array                     $data = [],
        ?SecureHtmlRenderer       $secureRenderer = null
    ) {
        $this->coreRegistry = $registry;
        $this->collectionFactory = $collectionFactory;
        $this->category = $category;
        $this->categoryHelper = $categoryHelper;
        $this->sellerFactory = $sellerFactory;
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

    /**
     * GetSellerAllowedCategory
     *
     * @return array $category;
     */
    public function getSellerAllowedCategory()
    {
        $sellerId = $this->coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        $seller = $this->sellerFactory->create()
            ->getCollection()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('store_id', 0)
            ->setPageSize(1)
            ->getFirstItem();
        $category = [];
        if ($seller->getEntityId()) {
            $category = $seller->getAllowedCategories() ? explode(',', $seller->getAllowedCategories()) :[];
        }
        return $category;
    }

    /**
     * Get Flagship Category
     *
     * @return array
     */
    public function getFlagshipCategory()
    {
        $flagshipCategory = $this->_scopeConfig->getValue(
            'seller_flagship/general/categories',
            ScopeInterface::SCOPE_STORE
        );
        if ($flagshipCategory) {
            return explode(',', $flagshipCategory);
        }
        return [];
    }

    public function getCategoriesTree()
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSort('position','asc');

        $categoryById = [
            CategoryModel::TREE_ROOT_ID => [
                'value'    => CategoryModel::TREE_ROOT_ID,
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
