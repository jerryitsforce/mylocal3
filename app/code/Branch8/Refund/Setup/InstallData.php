<?php

namespace Branch8\Refund\Setup;

use Branch8\Refund\Helper\RefundOperation;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class InstallData implements InstallDataInterface
{
    const REFUND_STATUS_LABEL = 'Refunded';

    /** @var \Magento\Sales\Model\Order\StatusFactory $statusFactory */
    protected $statusFactory;

    /** @var \Magento\Sales\Model\ResourceModel\Order\StatusFactory $statusResourceFactory */
    protected $statusResourceFactory;

    public function __construct(
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->addRefundOrderStatus();
    }

    protected function addRefundOrderStatus()
    {
        $statusResource = $this->statusResourceFactory->create();
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => RefundOperation::REFUND_STATUS_COMPLETE_CODE,
            'label' => self::REFUND_STATUS_LABEL,
        ]);
        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $exception) {
            return;
        }
        $status->assignState(RefundOperation::REFUND_STATE_COMPLETE_CODE, true, true);
    }
}
