<?php

namespace Branch8\FlagshipStore\Model\Config\Source;

use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\ScopeInterface;

class Sellers extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    /**
     * @var \Amasty\Rolepermissions\Helper\Data
     */
    protected $rolePermissionHelper;
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $sellerCollection;
    /**
     * @var \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller\CollectionFactory
     */
    protected $flagshipStoreSellerCollection;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Amasty\Rolepermissions\Helper\Data $rolePermissionHelper
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollection
     * @param \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller\CollectionFactory $flagshipStoreSellerCollection
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Amasty\Rolepermissions\Helper\Data $rolePermissionHelper,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollection,
        \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller\CollectionFactory $flagshipStoreSellerCollection,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->rolePermissionHelper = $rolePermissionHelper;
        $this->sellerCollection = $sellerCollection;
        $this->flagshipStoreSellerCollection = $flagshipStoreSellerCollection;
        $this->request = $request;
    }

    public function toOptionArray()
    {
        $categories = $this->getSellers();
        $optionArr = [
//            ['value' => '', 'label' => __('Please select')]
        ];

        $optionArr = array_merge($optionArr, $categories);

        return $optionArr;
    }

    public function getSellers()
    {
        $rule = $this->rolePermissionHelper->currentRule();
        $sellerAccessMode = $rule->getSellerAccessMode();
        $sellers = $this->sellerCollection->create()
            ->addFieldToSelect(['seller_id', 'seller_code']);
        if($sellerAccessMode == Seller::MODE_ANY){
//            $sellers = $this->sellerCollection->create();
        }
        if($sellerAccessMode == Seller::MODE_SELECTED){
            if ($rule->getSellers()) {
                $sellers->addFieldToFilter('seller_id', ['in' => explode(',', $rule->getSellers())]);
            }else{
                $sellers->addFieldToFilter('seller_id', ['in' => []]);
            }

        }
        /**
         * Exclude sellers that in other flagship store
         */
        $currentFlagshipId = (int)$this->request->getParam('id', 0);
        if($currentFlagshipId ){

        }
        $flagshipStoreSeller = $this->flagshipStoreSellerCollection->create()
            ->addFieldToFilter('flagship_store_id', ['neq' => $currentFlagshipId])
            ->addFieldToSelect('seller_id');
        $flagshipStoreSellerExclude = $flagshipStoreSeller->getColumnValues('seller_id');

        foreach($sellers as $_seller){
            if(trim((string)$_seller->getSellerCode()) == ''){
                continue;
            }
            $itemSeller = [
                'label' => $_seller->getSellerCode(),
                'value' => $_seller->getSellerId()
            ];
            if(in_array($_seller->getSellerId(), $flagshipStoreSellerExclude)){
                $itemSeller['readonly']  = true;
            }
            $configArr[] = $itemSeller;
        }
        return $configArr;
    }
}