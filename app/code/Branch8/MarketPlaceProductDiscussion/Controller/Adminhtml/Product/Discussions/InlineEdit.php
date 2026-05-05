<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussion\Controller\Adminhtml\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Branch8\MarketPlaceProductDiscussion\Model\ThreadFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Controller for the 'catalog/product_discussions/inlineedit' URL route.
 */
class InlineEdit extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceProductDiscussion::manage';

    /**
     * JSON Factory
     *
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * Author Factory
     *
     * @var AuthorFactory
     */
    public $thread;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ThreadFactory $postFactory
     */
    public function __construct(
        Context       $context,
        JsonFactory   $jsonFactory,
        ThreadFactory $postFactory
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->thread = $postFactory;

        parent::__construct($context);
    }

    /**
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];
        $authorItems = $this->getRequest()->getParam('items', []);
        if (!(!empty($authorItems) && $this->getRequest()->getParam('isAjax'))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }

        $key = array_keys($authorItems);
        $threadId = !empty($key) ? (int)$key[0] : '';
        /**
         * @var $thread Thread
         */
        $thread = $this->thread->create()->load($threadId);
        try {
            $data = $authorItems[$threadId];
            $thread->addData($data);
            $thread->save();
        } catch (LocalizedException $e) {
            $messages[] = $this->getErrorWithPostId($thread, $e->getMessage());
            $error = true;
        } catch (\RuntimeException $e) {
            $messages[] = $this->getErrorWithPostId($thread, $e->getMessage());
            $error = true;
        } catch (\Exception $e) {
            $messages[] = $this->getErrorWithPostId(
                $thread,
                __('Something went wrong while saving the Post.')
            );
            $error = true;
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }

    /**
     * @param Thread $author
     * @param $errorText
     * @return string
     */
    public function getErrorWithPostId(Thread $author, $errorText)
    {
        return '[Post ID: ' . $author->getId() . '] ' . $errorText;
    }
}
