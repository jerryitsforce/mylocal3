<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

use Branch8\MarketPlaceOrderExportRabbitMQ\Api\Data\ProfileInterface;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Batch\Collection;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Batch\CollectionFactory;
use Magento\Framework\DataObject;
use function Symfony\Component\String\u;

class Profile extends \Magento\Framework\Model\AbstractModel implements ProfileInterface
{
    const TYPE_ADMIN = 'admin';
    const TYPE_SELLER = 'seller';
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE = 'done';
    const BATCH_SIZE = 80000;

    private $batchCollectionFactory;
    /**
     * @var $batchCollection Collection
     */
    private $batchCollection = null;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CollectionFactory $batchCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        CollectionFactory                                       $batchCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
    )
    {

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->batchCollectionFactory = $batchCollectionFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile::class
        );
    }

    /**
     * @return array|int|mixed|null
     */
    public function getProfileId()
    {
        return (int)$this->getData(ProfileInterface::ID);
    }

    /**
     * @return string
     */
    public function getProfileType()
    {
        return (string)$this->getData(self::PROFILE_TYPE);
    }

    /**
     * @param string $profileType
     * @return $this|Profile
     */
    public function setProfileType(string $profileType)
    {
        $this->setData(self::PROFILE_TYPE, $profileType);
        return $this;
    }

    /**
     * @return array
     */
    public function getOrderIds()
    {
        $orderIds = $this->getData(self::ORDER_IDS);
        if (empty($orderIds)) {
            return [];
        }
        if (is_string($orderIds)) {
            return explode(',', $orderIds);
        }
        return $orderIds;
    }

    /**
     * @param array $orderIds
     * @return $this|Profile
     */
    public function setOrderIds(array $orderIds)
    {
        $this->setData(self::ORDER_IDS, $orderIds);
        return $this;
    }

    /**
     * @return string
     */
    public function getMeta()
    {
        return (string)$this->getData(self::META);
    }

    /**
     * @param string $meta
     * @return $this|Profile
     */
    public function setMeta(array $meta)
    {
        $this->setData(self::META, $meta);
        return $this;
    }

    /**
     * @return string
     */
    public function getExecuteAt()
    {
        return (string)$this->getData(self::EXECUTED_AT);
    }

    /**
     * @param string $executeAt
     * @return $this|Profile
     */
    public function setExecuteAt(string $executeAt)
    {
        $this->setData(self::EXECUTED_AT, $executeAt);
        return $this;
    }

    /**
     * @param string $filePath
     * @return $this|Profile
     */
    public function setFilePath(string $filePath)
    {
        $this->setData(self::FILE_PATH, $filePath);
        return $this;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return (string)$this->getData(self::FILE_PATH);
    }

    /**
     * @return string
     */
    public function getPublishAt()
    {

        return (string)$this->getData(self::PUBLISH_AT);
    }

    /**
     * @param string $executeAt
     * @return $this|Profile
     */
    public function setPublishAt(string $executeAt)
    {
        $this->setData(self::PUBLISH_AT, $executeAt);
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverEmail()
    {
        return (string)$this->getData(self::RECEIVER_EMAIL);
    }

    /**
     * @param string $mail
     * @return $this|Profile
     */
    public function setReceiverEmail(string $mail)
    {
        $this->setData(self::RECEIVER_EMAIL, $mail);
        return $this;
    }

    /**
     * @return string
     */
    public function getReceiverName()
    {
        return (string)$this->getData(self::RECEIVER_NAME);
    }

    /**
     * @param string $name
     * @return $this|Profile
     */
    public function setReceiverName(string $name)
    {
        $this->setData(self::RECEIVER_NAME, $name);
        return $this;
    }

    /**
     * @param int $userId
     * @return $this|mixed
     */
    public function setUserId(int $userId)
    {
        $this->setData(self::USER_ID, $userId);
        return $this;
    }

    /**
     * @return array|mixed|null
     */
    public function getUserId()
    {
        return (int)$this->getData(self::USER_ID);
    }

    /**
     * @param string $phpId
     * @return Profile
     */
    public function setPhpId(string $phpId)
    {
        $this->setData(self::PHPID, $phpId);
        return $this;
    }

    /**
     * @return string
     */
    public function getPhpId()
    {
        return (string)$this->getData(self::PHPID);
    }

    /**
     * @return $this
     */
    public function complete()
    {
        $this->setData('status', self::STATUS_DONE);
        $this->setData('complete_at', date('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * @return $this
     */
    public function process()
    {
        $this->setData('status', self::STATUS_PROCESSING);
        //$this->setData(self::EXECUTED_AT, date('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * @param string $status
     * @return $this|Profile
     */
    public function setStatus(string $status)
    {
        $this->setData('status', $status);
        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return (string)$this->getData('status');
    }

    /**
     * @return $this|void
     */
    public function createBatches()
    {
        $orderIds = $this->getOrderIds();
        if (empty($orderIds)) {
            return;
        }
        if (is_string($orderIds)) {
            $orderIds = explode(',', $orderIds);
        }
        $orderIds = array_unique($orderIds);
        $profileId = $this->getProfileId();
        $chunks = array_chunk($orderIds, self::BATCH_SIZE);
        $insertData = array_map(function ($chunk) use ($profileId) {
            return ['parent_id' => $profileId, 'order_ids' => implode(',', $chunk)];
        }, $chunks);
        $connection = $this->getResource()->getConnection();
        $connection->insertOnDuplicate('branch8_order_export_profile_batches', $insertData);
        return $this;
    }

    /**
     * @return Collection
     */
    public function getBatches()
    {
        /**
         * @var
         */
        if ($this->batchCollection === null) {
            $this->batchCollection = $this->batchCollectionFactory->create();
            $this->batchCollection->addFieldToFilter('parent_id', $this->getProfileId());
        }
        return $this->batchCollection;
    }
}
