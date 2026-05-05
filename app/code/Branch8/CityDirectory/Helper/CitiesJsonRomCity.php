<?php

namespace Branch8\CityDirectory\Helper;

use Branch8\CityDirectory\Model\RomCityRepository;
use Branch8\CityDirectory\Model\RomCity;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\ResourceModel\Country\Collection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Directory\Helper\Data;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Json\Helper\Data as DataHelper;
use Magento\Store\Model\StoreManagerInterface;

class CitiesJsonRomCity extends Data
{
    /**
     * Custom order for Taiwan regions
     *
     * @var array
     */
    private $taiwanRegionOrder = [
        '臺北市', '新北市', '桃園市', '臺中市', '臺南市', '高雄市',
        '基隆市', '新竹市', '新竹縣', '苗栗縣', '彰化縣', '南投縣',
        '雲林縣', '嘉義市', '嘉義縣', '屏東縣', '宜蘭縣', '花蓮縣',
        '臺東縣', '澎湖縣', '金門縣', '連江縣'
    ];

    private $romCityRepository;

    private $searchCriteria;

    private $cachedRegionData;

    public function __construct(
        Context $context,
        Config $configCacheType,
        Collection $countryCollection,
        CollectionFactory $regCollectionFactory,
        DataHelper $jsonHelper,
        StoreManagerInterface $storeManager,
        CurrencyFactory $currencyFactory,
        RomCityRepository $romCityRepository,
        SearchCriteriaBuilder $searchCriteria
    ) {
        $this->searchCriteria = $searchCriteria;
        $this->romCityRepository = $romCityRepository;
        parent::__construct(
            $context,
            $configCacheType,
            $countryCollection,
            $regCollectionFactory,
            $jsonHelper,
            $storeManager,
            $currencyFactory
        );
    }

    /**
     * Sort Taiwan regions according to custom order
     *
     * @param array $regions
     * @return array
     */
    private function sortTaiwanRegions($regions)
    {
        $sorted = [];
        $regionMap = [];

        // Create a map of region name to region data
        foreach ($regions as $regionId => $regionData) {
            $regionMap[$regionData['name']] = ['id' => $regionId, 'data' => $regionData];
        }

        // Sort according to custom order
        foreach ($this->taiwanRegionOrder as $regionName) {
            if (isset($regionMap[$regionName])) {
                $sorted[$regionMap[$regionName]['id']] = $regionMap[$regionName]['data'];
                unset($regionMap[$regionName]);
            }
        }

        // Append any remaining regions that weren't in the custom order
        foreach ($regionMap as $regionInfo) {
            $sorted[$regionInfo['id']] = $regionInfo['data'];
        }

        return $sorted;
    }

    /**
     * Retrieve regions data
     *
     * @return array
     */
    public function getRegionData()
    {
        if (!$this->cachedRegionData) {
            $countryIds = [];
            foreach ($this->getCountryCollection() as $country) {
                $countryIds[] = $country->getCountryId();
            }
            $collection = $this->_regCollectionFactory->create();
            $collection->addCountryFilter($countryIds)->load();
            $regions = [
                'config' => [
                    'show_all_regions' => $this->isShowNonRequiredState(),
                    'regions_required' => $this->getCountriesWithStatesRequired(),
                ],
            ];

            $searchCriteriaBuilder = $this->searchCriteria;
            $searchCriteria = $searchCriteriaBuilder->create();

            $citiesList = $this->romCityRepository->getList($searchCriteria);
            $items = $citiesList->getItems();

            /** @var RomCity $item */
            $citiesData = [];
            foreach ($items as $item) {
                $citiesData[$item->getRegionId()][$item->getEntityId()] = [
                    'name' => $item->getCityName(),
                    'id' => $item->getEntityId()
                ];
            }

            $tempRegions = [];
            foreach ($collection as $region) {
                /** @var $region Region */
                if (!$region->getRegionId()) {
                    continue;
                }

                $cities = isset($citiesData[$region->getId()]) ? $citiesData[$region->getId()] : [];

                $tempRegions[$region->getCountryId()][$region->getRegionId()] = [
                    'code' => $region->getCode(),
                    'name' => (string)__($region->getName()),
                    'cities' => $cities
                ];
            }

            // Apply custom sorting for Taiwan regions
            if (isset($tempRegions['TW'])) {
                $tempRegions['TW'] = $this->sortTaiwanRegions($tempRegions['TW']);
            }

            $regions = array_merge($regions, $tempRegions);
            $this->cachedRegionData = $regions;
        }
        return $this->cachedRegionData;
    }

    public function getAllCitiesForRMA(){
        $searchCriteriaBuilder = $this->searchCriteria;
        $searchCriteria = $searchCriteriaBuilder->create();

        $citiesList = $this->romCityRepository->getList($searchCriteria);
        $items = $citiesList->getItems();
        $returnData = [];
        foreach ($items as $item) {
            $returnData[$item->getRegionId()][] = $item->getCityName();
        }
        return $returnData;
    }

}
