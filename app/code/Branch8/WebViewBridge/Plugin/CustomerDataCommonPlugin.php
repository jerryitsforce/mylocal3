<?php
declare(strict_types=1);

namespace Branch8\WebViewBridge\Plugin;

use Branch8\WebViewBridge\CustomerData\BridgeData;
use Branch8\Customer\CustomerData\Common as CustomerDataCommon;

class CustomerDataCommonPlugin
{
    /**
     * @var BridgeData
     */
    protected $bridgeData;

    /**
     * @param BridgeData $bridgeData
     */
    public function __construct(BridgeData $bridgeData)
    {
        $this->bridgeData = $bridgeData;
    }

    /**
     * @param CustomerDataCommon $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerDataCommon $subject, array $result)
    {
        $result['webview_bridge'] = $this->bridgeData->getSectionData();
        return $result;
    }
}
