<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Category;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\ResourceModel\Post\Collection;

/**
 * Class Listpost
 * @package Branch8\Blog\Block\Category
 */
class Listpost extends \Branch8\Blog\Block\Listpost
{
    /**
     * @var string
     */
    protected $_category;

    /**
     * Override this function to apply collection for each type
     *
     * @return Collection|null
     * @throws NoSuchEntityException
     */
    protected function getCollection()
    {
        if ($category = $this->getBlogObject()) {
            return $this->helperData->getPostCollection(Data::TYPE_CATEGORY, $category->getId());
        }

        return null;
    }

    /**
     * @return mixed
     */
    protected function getBlogObject()
    {
        if (!$this->_category) {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $category = $this->helperData->getObjectByParam($id, null, Data::TYPE_CATEGORY);
                if ($category && $category->getId()) {
                    $this->_category = $category;
                }
            }
        }

        return $this->_category;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs')) {
            $category = $this->getBlogObject();
            $categoryName = preg_replace('/[^A-Za-z0-9\-]/', ' ', $category->getName());
            if ($category) {
                $breadcrumbs->addCrumb($category->getUrlKey(), [
                    'label' => __($categoryName),
                    'title' => __($categoryName)
                ]);
            }
        }
    }

    /**
     * @param bool $meta
     *
     * @return array|Phrase|string
     */
    public function getBlogTitle($meta = false)
    {
        $blogTitle = parent::getBlogTitle($meta);
        $category  = $this->getBlogObject();
        if (!$category) {
            return $blogTitle;
        }

        if ($meta) {
            if ($category->getMetaTitle()) {
                array_push($blogTitle, $category->getMetaTitle());
            } else {
                array_push($blogTitle, ucfirst($category->getName()));
            }

            return $blogTitle;
        }

        return ucfirst($category->getName());
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->getBlogObject();
    }
}
