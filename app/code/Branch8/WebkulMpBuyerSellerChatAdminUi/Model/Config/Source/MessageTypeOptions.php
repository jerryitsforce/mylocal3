<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       19/04/2026
 */

namespace Branch8\WebkulMpBuyerSellerChatAdminUi\Model\Config\Source;

use Branch8\WebkulMpBuyerSellerChat\Model\MessageType;
use Magento\Framework\Data\OptionSourceInterface;

class MessageTypeOptions implements OptionSourceInterface
{
    /**
     * @return void
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => '',
                'label' => '-- Select the message type --'
            ],
            [
                'value' => MessageType::TEXT,
                'label' => 'text'
            ],
            [
                'value' => MessageType::HTML,
                'label' => 'Html'
            ],
            [
                'value' => MessageType::FILE,
                'label' => 'File'
            ],
            [
                'value' => MessageType::IMAGE,
                'label' => 'Image'
            ],
            [
                'value' => MessageType::VIDEO,
                'label' => 'Video'
            ]
        ];
    }

}
