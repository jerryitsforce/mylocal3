<?php
declare(strict_types=1);

namespace Branch8\Sales\Model\Actions;

class ResyncOrdersToGrid
{
    private \Magento\Sales\Model\ResourceModel\Grid $grid;

    /**
     * @param \Magento\Sales\Model\ResourceModel\Grid $grid
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Grid $grid
    )
    {
        $this->grid = $grid;
    }

    /**
     * @param $orderIds
     * @return void
     */
    public function execute($orderIds = [])
    {
        foreach ($orderIds as $orderId) {
            try {
                $this->grid->refresh($orderId);
            } catch (\Exception $exception) {
                continue;
            }
        }
    }
}
