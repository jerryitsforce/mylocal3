<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */
namespace Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Api\MessageRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\Data\MessageInterface;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message as ResourceMessage;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 *
 */
class MessageRepository implements MessageRepositoryInterface
{
    private $resource;
    private $factory;
    private $collectionFactory;
    private $collectionProcessor;

    public function __construct(
        ResourceMessage $resource,
        MessageFactory $factory,
        CollectionFactory $collectionFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->factory = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function save(MessageInterface $message)
    {
        $this->resource->save($message);
        return $message;
    }

    public function getById($messageId)
    {
        $message = $this->factory->create();
        $this->resource->load($message, $messageId);

        if (!$message->getId()) {
            throw new NoSuchEntityException(__('Message not found.'));
        }

        return $message;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);
        return $collection;
    }

    public function delete(MessageInterface $message)
    {
        $this->resource->delete($message);
        return true;
    }

    public function deleteById($messageId)
    {
        return $this->delete($this->getById($messageId));
    }
}
