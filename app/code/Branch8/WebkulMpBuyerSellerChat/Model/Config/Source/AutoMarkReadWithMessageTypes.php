<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Config\Source;

use Branch8\WebkulMpBuyerSellerChat\Model\MessageType;

class AutoMarkReadWithMessageTypes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => MessageType::TEXT, 'label' => __('Text')],
            ['value' => MessageType::HTML, 'label' => __('Html')],
            ['value' => MessageType::IMAGE, 'label' => __('Image')],
            ['value' => MessageType::VIDEO, 'label' => __('Video')],
            ['value' => MessageType::FILE, 'label' => __('File')],
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            MessageType::TEXT => __('Text'),
            MessageType::HTML => __('Html'),
            MessageType::IMAGE => __('Image'),
            MessageType::VIDEO => __('Video'),
            MessageType::FILE => __('File'),
        ];
    }
}
