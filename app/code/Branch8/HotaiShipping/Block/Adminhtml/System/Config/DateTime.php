<?php

namespace Branch8\HotaiShipping\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class DateTime extends Field
{
    protected TimezoneInterface $timezone;

    public function __construct(
        Context $context,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->setDateFormat(\Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat($this->timezone->getTimeFormat());
        $element->setShowsTime(true);
        // $element->setData('readonly', 'readonly');
        return parent::render($element);
    }

    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = parent::_getElementHtml($element);

        $html .= '<script type="text/javascript">
            require(["jquery"], function ($) {
                $(document).ready(function () {
                    var intV'.$element->getHtmlId().' = setInterval(function () {
                        var $el = $("#' . $element->getHtmlId() . '");
                        if($el.attr("disabled") == "disabled"){
                            $el.removeAttr("disabled"); 
                            $el.attr("readonly", "readonly");
                            //clearInterval(intV'.$element->getHtmlId().');
                        }
                        
                    }, 500);
                });
            });
        </script>';

        return $html;
    }
}
