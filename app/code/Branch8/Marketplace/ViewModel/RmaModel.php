<?php
declare(strict_types=1);

namespace Branch8\Marketplace\ViewModel;
use Branch8\RmaAdminUi\Model\Actions\TotalRmaInReviews;
use Magento\Framework\View\Element\Block\ArgumentInterface;
/**
 *
 */
class RmaModel implements ArgumentInterface
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
     * @return array|array[]|null
     */
    public function getRegionCityOptions()
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
