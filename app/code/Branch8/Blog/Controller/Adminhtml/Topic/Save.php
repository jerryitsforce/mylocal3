<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\Topic;

use Branch8\Blog\Helper\Image;
use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Js;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Messages;
use Magento\Framework\View\LayoutFactory;
use Branch8\Blog\Controller\Adminhtml\Topic;
use Branch8\Blog\Model\TopicFactory;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Class Save
 * @package Branch8\Blog\Controller\Adminhtml\Topic
 */
class Save extends Topic
{
    /**
     * JS helper
     *
     * @var Js
     */
    public $jsHelper;

    /**
     * Layout Factory
     *
     * @var LayoutFactory
     */
    public $layoutFactory;

    /**
     * Result Json Factory
     *
     * @var JsonFactory
     */
    public $resultJsonFactory;

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Js $jsHelper
     * @param LayoutFactory $layoutFactory
     * @param JsonFactory $resultJsonFactory
     * @param TopicFactory $topicFactory
     * @param Image $imageHelper
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Js $jsHelper,
        LayoutFactory $layoutFactory,
        JsonFactory $resultJsonFactory,
        TopicFactory $topicFactory,
        Image $imageHelper
    ) {
        $this->jsHelper = $jsHelper;
        $this->layoutFactory = $layoutFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->imageHelper = $imageHelper;

        parent::__construct($context, $registry, $topicFactory);
    }

    /**
     * @return $this|ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        if ($this->getRequest()->getPost('return_session_messages_only')) {
            $topic = $this->initTopic();
            $topicPostData = $this->getRequest()->getPostValue();
            $topicPostData['store_ids'] = 0;
            $topicPostData['enabled'] = 1;
            if (!$this->getRequest()->getParam('image')) {
                try {
                    $this->imageHelper->uploadImage($topicPostData, 'image', Image::TEMPLATE_MEDIA_TYPE_TOPIC, $topic->getImage());
                } catch (Exception $exception) {
                    $topicPostData['image'] = isset($topicPostData['image']['value']) ? $topicPostData['image']['value'] : '';
                }
            } else {
                $topicPostData['image'] = '';
            }

            $topic->addData($topicPostData);

            try {
                $topic->save();
                $this->messageManager->addSuccessMessage(__('You saved the topic.'));
            } catch (AlreadyExistsException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Blog', 'exceptionlog')) {
                    $this->_objectManager->get(LoggerInterface::class)->critical($e);
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Blog', 'exceptionlog')) {
                    $this->_objectManager->get(LoggerInterface::class)->critical($e);
                }
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the topic.'));
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Blog', 'exceptionlog')) {
                    $this->_objectManager->get(LoggerInterface::class)->critical($e);
                }
            }

            $hasError = (bool)$this->messageManager->getMessages()->getCountByType(
                MessageInterface::TYPE_ERROR
            );

            $topic->load($topic->getId());
            $topic->addData([
                'level' => 1,
                'entity_id' => $topic->getId(),
                'is_active' => $topic->getEnabled(),
                'parent' => 0
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
                    'category' => $topic->toArray(),
                ]
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data = $this->getRequest()->getPost('topic')) {
            /** @var \Branch8\Blog\Model\Topic $topic */
            $topic = $this->initTopic();
            if (!$this->getRequest()->getParam('image')) {
                try {
                    $this->imageHelper->uploadImage($data, 'image', Image::TEMPLATE_MEDIA_TYPE_TOPIC, $topic->getImage());
                } catch (Exception $exception) {
                    $data['image'] = isset($data['image']['value']) ? $data['image']['value'] : '';
                }
            } else {
                $data['image'] = '';
            }
            $topic->setData($data);

            if ($posts = $this->getRequest()->getPost('posts', false)) {
                $topic->setPostsData($this->jsHelper->decodeGridSerializedInput($posts));
            }

            try {
                $topic->save();

                $this->messageManager->addSuccessMessage(__('The Topic has been saved.'));
                $this->_getSession()->setData('branch8_blog_topic_data', false);

                if ($this->getRequest()->getParam('back')) {
                    $resultRedirect->setPath('branch8_blog/*/edit', ['id' => $topic->getId(), '_current' => true]);
                } else {
                    $resultRedirect->setPath('branch8_blog/*/');
                }

                return $resultRedirect;
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (RuntimeException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Topic.'));
            }

            $this->_getSession()->setData('branch8_blog_topic_data', $data);

            $resultRedirect->setPath('branch8_blog/*/edit', ['id' => $topic->getId(), '_current' => true]);

            return $resultRedirect;
        }

        $resultRedirect->setPath('branch8_blog/*/');

        return $resultRedirect;
    }
}
