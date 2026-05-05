<?php

namespace Branch8\RmaAdminUi\Ui\Form;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class RegionOptions implements OptionSourceInterface, ArgumentInterface
{
    private $options = null;
    private \Branch8\CityDirectory\Helper\CitiesJsonRomCity $helper;
    private array $regionCityOptions;

    /**
     * @param \Branch8\CityDirectory\Helper\CitiesJsonRomCity $cityJsonRomCity
     */
    public function __construct(
        \Branch8\CityDirectory\Helper\CitiesJsonRomCity $cityJsonRomCity,
    )
    {
        $this->helper = $cityJsonRomCity;
    }

    /**
     * @return array|null
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $this->options = array_map(function ($item) {
            return ['value' => $item['name'], 'label' => $item['name'], 'cities' => $item['cities']];
        }, $this->helper->getRegionData()['TW']);
        array_unshift($this->options, ['value' => '', 'label' => __('-- Please Select --'), 'cities' => []]);
        return $this->options;
    }
}
