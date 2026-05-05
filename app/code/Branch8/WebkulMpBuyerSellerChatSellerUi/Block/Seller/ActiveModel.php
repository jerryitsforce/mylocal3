<?php declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatSellerUi\Block\Seller;

use Magento\Store\Model\ScopeInterface;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class ActiveModel extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Webkul\MpBuyerSellerChat\Model\ChatDataConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $mpHepler;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializerJson;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $currency;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Webkul\MpBuyerSellerChat\Model\EnableUserConfigProvider $configProvider
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Webkul\Marketplace\Helper\Data $mpHepler
     * @param \Magento\Framework\Serialize\Serializer\Json $serializerJson
     * @param \Magento\Directory\Model\Currency $currency
     * @param HelperData $subAccountHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Webkul\MpBuyerSellerChat\Model\EnableUserConfigProvider $configProvider,
        \Magento\Framework\App\Request\Http $request,
        \Webkul\Marketplace\Helper\Data $mpHepler,
        \Magento\Framework\Serialize\Serializer\Json $serializerJson,
        \Magento\Directory\Model\Currency $currency,
        HelperData $subAccountHelper,
        array $data = []
    ) {

        $this->scopeConfig = $context->getScopeConfig();
        $this->configProvider = $configProvider;
        $this->request = $request;
        $this->mpHepler = $mpHepler;
        $this->serializerJson = $serializerJson;
        $this->currency = $currency;
        parent::__construct($context, $data);
        $this->subAccountHelper = $subAccountHelper;
    }

    /**
     * Retrieve information from carrier configuration.
     *
     * @param string $field
     *
     * @return void|false|string
     */
    public function getConfigData($field)
    {
        $path = 'customer_termandcondition/parameter/'.$field;
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $this->_storeManager->getStore()->getId()
        );
    }

    /**
     * Retrieve ChatBox Config Data
     *
     * @return array
     */
    public function getChatBoxConfig()
    {
        $configData = $this->configProvider->getConfig();

        $sellerImage = isset($configData['sellerChatData']['sellerImage']) ?
                        $configData['sellerChatData']['sellerImage'] : null;

        if (isset($configData['sellerChatData']) && !$sellerImage) {
            $configData['sellerChatData']['sellerImage'] =
                $this->getViewFileUrl('Webkul_MpBuyerSellerChat::images/sellerimage.png');
        }
        return $configData;
    }

    /**
     * Check ChatWindow View on Seller End
     *
     * @return bool
     */
    public function checkChatWindowView()
    {
        $routeName = $this->request->getRouteName();
        if (in_array($routeName, ['marketplace', 'sellersubaccount'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get Helper Object
     *
     * @return object
     */
    public function getHelperObject()
    {
        return $this->mpHepler;
    }

    /**
     * Encode data
     *
     * @param array $data
     * @return string
     */
    public function jsonFormat($data)
    {
        return $this->serializerJson->serialize($data);
    }

    /**
     * Get current currency sysmbol
     *
     * @return string
     */
    public function getCurrentCurrencySymbol()
    {
        return $this->currency->getCurrencySymbol();
    }

    public function toHtml()
    {
        $subAccount = $this->subAccountHelper->getCurrentSubAccount();
        if($subAccount->getId()){/*Current logged in account is Sub account*/
            $allowedPermission = $subAccount->getPermissionType();
            $allowedPermissionArr = explode(',', (string)$allowedPermission);
            if(in_array(\Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Config\ChatPermission::CHAT_PERMISSION_KEY, $allowedPermissionArr)){
                return parent::toHtml();
            }
        }else{
            /**
             * Current logged in is seller
             */
            return parent::toHtml();
        }
        return '';
    }
}
