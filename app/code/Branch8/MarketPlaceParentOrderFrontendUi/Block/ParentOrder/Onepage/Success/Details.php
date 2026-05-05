<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\Onepage\Success;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Magento\Checkout\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\OrderFactory;
use Branch8\Customer\Helper\Info;

class Details extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Session
     */
    protected $session;

    protected $_orderFactory;
    protected $parentOrderManagement;
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress
     */
    protected $addressRenderer;

    /** @var CustomerRepositoryInterface */
    protected $customerRepository;

    protected $quoteFactory;

    /**
     * @var Info
     */
    protected $infoHelper;


    /**
     * @param Template\Context $context
     * @param Registry $registry
     * @param Session $session
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param OrderFactory $orderFactory
     * @param \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement $parentOrderManagement
     * @param \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $addressRenderer
     * @param CustomerRepositoryInterface $customerRepository
     * @param Info $infoHelper
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param array $data
     */
    public function __construct(
        Template\Context               $context,
        Registry                       $registry,
        Session                        $session,
        ParentOrderRepositoryInterface $parentOrderRepository,
        OrderFactory $orderFactory,
        \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagement $parentOrderManagement,
        \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $addressRenderer,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        Info $infoHelper,
        array                          $data = []
    )
    {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->customerRepository    = $customerRepository;
        $this->parentOrderrRepository = $parentOrderRepository;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->_orderFactory            = $orderFactory;
        $this->addressRenderer = $addressRenderer;
        $this->session = $session;
        $this->infoHelper = $infoHelper;
    }

    protected function _prepareLayout()
    {
        if (!$this->registry->registry('current_parent_order')) {
            $this->registry->register('current_parent_order', $this->getOrder());
        }

        return parent::_prepareLayout();
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|null
     */
    public function getOrder()
    {
        try {
            $id = (int)$this->session->getData('parentOrderId');
            return $this->parentOrderrRepository->get($id);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        return $this->registry->registry('current_parent_order');
    }

    /**
     * @param ParentOrderAddress $address
     * @return null
     */
    public function getFormattedAddress(ParentOrderAddress $address = null)
    {
        return $this->addressRenderer->getFormattedAddress($address);
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return $this->parentOrderManagement->getTotals($this->getParentOrder());
    }

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->parentOrderManagement->getPaymentHtml($this->getParentOrder());
    }

    /**
     * @param  int  $customerId
     *
     * @return string|null
     */
    public function getOneIdByCustomerId(int $customerId): string|null
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getCustomAttribute('member_seq')->getValue();
        }catch (\Exception $e) {
            return null;
        }
    }

    /***
     * @param $customerId
     * @return mixed|void|null
     */
    public function getCustomerName($customerId){
        try {
            $customer = $this->customerRepository->getById($customerId);
            $nicknameAttribute = $customer->getCustomAttribute('nickname');
            if ($nicknameAttribute && $nicknameAttribute->getValue()) {
                return $nicknameAttribute->getValue();
            }
            $firstname = $customer->getFirstname();
            if ($firstname) {
                return $this->encodeCustomerName($firstname);
            }
            return '';
        }catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Encode customer name (keep last character, replace others with 'O')
     * Example: "TestABC" becomes "OOOOOOC"
     *
     * @param string $name
     * @return string
     */
    private function encodeCustomerName($name)
    {
        $name = trim($name);
        $length = mb_strlen($name);
        if ($length <= 0) {
            return '';
        }
        // Get the last character
        $lastChar = mb_substr($name, -1);
        // Create string of 'O' characters with length-1
        $encoded = str_repeat('O', $length - 1);
        // Append the last character
        return $encoded . $lastChar;
    }

    /**
     * @return string
     */
    public function getOrderDetailUrl()
    {
        return $this->getUrl('sales/parentOrder/history', ['_query' => ['search' => $this->getOrder()->getDetail()->getData('increment_id')]]);
    }

    /**
     * @return string
     */
    public function getDownloadPdfUrl()
    {
        return $this->getUrl('sales/parentOrder/printPdf', ['id' => $this->getParentOrder()->getId()]);
    }

    /**
     * Format an address to display only street, city, region, postcode, and country.
     *
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function formatAddress($address)
    {
        if (!$address) {
            return __('Address is not available.');
        }

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

    public function getProcessedTelephone($telephone){
        return substr($telephone, 0, 3).'*****'.substr($telephone, -2);
    }

    /**
     * @param $createdAt
     * @return string
     */
    public function getOrderDate($createdAt)
    {
        try {
            return $this->_localeDate->date($createdAt)->format('Y/m/d H:i');
        }catch (\Exception $exception){
            return '';
        }
    }
}
