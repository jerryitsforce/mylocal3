<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       02/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionSeller\ViewModel;

use Branch8\MarketPlaceProductDiscussionSeller\Model\ConfigData;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Thread implements ArgumentInterface
{
    private ConfigData $configData;

    /**
     * @param ConfigData $configData
     */
    public function __construct(
        ConfigData        $configData
    )
    {
        $this->configData = $configData;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        return [
            'pageSize' => $this->configData->getValue('pageSize', ConfigData::PREFIX_PATH) ?: 10,
        ];
    }
}
