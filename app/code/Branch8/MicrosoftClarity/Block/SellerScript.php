<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MicrosoftClarity\Block;

use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;

class SellerScript extends \Magento\Framework\View\Element\Template
{
   const MarketPlaceRoutes = [
      'marketplace',
      'sellersubaccount',
      'mprmasystem',
      'mppreorder',
      'mpmassupload',
      'marketplacectrl'
    ];

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        HttpContext $httpContext,
        CustomerRepositoryInterface $customerRepository,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        RequestInterface $request,
        Session $customerSession,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->customerRepository = $customerRepository;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->request = $request;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function isMarketplaceController()
    {
      $moduleName = $this->request->getModuleName();
      return in_array($moduleName, self::MarketPlaceRoutes);
      // return $this->request->getModuleName() === 'marketplace';
    }

    public function getCustomer(){
        if ($this->httpContext->getValue('customer_id')) {
            return $this->customerRepository->getById($this->httpContext->getValue('customer_id'));
        }
        return null;
    }

    public function getSellerId(){
      $sellerId = $this->httpContext->getValue('customer_id');
      if($sellerId == null){
          $sellerId = $this->customerSession->getCustomerId();
      }
      if((int)$sellerId == 0){
          return false;
      }
      return $sellerId;
    }

    public function isSeller(){
      return $this->b8CustomerHelper->isSeller();
    }

    public function isSellerBoard(){
      // return true;
      return $this->isMarketplaceController() && $this->getSellerId() && $this->isSeller();
    }

    public function getSellerEmail(){
      $customer = $this->getCustomer();
      if($customer){
          return $customer->getEmail();
      }
      return null;
    }

    public function getClarityCode()
    {
      return $this->scopeConfig->getValue('branch8_microsoftclarity/general/clarity_project_id_seller');
    }
}

