<?php

namespace Branch8\MarketPlaceOrderExport\Model\Column;

use Branch8\MarketPlaceOrderExport\Model\ColumnInterface;

class OptionName implements ColumnInterface
{
    private $cached = [];

    /**
     * Serializer interface instance.
     *
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $serializer;

    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ){
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\Serialize\Serializer\Json::class);
    }

    public function getHeader()
    {
        return __('Option Name');
    }

    /**
     * @param array $row
     * @return int|mixed|string
     */
    public function processColumnData(array $row = [])
    {
        if ($row['item_type'] !== 'item') {
            return '';
        }

        $key = sprintf("%s_%s", $row['order_id'], $row['item_id']);
        if (isset($this->cached[$key])) {
            return $this->cached[$key];
        }
        $this->cached[$key] = '';
        $options = $this->getItemOptions($row['product_options']);
        if ($options) {
            $build = [];
            foreach ($options as $option) {
                $build[] = $option['value'];
            }
            if ($build) {
                $this->cached[$key] = join(",", $build);
            }
        }
        return $this->cached[$key];
    }

    /**
     * @param $options
     * @return array
     */
    private function getItemOptions($options)
    {
        $result = [];
        if (is_string($options)) {
            $options = $this->serializer->unserialize($options);
        }
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
