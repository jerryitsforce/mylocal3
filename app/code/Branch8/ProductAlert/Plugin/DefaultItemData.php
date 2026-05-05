<?php

namespace Branch8\ProductAlert\Plugin;

use Magento\Checkout\CustomerData\AbstractItem;
use Magento\Quote\Model\Quote\Item;

class DefaultItemData
{
    /**
     * @var \Branch8\ProductAlert\Helper\Data
     */
    protected $data;

    /**
     * @param \Branch8\ProductAlert\Helper\Data $data
     */
    public function __construct(
        \Branch8\ProductAlert\Helper\Data $data
    ) {
        $this->data = $data;
    }

    /**
     * @param AbstractItem $subject
     * @param $result
     * @param Item $item
     */
    public function afterGetItemData(AbstractItem $subject, $result, Item $item)
    {
        $data = [];
        if ($this->data->getEighteenProducts($result['product_id'])) {
            $data= [
                'eighteen_product' => true,
                'image_src' => $this->data->getProductAlertImage()
            ];
        } else {
            $data['eighteen_product'] = false;
        }
        return \array_merge(
            $data,
            $result
        );
    }
}
