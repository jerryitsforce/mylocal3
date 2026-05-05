<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Post;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Helper\Image;
use Branch8\Blog\Model\ResourceModel\Post\Collection;

/**
 * Class RelatedPost
 * @package Branch8\Blog\Block\Post
 */
class RelatedPost extends Template
{
    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var Collection
     */
    protected $_relatedPosts;

    /**
     * @var int
     */
    protected $_limitPost;

    /**
     * RelatedPost constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Data $helperData
     * @param array $data
     *
     * @throws NoSuchEntityException
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $helperData,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->helperData    = $helperData;

        parent::__construct($context, $data);

        $this->setTabTitle();
    }

    /**
     * Get current product id
     *
     * @return null|int
     */
    public function getProductId()
    {
        $product = $this->_coreRegistry->registry('product');

        return $product ? $product->getId() : null;
    }

    /**
     * @return Collection
     * @throws NoSuchEntityException
     */
    public function getRelatedPostList()
    {
        if ($this->_relatedPosts == null) {
            /** @var Collection $collection */
            $collection = $this->helperData->getPostList();
            $collection->getSelect()
                ->join(
                    [
                        'related' => $collection->getTable('branch8_blog_post_product')
                    ],
                    'related.post_id=main_table.post_id AND related.entity_id=' . $this->getProductId()
                )
                ->limit($this->getLimitPosts());

            $this->_relatedPosts = $collection;
        }

        return $this->_relatedPosts;
    }

    /**
     * @return int|mixed
     */
    public function getLimitPosts()
    {
        if (!$this->_limitPost) {
            $this->_limitPost = (int) $this->helperData->getBlogConfig('product_post/product_detail/post_limit') ?: 1;
        }

        return $this->_limitPost;
    }

    /**
     * Set tab title
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function setTabTitle()
    {
        $relatedSize = min($this->getRelatedPostList()->getSize(), $this->getLimitPosts());

        $title = $relatedSize
            ? __('Related Posts %1', '(' . $relatedSize . ')')
            : __('Related Posts');
        if ($this->helperData->isEnabled()) {
            $this->setTitle($title);
        }
    }

    /**
     * @return bool
     */
    public function isEnabledBlog()
    {
        return $this->helperData->isEnabled();
    }

    /**
     * @return bool
     */
    public function getRelatedMode()
    {
        return (int) $this->helperData->getPostViewPageConfig('related_mode') === 1;
    }

    /**
     * Resize Image Function
     *
     * @param $image
     * @param null $size
     * @param string $type
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function resizeImage($image, $size = null, $type = Image::TEMPLATE_MEDIA_TYPE_POST)
    {
        if (!$image) {
            return $this->getDefaultImageUrl();
        }

        return $this->helperData->getImageHelper()->resizeImage($image, $size, $type);
    }

    /**
     * get default image url
     */
    public function getDefaultImageUrl()
    {
        return $this->getViewFileUrl('Branch8_Blog::media/images/logo-default.png');
    }
}
