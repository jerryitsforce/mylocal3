<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionCustomer\ViewModel;

use Branch8\MarketplaceProduct\Model\ProductRepository;
use Branch8\MarketPlaceProductDiscussionCustomer\Model\ConfigData;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Customer implements ArgumentInterface
{
    private \Magento\Customer\Model\Session $customerSession;
    private ConfigData $configData;
    private UrlInterface $url;
    private RequestInterface $httpRequest;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param RequestInterface $httpRequest
     * @param UrlInterface $url
     * @param ConfigData $configData
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        RequestInterface                $httpRequest,
        UrlInterface                    $url,
        ConfigData                      $configData
    )
    {
        $this->httpRequest = $httpRequest;
        $this->url = $url;
        $this->configData = $configData;
        $this->customerSession = $customerSession;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        return [
            'filterUrl' => $this->url->getUrl('product_discussion/member/filterQuestion'),
            /*     'maxLength' => $this->configData->getValue('max_length_input') ?: 200,
                 'rateLimit' => $this->configData->getValue('submit_rate_limit') ?: 30,*/
        ];
    }
}
