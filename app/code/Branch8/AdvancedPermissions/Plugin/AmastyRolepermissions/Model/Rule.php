<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\AmastyRolepermissions\Model;

use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Categories;
use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Products;
use Amasty\Rolepermissions\Model\Authorization\GetCurrentUserInterface;
use Amasty\Rolepermissions\Model\ResourceModel\Product\Collection\ResourceAdapter as ProductCollectionResourceAdapter;
use Amasty\Rolepermissions\Model\State\NewProductSavingFlag;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\State;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\Rolepermissions\Model\Rule as AmRoleRuleModel;
use Webkul\Marketplace\Model\ProductFactory as SellerProductFactory;

/**
 *
 */
class Rule
{
    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CollectionFactory
     */
    protected $_productFactory;

    /**
     * @var NewProductSavingFlag $sellerProductFactory
     */
    protected $sellerProductFactory;

    /**
     * @var NewProductSavingFlag
     */
    private $newProductSavingFlag;

    private bool $isJoinCalled = false;
    /**
     * @var State
     */
    private State $state;
    /**
     * @var ProductCollectionResourceAdapter
     */
    private ProductCollectionResourceAdapter $productCollectionResourceAdapter;
    private GetCurrentUserInterface $getCurrentUser;

    /**
     * @param CollectionFactory $productFactory
     * @param NewProductSavingFlag $newProductSavingFlag
     * @param SellerProductFactory $sellerProductFactory
     * @param ProductCollectionResourceAdapter $productCollectionResourceAdapter
     * @param GetCurrentUserInterface $getCurrentUser
     * @param State $state
     */
    public function __construct(
        CollectionFactory $productFactory,
        NewProductSavingFlag $newProductSavingFlag,
        SellerProductFactory $sellerProductFactory,
        ProductCollectionResourceAdapter $productCollectionResourceAdapter,
        GetCurrentUserInterface $getCurrentUser,
        State $state

    ) {
        $this->getCurrentUser = $getCurrentUser;
        $this->state = $state;
        $this->_productFactory = $productFactory;
        $this->newProductSavingFlag = $newProductSavingFlag;
        $this->sellerProductFactory = $sellerProductFactory;
        $this->productCollectionResourceAdapter = $productCollectionResourceAdapter;
    }

    /**
     * This function added to remove distinct function because it raising perfomance problem
     * @param AmRoleRuleModel $subject
     * @param callable $process
     * @param ProductCollection $collection
     * @return void
     */
    public function aroundRestrictProductCollection(AmRoleRuleModel $subject, callable $process, ProductCollection $collection)
    {
        if ($this->state->getAreaCode() !== \Magento\Framework\App\Area::AREA_ADMINHTML) {
            $process($collection);
        } else {
            if ($this->newProductSavingFlag->isSaving()) {
                return;
            }
            $ruleConditions = [];
            $userId = $this->getCurrentUser->execute()->getId();
            $collection->addAttributeToSelect('amrolepermissions_owner', 'left');
            $allowOwn = false;
            $groupOwned = false;

            switch ($subject->getProductAccessMode()) {
                case Products::MODE_ANY:
                    break;
                case Products::MODE_SELECTED:
                    if ($subject->getProducts()) {
                        $ruleConditions[] = $this->productCollectionResourceAdapter->formatProductCondition(
                            $subject->getProducts()
                        );
                    }
                    break;
                case Products::MODE_MY:
                    $allowOwn = true;
                    break;
                case Products::MODE_SCOPE:
                    $groupOwned = true;
                    break;
            }

            try {
                $fromSelect = $collection->getSelect()->getPart(Select::FROM);
            } catch (\Zend_Db_Select_Exception $e) {
                return;
            }

            if ($subject->getCategoryAccessMode() == Categories::MODE_SELECTED
                && !isset($fromSelect[ProductCollectionResourceAdapter::CATEGORY_PRODUCT_TABLE_ALIAS])
                && $subject->getData('categories')
            ) {
                $ruleConditions[] = $this->productCollectionResourceAdapter->resolveCategoryCondition(
                    $collection,
                    $subject->getData('categories')
                );
            }

            if ($subject->getScopeAccessMode()
                && !isset($fromSelect[ProductCollectionResourceAdapter::PRODUCT_WEBSITE_TABLE_ALIAS])
                && $partiallyAccessibleWebsites = $subject->getPartiallyAccessibleWebsites()
            ) {
                $ruleConditions[] = $this->productCollectionResourceAdapter->resolveWebsiteCondition(
                    $collection,
                    $partiallyAccessibleWebsites
                );
            }
            $collection->distinct(true);
            if ($ruleConditions) {
                $this->productCollectionResourceAdapter->applyRuleCondition(
                    $collection,
                    $ruleConditions,
                    (int)$userId
                );
            }
            if ($allowOwn) {
                $this->productCollectionResourceAdapter->applyOwnerCondition(
                    $collection,
                    (int)$userId
                );
            }
            if ($groupOwned) {
                $this->productCollectionResourceAdapter->applyGroupOwnerCondition(
                    $collection,
                    $this->getCurrentUser->execute()->getRole()->getRoleUsers()
                );
            }
        }
       // $collection->getSelect()->distinct();
    }

