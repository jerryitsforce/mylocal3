<?php
namespace Branch8\Spin2Win\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'spin2win', 'label' => __('System Log(var/log/spintowin.log)')]
        ];
    }
}
