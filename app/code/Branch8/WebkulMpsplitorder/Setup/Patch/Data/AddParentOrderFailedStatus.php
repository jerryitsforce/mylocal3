<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Setup\Patch\Data;

use Branch8\HotaiCore\Helper\CreateTicketAttributeSet as CreateTicketAttributeSetHelper;
use Branch8\HotaiCore\Model\Ticket\AttributeCodes as TicketAttributeCodes;

use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddParentOrderFailedStatus implements DataPatchInterface
{

    /** @var CreateTicketAttributeSetHelper */
    protected $statusFactory;

    private \Magento\Sales\Model\ResourceModel\Order\Status $statusResource;

    /**
     * @param \Magento\Sales\Model\Order\StatusFactory $statusFactory
     * @param \Magento\Sales\Model\ResourceModel\Order\Status $statusResource
     */
    public function __construct(
        \Magento\Sales\Model\Order\StatusFactory        $statusFactory,
        \Magento\Sales\Model\ResourceModel\Order\Status $statusResource
    )
    {
        $this->statusFactory = $statusFactory;
        $this->statusResource = $statusResource;
    }

    public function apply()
    {
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => 'parent_order_failed',
            'label' => 'Parent Order Failed'
        ]);

        try {
            $this->statusResource->save($status);
            $status->assignState('pending', false, false);
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}
