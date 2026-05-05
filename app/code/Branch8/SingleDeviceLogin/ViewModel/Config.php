<?php
declare(strict_types=1);

namespace Branch8\SingleDeviceLogin\ViewModel;

use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Branch8\SingleDeviceLogin\Model\ConfigData;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 *
 */
class Config implements ArgumentInterface
{
    private ConfigData $configData;
    private \Magento\Framework\App\Http\Context $httpContext;
    private MobileDetect $mobileDetect;

    /**
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param ConfigData $configData
     */
    public function __construct(
        \Magento\Framework\App\Http\Context $httpContext,
        ConfigData                          $configData,
        MobileDetect $mobileDetect,
    )
    {
        $this->httpContext = $httpContext;
        $this->configData = $configData;
        $this->mobileDetect = $mobileDetect;
    }

    public function isHotaiApp()
    {
        return $this->mobileDetect->isHotaiApp();
    }

    /**
     * @return bool
     */
    public function isLogin()
    {
        if ($this->httpContext->getValue('customer_id')) {
            return true;
        }
        return false;
    }

    /**
     * @return null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function isEnable()
    {
        return (bool)$this->configData->getValue('single_device_login/general/enable_and_use_socket');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        $interval = (int)$this->configData->getValue('single_device_login/general/popup_inteval');
        $textWithTimer = __('You are already logged in on a different device. You will be automatically logged out in a (<span class="counter">%1</span>) seconds.', $interval);
        $textWithoutTimer = __('You are already logged in on a different device. You will be automatically logged out');
        return [
            'host' => $this->configData->getHost(),
            'ws_logout_url' => $this->configData->getForceLogoutUrl(),
            'debug_frontend' => (bool)$this->configData->getValue('single_device_login/general/debug_frontend'),
            'defaultNotifyText' => $textWithTimer,
            'mobileNotifyText' => $textWithoutTimer,
            'showTimerOnMobile' => (bool)$this->configData->getValue('single_device_login/general/show_timer_on_mobile'),
            'interval' => $interval ?: 5
        ];
    }
}
