<?php

namespace Branch8\Yoxi\Helper;

use Magento\Store\Model\ScopeInterface;

class Email
{
    const CONFIG_PATH_NOTIFICATION_EMAIL_RECEIVER_ADDRESSES = "yoxi/email/notification_receiver_addresses";

    const EMAIL_TEMPLATE_ID_SAFETY_QUANTITY_NOTIFICATION = 'yoxi/email/check_safety_quantity_notification';

    const MAGENTO_STORE_NAME_SCOPE_CONFIG_PATH  = "trans_email/ident_support/name";
    const MAGENTO_STORE_EMAIL_SCOPE_CONFIG_PATH = "trans_email/ident_support/email";

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /** @var \Magento\Framework\Translate\Inline\StateInterface */
    protected $inlineTranslation;

    /** @var \Magento\Framework\Mail\Template\TransportBuilder */
    protected $_transportBuilder;

    protected $temp_id;

    /**
     * @param Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Magento\Store\Model\StoreManagerInterface $storeManager
     * @param Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
    ) {
        $this->_scopeConfig      = $scopeConfig;
        $this->_storeManager     = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
    }

    /**
     * 將參數填入指定email模板
     *
     * @param  string[] $emailTemplateVariables
     * @param  string[] $receiverInfo
     * @return void
     */
    public function generateTemplate(array $emailTemplateVariables, array $receiverInfo): void
    {
        $this->_transportBuilder->setTemplateIdentifier($this->temp_id)
            ->setTemplateOptions(
                [
                    'area'  => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->_storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom([
                "name"  => $this->_scopeConfig->getValue(self::MAGENTO_STORE_NAME_SCOPE_CONFIG_PATH, ScopeInterface::SCOPE_STORE),
                "email" => $this->_scopeConfig->getValue(self::MAGENTO_STORE_EMAIL_SCOPE_CONFIG_PATH, ScopeInterface::SCOPE_STORE),
            ])
            ->addTo($receiverInfo['email'], $receiverInfo['name']);
    }

    /**
     * 寄送YOXI安全庫存檢查通知信
     *
     * @param  array $emailTemplateVariables
     * $emailTemplateVariables = [
     *  "titleTimeString" => date("Y-m-d H:i:s"),
     *  "tableContent" => string
     * ];
     * @param  array $receiverInfo= [
     *  "name"  => "Administrator",
     *  "email" => john@gmail.com,
     * ];
     * @return void
     */
    public function sendSafetyQuantityNotificationEmail(array $emailTemplateVariables, array $receiverInfo): void
    {
        $this->temp_id = self::EMAIL_TEMPLATE_ID_SAFETY_QUANTITY_NOTIFICATION;
        $this->inlineTranslation->suspend();
        $this->generateTemplate($emailTemplateVariables, $receiverInfo);
        $transport = $this->_transportBuilder->getTransport();
        $transport->sendMessage();
        $this->inlineTranslation->resume();
    }

    /**
     * 取得後台設定的YOXI安全庫存檢查通知信收件人地址
     *
     * @return array
     */
    public function getNotificationEmailReceiverAddresses(): array
    {
        $addressesString = $this->_scopeConfig->getValue(self::CONFIG_PATH_NOTIFICATION_EMAIL_RECEIVER_ADDRESSES);
        $addressesString = str_replace(' ', '', $addressesString);
        return explode(",", $addressesString);
    }
}
