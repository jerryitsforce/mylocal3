<?php
namespace Branch8\AppSettings\Helper;

use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_ANDROID_VERSION = 'app_settings/versioning/android_version';
    const XML_PATH_IOS_VERSION = 'app_settings/versioning/ios_version';
    const XML_PATH_IOS_MIN_SUPPORTED_VERSION = 'app_settings/versioning/ios_min_supported_version';
    const XML_PATH_ANDROID_MIN_SUPPORTED_VERSION = 'app_settings/versioning/android_min_supported_version';
    const XML_PATH_UPDATE_MESSAGE = 'app_settings/versioning/update_message';

    const XML_PATH_FIREBASE_CREDENTIAL_JSON = 'app_settings/firebase/service_account_credential_json';
    const XML_PATH_FIREBASE_ENV = 'app_settings/firebase/environment';
    const XML_PATH_FIREBASE_PROJECT_ID = 'app_settings/firebase/project_id';


    const XML_PATH_DEVELOPER_ENABLE_DEBUGGER = 'app_settings/developer/enable_debugger';
    private MobileDetect $mobileDetect;

    const DEFAULT_VERSION = [
        'major' => 1,
        'minor' => 0,
        'patch' => 0,
        'build' => 0
    ];

    public function __construct(
        Context $context,
        MobileDetect $mobileDetect
    ) {
        $this->mobileDetect = $mobileDetect;
        parent::__construct($context);
    }

    /**
     * @return string
     */
    public function getAndroidVersion()
    {
        return $this->getVersionSetting(self::XML_PATH_ANDROID_VERSION);
    }

    /**
     * @return string
     */
    public function getIOSVersion()
    {
        $setting = $this->getVersionSetting(self::XML_PATH_IOS_VERSION);
        $setting['patch'] = 0; // Ensure patch is set to 0 for consistency
        return $setting;
    }

    /**
     * @return string
     */
    public function getIOSMinSupportedVersion()
    {
        $setting = $this->getVersionSetting(self::XML_PATH_IOS_MIN_SUPPORTED_VERSION);
        $setting['patch'] = 0; // Ensure patch is set to 0 for consistency
        return $setting;
    }

    /**
     * @return string
     */
    public function getAndroidMinSupportedVersion()
    {
        return $this->getVersionSetting(self::XML_PATH_ANDROID_MIN_SUPPORTED_VERSION);
    }


    private function getVersionSetting($key) {
        $setting = $this->scopeConfig->getValue($key, ScopeInterface::SCOPE_WEBSITE);
        $setting = $setting ? json_decode($setting, true) : [];
        if (!empty($setting)) {
            $setting = reset($setting);
        } else {
            $setting = self::DEFAULT_VERSION;
        }
        foreach ($setting as $key => $value) {
            $setting[$key] = (int)$value;
        }
        return $setting;
    }

    /**
     * @return string
     */
    public function getUpdateMessage()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_UPDATE_MESSAGE, ScopeInterface::SCOPE_WEBSITE) ?? '';
    }

    /**
     * @return bool
     */
    public function isDebuggerEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_DEVELOPER_ENABLE_DEBUGGER, ScopeInterface::SCOPE_WEBSITE);
    }

    public function isHotaiApp()
    {
        return $this->mobileDetect->isHotaiApp();
    }

    public function getFirebaseServiceAccountCredentialJson()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FIREBASE_CREDENTIAL_JSON, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getFirebaseEnvironment()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FIREBASE_ENV, ScopeInterface::SCOPE_WEBSITE);
    }

    public function getFirebaseProjectId()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FIREBASE_PROJECT_ID, ScopeInterface::SCOPE_WEBSITE);
    }


}
