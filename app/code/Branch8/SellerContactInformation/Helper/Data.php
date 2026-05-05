<?php

namespace Branch8\SellerContactInformation\Helper;

use Branch8\Sales\Model\Product\Attribute\Source\PreservationStatus;
use Magento\Authorization\Model\ResourceModel\Role\Grid\CollectionFactory;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Block\Adminhtml\Customer\Edit;
use Webkul\Marketplace\Model\SaleperpartnerFactory;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serialize;

    /**
     * @var \Webkul\Marketplace\Block\Adminhtml\Customer\Edit
     */
    protected $customerEdit;

    /**
     * @var \Webkul\Marketplace\Model\SaleperpartnerFactory
     */
    protected $saleperpartnerFactory;

     /**
     * @var \Branch8\Sales\Model\Product\Attribute\Source\PreservationStatus
     */
    protected $preservationStatusSource;

    /**
     * @var \Magento\Authorization\Model\ResourceModel\Role\Grid\CollectionFactory
     */
    protected $roleCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;


    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param Json $serialize
     * @param Edit $customerEdit
     * @param SaleperpartnerFactory $saleperpartnerFactory
     * @param PreservationStatus $preservationStatusSource ,
     * @param CollectionFactory $roleCollectionFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Json $serialize,
        \Webkul\Marketplace\Block\Adminhtml\Customer\Edit $customerEdit,
        \Webkul\Marketplace\Model\SaleperpartnerFactory $saleperpartnerFactory,
        \Branch8\Sales\Model\Product\Attribute\Source\PreservationStatus $preservationStatusSource,
        \Magento\Authorization\Model\ResourceModel\Role\Grid\CollectionFactory $roleCollectionFactory,
        ResourceConnection $resourceConnection,
    ){
        $this->storeManager = $storeManager;
        $this->serialize = $serialize;
        $this->customerEdit = $customerEdit;
        $this->saleperpartnerFactory = $saleperpartnerFactory;
        $this->preservationStatusSource = $preservationStatusSource;
        $this->roleCollectionFactory = $roleCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getStoreid()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return array|void
     */
    public function getPreservationStatusOptions(){
        return $this->preservationStatusSource->getAllOptions();
    }

    /**
     * @return array|void
     */
    public function getAdminRolesArrayOptions()
    {
        $role = $this->roleCollectionFactory->create();
        $result = array();
        foreach ($role as $key => $role) {
            $data = [];
            $data['value'] = $role->getData('role_id');
            $data['label'] = $role->getData('role_name');
            $result[] = $data;
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getCommissionData($sellerId)
    {
        $collection = $this->saleperpartnerFactory->create()->getCollection()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('commission_status', 1)
            ->addFieldToSelect('min_commission_rate')
            ->addFieldToSelect('commission_rate');

        $commission = $collection->getFirstItem();
        $commissionRate = (int)$commission->getCommissionRate();
        $minCommissionRate = (int)$commission->getMinCommissionRate();
        // if ($commissionRate === null) {
        //     $commissionRate = $this->customerEdit->getConfigCommissionRate();
        // }
        $tsale = 0;
        $tcomm = 0;
        $tact = 0;
        $collection1 = $this->customerEdit->getSalesListCollection();
        foreach ($collection1 as $key) {
            $tsale += $key->getTotalAmount();
            $tcomm += $key->getTotalCommission();
            $tact += $key->getActualSellerAmount();
        }
        return [
            'total_sale' => $tsale,
            'total_comm' => $tcomm,
            'actual_seller_amt' => $tact,
            'current_val' => $commissionRate,
            'min_commission_rate' => $minCommissionRate
        ];
    }

    /**
     * @param $sellerId
     * @return array|mixed
     */
    public function getSellerData($sellerId): mixed
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from('marketplace_userdata')
            ->where('seller_id = ?', $sellerId)
            ->limit(1);

        $result = $connection->fetchRow($select);
        if ($result === false) {
            return [];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllSellerDataWithNotification(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(['mu' => 'marketplace_userdata'])
            ->joinLeft(
                ['ce' => 'customer_entity'],
                'mu.seller_id = ce.entity_id',
                ['email']
            )
            ->where('mu.enable_low_notification = 1 AND mu.is_seller = 1');

        return $connection->fetchAll($select) ?: [];
    }
}
