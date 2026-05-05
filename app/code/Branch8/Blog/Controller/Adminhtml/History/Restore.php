<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Controller\Adminhtml\History;

use Exception;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Branch8\Blog\Controller\Adminhtml\History;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\PostHistory;

/**
 * Class Restore
 * @package Branch8\Blog\Controller\Adminhtml\Restore
 */
class Restore extends History
{
    /**
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $postId = $this->getRequest()->getParam('post_id');
        $historyId = $this->getRequest()->getParam('id');
        if ($historyId) {
            try {
                $history = $this->postHistoryFactory->create()
                    ->load($historyId);
                $post = $this->postFactory->create()->load($postId);

                $data = $this->prepareData($history);

                $post->addData($data)->save();

                $this->messageManager->addSuccessMessage(__('The Post History has been restore.'));
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('Post History to restore was not found.'));
        }

        $resultRedirect->setPath('branch8_blog/post/edit', ['id' => $postId]);

        return $resultRedirect;
    }

    /**
     * @param PostHistory $history
     *
     * @return array
     */
    protected function prepareData($history)
    {
        $history->setUpdatedAt($this->date->date());
        $history->setData('categories_ids', empty($history->getCategoryIds())
            ? [] : explode(',', $history->getCategoryIds() ?? ''));
        $history->setData('tags_ids', empty($history->getTagIds()) ? [] : explode(',', $history->getTagIds() ?? ''));
        $history->setData('topics_ids', empty($history->getTopicIds()) ? [] : explode(',', $history->getTopicIds() ?? ''));
        $history->setData('products_data', empty($history->getProductIds())
            ? [] : Data::jsonDecode($history->getProductIds()));
        $data = $history->getData();
        unset(
            $data['post_id'],
            $data['history_id'],
            $data['category_ids'],
            $data['tag_ids'],
            $data['topic_ids'],
            $data['product_ids']
        );

        return $data;
    }
}
