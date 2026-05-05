<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

class Batch extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_DONE = 'done';
    const BATCH_SIZE = 80000;
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Batch::class
        );
    }

    /**
     * @return array
     */
    public function getOrderIds()
    {
        $orderIds = $this->getData('order_ids');
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
     * @return $this
     */
    public function setOrderIds(array $orderIds)
    {
        $this->setData('order_ids', $orderIds);
        return $this;
    }

    /**
     * @return string
     */
    public function getExecuteAt()
    {
        return (string)$this->getData('execute_at');
    }

    /**
     * @param string $executeAt
     * @return $this
     */
    public function setExecuteAt(string $executeAt)
    {
        $this->setData('execute_at', $executeAt);
        return $this;
    }

    /**
     * @param string $filePath
     * @return $this|Profile
     */
    public function setFilePath(string $filePath)
    {
        $this->setData('file_path', $filePath);
        return $this;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return (string)$this->getData('file_path');
    }

    /**
     * @param string $phpId
     * @return Batch
     */
    public function setPhpId(string $phpId)
    {
        $this->setData('phpid', $phpId);
        return $this;
    }

    /**
     * @return string
     */
    public function getPhpId()
    {
        return (string)$this->getData('phpid');
    }

    /**
     * @return $this
     */
    public function complete()
    {
        $this->setData('batch_status', self::STATUS_DONE);
        $this->setData('complete_at', date('Y-m-d H:i:s'));
        return $this;
    }

    /**
     * @return $this
     */
    public function process()
    {
        $this->setData('batch_status', self::STATUS_PROCESSING);
        $this->setData('executed_at', date('Y-m-d H:i:s'));
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
}
