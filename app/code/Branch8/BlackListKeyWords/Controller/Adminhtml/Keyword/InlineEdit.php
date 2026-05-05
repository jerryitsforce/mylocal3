<?php
declare(strict_types=1);

namespace Branch8\BlackListKeyWords\Controller\Adminhtml\Keyword;

use Branch8\BlackListKeyWords\Model\Keyword as KeywordModel;
use Branch8\BlackListKeyWords\Model\KeywordFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Controller for the 'blacklist_words/keyword/InlineEdit' URL route.
 */
class InlineEdit extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_BlackListKeyWords::management';

    /**
     * JSON Factory
     *
     * @var JsonFactory
     */
    public $jsonFactory;

    /**
     * @var KeywordFactory
     */
    public $keywordFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param KeywordFactory $keywordFactory
     */
    public function __construct(
        Context       $context,
        JsonFactory   $jsonFactory,
        KeywordFactory $keywordFactory
    )
    {
        $this->jsonFactory = $jsonFactory;
        $this->keywordFactory = $keywordFactory;

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
        $id = !empty($key) ? (int)$key[0] : '';
        /**
         * @var $keyword KeywordModel
         */
        $keyword = $this->keywordFactory->create()->load($id);
        try {
            $data = $authorItems[$id];
            $keyword->addData($data);
            $keyword->save();
        } catch (LocalizedException $e) {
            $messages[] = $this->getErrorWithPostId($keyword, $e->getMessage());
            $error = true;
        } catch (\RuntimeException $e) {
            $messages[] = $this->getErrorWithPostId($keyword, $e->getMessage());
            $error = true;
        } catch (\Exception $e) {
            $messages[] = $this->getErrorWithPostId(
                $keyword,
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