    /**
     * @param AmRoleRuleModel $subject
     * @param $result
     * @param ProductCollection $collection
     * @return void
     */
    public function afterRestrictProductCollection(AmRoleRuleModel $subject, $result, ProductCollection $collection): void
    {
        /**
         * To receive correct product ID, there shouldn't be
         * filters applied to the collection on a new product save
         *
         * @see \Magento\CatalogInventory\Model\Stock\StockItemRepository::save
         */
        if ($this->newProductSavingFlag->isSaving() || $this->isJoinCalled) {
            return;
        }

        switch ($subject->getSellerAccessMode()) {
            case Products::MODE_ANY:
                break;
            case Products::MODE_SELECTED:
                if ($subject->getSellers()) {
                    $this->isJoinCalled = true;
                    $sellerProduct = $subject->getResource()->getTable('marketplace_product');
                    $collection->getSelect()->join(
                        $sellerProduct.' as mp',
                        'mp.mage_pro_row_id = e.row_id',
                        ['seller_id']
                    )->where("seller_id IN (?)", $subject->getSellers());
                }
                break;
        }
    }

    public function afterGetAllowedProductIds(AmRoleRuleModel $subject, $result)
    {
        if (!$result) {
            switch ($subject->getSellerAccessMode()) {
                case Products::MODE_ANY:
                    return false;
                case Products::MODE_SELECTED:
                    if ($subject->getSellers()) {
                        $this->isJoinCalled = true;
                        $collection = $this->_productFactory->create();
                        $sellerProduct = $subject->getResource()->getTable('marketplace_product');
                        $collection->getSelect()->join(
                            $sellerProduct . ' as mp',
                            'mp.mage_pro_row_id = e.row_id',
                            ['seller_id']
                        )->where("seller_id IN (?)", $subject->getSellers());
                        $collection->getSelect()->distinct();
                        return $collection->getColumnValues('entity_id');
                    }
                    break;
            }
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return bool
     */
    public function afterCheckProductPermissions(AmRoleRuleModel $subject, $result, $product)
    {
        if ($result) {
            if ($subject->getSellerAccessMode() == Products::MODE_SELECTED) {
                if ($subject->getSellers()) {
                    $collection = $this->sellerProductFactory->create()->getCollection();
                    $collection->addFieldToFilter('seller_id', ['in' => $subject->getSellers()]);
                    $collection->addFieldToFilter('mage_pro_row_id', $product->getRowId());
                    $sellerProduct = $collection->getFirstItem();
                    if (!$sellerProduct->getId()) {
                        return false;
                    }
                }

            }
        }

        return $result;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     *
     * @return bool
     */
    public function aroundCheckProductOwner(AmRoleRuleModel $subject, callable $proceed, $product)
    {
        return true;
    }
}
