<?php
namespace Branch8\WebkulMpsplitorder\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'systemlog', 'label' => __('System Log(var/log/system.log)')],
            ['value' => 'exceptionlog', 'label' => __('System Log(var/log/exception.log)')],
            ['value' => 'deactive_cancel_subquote', 'label' => __('System Log(var/log/split_order/error.log)')],
            ['value' => 'quoteManagementPlugin', 'label' => __('QuoteManagementPlugin Log(var/log/WebkulMpsplitorder/QuoteManagementPlugin/{Y_m_d}.log)')],
            ['value' => 'splitMasterQuoteIntoSubQuote', 'label' => __('SplitMasterQuoteIntoSubQuote Log(var/log/WebkulMpsplitorder/Services/SplitMasterQuoteIntoSubQuote/{Y_m_d}.log)')],
            ['value' => 'ProcessParentOrderFailed', 'label' => __('ProcessParentOrderFailed Log(var/log/WebkulMpsplitorder/ProcessParentOrderFailed/{Y_m_d}.log)')]
        ];
    }
}
