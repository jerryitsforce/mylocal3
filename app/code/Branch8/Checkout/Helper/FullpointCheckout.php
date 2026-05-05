<?php
namespace Branch8\Checkout\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Context as CustomerContext;

class FullpointCheckout extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $scopeConfig;

    protected $httpContext;

    protected $pointMoneyHelper;

    protected $productRepository;


    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        ProductRepositoryInterface $productRepository,
        \Branch8\PointMoneyCollect\Helper\Data $pointMoneyHelper,
        \Magento\Framework\App\Http\Context $customerContext
    )
    {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->httpContext = $customerContext;
        $this->pointMoneyHelper = $pointMoneyHelper;
        $this->productRepository = $productRepository;
    }

    public function fullpointProductCheckoutData($productId){
        $data = [
            'is_virtual' => false,
            'is_full_point' => false,
            'is_enable_full_point_checkout' => false
        ];
        try{
            $product = $this->productRepository->getById($productId);
        }catch(\Exception $e){

        }
        $data = $this->getFullPointCheckoutProduct($product);
        $data['point_rate'] = $this->pointMoneyHelper->getPointConvertRate();
        $data['is_logged_in'] = $this->isLoggedIn();

        return $data;
    }

    public function getFullPointCheckoutProduct($product){

        $isFullPoint = $product->getData('point_money_config_type') == \Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigType::TYPE_ONLY_POINT;
        $isVirtual = $product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL;
        $isEbableFullPointCheckout = $this->scopeConfig->getValue('checkout/advance/buy_now_full_point_enable');

        $data = [
            'is_virtual' => $isVirtual,
            'is_full_point' => $isFullPoint,
            'is_enable_full_point_checkout' => $isEbableFullPointCheckout
        ];

        return $data;
    }

    public function isProductValidFullpointCheckout($product){
        $validateData = $this->getFullPointCheckoutProduct($product);
        if($validateData['is_virtual'] && $validateData['is_full_point'] && $validateData['is_enable_full_point_checkout']){
            return true;
        }
        return false;
    }

    public function isValidFullPointCheckout($product){
        $isValidProduct = $this->isProductValidFullpointCheckout($product);
        $isLoggedIn = $this->isLoggedIn();

        if($isLoggedIn && $isValidProduct){
            return ['result' => true];
        }
        $validateData = $this->getFullPointCheckoutProduct($product);
        $validateData['is_logged_in'] = $isLoggedIn;
        return ['result' => false, 'data' => $validateData];
    }

    public function isLoggedIn(){
        return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
    }
}
