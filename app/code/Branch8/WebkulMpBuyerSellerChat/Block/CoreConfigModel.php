<?php

namespace Branch8\WebkulMpBuyerSellerChat\Block;

class CoreConfigModel extends \Webkul\MpBuyerSellerChat\Block\CoreConfigModel
{
    /**
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomChatBoxCoreConfig()
    {
       /* $configData = parent::getChatBoxCoreConfig();*/
        /*
         * check chat server running is making site slow
         *  */
        $configData['serverRunning'] = $this->isServerRunning();
        $configData['loaderImage'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/loader-2.gif');
        $configData['soundUrl'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/sound/notify.ogg');
        $configData['soundImage'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/sound.png');
        $configData['settingImage'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/setting.png');
        $configData['customerImage'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/default.png');
        $configData['attachmentImage'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/attachment.png');
        $configData['sellerImage'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/sellerimage.png');
        $configData['emojiImagePath'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/emoji');
        $configData['profileSetting'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/profile_setting.png');
        $configData['sellerOptions'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/seller_options.png');
        $configData['sellerRetract'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/seller_retract.png');
        $configData['greenCheck'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/green_check.png');
        $configData['startChat'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/start_chat.png');
        $configData['minimize'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/minimize.png');
        $configData['maximize'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/maximize.png');
        $configData['address'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/address.png');
        $configData['dollarSign'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/dollar-sign.png');
        $configData['gender'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/gender.png');
        $configData['orders'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/orders.png');
        $configData['phone'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/phone.png');
        $configData['backArrow'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/arrow-left.png');
        $configData['close'] = $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/close.png');
        $configData['host'] = $this->getConfigData('general_settings', 'host_name').
            ':'.$this->getConfigData('general_settings', 'port_number');
        $configData['chatName'] = $this->getConfigData('general_settings', 'chat_name');
        $configData['storeCode'] = $this->_storeManager->getStore()->getCode();
        $configData['maxFileSize'] = (int)$this->getConfigData('general_settings', 'max_size');
        $configData['uiConfig'] = $this->uiConfig->get();
        $configData['captchaTypeForLogin'] = $this->captchaType->getCaptchaTypeFor('customer_login');
        $configData['captchaTypeForRegister'] = $this->captchaType->getCaptchaTypeFor('customer_create');
        return $configData;
    }

    /**
     * @return true
     */
    private function isServerRunning()
    {
        return true;
    }
}
