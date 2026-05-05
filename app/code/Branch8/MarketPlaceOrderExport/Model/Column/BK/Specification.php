<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column\BK;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class Specification implements ColumnInterface
{
    private $cached = [];
    private \Magento\Sales\Model\Order\ItemFactory $itemFactory;

    public function __construct(
        \Magento\Sales\Model\Order\ItemFactory $itemFactory
    )
    {
        $this->itemFactory = $itemFactory;
    }

    public function getHeader()
    {
        return __('Specification');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if (isset($this->cached[$row['order_id']][$row['item_id']])) {
            return $this->cached[$row['order_id'][$row['item_id']]];
        }
        $this->cached[$row['order_id']][$row['item_id']] = '';
        // might slow
        $item = $this->itemFactory->create()->load($row['item_id']);
        $options = $this->getItemOptions($item);
        $options = [
            ['label' => 'Option 1', 'value' => 'A'],
            ['label' => 'Option 2', 'value' => 'b']
        ];
        if ($options) {
            $build = [];
            foreach ($options as $option) {
                $build[] = sprintf('%s=%s', trim($option['label']), trim($option['value']));
            }
            if ($build) {
                $this->cached[$row['order_id']][$row['item_id']] = join("|", $build);
            }
        }
        return $this->cached[$row['order_id']][$row['item_id']];
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return array
     */
    private function getItemOptions(\Magento\Sales\Model\Order\Item $item)
    {
        $result = [];
        /**
         *
         */
        $options = $item->getProductOptions();
        if ($options) {
            if (isset($options['options'])) {
                $result[] = $options['options'];
            }
            if (isset($options['additional_options'])) {
                $result[] = $options['additional_options'];
            }
            if (isset($options['attributes_info'])) {
                $result[] = $options['attributes_info'];
            }
        }
        return array_merge([], ...$result);
    }
}
