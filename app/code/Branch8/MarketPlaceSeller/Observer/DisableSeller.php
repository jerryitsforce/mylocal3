<?php

namespace Branch8\MarketPlaceSeller\Observer;

use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Webkul\Marketplace\Model\ProductFactory as ProductModel;
use Magento\Framework\Event\ObserverInterface;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;

class DisableSeller implements ObserverInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var ProductModel
     */
    protected $productModel;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ProductAction
     */
    protected $productAction;
    /**
     * @var Processor
     */
    protected $_productPriceIndexerProcessor;
    /**
     * @var MpHelper
     */
    protected $mpHelper;
    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerModel;

    protected $userDataCollection;

    /**
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param ProductModel $productModel
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param ProductAction $productAction
     * @param Processor $productPriceIndexerProcessor
     * @param MpHelper $mpHelper
     * @param \Magento\Customer\Model\CustomerFactory $customerModel
     * @param MpEmailHelper $mpEmailHelper
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        ProductModel $productModel,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        ProductAction $productAction,
        Processor $productPriceIndexerProcessor,
        MpHelper $mpHelper,
        \Magento\Customer\Model\CustomerFactory $customerModel,
        MpEmailHelper $mpEmailHelper,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $userDataCollection
    ){
        $this->_collectionFactory = $collectionFactory;
        $this->_date = $date;
        $this->productModel = $productModel;
        $this->_storeManager = $storeManager;
        $this->productAction = $productAction;
        $this->_productPriceIndexerProcessor = $productPriceIndexerProcessor;
        $this->mpHelper = $mpHelper;
        $this->customerModel = $customerModel;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->_eventManager = $eventManager;
        $this->userDataCollection = $userDataCollection;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $customer = $observer->getCustomer();
        $customerid = $customer->getId();
        $isDisableSeller = $request->getPost('is_seller_disable');
        if ($isDisableSeller && $isDisableSeller == 1 && $this->isSeller($customerid)) {
            //disapprove
            $sellerData = $this->userDataCollection->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('seller_id', $customerid)
                ->addFieldToFilter('is_seller', 1)
                ->getFirstItem();
            $this->disableSeller($sellerData);
        }
    }

    public function isSeller($customerid)
    {
//        $sellerStatus = 0;
        $model = $this->_collectionFactory->create()
            ->addFieldToFilter('seller_id', $customerid)
            ->addFieldToFilter('store_id', 0);
//        foreach ($model as $value) {
//            $sellerStatus = $value->getIsSeller();
//        }

//        return $sellerStatus;
        if($model->getSize()){
            return true;
        }else{
            return false;
        }
    }

    protected function disableSeller($item){
        $sellerStatus = \Webkul\Marketplace\Model\Seller::STATUS_DISABLED;
        $status = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
        $item->setIsSeller($sellerStatus);
        $item->setUpdatedAt($this->_date->gmtDate());
        $item->save();
        $sellerProduct = $this->productModel->create()
            ->getCollection()
            ->addFieldToFilter('seller_id', $item->getSellerId());

        if ($sellerProduct->getSize()) {
            $productIds = $sellerProduct->getAllIds();
            $coditionArr = [];
            foreach ($productIds as $key => $id) {
                $condition = "`mageproduct_id`=".$id;
                array_push($coditionArr, $condition);
            }
            $coditionData = implode(' OR ', $coditionArr);

            $sellerProduct->setProductData(
                $coditionData,
                ['status' => $status]
            );
            $allStores = $this->_storeManager->getStores();
            foreach ($allStores as $store) {
                $storeId = $store->getData('store_id');
                $this->productAction->updateAttributes(
                    $productIds,
                    ['status' => $status],
                    $storeId
                );
            }

            $this->productAction->updateAttributes($productIds, ['status' => $status], 0);

            $this->_productPriceIndexerProcessor->reindexList($productIds);
        }
        $helper = $this->mpHelper;
        $adminStoremail = $helper->getAdminEmailId();
        $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
        $adminUsername = $helper->getAdminName();
        $customerModel = $this->customerModel->create();
        $seller = $customerModel->load($item->getSellerId());

        $emailTempVariables['myvar1'] = $seller->getName();
        $emailTempVariables['myvar2'] = $this->_storeManager->getStore()->getBaseUrl().'customer/account/login';
        $senderInfo = [
            'name' => $adminUsername,
            'email' => $adminEmail,
        ];
        $receiverInfo = [
            'name' => $seller->getName(),
            'email' => $seller->getEmail(),
        ];
        $this->mpEmailHelper->sendSellerDisapproveMail(
            $emailTempVariables,
            $senderInfo,
            $receiverInfo
        );
        $this->_eventManager->dispatch(
            'mp_disapprove_seller',
            ['seller' => $seller]
        );
    }
}