<?php

namespace Branch8\SellerDocument\Controller\Seller;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Branch8\SellerDocument\Model\FileFactory;
use Branch8\SellerDocument\Helper\Data as SellerDocumentHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;
use Webkul\Marketplace\Model\SellerFactory;

class UploadDestructionDocuments extends \Magento\Framework\App\Action\Action
{
    protected $fileUploaderFactory;
    protected $messageManager; 
    protected $filesystem;
    protected $file;
    protected $fileModelFactory;
    protected $helper;
    protected $customerSession;
    protected $sellerCollectionFactory;
    protected $sellerFactory; 

    public function __construct(
        \Branch8\SellerContactInformation\Model\ContractUploadFactory $fileUploaderFactory,
        ManagerInterface $messageManager,
        Filesystem $filesystem,
        File $file,
        FileFactory $fileModelFactory,
        SellerDocumentHelper $helper,
        CustomerSession $customerSession,
        SellerCollectionFactory $sellerCollectionFactory,
        SellerFactory $sellerFactory,
        Context $context
    ) {
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->messageManager = $messageManager;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->fileModelFactory = $fileModelFactory;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->sellerFactory = $sellerFactory;
        parent::__construct($context);
    }
 
    public function execute() {
        try {
            $sellerId = $this->customerSession->getCustomerId();
            $customer = $this->customerSession->getCustomer();
            $customerName = $customer->getName();
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $destinationPath = $mediaDirectory->getAbsolutePath('marketplace/seller_destruction_documents/'.$sellerId);
            
            $arrayResult = [];
            $files = $this->getRequest()->getFiles();
            $postData = $this->getRequest()->getPost();
            if (isset($files)){
                foreach ($files as $key => $value) {
                    if(!empty($value['name'])){
                        $fileId = $key;
                        $uploader = $this->fileUploaderFactory->create(['fileId' => $fileId]);
                        $uploader->setAllowedExtensions(['jpg','jpeg','gif','png','pdf','zip','csv','xls','xlsb','xlsx','ods','xps','doc','docm','docx','dot','dotx','xml','odt']);
                        $uploader->setAllowCreateFolders(true);
                        $uploader->setAllowRenameFiles(true);
                        
                        $result = $uploader->save($destinationPath);
                        if (!$result) {
                            $this->messageManager->addErrorMessage(__('File cannot be saved to path: $1', $destinationPath));
                        } else {
                            $arrayResult[$key] = $result;
                        }
                    }
                }
                $this->messageManager->addSuccess(__('File has been successfully uploaded.')); 
            }

            //when upload file success. add records to table branch8_sellerdocument_file
            if (isset($arrayResult['personal_data_destruction_affidavit']['file']) && isset($arrayResult['personal_data_destruction_certificate']['file'])) {
                $expiredTime = isset($postData['expired_time']) ? $postData['expired_time'] : 90;
                $today = date("Y.m.d");
                $endDay = date('Y.m.d', strtotime("+$expiredTime days"));
                $period = $today . ' - ' . $endDay;
                $model = $this->fileModelFactory->create();
                $model->setData('personal_data_destruction_affidavit', $arrayResult['personal_data_destruction_affidavit']['file']);
                $model->setData('personal_data_destruction_certificate', $arrayResult['personal_data_destruction_certificate']['file']);
                $model->setData('personal_data_destruction_period', $period);
                $model->setData('expired_time', $expiredTime);
                $model->setData('account_make_change', $customerName);
                $model->setData('seller_id', $sellerId);
                $model->setData('status', 1);
                $model->save();

                $collection = $this->sellerCollectionFactory->create()
                        ->addFieldToFilter('seller_id', $sellerId)
                        ->addFieldToFilter('store_id', 0);
                if (count($collection)) {
                    foreach ($collection as $value) {
                        $autoId = $value->getId();
                    }
                    $seller = $this->sellerFactory->create()->load($autoId);
                    $seller->setData('most_recent_personal_data_destruction_period', $period);
                    $seller->setData('destruction_document_status', 1);
                    $seller->setData('personal_data_destruction_affidavit', $arrayResult['personal_data_destruction_affidavit']['file']);
                    $seller->setData('personal_data_destruction_certificate', $arrayResult['personal_data_destruction_certificate']['file']);
                    $seller->save();
                }
            } else {
                $this->messageManager->addError(__('File has not been successfully uploaded.'));
            }
            
        } catch  (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath('marketplace/account/dashboard/', ['_current' => true]);
    }
}