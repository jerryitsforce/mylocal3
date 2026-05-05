<?php

namespace Branch8\Checkout\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Quote\Model\QuoteRepository;

class Data extends AbstractHelper
{
    const PLACEHOLDER_ADDRESS_FIRSTNAME = 'checkout/placeholder_address/firstname';

    const PLACEHOLDER_ADDRESS_TELEPHONE = 'checkout/placeholder_address/telephone';

    const PLACEHOLDER_ADDRESS_REGION_ID = 'checkout/placeholder_address/region_id';

    const PLACEHOLDER_ADDRESS_CITY = 'checkout/placeholder_address/city';

    const PLACEHOLDER_ADDRESS_STREET = 'checkout/placeholder_address/street';

    const PLACEHOLDER_ADDRESS_LASTNAME = 'checkout/placeholder_address/lastname';

    protected HttpContext $httpContext;

    protected $customerSession;

    protected $countryModel;

    protected $quoteRepository;

    /** 
     * @var \Branch8\HotaiCore\Helper\VirtualProduct
     */
    protected $virtualHelper;

    public function __construct(
        Context $context,
        Session $customerSession,
        HttpContext $httpContext,
        \Magento\Directory\Model\Country $countryModel,
        QuoteRepository $quoteRepository,
        \Branch8\HotaiCore\Helper\VirtualProduct $virtualHelper
    ){
        parent::__construct($context);
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
        $this->countryModel = $countryModel;
        $this->quoteRepository = $quoteRepository;
        $this->virtualHelper = $virtualHelper;
    }

    public function isCustomerNoAddress()
    {
        $addressesCollection = $this->customerSession
            ->getCustomer()
            ->getAddressesCollection();
        if(!$addressesCollection->getSize()){
            return true;
        }
        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    public function setPlaceHolderAddress(\Magento\Quote\Model\Quote $quote)
    {
        $billingAddress = $quote->getBillingAddress();
        $billingPlaceHolderData = $this->getPlaceHolderData();

        $billingAddress->setSaveInAddressBook($billingPlaceHolderData['save_in_address_book']);
        $billingAddress->setFirstname($billingPlaceHolderData['firstname']);
        $billingAddress->setLastname($billingPlaceHolderData['lastname']);
        $billingAddress->setCountryId($billingPlaceHolderData['country_id']);
        $billingAddress->setTelephone($billingPlaceHolderData['telephone']);
        $billingAddress->setStreet($billingPlaceHolderData['street']);
        $billingAddress->setRegionId($billingPlaceHolderData['region_id']);
        $billingAddress->setRegion($billingPlaceHolderData['region']);
        $billingAddress->setCity($billingPlaceHolderData['city']);

        $quote->setBillingAddress($billingAddress);
        // $this->quoteRepository->save($quote);
    }

    public function getPlaceHolderData()
    {
        $countryCode = 'TW';
        $regionId = $this->scopeConfig->getValue(self::PLACEHOLDER_ADDRESS_REGION_ID);
        $regionModel = $this->countryModel
            ->loadByCode($countryCode)
            ->getRegionCollection()
            ->addFieldToFilter('main_table.region_id', $regionId)
            ->getFirstItem();

        $billingData = [
            'save_in_address_book' => 0,
            'country_id' => 'TW',
            'firstname' => $this->scopeConfig->getValue(self::PLACEHOLDER_ADDRESS_FIRSTNAME),
            'lastname' => $this->scopeConfig->getValue(self::PLACEHOLDER_ADDRESS_LASTNAME),
            'telephone' => $this->scopeConfig->getValue(self::PLACEHOLDER_ADDRESS_TELEPHONE),
            'street' => [$this->scopeConfig->getValue(self::PLACEHOLDER_ADDRESS_STREET)],
            'region_id' => $regionId,
            'region' => $regionModel->getDefaultName(),
            'city' => $this->scopeConfig->getValue(self::PLACEHOLDER_ADDRESS_CITY),
        ];
        return $billingData;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quotes
     * @return bool
     */
    public function isPlaceHodlerBilling(\Magento\Quote\Model\Quote $quotes)
    {
        $billingAddress = $quotes->getBillingAddress();
        $billingAddressSaved = [
            'country_id' => 'TW',
            'firstname' => $billingAddress->getFirstname(),
            'lastname' => $billingAddress->getLastname(),
            'telephone' => $billingAddress->getTelephone(),
            'street' => $billingAddress->getStreet(),
            'region_id' => $billingAddress->getRegionId(),
            'region' => $billingAddress->getRegion(),
            'city' => $billingAddress->getCity()
        ];

        $placeHolderAddress = $this->getPlaceHolderData();
        unset($placeHolderAddress['save_in_address_book']);
        if(!count(@array_diff($placeHolderAddress, $billingAddressSaved))
        ){
            return true;
        }
        return false;
    }

    public function clearBillingAddress(\Magento\Quote\Model\Quote $quote){
        $billingAddress = $quote->getBillingAddress();

        $billingAddress->setSaveInAddressBook(0);
        $billingAddress->setFirstname(NULL);
        $billingAddress->setLastname(NULL);
        $billingAddress->setCountryId(NULL);
        $billingAddress->setTelephone(NULL);
        $billingAddress->setStreet(NULL);
        $billingAddress->setRegionId(NULL);
        $billingAddress->setRegion(NULL);
        $billingAddress->setCity(NULL);

        $quote->setBillingAddress($billingAddress);
        $this->quoteRepository->save($quote);
    }

    public function checkIfQuantityEnoughByCustomOptionAndRequestQuantity(
        int|string $productId,
        string $customOptionValue,
        int|string $requestQuantity
    ) {
        return $this->virtualHelper->checkIfQuantityEnoughByCustomOptionAndRequestQuantity(
            $productId,
            $customOptionValue,
            $requestQuantity
        );
    }

    public function getTicketAvailableBatchData(
        int|string $productId
    ) {
        return $this->virtualHelper->getTicketAvailableBatchData($productId);
    }
}