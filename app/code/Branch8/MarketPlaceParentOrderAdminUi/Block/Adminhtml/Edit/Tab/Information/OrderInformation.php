<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Tab\Information;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\Services\CalculateTotalParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress;
use Branch8\MarketPlaceParentOrder\Model\Services\SubOrderFinder;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Address\Config as AddressConfig;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\Address;

class OrderInformation extends \Magento\Backend\Block\Template
{
    const XML_PATH_SHOW_STATUS = 'parent_order/parent_order_configuration/show_status_admin';
    private $groupRepository;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    private $calculateTotalParentOrder;

    private $priceCurrency;

    private $paymentData;

    private $subOrderFinder;

    private $totals = null;
    /**
     * @var \Branch8\HotaiCore\Model\Config\Source\RmaStatus
     */
    protected $rmaStatusOptions;

    protected $flagshipHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Model\Order\StatusLabel $statusLabel
     * @param AddressConfig $addressConfig
     * @param GroupRepositoryInterface $groupRepository
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param CalculateTotalParentOrder $calculateTotalParentOrder
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param SubOrderFinder $subOrderFinder
     * @param FormatAddress $formatAddress
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Branch8\HotaiCore\Model\Config\Source\RmaStatus $rmaStatusOptions
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context           $context,
        \Magento\Framework\Registry                       $registry,
        \Magento\Sales\Model\Order\StatusLabel            $statusLabel,
        AddressConfig                                     $addressConfig,
        GroupRepositoryInterface                          $groupRepository,
        \Magento\Sales\Helper\Admin                       $adminHelper,
        CalculateTotalParentOrder                         $calculateTotalParentOrder,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Payment\Helper\Data                      $paymentData,
        SubOrderFinder                                    $subOrderFinder,
        FormatAddress                                     $formatAddress,
        ParentOrderManagementInterface                    $parentOrderManagement,
        \Branch8\HotaiCore\Model\Config\Source\RmaStatus $rmaStatusOptions,
        \Branch8\FlagshipStore\Helper\Sales $flagshipHelper,
        array                                             $data = []
    )
    {
        $this->groupRepository = $groupRepository;
        $this->statusLabel = $statusLabel;
        $this->_coreRegistry = $registry;
        $this->addressConfig = $addressConfig;
        $this->_adminHelper = $adminHelper;
        $this->priceCurrency = $priceCurrency;
        $this->calculateTotalParentOrder = $calculateTotalParentOrder;
        $this->paymentData = $paymentData;
        $this->subOrderFinder = $subOrderFinder;
        $this->fomatAddress = $formatAddress;
        $this->parentOrderManagement = $parentOrderManagement;
        parent::__construct($context, $data);
        $this->rmaStatusOptions = $rmaStatusOptions;
        $this->flagshipHelper = $flagshipHelper;
    }

    /**
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        return $this->_coreRegistry->registry('parent_order');
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface|null
     */
    public function getDetail()
    {
        return $this->getParentOrder()->getExtensionAttributes()->getDetail();
    }

    /**
     * @param $status
     * @return string|null
     */
    public function getStatuslabel($status)
    {
        return $this->statusLabel->getStatusLabel($status);
    }

    /**
     * @param $createdAt
     * @return \DateTime
     * @throws \Exception
     */
    public function getOrderAdminDate($createdAt)
    {
        return $this->_localeDate->date(new \DateTime($createdAt));
    }

    /**
     * @return string
     */
    public function getCustomerViewUrl()
    {
        if (!$this->getParentOrder()->getDetail()->getCustomerId()) {
            return '';
        }

        return $this->getUrl('customer/index/edit', ['id' => $this->getParentOrder()->getDetail()->getCustomerId()]);
    }

    public function getCustomerGroupName()
    {
        if ($this->getParentOrder()) {
            $customerGroupId = $this->getParentOrder()->getDetail()->getCustomerGroupId();
            try {
                if ($customerGroupId !== null) {
                    return $this->groupRepository->getById($customerGroupId)->getCode();
                }
            } catch (NoSuchEntityException $e) {
                return '';
            }
        }
        return '';
    }

    /**
     * Returns string with formatted address
     *
     * @param Address $address
     * @return null|string
     */
    public function getFormattedAddress(ParentOrderAddress $address = null)
    {
        if (!$address) {
            return '';
        }
        return $this->fomatAddress->getFormattedAddress($address);
    }

    /**
     * @param ParentOrder $parentOrder
     * @return string
     */
    public function totalShippingLabel(ParentOrder $parentOrder)
    {
        $totalShipping = $this->calculateTotalParentOrder->shipping($parentOrder);
        $flagshipOrderProcessValue = $this->flagshipHelper->getProcessOrderValues($parentOrder->getDetail()->getParentId());
        if ($totalShipping['isDisplayIncludeTax']) {
            if(isset($flagshipOrderProcessValue['subtotal_incl_tax'])){
                $totalValue = $totalShipping['incl'] + $flagshipOrderProcessValue['subtotal_incl_tax'];
            }else{
                $totalValue = $totalShipping['incl'];
            }
        } else {
            if(isset($flagshipOrderProcessValue['subtotal'])){
                $totalValue = $totalShipping['excl'] + $flagshipOrderProcessValue['subtotal'];
            }else{
                $totalValue = $totalShipping['excl'];
            }
        }
        $totalLabel = $this->displayPrices($totalValue);
        return $totalLabel;
    }

    /**
     * @param $price
     * @param $strong
     * @return string
     */
    public function displayPrices($price, $strong = false)
    {
        $res = $this->priceCurrency->format($this->escapeHtml($price));
        if ($strong) {
            $res = '<strong>' . $res . '</strong>';
        }
        return $res;
    }

    /**
     * @return string
     */
    public function getPaymentInformation()
    {
        $info = '';
        try {
            $info = $this->parentOrderManagement->getPaymentHtml($this->getParentOrder());
        } catch (\Exception $exception) {
            $this->_logger->critical($exception->getMessage());
        }
        return $info;
    }

    /**
     * @return array|mixed
     */
    public function getSubOrders()
    {
        return $this->subOrderFinder->find($this->getParentOrder());
    }

    /**
     * @return mixed|null
     */
    public function getTotals($area = null)
    {
        $all = $this->_totals();
        if ($area === null) {
            return $this->totals;
        } else {
            $totals = [];
            $area = (string)$area;
            foreach ($all as $total) {
                $totalArea = (string)$total->getArea();
                if ($totalArea == $area) {
                    $totals[] = $total;
                }
            }
        }
        return $totals;
    }

    /**
     * @return array|mixed
     */
    private function _totals()
    {
        if ($this->totals === null) {
            $this->totals = $this->parentOrderManagement->getTotals($this->getParentOrder());
        }
        return $this->totals;
    }

    /**
     * @return bool
     */
    public function showStatus()
    {
        return (bool)$this->_scopeConfig->getValue(self::XML_PATH_SHOW_STATUS);
    }

    public function getRMAStatus(){
        $rmaStatus = $this->getParentOrder()->getDetail()->getRmaStatus();
        if(!$rmaStatus){
            return 'N/A';
        }
        $options = $this->rmaStatusOptions->getOptionArray();
        if(isset($options[$rmaStatus])){
            return $options[$rmaStatus];
        }
        return 'N/A';
    }
}
