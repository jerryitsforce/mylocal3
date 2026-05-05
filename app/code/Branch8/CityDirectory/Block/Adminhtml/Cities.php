<?php

namespace Branch8\CityDirectory\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Branch8\CityDirectory\Model\RomCityRepository;
use Branch8\CityDirectory\Model\RomCity;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Backend\Block\Template\Context;

/**
 * Class Cities
 * @package Branch8\CityDirectory\Block\Adminhtml
 */
class Cities extends Template
{
     /** @var RomCityRepository  */
    private $romCityRepository;

    /** @var SearchCriteriaBuilder  */
    private $searchCriteria;

    /** @var SerializerInterface  */
    private $serializer;

    public function __construct(
        Context $context,
        RomCityRepository $romCityRepository,
        SearchCriteriaBuilder $searchCriteria,
        SerializerInterface $serializer,
        array $data = []
    )
    {
        $this->searchCriteria = $searchCriteria;
        $this->romCityRepository = $romCityRepository;
        $this->serializer = $serializer;
        parent::__construct($context, $data);
    }

    public function citiesJson()
    {

        $searchCriteriaBuilder = $this->searchCriteria;
        $searchCriteria = $searchCriteriaBuilder->create();

        $citiesList = $this->romCityRepository->getList($searchCriteria);
        $items = $citiesList->getItems();

        $return = [];

        /** @var RomCity $item */
        foreach ($items as $item) {
            $return[] = ['region_id' => $item->getRegionId(), 'city_name' => $item->getCityName()];
        }

        return $this->serializer->serialize($return);
    }
}
