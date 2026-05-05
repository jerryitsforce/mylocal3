<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       26/03/2026
 */

namespace Branch8\OptionsWithStockAndImages\Plugin\Magento\Quote\Model\Quote\Item;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\Quote\Model\Quote\Item\ToOrderItem;
use Magento\Sales\Api\Data\OrderItemInterface;
class ToOrderItemPlugin
{
    /**
     * Convert quote item data to order item data.
     *
     * @param ToOrderItem $subject
     * @param OrderItemInterface $orderItem
     * @param AbstractItem $item
     * @param array $data
     *
     * @return OrderItemInterface
     */
    public function afterConvert(
        ToOrderItem        $subject,
        OrderItemInterface $orderItem,
        AbstractItem       $item,
        array              $data = []
    ): OrderItemInterface
    {
        $fields = [
            'combo'
        ];
        foreach ($fields as $key) {
            $orderItem->setData($key, $item->getData($key));
        }
        return $orderItem;
    }
}
