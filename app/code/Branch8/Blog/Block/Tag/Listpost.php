<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Tag;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\ResourceModel\Post\Collection;
use Branch8\Blog\Model\TagFactory;

/**
 * Class Listpost
 * @package Branch8\Blog\Block\Tag
 */
class Listpost extends \Branch8\Blog\Block\Listpost
{
    /**
     * @var TagFactory
     */
    protected $_tag;

    /**
     * Override this function to apply collection for each type
     *
     * @return Collection
     * @throws NoSuchEntityException
     */
    protected function getCollection()
    {
        if ($tag = $this->getBlogObject()) {
            return $this->helperData->getPostCollection(Data::TYPE_TAG, $tag->getId());
        }

        return null;
    }

    /**
     * @return mixed
     */
    protected function getBlogObject()
    {
        if (!$this->_tag) {
            $id = $this->getRequest()->getParam('id');

            if ($id) {
                $tag = $this->helperData->getObjectByParam($id, null, Data::TYPE_TAG);
                if ($tag && $tag->getId()) {
                    $this->_tag = $tag;
                }
            }
        }

        return $this->_tag;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs')) {
            $tag     = $this->getBlogObject();
            $tagName = preg_replace('/[^A-Za-z0-9\-]/', ' ', $tag->getName());
            if ($tag) {
                $breadcrumbs->addCrumb($tag->getUrlKey(), [
                    'label' => __($tagName),
                    'title' => __($tagName)
                ]);
            }
        }
    }

    /**
     * @param bool $meta
     *
     * @return array
     */
    public function getBlogTitle($meta = false)
    {
        $blogTitle = parent::getBlogTitle($meta);
        $tag       = $this->getBlogObject();
        if (!$tag) {
            return $blogTitle;
        }

        if ($meta) {
            if ($tag->getMetaTitle()) {
                array_push($blogTitle, $tag->getMetaTitle());
            } else {
                array_push($blogTitle, ucfirst($tag->getName()));
            }

            return $blogTitle;
        }

        return ucfirst($tag->getName());
    }
}
