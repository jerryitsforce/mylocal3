<?php

namespace Branch8\Customer\Block\Address;

use Branch8\Customer\Helper\AddressRenderer;
use Branch8\OneStepCheckout\Model\Source\HotaiAddressType;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Block\Address\Grid;
use Magento\Customer\Block\Address\Grid as AddressesGrid;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Helper\View;
use Magento\Customer\Model\Address\Config;
use Magento\Customer\Model\Address\Mapper;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Branch8\Customer\Helper\Info;

class Book extends \Magento\Customer\Block\Address\Book
{
    /**
     * Constants for info detail.
     */
    public const TAB_HOME_DELIVERY = 'home';
    public const TAB_CONVENIENCE = 'convenience_store';
    /**
     * @var AddressesGrid
     */
    private $addressesGrid;
    /**
     * @var CollectionFactory
     */
    protected $addressCollectionFactory;
    /**
     * @var Session
     */
    protected $_session;
    /**
     * @var View
     */
    protected $customerViewHelper;

    /**
     * @var AddressRenderer
     */
    private $addressRenderer;

    /**
     * @var Info
     */
    protected $infoHelper;

    /**
     * @param Context $context
     * @param CustomerRepositoryInterface|null $customerRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param CurrentCustomer $currentCustomer
     * @param Config $addressConfig
     * @param Mapper $addressMapper
     * @param CollectionFactory $addressCollectionFactory
     * @param Session $session
     * @param View $customerViewHelper
     * @param Info $infoHelper
     * @param AddressRenderer $addressRenderer
     * @param CustomerRepositoryInterface|null $customerRepository
     * @param AddressesGrid|null $addressesGrid
     * @param array $data
     */
    public function __construct(
        Context $context,
        AddressRepositoryInterface $addressRepository,
        CurrentCustomer $currentCustomer,
        Config $addressConfig,
        Mapper $addressMapper,
        CollectionFactory $addressCollectionFactory,
        Session $session,
        View $customerViewHelper,
        Info $infoHelper,
        AddressRenderer $addressRenderer,
        CustomerRepositoryInterface $customerRepository = null,
        Grid $addressesGrid = null,
        array $data = []
    ){
        parent::__construct($context, $customerRepository, $addressRepository, $currentCustomer, $addressConfig, $addressMapper, $data, $addressesGrid);
        $this->addressesGrid = $addressesGrid;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->_session = $session;
        $this->customerViewHelper = $customerViewHelper;
        $this->addressRenderer = $addressRenderer;
        $this->infoHelper = $infoHelper;
    }

    public function getAddressByType($addressType){
        $customerId = $this->_session->getCustomerId();
        $addressCol = $this->addressCollectionFactory->create()
            ->addAttributeToFilter('hotai_address_type', $addressType)
            ->addAttributeToFilter('parent_id', $customerId)
            ->addAttributeToSelect('*');
        return $addressCol;
    }

    public function getSortedList($addressCollection, $addressType) {
        $primaryAddress = $addressType == HotaiAddressType::ADDRESS_TYPE_NORMAL
            ? $this->getDefaultShipping()
            : $this->getDefaultConvenienceAddress();

        $addresses = [];
        foreach ($addressCollection as $address) {

            if ($address->getId() == $primaryAddress) {
                array_unshift($addresses , $address);
                continue;
            }

            $addresses[] = $address;
        }

        return $addresses;
    }

    public function getProcessedTelephone($telephone){
        return substr($telephone, 0, 3).'*****'.substr($telephone, -2);
    }

    public function getProcessedFullname(){
        return $this->customerViewHelper->getCustomerName($this->getCustomer());
    }

    /**
     * @param AddressInterface $address
     * @return string
     */
    public function renderHomeAddress($address)
    {
        $city = '';
        $region = '';

        if ($address->getRegionId() || $address->getRegion()) {
            $region = $address->getRegion();
        }

        if ($address->getCity()) {
            $city = $address->getCity();
        }

        $street = implode('', $address->getStreet());
        
        return $region . $city . $this->infoHelper->getOAuthStreet($street);
    }

    /**
     * @param AddressInterface $address
     * @return string
     */
    public function renderConvenienceAddress($address){

        $city = '';

        if ($address->getCity() || $address->getCvsStoreName()) {
            $city = $address->getCity();
        }

        $street = implode('', $address->getStreet());

        return '711' . $city . ' ' . $street;
    }

    /**
     * @param AddressInterface $address
     * @return string
     */
    public function getAddress($address){
        $city = '';
        $region = '';

        if ($address->getRegionId() || $address->getRegion()) {
            $region = $address->getRegion();
        }

        if ($address->getCity()) {
            $city = $address->getCity();
        }

        $street = implode('', $address->getStreet());
        
        return $region. $city. $this->infoHelper->getOAuthStreet($street);
    }

    
    /**
     * @param AddressInterface $address
     * @return string
     */
    public function getConvenienceAddress($address){
        $city = '';

        if ($address->getCity() || $address->getCvsStoreName()) {
            $city = $address->getCity();
        }

        $street = implode('', $address->getStreet());

        return '711' . $city . ' ' . $street;
    }

    public function getDefaultConvenienceAddress(){
        $customer = $this->getCustomer();
        return $customer->getCustomAttribute('default_convenience_store')?->getValue();
    }

     /**
     * Returns current tab.
     *
     * @return string
     */
    public function getCurrentTab(): string
    {
        return (string) $this->getRequest()->getParam('tab', self::TAB_HOME_DELIVERY);
    }

    /**
     * Returns current tab by number
     *
     * @return int
     */
    public function getCurrentTabByNum()
    {
        if($this->getCurrentTab() == self::TAB_CONVENIENCE) {
            return 1;
        }
        return 0;
    }
}