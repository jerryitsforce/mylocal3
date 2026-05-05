<?php

namespace Branch8\SalesRule\Block\Widget\Grid\Column\Filter;

class Datetime extends \Magento\Backend\Block\Widget\Grid\Column\Filter\Datetime
{
    /***
     * @param $index
     * @return array|float|int|mixed|string|null
     * @throws \DateInvalidTimeZoneException
     * @throws \DateMalformedStringException
     */
    public function getValue($index = null)
    {
        if ($index) {
            if ($data = $this->getData('value', 'orig_' . $index)) {
                return $data;
            }
            return null;
        }
        $value = $this->getData('value');
        if (is_array($value)) {
            $value['datetime'] = true;
        }
        if (!empty($value['to']) && !$this->getColumn()->getFilterTime()) {
            $datetimeTo = $value['to'];

            //calculate end date considering timezone specification
            /** @var $datetimeTo \DateTime */
            $datetimeTo->setTimezone(
                new \DateTimeZone(
                    $this->_scopeConfig->getValue(
                        $this->_localeDate->getDefaultTimezonePath(),
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                )
            );
            $datetimeTo->modify('+1 day')->modify('-1 second');
            $datetimeTo->setTimezone(
                new \DateTimeZone('UTC')
            );
        }
        return $value;
    }

    public function setData($key, $value = null)
    {
        parent::setData($key, $value);
        return $this;
    }

    /**
     * Render filter html
     *
     * @return string
     */
    public function getHtml()
    {
        $htmlId = $this->mathRandom->getUniqueHash($this->_getHtmlId());
        $format = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = '';

        if ($this->getColumn()->getFilterTime()) {
            $timeFormat = $this->_localeDate->getTimeFormat(
                \IntlDateFormatter::SHORT
            );
        }

        $html =
            '<div class="range" id="' . $htmlId . '_range"><div class="range-line date">' . '<input type="text" name="'
            . $this->_getHtmlName() . '[from]" id="' . $htmlId . '_from"' . ' value="' . $this->getEscapedValue('from')
            . '" class="input-text admin__control-text no-changes"  ' . $this->getUiId(
                'filter',
                $this->_getHtmlName(),
                'from'
            ) . '/>' . '</div>';
        $html .= '<div class="range-line date" style="display:none">' . '<input  type="text" name="' . $this->_getHtmlName() . '[to]" id="'
            . $htmlId . '_to"' . ' value="' . $this->getEscapedValue(
                'to'
            ) . '" class="input-text admin__control-text no-changes" placeholder="' . __(
                'To'
            ) . '" ' . $this->getUiId(
                'filter',
                $this->_getHtmlName(),
                'to'
            ) . '/>' . '</div></div>';
        $html .= '<input type="hidden" name="' . $this->_getHtmlName() . '[locale]"' . ' value="'
            . $this->localeResolver->getLocale() . '"/>';
        $scriptString = 'require(["jquery", "mage/calendar"],function($){
                    $("#' . $htmlId . '_range").dateRange({
                        dateFormat: "' . $format . '",
                        timeFormat: "' . $timeFormat . '",
                        showsTime: ' . ($this->getColumn()->getFilterTime() ? 'true' : 'false') . ',
                        buttonText: "' . $this->escapeHtml(__('Date selector')) . '",
                        buttonImage: "' . $this->getViewFileUrl('Magento_Theme::calendar.png') . '",
                        from: {
                            id: "' . $htmlId . '_from"
                        },
                        to: {
                            id: "' . $htmlId . '_to"
                        }
                    })
            });';
        $html .= $this->secureHtmlRenderer->renderTag('script', [], $scriptString, false);

        return $html;
    }

}
