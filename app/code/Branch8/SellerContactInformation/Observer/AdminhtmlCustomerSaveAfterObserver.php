<?php

namespace Branch8\SellerContactInformation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Webkul\Marketplace\Model\SaleperpartnerFactory as MpSalesPartner;
use Branch8\SellerContactInformation\Model\Config\Source\ContractStatus;

class AdminhtmlCustomerSaveAfterObserver implements ObserverInterface
{
    /**
     * File Uploader factory.
     *
     * @var \Branch8\SellerContactInformation\Model\ContractUploadFactory
     */
    protected $_fileUploaderFactory;
    /**
     * @var Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;
    /**
     * @var \Branch8\SellerContactInformation\Model\ContractFilesFactory
     */
    protected $_contractFilesFactory;
    /**
     * @var \Branch8\SellerDocument\Model\FileFactory
     */
    protected $_destructionDocumentsFactory;
    /**
     * @var \Webkul\Marketplace\Model\SellerFactory
     */
    protected $_sellerFactory;
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    protected $_collectionFactory;
    /**
     * @var MpSalesPartner
     */
    protected $mpSalesPartner;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\MessageQueue\PublisherInterface
     */
    protected $publisher;

    /**
     * @param Filesystem $filesystem
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory
     * @param \Branch8\SellerDocument\Model\FileFactory $destructionDocumentsFactory
     * @param \Webkul\Marketplace\Model\SellerFactory $sellerFactory
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $collectionFactory
     * @param MpSalesPartner $mpSalesPartner
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     * @param \Magento\Framework\MessageQueue\PublisherInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Filesystem $filesystem,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Branch8\SellerContactInformation\Model\ContractUploadFactory $fileUploaderFactory,
        \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory,
        \Branch8\SellerDocument\Model\FileFactory $destructionDocumentsFactory,
        \Webkul\Marketplace\Model\SellerFactory $sellerFactory,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $collectionFactory,
        MpSalesPartner $mpSalesPartner,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\MessageQueue\PublisherInterface $publisher
    ) {
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_contractFilesFactory = $contractFilesFactory;
        $this->_destructionDocumentsFactory = $destructionDocumentsFactory;
        $this->_sellerFactory = $sellerFactory;
        $this->_collectionFactory = $collectionFactory;
        $this->mpSalesPartner  = $mpSalesPartner;
        $this->authSession = $authSession;
        $this->resourceConnection = $resourceConnection;
        $this->timezone = $timezone;
        $this->publisher = $publisher;
        
    }

    public function getCurrentUser()
    {
        return $this->authSession->getUser();
    }

    /**
     * admin customer save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $customer = $observer->getCustomer();
        $sellerId = $customer->getId();
        $postData = $observer->getRequest()->getPostValue();

        //save commission_rate
        $partnerModel = $this->mpSalesPartner->create()
            ->getCollection()
            ->addFieldToSelect('entity_id')
            ->addFieldToFilter(
                'seller_id',
                $sellerId
            )->getFirstItem();
        $isSavePartner = false;
        if(isset($postData['default_commisssion_rate'])){
            $partnerModel->setDefaultCommissionRate($postData['default_commisssion_rate']);
            $partnerModel->setCommissionStatus(1);
            $isSavePartner = true;
        }
        if(isset($postData['default_min_commisssion_rate'])){
            $partnerModel->setDefaultMinCommissionRate($postData['default_min_commisssion_rate']);
            $partnerModel->setCommissionStatus(1);
            $isSavePartner = true;
        }
        $sqlActiveContract = 'select count(*) as cnt from seller_contract_files where seller_id='.$sellerId.' and is_active='.ContractStatus::STATUS_ACTIVE;
        $conn = $this->resourceConnection->getConnection();
        $hasActiveContract = $conn->fetchOne($sqlActiveContract);

        $isNeedToRunUpdateProduct = false;
        if(isset($postData['default_commisssion_rate']) && $partnerModel->getCommissionRate() != $postData['default_commisssion_rate'] && !$hasActiveContract){
            $partnerModel->setCommissionRate($postData['default_commisssion_rate']);
            $partnerModel->setMinCommissionRate($postData['default_min_commisssion_rate']);
            $isSavePartner = true;
            $isNeedToRunUpdateProduct = true;
            $partnerModel->setCommissionStatus(1);
            if(!$partnerModel->getSellerId()){
                $partnerModel->setSellerId($sellerId);
                $partnerModel->setCreatedAt($this->timezone->convertConfigTimeToUtc($this->timezone->date()));
            }
        }

        if($isSavePartner) {
            if(!$partnerModel->getSellerId()){
                $partnerModel->setSellerId($sellerId);
            }
            $partnerModel->save();
        }
        if($isNeedToRunUpdateProduct){
            $queueData = [
                'seller_id' => $sellerId,
                'action' => \Branch8\SellerContactInformation\Model\Consumer::ACTION_DEFAULT_SETTING_CHANGED
            ];
            $this->publisher->publish(
                'seller.contract.active.update_product',
                json_encode($queueData)
            );
        }

        //load seller
        $sellerCollection = $this->_collectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId)
            ->addFieldToFilter('store_id', 0);
        if ($sellerCollection->getSize()) {
            foreach ($sellerCollection as $value) {
                $autoId = $value->getId();
            }
            $seller = $this->_sellerFactory->create()->load($autoId);
        }

        //save preservation_status
        $sqlPreservation = '';
        if(isset($seller)) {
            if (isset($postData['change_shipping_settings_enable']) && $postData['change_shipping_settings_enable'] == 1) {
                if(isset($postData['preservation'])){
                    $preservationStatus = implode(',', $postData['preservation']);
                    if ($preservationStatus == "") {
                        $preservationStatus = '';
                    } else {
                        $preservationStatus = implode(',', $postData['preservation']);
                    }
                    $sqlPreservation = 'update marketplace_userdata set preservation_status ="' . $preservationStatus . '" where seller_id=' . $sellerId;
                }else{
                    $sqlPreservation = 'update marketplace_userdata set preservation_status = NULL where seller_id=' . $sellerId;
                }
            }
            //update directly because preservation status NORMAL is "", but Magento will save "" to NULL to DB
            if(trim($sqlPreservation) != '') {
                $this->resourceConnection->getConnection()->query($sqlPreservation);
            }
        }

        //save salesperson ( Sales representative )
        if(!empty($postData['salesperson_select']) && isset($seller)) {
            $salesperson = implode(',', $postData['salesperson_select']);
            $seller->setData('salesperson', $salesperson);
            $seller->save();
        }

        //save shipping_methods
        if(!empty($postData['shippingMethods']) && isset($seller)) {
            $shippingMethods = implode(',', $postData['shippingMethods']);
            $seller->setData('shipping_methods', $shippingMethods);
            $seller->save();
        }

        //save basic_information_registration_file
        if (isset($seller) && isset($_FILES['basic_information_registration_file']['name']) && $_FILES['basic_information_registration_file']['name'] != '') {
            $target = $this->_mediaDirectory->getAbsolutePath('marketplace/remittance_file/'.$sellerId);
            try {
                /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                $uploader = $this->_fileUploaderFactory->create(
                    ['fileId' => 'basic_information_registration_file']
                );
                $uploader->setAllowedExtensions(
                    ['jpg', 'jpeg', 'png', 'pdf']
                );
                $uploader->setAllowRenameFiles(true);
                $result = $uploader->save($target);

                if ($result['file'] && $this->isSeller($sellerId)) {
                    $seller->setData('basic_information_registration_file',$result['file']);
                    $seller->save();
                }
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        //save applicable_contracts_and_quotations file
        if (isset($seller) && isset($_FILES['applicable_contracts_and_quotations']['name']) && $_FILES['applicable_contracts_and_quotations']['name'] != '') {

            $target = $this->_mediaDirectory->getAbsolutePath('marketplace/contracts_files/'.$sellerId);
            try {
                /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                $uploader = $this->_fileUploaderFactory->create(
                    ['fileId' => 'applicable_contracts_and_quotations']
                );
                $uploader->setAllowedExtensions(
                    ['docx', 'pdf']
                );
                $uploader->setAllowRenameFiles(true);
                $result = $uploader->save($target);

                if ($result['file'] && $this->isSeller($sellerId)) {
                    $seller->setData('applicable_contracts_and_quotations',$result['file']);
                    $seller->save();

                    $contractFilesModel = $this->_contractFilesFactory->create();
                    $contractFilesModel->setData('file_name', $result['file']);
                    $contractFilesModel->setData('seller_id', $sellerId);
                    $applicablePeriod = '';
                    if (isset($postData['contracts_from']) && strtotime($postData['contracts_from']) > 0) {
                        $contractFilesModel->setData('contracts_from', $postData['contracts_from']);
                        $applicablePeriod = __('From') .' '. date("d/m/Y", strtotime($postData['contracts_from']));
                    }
                    if (isset($postData['contracts_to']) && strtotime($postData['contracts_to']) > 0) {
                        $contractFilesModel->setData('contracts_to', $postData['contracts_to']);
                        $applicablePeriod .= ' '. __('To') .' '. date("d/m/Y", strtotime($postData['contracts_to']));
                    }
                    if (!empty($applicablePeriod)) {
                        $contractFilesModel->setData('applicable_period', $applicablePeriod);
                    }
                    $contractFilesModel->save();
                }
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        //save personal_data_destruction documents
        if (isset($seller) && isset($_FILES) && isset($_FILES['personal_data_destruction_affidavit']['name'])  && isset($_FILES['personal_data_destruction_certificate']['name'])) {
            
            $target = $this->_mediaDirectory->getAbsolutePath('marketplace/seller_destruction_documents/'.$sellerId);
            try {
                if( ($_FILES['personal_data_destruction_affidavit']['name'] != '' && $_FILES['personal_data_destruction_certificate']['name'] == '') 
                    || ($_FILES['personal_data_destruction_certificate']['name'] != '' && $_FILES['personal_data_destruction_affidavit']['name'] == '') ){
                    throw new \Exception(__('Please upload all documents'));
                }
                /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
                $uploader1 = $this->_fileUploaderFactory->create(
                    ['fileId' => 'personal_data_destruction_affidavit']
                );
                $uploader1->setAllowedExtensions(['doc', 'docx', 'pdf']);
                $uploader1->setAllowCreateFolders(true);
                $uploader1->setAllowRenameFiles(true);
                $result1 = $uploader1->save($target);

                $uploader2 = $this->_fileUploaderFactory->create(
                    ['fileId' => 'personal_data_destruction_certificate']
                );
                $uploader2->setAllowedExtensions(['jpg', 'jpeg', 'png']);
                $uploader2->setAllowCreateFolders(true);
                $uploader2->setAllowRenameFiles(true);
                $result2 = $uploader2->save($target);

                if ($result1['file'] && $result2['file'] && $this->isSeller($sellerId)) {
                    $selectedPeriod = $postData['personal_data_destruction_period'] ? (int) $postData['personal_data_destruction_period'] : 90;
                    $today = date("Y.m.d");
                    $endDay = date('Y.m.d', strtotime("+$selectedPeriod days"));
                    $periodText = $today . ' - ' . $endDay;

                    $seller->setData('most_recent_personal_data_destruction_period', $periodText);
                    $seller->setData('destruction_document_status', 1);
                    $seller->setData('personal_data_destruction_affidavit',$result1['file']);
                    $seller->setData('personal_data_destruction_certificate',$result2['file']);
                    $seller->save();

                    $accountMakeChange = 'Admin ' . ' : ' . $this->getCurrentUser()->getUsername();
                    $destructionDocumentsModel = $this->_destructionDocumentsFactory->create();
                    $destructionDocumentsModel->setData('personal_data_destruction_affidavit', $result1['file']);
                    $destructionDocumentsModel->setData('personal_data_destruction_certificate', $result2['file']);
                    $destructionDocumentsModel->setData('personal_data_destruction_period', $periodText);
                    $destructionDocumentsModel->setData('expired_time', $selectedPeriod);
                    $destructionDocumentsModel->setData('account_make_change', $accountMakeChange);
                    $destructionDocumentsModel->setData('status', 1);
                    $destructionDocumentsModel->setData('seller_id', $sellerId);
                    $destructionDocumentsModel->save();
                }
            } catch(\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Check is seller
     *
     * @param int $customerId
     * @return boolean
     */
    public function isSeller($customerId)
    {
        $sellerStatus = 0;
        $model = $this->_collectionFactory->create()
            ->addFieldToFilter('seller_id', $customerId)
            ->addFieldToFilter('store_id', 0);
        foreach ($model as $value) {
            $sellerStatus = $value->getIsSeller();
        }

        return $sellerStatus;
    }
}
