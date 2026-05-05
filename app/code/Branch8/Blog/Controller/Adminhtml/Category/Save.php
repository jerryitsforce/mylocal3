<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\Category;

use Branch8\Blog\Helper\Image;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Js;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Messages;
use Magento\Framework\View\LayoutFactory;
use Branch8\Blog\Controller\Adminhtml\Category;
use Branch8\Blog\Model\CategoryFactory;
use Psr\Log\LoggerInterface;

/**
 * Class Save
 * @package Branch8\Blog\Controller\Adminhtml\Category
 */
class Save extends Category
{
    /**
     * Result Raw Factory
     *
     * @var RawFactory
     */
    public $resultRawFactory;

    /**
     * Result Json Factory
     *
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * Layout Factory
     *
     * @var LayoutFactory
     */
    public $layoutFactory;

    /**
     * JS helper
     *
     * @var Js
     */
    public $jsHelper;

    /**
     * @var Image
     */
    protected $imageHelper;
    private LoggerInterface $logger;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param CategoryFactory $categoryFactory
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $resultJsonFactory
     * @param LayoutFactory $layoutFactory
     * @param Js $jsHelper
     * @param Image $imageHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        CategoryFactory $categoryFactory,
        RawFactory $resultRawFactory,
        JsonFactory $resultJsonFactory,
        LayoutFactory $layoutFactory,
        Js $jsHelper,
        Image $imageHelper,
        LoggerInterface $logger
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->jsHelper = $jsHelper;
        $this->imageHelper = $imageHelper;
        $this->logger = $logger;
        parent::__construct($context, $coreRegistry, $categoryFactory);
    }

    /**
     * @return Json|Redirect
     */
    public function execute()
    {
        if ($this->getRequest()->getPost('return_session_messages_only')) {
            $category = $this->initCategory();
            $categoryPostData = $this->getRequest()->getPostValue();
            $categoryPostData['store_ids'] = 0;
            $categoryPostData['enabled'] = 1;
            if (!$this->getRequest()->getParam('image')) {
                try {
                    $this->imageHelper->uploadImage($categoryPostData, 'image', Image::TEMPLATE_MEDIA_TYPE_CAT, $category->getImage());
                } catch (Exception $exception) {
                    $categoryPostData['image'] = isset($categoryPostData['image']['value']) ? $categoryPostData['image']['value'] : '';
                }
            } else {
                $categoryPostData['image'] = '';
            }

            $category->addData($categoryPostData);

            $parentId = $this->getRequest()->getParam('parent');
            if (!$parentId) {
                $parentId = CategoryModel::TREE_ROOT_ID;
            }
            $parentCategory = $this->categoryFactory->create()->load($parentId);
            $category->setPath($parentCategory->getPath());
            $category->setParentId($parentId);

            try {
                $category->save();
                $this->messageManager->addSuccessMessage(__('You saved the category.'));
            } catch (AlreadyExistsException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Blog', 'exceptionlog')) {
                    $this->logger->critical($e);
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Blog', 'exceptionlog')) {
                    $this->logger->critical($e);
                }
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the category.'));
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Blog', 'exceptionlog')) {
                    $this->logger->critical($e);
                }
            }

            $hasError = (bool)$this->messageManager->getMessages()->getCountByType(
                MessageInterface::TYPE_ERROR
            );

            $category->load($category->getId());
            $category->addData([
                'entity_id' => $category->getId(),
                'is_active' => $category->getEnabled(),
                'parent' => $category->getParentId()
            ]);

            // to obtain truncated category name
            /** @var $block Messages */
            $block = $this->layoutFactory->create()->getMessagesBlock();
            $block->setMessages($this->messageManager->getMessages(true));

            /** @var Json $resultJson */
            $resultJson = $this->resultJsonFactory->create();

            return $resultJson->setData(
                [
                    'messages' => $block->getGroupedHtml(),
                    'error' => $hasError,
                    'category' => $category->toArray(),
                ]
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data = $this->getRequest()->getPost('category')) {
            $category = $this->initCategory(false, true);
            if (!$this->getRequest()->getParam('image')) {
                try {
                    $this->imageHelper->uploadImage($data, 'image', Image::TEMPLATE_MEDIA_TYPE_CAT, $category->getImage());
                } catch (Exception $exception) {
                    $data['image'] = isset($data['image']['value']) ? $data['image']['value'] : '';
                }
            } else {
                $data['image'] = '';
            }
            if ($this->getRequest()->getParam('duplicate')) {
                unset($data['id']);
            }
            if (!$category) {
                $resultRedirect->setPath('branch8_blog/*/', ['_current' => true]);

                return $resultRedirect;
            }

            $category->addData($data);
            if ($posts = $this->getRequest()->getPost('selected_products')) {
                $posts = json_decode($posts, true);
                $category->setPostsData($posts);
            }

            if (!$category->getId()) {
                $parentId = $this->getRequest()->getParam('parent');
                if (!$parentId) {
                    $parentId = CategoryModel::TREE_ROOT_ID;
                }
                $parentCategory = $this->categoryFactory->create()->load($parentId);
                $category->setPath($parentCategory->getPath());
                $category->setParentId($parentId);
            }

            $this->_eventManager->dispatch(
                'branch8_blog_category_prepare_save',
                ['category' => $category, 'request' => $this->getRequest()]
            );

            try {
                $category->save();
                $this->messageManager->addSuccessMessage(__('You saved the Blog Category.'));
                $this->_getSession()->setData('branch8_blog_category_data', false);
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_getSession()->setData('branch8_blog_category_data', $data);
            }

            $resultRedirect->setPath('branch8_blog/*/edit', ['_current' => true, 'id' => $category->getId()]);

            return $resultRedirect;
        }

        $resultRedirect->setPath('branch8_blog/*/edit', ['_current' => true]);

        return $resultRedirect;
    }
}
