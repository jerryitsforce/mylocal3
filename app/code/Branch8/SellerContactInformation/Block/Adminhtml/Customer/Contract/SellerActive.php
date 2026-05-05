<?php
namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Contract;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;

class SellerActive  extends \Magento\Backend\Block\Template
{
    protected $_template = 'Branch8_SellerContactInformation::contract_active.phtml';

    protected $contractFilesFactory;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context,$data, $jsonHelper, $directoryHelper);
        $this->contractFilesFactory = $contractFilesFactory;
    }

    public function getContract(){
        $contractId = $this->getRequest()->getParam('cid');
        $contract = $this->contractFilesFactory->create()->load($contractId);
        return $contract;
    }
}
