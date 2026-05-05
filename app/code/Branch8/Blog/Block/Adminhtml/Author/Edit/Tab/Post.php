<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Adminhtml\Author\Edit\Tab;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Helper\Data;
use Magento\Framework\Registry;
use Branch8\Blog\Model\PostFactory;
use Branch8\Blog\Model\ResourceModel\Post\Collection;
use Branch8\Blog\Model\ResourceModel\Post\CollectionFactory;

/**
 * Class Post
 * @package Branch8\Blog\Block\Adminhtml\Tag\Edit\Tab
 */
class Post extends Extended implements TabInterface
{
    /**
     * Post collection factory
     *
     * @var CollectionFactory
     */
    public $postCollectionFactory;

    /**
     * Registry
     *
     * @var Registry
     */
    public $coreRegistry;

    /**
     * Post factory
     *
     * @var PostFactory
     */
    public $postFactory;

    /**
     * Post constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param Data $backendHelper
     * @param PostFactory $postFactory
     * @param CollectionFactory $postCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        Data $backendHelper,
        PostFactory $postFactory,
        CollectionFactory $postCollectionFactory,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->postFactory = $postFactory;
        $this->postCollectionFactory = $postCollectionFactory;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Set grid params
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('post_grid');
        $this->setDefaultSort('position');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareCollection()
    {
        /** @var Collection $collection */
        $collection = $this->postCollectionFactory->create();
        $collection->addFieldToFilter('author_id', $this->getAuthor()->getId());

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @return $this
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn('post_id', [
            'header' => __('ID'),
            'sortable' => true,
            'index' => 'post_id',
            'type' => 'number',
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id'
        ]);
        $this->addColumn('title', [
            'header' => __('Name'),
            'index' => 'name',
            'header_css_class' => 'col-name',
            'column_css_class' => 'col-name'
        ]);
        $this->addColumn('publish_date', [
            'header' => __('Published'),
            'index' => 'publish_date',
            'header_css_class' => 'col-name',
            'column_css_class' => 'col-name'
        ]);

        return $this;
    }

    /**
     * @param \Branch8\Blog\Model\Post|Object $item
     *
     * @return string
     */
    public function getRowUrl($item)
    {
        return $this->getUrl('branch8_blog/post/edit', ['id' => $item->getId()]);
    }

    /**
     * get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/*/postsgrid', ['id' => $this->getAuthor()->getId()]);
    }

    /**
     * @return \Branch8\Blog\Model\Author
     */
    public function getAuthor()
    {
        return $this->coreRegistry->registry('branch8_blog_author');
    }

    /**
     * @return string
     */
    public function getTabLabel()
    {
        return __('Posts');
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('branch8_blog/author/posts', ['_current' => true]);
    }

    /**
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }
}
