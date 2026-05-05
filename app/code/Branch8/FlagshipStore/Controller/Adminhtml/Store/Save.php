<?php

namespace Branch8\FlagshipStore\Controller\Adminhtml\Store;

class Save extends \Magento\Backend\App\Action
{
    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;
    /**
     * @var \Branch8\FlagshipStore\Model\FlagshipStoreFactory
     */
    protected $flagshipStoreFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Branch8\FlagshipStore\Model\FlagshipStoreSellerFactory
     */
    protected $flagshipStoreSellerFactory;
    /**
     * @var \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller\CollectionFactory
     */
    protected $flagshipStoreSellerCollectionFactory;

    protected $saveFlaghipHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Branch8\FlagshipStore\Model\FlagshipStoreFactory $flagshipStoreFactory
     * @param \Branch8\FlagshipStore\Model\FlagshipStoreSellerFactory $flagshipStoreSellerFactory
     * @param \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller\CollectionFactory $flagshipStoreSellerCollectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\FlagshipStore\Model\FlagshipStoreFactory $flagshipStoreFactory,
        \Branch8\FlagshipStore\Model\FlagshipStoreSellerFactory $flagshipStoreSellerFactory,
        \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStoreSeller\CollectionFactory $flagshipStoreSellerCollectionFactory,
        \Branch8\FlagshipStore\Helper\SaveFlaghip $saveFlaghipHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->flagshipStoreFactory = $flagshipStoreFactory;
        $this->timezone = $timezone;
        $this->flagshipStoreSellerFactory = $flagshipStoreSellerFactory;
        $this->flagshipStoreSellerCollectionFactory = $flagshipStoreSellerCollectionFactory;
        $this->saveFlaghipHelper = $saveFlaghipHelper;
        parent::__construct($context);
    }
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute(){
        $resultPage = $this->resultPageFactory->create();
        $postData = $this->getRequest()->getPost();
        $updateData = [];
        $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        if(isset($postData['entity_id']) && (int)$postData['entity_id'] != 0){
            /**
             * Validate for update
             */
            $isNewFlagShipStore = false;
            $flagshipStore = $this->flagshipStoreFactory->create()->load($postData['entity_id']);
            $flagshipStore->setUpdatedAt($currentTime);
        }else{
            /**
             * Validate for new
             */
            $isNewFlagShipStore = true;
            $flagshipStore = $this->flagshipStoreFactory->create();
            $flagshipStore->setCreatedAt($currentTime);
        }
        $flagshipStore->setCategoryId($postData['category_id']);
        $flagshipStore->setStoreName($postData['store_name']);
        $flagshipStore->setIsActive($postData['is_active']);
        $flagshipStore->setMainSeller($postData['main_seller']);
        try {
            $flagshipStore->save();
            $connection = $this->flagshipStoreSellerCollectionFactory->create()->getConnection();
            $flagshipStoreId = $flagshipStore->getId();
            $submitedSellerIds = $postData['seller_ids'];
            foreach ($submitedSellerIds as $skey => $sid){
                if($sid == ''){
                    unset($submitedSellerIds[$skey]);
                }
            }
            if(!in_array($postData['main_seller'], $submitedSellerIds)){
                $submitedSellerIds[] = $postData['main_seller'];
            }
            $connection->beginTransaction();



            if ($isNewFlagShipStore) {
                /**
                 * New
                 */
                if (count($submitedSellerIds)) {
                    foreach ($submitedSellerIds as $sellerId) {
                        $sqlFlagshipStoreInsertSellerNew = 'insert into flagship_store_seller values(NULL, '.$flagshipStoreId.', '.$sellerId.', "'.$currentTime.'");';
                        $connection->query($sqlFlagshipStoreInsertSellerNew);
                    }
                    /**
                     * Update other logic
                     * 1. Asign Category and sub category to  seller category
                     * 2. Create a virtual fake product and assign to main seller(for calculate shipping fee)
                     *
                     * /
                    /* 1 */
                    $this->saveFlaghipHelper->autoAssignFlagshipCategoryToSeller($flagshipStore->getCategoryId(), $submitedSellerIds, $connection);
                    /* 2 */
                    $this->saveFlaghipHelper->assignVirtualFakeProduct($postData['main_seller'], $connection);
                }
            } else {
                /**
                 * Update
                 */
                $existedSeller = $this->flagshipStoreSellerCollectionFactory->create()
                    ->addFieldToSelect('seller_id')
                    ->addFieldToFilter('flagship_store_id', $flagshipStoreId);
                $existedSellerIds = $existedSeller->getColumnValues('seller_id');
                $sellerNeedToRemove = array_diff($existedSellerIds, $submitedSellerIds);
                $sellerNeedToAdd = array_diff($submitedSellerIds, $existedSellerIds);
                foreach($sellerNeedToRemove as $_rmSellerItem){
                    $sqlRemove = 'delete from flagship_store_seller where flagship_store_id='.$flagshipStoreId.' and seller_id='.$_rmSellerItem;
                    $connection->query($sqlRemove);
                }
                foreach($sellerNeedToAdd as $_addSellerItem){
                    $sqlAdd = 'insert into flagship_store_seller values(NULL, '.$flagshipStoreId.', '.$_addSellerItem.', "'.$currentTime.'");';
                    $connection->query($sqlAdd);
                }
                /**
                 * Update other logic
                 * 1. Asign Category and sub category to  seller category
                 * 2. Create a virtual fake product and assign to main seller(for calculate shipping fee)
                 * 3. Remove flagship category if seller is not in flagship store
                 * 4. Unset Flagship category value from product
                 */
                /* 1 */
                $this->saveFlaghipHelper->autoAssignFlagshipCategoryToSeller($flagshipStore->getCategoryId(), $submitedSellerIds, $connection);
                /* 2 */
                $this->saveFlaghipHelper->assignVirtualFakeProduct($postData['main_seller'], $connection);
                if(!empty($sellerNeedToRemove)) {
                    /* 3 */
                    $this->saveFlaghipHelper->removeFlagshipCategoryFromSeller($sellerNeedToRemove, $connection);
                    /* 4 */
                    $this->saveFlaghipHelper->removeFlagshipCategoryFromSellerProduct($sellerNeedToRemove, $connection);
                }
            }
            $connection->commit();
            $this->messageManager->addSuccessMessage('Save Successfully');
        }catch (\Exception $e){
            $this->messageManager->addErrorMessage(__('Save Flagship Store fail.'.$e->getMessage()));
            $connection->rollBack();
            if($isNewFlagShipStore){
                $flagshipStore->delete();
            }

        }


        return $this->_redirect('*/*/index')->sendResponse();
    }

    /**
     * @return mixed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_FlagshipStore::manage_store');
    }
}