<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model;
class ChatStatus implements \Magento\Framework\Data\OptionSourceInterface
{
    const ONLINE = 1;

    const OFFLINE = 0;
    const BUSY = 2;

    private $options;

    const COLOR = [
        self::ONLINE => 'green',
        self::OFFLINE => 'gray',
        self::BUSY => '#e74c3c',
    ];

    /**
     * getStatusLabel
     * @param $status
     * @return \Magento\Framework\Phrase|string
     */
    public static function getStatusLabel($status)
    {
        $label = '';
        switch ($status) {
            case self::ONLINE:
                $label = __('Online');
                break;
            case self::OFFLINE:
                $label = __('Offline');
                break;
            case self::BUSY:
                $label = __('Busy');
                break;
        };
        return $label;
    }

    /**
     * getStatusHtml
     * @param $status
     * @return string
     */
    public static function getStatusHtml($status)
    {
        $html = (
        '<span class="chat status %s"
                style="width: 10px;height: 10px;
                position: absolute;
                top:0;left: 0;
                background:%s">
                </span>
                <span>%s</span>'
        );
        switch ($status) {
            case self::ONLINE:
                $html = sprintf($html, 'online',
                    self::COLOR[self::ONLINE],
                    self::getStatusLabel($status)->render()
                );
                break;
            case self::OFFLINE:
                $html = sprintf($html, 'offline',
                    self::COLOR[self::OFFLINE],
                    self::getStatusLabel($status)->render()
                );
                break;
            case self::BUSY:
                $html = sprintf($html, 'busy',
                    self::COLOR[self::BUSY],
                    self::getStatusLabel($status)->render()
                );
                break;
        };
        return $html;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        if (null == $this->options) {
            $this->options = [
                ['value' => self::ONLINE,
                    'label' => self::getStatusLabel(self::ONLINE)
                ],
                ['value' => self::OFFLINE,
                    'label' => self::getStatusLabel(self::OFFLINE)
                ],
                ['value' => self::BUSY,
                    'label' => self::getStatusLabel(self::BUSY)
                ],
            ];
        }
        return $this->options;
    }
}
