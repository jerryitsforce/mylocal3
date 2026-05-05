<?php

namespace Branch8\Checkout\Model\Config\Source;

class TaiwanCity implements \Magento\Framework\Option\ArrayInterface
{

    protected $countryModel;
    public function __construct(
        \Magento\Directory\Model\Country $countryModel
    ){
        $this->countryModel = $countryModel;
    }
    public function toOptionArray()
    {
        $data = [];
        $countryCode = 'TW';
        $regionData = $this->countryModel->loadByCode($countryCode)->getRegions()->loadData()->toArray();
        foreach($regionData['items'] as $_region){
            $data[] = [
                'label' => $_region['default_name'],
                'value' => $_region['region_id']
            ];
        }

        return $data;
    }
}