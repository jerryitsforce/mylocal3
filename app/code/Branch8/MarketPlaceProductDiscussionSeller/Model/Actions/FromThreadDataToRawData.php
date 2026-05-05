<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionSeller\Model\Actions;

use Branch8\MarketPlaceProductDiscussion\Api\Data\MessageInterface;
use Branch8\MarketPlaceProductDiscussion\Model\Actions\AbstractFromThreadDataToRawData;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ImageBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class FromThreadDataToRawData extends AbstractFromThreadDataToRawData
{
    private MessageRawConverter $messageRawConverter;

    /**
     * @param TimezoneInterface $timezone
     * @param ProductRepositoryInterface $productRepository
     * @param ImageBuilder $imageBuilder
     * @param MessageRawConverter $messageRawConverter
     */
    public function __construct(
        TimezoneInterface          $timezone,
        ProductRepositoryInterface $productRepository,
        ImageBuilder               $imageBuilder,
        MessageRawConverter        $messageRawConverter
    )
    {
        parent::__construct($timezone, $productRepository, $imageBuilder);
        $this->messageRawConverter = $messageRawConverter;
    }

    /**
     * @param Thread $thread
     * @return array
     */
    public function convert(Thread $thread)
    {
        $messages = $this->getMessages($thread);
        $product = $this->getProduct($thread->getProductId());
        $info = [
            'id' => $thread->getId(),
            'title' => $thread->getTitle(),
            'content' => $thread->getContent(),
            'created_at' => $this->formatDate($thread->getCreatedAt()),
            'messages' => $messages,
            'customer' => $thread->getData('author_name'),
            'status' => $thread->getData('status'),
            'has_messages' => count($messages) > 0
        ];
        if ($product) {
            $info['product'] = [
                'shortDescription' => $product->getData('short_description'),
                'name' => $product->getName(),
                'url' => $product->getProductUrl(),
                'sku' => $product->getSku(),
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
        /**
         * @var $messages \Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message\Collection
         */
        $collection = $thread->getMessages(1, 10)
            ->addFieldToFilter('author_type', MessageInterface::AUTHOR_TYPE_SELLER)
            ->setPageSize(1)->setCurPage(1);
        foreach ($collection as $message) {
            $messages[] = $this->messageRawConverter->convert($message);
        }
        return $messages;
    }
}

