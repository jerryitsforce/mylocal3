<?php

declare(strict_types=1);

namespace Branch8\HotaiPoint\Helper;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ApiHelper
     */
    protected ApiHelper $apiHelper;

    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    /**
     * @var mixed
     */
    protected mixed $pointUser = null;

    /**
     * @var mixed
     */
    protected mixed $expirePointUser = null;

    protected array $expirePointData;

    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    /**
     * @param StoreManagerInterface $storeManager
     * @param Api $apiHelper
     * @param HttpContext $httpContext
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param Context $context
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ApiHelper $apiHelper,
        HttpContext $httpContext,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        Context $context
    ){
        $this->storeManager = $storeManager;
        $this->apiHelper = $apiHelper;
        $this->httpContext = $httpContext;
        $this->b8CustomerHelper = $b8CustomerHelper;
        parent::__construct($context);
    }

    /**
     * Get point image
     *
     * @param null $store
     * @return string
     */
    public function getPointImage($store = null, $size = null, $isDeduct = false)
    {
        $image = '';
        try {
            $pointImage = $this->scopeConfig->getValue(
                $isDeduct ? 'hotai_point/general/point_deduct_image' : 'hotai_point/general/point_image',
                \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                $store
            );
            if ($pointImage) {
                $imageSrc = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . 'hotai/rewardpoints/' . $pointImage;
                if ($size) {
                    $image = __('<img src="%1" width="%2" height="%2" alt="Reward Points"/>', $imageSrc, $size);
                } else {
                    $image = __('<img src="%1" alt="Reward Points"/>', $imageSrc);
                }
            }
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
        }
        return $image;
    }

    /**
     * Get point unit
     *
     * @param $points
     * @param bool $showImage
     * @return string
     */
    public function formatPoints($points, $showImage = false, $showSigned = false, $showTag = true)
    {
        $originalPoints = (float)$points;
        $points = number_format((float)$points, 0, '.', ',');
        if (($pointImage = $this->getPointImage(null, null, $originalPoints < 0)) && $showImage) {
            $points = $pointImage.'<span>'. ($showSigned && $originalPoints >= 0 ? '+' : '') . $points.'</span>';
        }
        if($showTag) {
            $points = '<span class="hotai-customer-points">' .$points . '</span>';
        }
        return $points;
    }

    /**
     * Get Hotai Point
     *
     * @return int
     */
    public function getHotaiPoint(): int
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/check-transinfo.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(get_class($this).' getHotaiPoint');
        if (is_null($this->pointUser)) {
            try {
                $this->pointUser = 0;
                if ($this->b8CustomerHelper->isLoggedInAndIsBuyer()) {  
                    $logger->info(get_class($this).' getHotaiPoint - OK');
                    $result = $this->apiHelper->requestApiGetTransInfo((int) $this->httpContext->getValue('customer_id'));

                    if ($this->apiHelper->getReturnCodeFromResponse($result) == ApiHelper::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
                        $this->pointUser = 0;
                    }

                    $this->pointUser = (int) $this->apiHelper->getPointFromResponse($result);
                }
            } catch (\Exception $e) {
                $this->pointUser = 0;
            }
        }
        return $this->pointUser;
    }

    public function getHotaiPointByCustomerId($customerId): int
    {
        
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/check-transinfo.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(get_class($this).' getHotaiPointByCustomerId');
         
        $point = 0;
        try {
            $result = $this->apiHelper->requestApiGetTransInfo((int) $customerId);

            if ($this->apiHelper->getReturnCodeFromResponse($result) == ApiHelper::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
                $point = 0;
            }else {
                $point = (int)$this->apiHelper->getPointFromResponse($result);
            }
        } catch (\Exception $e) {
            $point = 0;
        }
        return $point;
    }

    /**
     * Get Hotai Point
     *
     * @return int
     */
    public function getExpireHotaiPoint(): int
    {
        if (is_null($this->expirePointUser)) {
            try {
                $this->expirePointUser = 0;
                if ($this->b8CustomerHelper->isLoggedInAndIsBuyer()) {
                    $result = $this->apiHelper->requestApiGetPointByOneid((int) $this->httpContext->getValue('customer_id'));

                    if ($this->apiHelper->getReturnCodeFromResponse($result) == ApiHelper::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
                        $this->expirePointUser = 0;
                    }

                    $this->expirePointUser = (int) $this->apiHelper->getExpirePointFromResponse($result);
                }
            } catch (\Exception $e) {
                $this->expirePointUser = 0;
            }
        }
        return $this->expirePointUser;
    }

    public function getNearestExpirePoint(){
        if (empty($this->expirePointData)) {
            try {
                $this->expirePointData = [];
                if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
                    $result = $this->apiHelper->getRecentMonthsDuePointsByCustomerId((int) $this->httpContext->getValue('customer_id'), SORT_ASC);
                    if(!empty($result) && isset($result[0])){
                        $nearestExpirePointData = $result[0];
                        if(is_array($nearestExpirePointData)){
                            $this->expirePointData = $nearestExpirePointData;
                        }else{
                            $this->expirePointData = [];
                        }
                    }else{
                        $this->expirePointData = [];
                    }

                }
            } catch (\Exception $e) {
                $this->expirePointData = [];
            }
        }
        return $this->expirePointData;
    }

    public function isUserLogin()
    {
        return $this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }


    public function getPointRuleCmsBlockId($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'hotai_account_page/hotai_point/rules_explanation_cms_block',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPointTransferUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'hotai_account_page/hotai_point/point_transfer_url',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPointTransferStaticContentIdentity($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'hotai_account_page/hotai_point/point_transfer_static_content',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPointConversionUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'hotai_account_page/hotai_point/point_conversion_url',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPointRegistrationUrl($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'hotai_account_page/hotai_point/point_registration_url',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPointRegistrationStaticContentIdentity($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'hotai_account_page/hotai_point/point_registration_static_content',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }


    public function getMaxDateRangeFilter($storeId = null)
    {
        return $this->scopeConfig->getValue(
            'hotai_account_page/hotai_point/max_date_range_filter',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
