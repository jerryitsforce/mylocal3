<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionCustomer\Model\Actions;

use Branch8\MarketPlaceProductDiscussion\Model\Actions\AbstractFromThreadDataToRawData;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;

class FromThreadDataToRawData extends AbstractFromThreadDataToRawData
{
    /**
     * @param Thread $thread
     * @param int $customerId
     * @return array
     */
    public function convert(Thread $thread, int $customerId)
    {
        $messages = $this->getMessages($thread);
        $product = $this->getProduct($thread->getProductId());
        $info = [
            'id' => $thread->getId(),
            'title' => $thread->getTitle(),
            'is_my_thread' => (int)$thread->getAuthorId() === $customerId,
            'product_spec' => $thread->getProductSpec(),
            'content' => $thread->getContent(),
            'created_at' => $this->formatDate($thread->getCreatedAt()),
            'messages' => $messages,
            'has_messages' => count($messages) > 0
        ];
        if ($product) {
            $info['product'] = [
                'shortDescription' => $product->getData('short_description'),
                'name' => $product->getName(),
                'url' => $product->getProductUrl(),
                'imageUrl' => $this->imageBuilder->create($product, 'product_small_image')->getImageUrl()
            ];
        }
        return $info;
    }

    /**
     * @param Thread $thread
     * @return array
     */
    private function getMessages(Thread $thread)
    {
        $messages = [];
        foreach ($thread->getMessages(1, 10) as $message) {
            $messages[] = [
                'title' => $message->getMessage(),
                'message' => $message->getMessage(),
                'created_at' => $this->formatDate($message->getCreatedAt()),
            ];
        }
        return $messages;
    }
}

