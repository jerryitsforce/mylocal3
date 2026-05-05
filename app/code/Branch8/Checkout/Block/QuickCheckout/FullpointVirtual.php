<?php
namespace Branch8\Checkout\Block\QuickCheckout;

use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Quote\Api\Data\AddressInterface;

class FullpointVirtual extends \Magento\Checkout\Block\Cart\Item\Renderer
{
    private $itemResolver;

    protected $quoteRepository;

    protected $dataObjectProcessor;

    protected $customerRepository;

    protected $addressRepository;

    protected $regionCollectionFactory;

    protected $checkoutHelperData;

    protected $configProvider;

    protected $cartTotal;

    protected $pointMoneyConfigHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Helper\Product\Configuration $productConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Module\Manager $moduleManager,
        InterpretationStrategyInterface $messageInterpretationStrategy,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        DataObjectProcessor $dataObjectProcessor,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionCollectionFactory,
        \Branch8\Checkout\Helper\Data $checkoutHelperData,
        \Magento\Checkout\Model\CompositeConfigProvider $configProvider,
        \Magento\Quote\Model\Cart\CartTotalRepository $cartTotal,
        \Branch8\PointMoneyConfig\Helper\Common $pointMoneyConfigHelper,
        array $data = [],
        ItemResolverInterface $itemResolver = null
    ) {
        parent::__construct($context, $productConfig, $checkoutSession, $imageBuilder, $urlHelper,
        $messageManager, $priceCurrency, $moduleManager, $messageInterpretationStrategy, $data, $itemResolver);
        $this->itemResolver = $itemResolver ?: ObjectManager::getInstance()->get(ItemResolverInterface::class);
        $this->quoteRepository = $quoteRepository;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->customerRepository = $customerRepository;
        $this->addressRepository = $addressRepository;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->checkoutHelperData = $checkoutHelperData;
        $this->configProvider= $configProvider;
        $this->cartTotal = $cartTotal;
        $this->pointMoneyConfigHelper  = $pointMoneyConfigHelper;
    }

    public function calcTotal(){
        $quote = $this->getItem()->getQuote();
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $this->quoteRepository->save($quote);
    }

    public function getItem(){
        return $this->getData('quoteItem');
    }

    public function getImage($product, $imageId, $attributes = [])
    {
        return $this->imageBuilder->create($product, $imageId, $attributes);
    }

    public function getProductForThumbnail()
    {
        return $this->itemResolver->getFinalProduct($this->getItem());
    }

    public function toHtml(
    ) {
        $this->setTemplate('Branch8_Checkout::quick_checkout/fullpoint_virtual_confimation.phtml');
        return parent::toHtml();
    }

    public function getBillingAddress(){
        $quote = $this->getItem()->getQuote();
        $billingAddress = $quote->getBillingAddress();
        $billingAddressArray = $this->dataObjectProcessor->buildOutputDataArray($billingAddress, AddressInterface::class);
        unset($billingAddressArray['id']);
        return $billingAddressArray;
    }

    public function prepairCheckoutData(){
        $quote = $this->getItem()->getQuote();
        $cartId = $quote->getId();
        $customerId = $quote->getCustomerId();
        $customer = $this->customerRepository->getById($customerId);
        $addressId = $customer->getDefaultBilling();
        if(!$addressId){
            $addressId = $customer->getDefaultShipping();
        }

        if(!$addressId){
            //Load Place holder address
            $addressArr = $this->checkoutHelperData->getPlaceHolderData();

            $regionData = $this->regionCollectionFactory->create()
                    ->addFieldToFilter('main_table.region_id', $addressArr['region_id'])
                    ->getFirstItem();
            $addressArr['postcode'] = '000';
            $addressArr['regionCode'] = $regionData->getCode();
            $addressArr['regionId'] = $addressArr['region_id'];
            $addressArr['customAttributes'] = [
                ['attribute_code' => 'cvs_store_outside', 'value' => '0'],
                ['attribute_code' => 'hotai_address_type', 'value' => 'normal', 'label' => '常溫']
            ];
            $addressArr['saveInAddressBook'] = null;
        }else{
            try {
                $address = $this->addressRepository->getById($addressId);
                $regionData = $this->regionCollectionFactory->create()
                    ->addFieldToFilter('main_table.region_id', $address->getRegionId())
                    ->getFirstItem();
                $addressArr = [
                    'customerAddressId' => $address->getId(),
                    'countryId' => $address->getCountryId(),
                    'regionId' => $address->getRegionId(),
                    'regionCode' => $regionData->getCode(),
                    'region' => $address->getRegion()->getRegion(),
                    'customerId' => $customerId,
                    'street' => $address->getStreet(),
                    'company' => $address->getCompany(),
                    'telephone' => $address->getTelephone(),
                    'fax' => $address->getFax(),
                    'postcode' => $address->getFax(),
                    'city' => $address->getCity(),
                    'firstname' => $address->getFirstname(),
                    'lastname' =>$address->getLastname(),
                    'middlename' => $address->getMiddlename(),
                    'prefix' => $address->getPrefix(),
                    'suffix' => $address->getSuffix(),
                    'vatId' => $address->getVatId(),
                    'customAttributes' => [
                        ['attribute_code' => 'cvs_store_outside', 'value' => '0'],
                        ['attribute_code' => 'hotai_address_type', 'value' => 'normal', 'label' => '常溫']
                    ],
                    'saveInAddressBook' => null
                ];
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Address not found
            }
        }
    
        $paymentMethod = [
            'method' => 'free',
            'po_number' => null,
            'additional_data' => null,
            'extension_attributes' => [
                'referrer_code' => '',
                'order_note' => '',
                'ecpay_invoice_carruer_type' => '0',
                'ecpay_invoice_type' => 'p',
                'ecpay_invoice_customer_identifier' => '',
                'ecpay_invoice_customer_company' => '',
                'ecpay_invoice_love_code' => '',
                'ecpay_invoice_carruer_num' => ''
            ]            
        ];

        return [
            'cartId' => $cartId,
            'billingAddress' => $addressArr,
            'paymentMethod' => $paymentMethod
        ];
    }

    public function getCheckoutConfig()
    {
        return $this->configProvider->getConfig();
    }

    public function getTotals(){
        $quoteId = $this->getItem()->getQuote()->getId();
        $total = $this->cartTotal->get($quoteId);
        $totalData = $total->getData();
    }

    public function convertToPoint($value){
        $currencyChar = $this->priceCurrency->getCurrency()->getCurrencySymbol();
        $value = str_replace([$currencyChar, ','], '', (string)$value);
        $ratio = $this->pointMoneyConfigHelper->getRatio();
        if($ratio == 0){
            return $value;
        }
        $point = number_format(bcdiv($value, $ratio, 0), '0', '.', ',');

        return $point;
    }
}
