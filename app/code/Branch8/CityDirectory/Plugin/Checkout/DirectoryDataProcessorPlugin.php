<?php

namespace Branch8\CityDirectory\Plugin\Checkout;

use Magento\Checkout\Block\Checkout\DirectoryDataProcessor;

class DirectoryDataProcessorPlugin
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

    /**
     * After plugin to sort region options for Taiwan
     *
     * @param DirectoryDataProcessor $subject
     * @param array $result
     * @return array
     */
    public function afterProcess(DirectoryDataProcessor $subject, $result)
    {
        if (isset($result['components']['checkoutProvider']['dictionaries']['region_id'])) {
            $result['components']['checkoutProvider']['dictionaries']['region_id'] =
                $this->sortTaiwanRegions($result['components']['checkoutProvider']['dictionaries']['region_id']);
        }

        return $result;
    }

    /**
     * Sort Taiwan regions according to custom order
     *
     * @param array $regionOptions
     * @return array
     */
    private function sortTaiwanRegions($regionOptions)
    {
        $taiwanRegions = [];
        $otherRegions = [];

        // Separate Taiwan regions from others
        foreach ($regionOptions as $region) {
            if (isset($region['country_id']) && $region['country_id'] === 'TW') {
                $taiwanRegions[] = $region;
            } else {
                $otherRegions[] = $region;
            }
        }

        // Sort Taiwan regions
        if (!empty($taiwanRegions)) {
            $sorted = [];
            $regionMap = [];

            // Create a map of region label to region option
            foreach ($taiwanRegions as $region) {
                if (isset($region['label'])) {
                    $regionMap[$region['label']] = $region;
                }
            }

            // Sort according to custom order
            foreach ($this->taiwanRegionOrder as $regionName) {
                if (isset($regionMap[$regionName])) {
                    $sorted[] = $regionMap[$regionName];
                    unset($regionMap[$regionName]);
                }
            }

            // Append any remaining Taiwan regions that weren't in the custom order
            foreach ($regionMap as $region) {
                $sorted[] = $region;
            }

            // Merge sorted Taiwan regions with other regions
            return array_merge($sorted, $otherRegions);
        }

        return $regionOptions;
    }
}
