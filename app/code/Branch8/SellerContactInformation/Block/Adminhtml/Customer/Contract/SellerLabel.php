<?php
namespace Branch8\SellerContactInformation\Block\Adminhtml\Customer\Contract;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;

class SellerLabel  extends \Magento\Backend\Block\Template
{
    protected $_template = 'Branch8_SellerContactInformation::seller_html.phtml';

    protected $_conn;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context,$data, $jsonHelper, $directoryHelper);
        $this->_conn = $resourceConnection->getConnection();
    }

    public function getSeller(){
        $sellerId = $this->getRequest()->getParam('seller_id');
        $sellerSql = 'select entity_id, name from customer_grid_flat where entity_id='.$sellerId;
        $seller = $this->_conn->fetchRow($sellerSql);
        return $seller;
    }

    public function isContractActive($cid){
        if(!(int)$cid){
            return false;
        }
        $sql = 'select is_active from seller_contract_files where entity_id='.$cid;
        $isActive = $this->_conn->fetchOne($sql);
        return (int)$isActive;
    }
}
