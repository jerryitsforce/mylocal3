<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\Category;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Registry;
use Magento\Framework\View\LayoutFactory;
use Branch8\Blog\Block\Adminhtml\Category\Tree;
use Branch8\Blog\Controller\Adminhtml\Category;
use Branch8\Blog\Model\CategoryFactory;

/**
 * Class SuggestCategories
 * @package Branch8\Blog\Controller\Adminhtml\Category
 */
class SuggestCategories extends Category
{
    /**
     * Json result factory
     *
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * Layout factory
     *
     * @var LayoutFactory
     */
    public $layoutFactory;

    /**
     * SuggestCategories constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param CategoryFactory $categoryFactory
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        CategoryFactory $categoryFactory,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory     = $layoutFactory;

        parent::__construct($context, $coreRegistry, $categoryFactory);
    }

    /**
     * Blog Category list suggestion based on already entered symbols
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Tree $treeBlock */
        $treeBlock = $this->layoutFactory->create()->createBlock(Tree::class);
        $data      = $treeBlock->getSuggestedCategoriesJson($this->getRequest()->getParam('label_part'));

        /** @var Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        $resultJson->setJsonData($data);

        return $resultJson;
    }
}
