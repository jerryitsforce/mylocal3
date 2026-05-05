<?php

namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab;

class Commission extends \Webkul\Marketplace\Block\Adminhtml\Customer\Edit\Tab\Commission
{
    public const COMM_TEMPLATE = 'Branch8_SellerContactInformation::customer/commission.phtml';

    protected $blockGrid;

    /**
     * @var \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory
     */
    protected $contractFilesCollectionFactory;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        \Branch8\SellerContactInformation\Model\ResourceModel\ContractFiles\CollectionFactory $contractFilesCollectionFactory,
        array $data = [],
        \Magento\Framework\Pricing\Helper\Data $pricingHelper = null
    ) {
        $this->_coreRegistry = $registry;
        $this->customerEdit = $customerEdit;
        $this->pricingHelper = $pricingHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
        ->get(\Magento\Framework\Pricing\Helper\Data::class);
        $this->contractFilesCollectionFactory = $contractFilesCollectionFactory;
        parent::__construct($registry, $context, $customerEdit, $data, $pricingHelper);
    }

    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                \Branch8\SellerContactInformation\Block\Adminhtml\Customer\Edit\Tab\Grid\ContractFiles::class, 'contracts.files.grid');
        }
        return $this->blockGrid;
    }

    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * @return array
     */
    public function getSellerInfo()
    {
        $partner = $this->customerEdit->getSellerInfoCollection();
        return $partner;
    }

    /**
     * @return mixed
     */
    public function getDateFormat()
    {
        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        return $dateFormat;
    }

    public function getActiveContracts($sellerId){
        $collection = $this->contractFilesCollectionFactory->create();
        $collection->addFieldToFilter('seller_id', ['eq' => $sellerId])
            ->addFieldToFilter('is_active', 1);
        $contract = $collection->getFirstItem();
        return $contract;
    }

    public function getPartnerCollection(){
        return $this->customerEdit->getSalesPartnerCollection();
    }

    public function getConfigCommissionRate(){
        return $this->customerEdit->getConfigCommissionRate();
    }

}
