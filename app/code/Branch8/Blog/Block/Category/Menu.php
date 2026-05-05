<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Category;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\Blog\Api\Data\CategoryInterface;
use Branch8\Blog\Helper\Data as HelperData;
use Branch8\Blog\Model\Category;
use Branch8\Blog\Model\CategoryFactory;
use Branch8\Blog\Model\ResourceModel\Category\Collection;
use Branch8\Blog\Model\ResourceModel\Category\CollectionFactory;

/**
 * Class Widget
 * @package Branch8\Blog\Block\Category
 */
class Menu extends Template
{
    /**
     * @var CollectionFactory
     */
    protected $categoryCollection;

    /**
     * @var CategoryFactory
     */
    protected $category;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Menu constructor.
     *
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param CategoryFactory $categoryFactory
     * @param HelperData $helperData
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        CategoryFactory $categoryFactory,
        HelperData $helperData,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->categoryCollection = $collectionFactory;
        $this->category           = $categoryFactory;
        $this->helper             = $helperData;
        $this->storeManager       = $storeManager;

        parent::__construct($context, $data);
    }

    /**
     * @param $id
     *
     * @return CategoryInterface[]
     * @throws NoSuchEntityException
     */
    public function getChildCategory($id)
    {
        $collection = $this->categoryCollection->create()->addAttributeToFilter('parent_id', $id)
            ->addAttributeToFilter('enabled', '1');
        $this->helper->addStoreFilter($collection, $this->storeManager->getStore()->getId());

        return $collection->getItems();
    }

    /**
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getCollections()
    {
        $collection = $this->categoryCollection->create()
            ->addAttributeToFilter('level', '1')->addAttributeToFilter('enabled', '1')->setOrder('position','ASC');

        return $this->helper->addStoreFilter($collection, $this->storeManager->getStore()->getId());
    }

    /**
     * @param Category $parentCategory
     *
     * @return string
     */
    public function getMenuHtml($parentCategory)
    {
        $categoryUrl = $this->helper->getBlogUrl('category/' . $parentCategory->getUrlKey());
        $html = '<li class="level' . $parentCategory->getLevel()
            . ' category-item ui-menu-item" role="presentation">'
            . '<a href="' . $categoryUrl . '" class="ui-corner-all" tabindex="-1" role="menuitem">'
            . '<span>' . $parentCategory->getName() . '</span></a>';

        $childCategorys = $this->getChildCategory($parentCategory->getId());

        if (count($childCategorys) > 0) {
            $html .= '<ul class="level' . $parentCategory->getLevel() . ' submenu ui-menu ui-widget'
                . ' ui-widget-content ui-corner-all"'
                . ' role="menu" aria-expanded="false" style="display: none; top: 47px; left: -0.15625px;"'
                . ' aria-hidden="true">';

            /** @var Category $childCategory */
            foreach ($childCategorys as $childCategory) {
                $html .= $this->getMenuHtml($childCategory);
            }
            $html .= '</ul>';
        }
        $html .= '</li>';

        return $html;
    }

    /**
     * @return \Magento\Framework\Phrase|mixed
     * @throws NoSuchEntityException
     */
    public function getBlogHomePageTitle()
    {
        return $this->helper->getBlogConfig('display/name', $this->helper->getCurrentStoreId()) ?: __('Blog');
    }

    /**
     * @return string
     */
    public function getBlogHomeUrl()
    {
        return $this->helper->getBlogUrl('');
    }


    public function getBlogUrlByUrlKey($urlKey)
    {
        return $this->helper->getBlogUrl('category/' . $urlKey);
    }

    public function getChildDataCate($category)
    {
        $childCategorys    = $this->getChildCategory($category->getId());
        $childCategoryData = [];
        if (count($childCategorys) > 0) {
            foreach ($childCategorys as $childCategory) {
                $childCategoryUrl = $this->getBlogUrlByUrlKey($childCategory->getUrlKey());
                array_push($childCategoryData,
                    [
                        "name"             => $childCategory->getName(),
                        "id"               => "mg-blog" . $childCategory->getId(),
                        "url"              => $childCategoryUrl,
                        "image"            => false,
                        "has_active"       => false,
                        "is_active"        => false,
                        "is_category"      => true,
                        "is_parent_active" => true,
                        "position"         => null,
                        "path"             => "1/2/38",
                        "childData"        => $this->getChildDataCate($childCategory)
                    ]
                );
            }
            return $childCategoryData;
        } else {
            return [];
        }
    }
}
