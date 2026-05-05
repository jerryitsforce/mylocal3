<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\ResourceModel\Quote\Address as AddressResource;
use Magento\Shipping\Model\Rate\Result;

class HotaiDelivery extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{

    protected $_code = 'hotai_delivery';

    protected $_isFixed = true;

    protected $_rateResultFactory;

    protected $_rateMethodFactory;
    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $splitCartHelper;
    /**
     * @var \Branch8\HotaiShipping\Helper\Data
     */
    protected $hotaiShippingHelper;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var AddressResource
     */
    private $addressResource;
    /**
     * @var AddressResource\CollectionFactory
     */
    protected $quoteAddressCollectionFactory;
    /**
     * @var \Branch8\FlagshipStore\Helper\Sales
     */
    protected $flagshipSalesHelper;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Branch8\SplitCart\Helper\Data $splitCartHelper
     * @param \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param AddressResource $addressResource
     * @param AddressResource\CollectionFactory $quoteAddressCollectionFactory
     * @param \Branch8\FlagshipStore\Helper\Sales $flagshipSalesHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Branch8\SplitCart\Helper\Data $splitCartHelper,
        \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        AddressResource $addressResource,
        \Magento\Quote\Model\ResourceModel\Quote\Address\CollectionFactory $quoteAddressCollectionFactory,
        \Branch8\FlagshipStore\Helper\Sales $flagshipSalesHelper,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
        $this->splitCartHelper = $splitCartHelper;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
        $this->checkoutSession = $checkoutSession;
        $this->addressResource = $addressResource;
        $this->quoteAddressCollectionFactory = $quoteAddressCollectionFactory;
        $this->flagshipSalesHelper = $flagshipSalesHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();

        $items = $request->getAllItems();
        //Check this method is available to checkout
        $allShippingMethods = $this->hotaiShippingHelper->getIntersectionShippingMethods($items);
        if(!in_array($this->_code, $allShippingMethods)) {
            return false;
        }
        $quoteId = $this->checkoutSession->getQuoteId();
        $shippingAddress = $this->quoteAddressCollectionFactory->create()
            ->addFieldToSelect(['address_id', 'shipping_method', 'detail_shipping_fee'])
            ->addFieldToFilter('quote_id', $quoteId)
            ->addFieldToFilter('address_type', 'shipping')
            ->getFirstItem();
        $shippingMethod = $shippingAddress->getShippingMethod();
        //because method code is the same carrier code at $this->getAllowedMethods
        $isSaveDetail = 0;
        if($shippingMethod == $this->_code.'_'.$this->_code){
            $isSaveDetail = 1;
        }
        //split cart to sub cart again
        $allSubCart = $this->splitCartHelper->splitCartToSubCart($items);
        $shippingPrice = 0;
        foreach ($allSubCart as $sellerId => $sellerCart) {
            if(strpos((string)$sellerId, \Branch8\FlagshipStore\Helper\Sales::CART_FLAGSHIP_STORE_PREFIX) === 0){
                $seller = $this->flagshipSalesHelper->getFlagshipStoreData($sellerId);
            }else{
                $seller = $this->splitCartHelper->getSellerBySellerId($sellerId);
            }

            foreach ($sellerCart as $subCartType => $items) {
                $shippingFeeData = $this->hotaiShippingHelper
                    ->getSubCartFreeShippingData($subCartType, [$this->_code], $items);
                if(empty($shippingFeeData)) {
                    continue;
                }
                $shippingFee = $shippingFeeData['carries_shipping_fee'][$this->_code]['shipping_fee'];
                $shippingPrice += $shippingFee;
                if($isSaveDetail){
                    if(is_array($seller)){
                        $sellerName = $seller['shop_title']?: $seller['shop_url'];
                    }else{
                        $sellerName = 'N/A';
                    }
                    $shippingDetail[] = [
                        'seller_name' => $sellerName,
                        'order_type' => $subCartType,
                        'fee' => $shippingFee
                    ];
                }
            }
        }
        if($isSaveDetail){
            $shippingAddress->setData('detail_shipping_fee',json_encode($shippingDetail));
            $this->addressResource->save($shippingAddress);
        }

        if ($shippingPrice !== false) {
            $method = $this->_rateMethodFactory->create();

            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));

            $method->setMethod($this->_code);
            $method->setMethodTitle($this->getConfigData('name'));

            $method->setCost($shippingPrice);
            if ($request->getFreeShipping() === true) {
                $shippingPrice = 0;
            }

            $method->setPrice($shippingPrice);
            $result->append($method);
        }

        return $result;
    }

    /**
     * getAllowedMethods
     *
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
