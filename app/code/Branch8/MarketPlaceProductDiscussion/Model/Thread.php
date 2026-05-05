<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Magento\Framework\Model\AbstractModel;
use Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterface;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Message\CollectionFactory as MessageCollectionFactory;

/**
 * @api
 * @method ThreadInterface setContent(string $content)
 * @method ThreadInterface setTitle(string $title)
 * @method ThreadInterface setThreadType(string $threadType)
 * @method ThreadInterface setAuthor(string $author)
 * @method ThreadInterface setAuthorId(int $authorId)
 * @method ThreadInterface setStatus(string $status)
 * @method string getContent()
 * @method string getTitle()
 * @method string getThreadType()
 * @method string getAuthor()
 * @method int getAuthorId()
 * @method string getStatus()
 */
class Thread extends AbstractModel implements ThreadInterface
{
    private $messageCollectionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param MessageCollectionFactory $messageCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        MessageCollectionFactory                                $messageCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
    )
    {
        $this->messageCollectionFactory = $messageCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection);
    }

    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(
            \Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread::class
        );
    }

    /**
     * @return int
     */
    public function getProductId(): int
    {
        return (int)$this->getData('product_id');
    }

    /**
     * @param int $productId
     * @return ThreadInterface
     */
    public function setProductId(int $productId): ThreadInterface
    {
        return $this->setData('product_id', $productId);
    }

    /**
     * @param int $page
     * @param $pageSize
     * @return ResourceModel\Message\Collection
     */
    public function getMessages(int $page = 1, $pageSize = 10)
    {
        $messages = $this->messageCollectionFactory->create();
        $messages->addFieldToFilter('thread_id', (int)$this->getId());
        $messages->setPageSize($pageSize);
        $messages->setCurPage($page);
        return $messages;
    }
}
