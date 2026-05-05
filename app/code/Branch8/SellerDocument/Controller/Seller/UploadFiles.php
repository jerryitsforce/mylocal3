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

class UploadFiles extends \Magento\Framework\App\Action\Action
{
    protected $fileUploaderFactory;
    protected $messageManager; 
    protected $filesystem;
    protected $file;
    protected $fileModelFactory;
    protected $helper;
    protected $customerSession;

    public function __construct(
        UploaderFactory $fileUploaderFactory,
        ManagerInterface $messageManager,
        Filesystem $filesystem,
        File $file,
        FileFactory $fileModelFactory,
        SellerDocumentHelper $helper,
        CustomerSession $customerSession,
        Context $context
    ) {
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->messageManager = $messageManager;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->fileModelFactory = $fileModelFactory;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }
 
    public function execute(){
        try {
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $destinationPath = $mediaDirectory->getAbsolutePath('marketplace/seller_account_setup');
            
            $sellerId = $this->customerSession->getCustomerId();
            $sellerDocumentCollection = $this->helper->getSellerDocumentCollection($sellerId);
            $sellerDocumentCollection->load();

            $arrayResult = [];
            $files = $this->getRequest()->getFiles();
            if (isset($files['files'])){
        
                foreach ($files['files'] as $key => $value) {
                    if(!empty($value['name'])){
                        $fileId = "files[".$key."]";
                        $uploader = $this->fileUploaderFactory->create(['fileId' => $fileId]);
                        $uploader->setAllowedExtensions(['jpg','jpeg','gif','png','pdf','zip','csv','xls','xlsb','xlsx','ods','xps','doc','docm','docx','dot','dotx','xml','odt']);
                        $uploader->setAllowCreateFolders(true);
                        $uploader->setAllowRenameFiles(true);
                        $uploader->setFilesDispersion(true);
                        
                        $result = $uploader->save($destinationPath);
                        if (!$result) {
                            $this->messageManager->addErrorMessage(__('File cannot be saved to path: $1', $destinationPath));
                        } else {
                            $arrayResult[] = $result;
                        }
                    }
                }
                $this->messageManager->addSuccess(__('File has been successfully uploaded.')); 
            }

            //when upload file success. add records to table branch8_sellerdocument_file
            foreach ($arrayResult as $result) {
                $model = $this->fileModelFactory->create();
                $model->setData('file_name', $result['name']);
                $model->setData('file_path', $result['file']);
                $model->setData('file_type', $result['type']);
                $model->setData('seller_id', $sellerId);
                $model->setData('status', 1);
                $model->save();
            }
            
            //when upload file success. remove all current files of seller
            foreach ($sellerDocumentCollection as $item) {
                $item->delete();
                if ($this->file->isExists($destinationPath . $item['file_path'])) {
                    $this->file->deleteFile($destinationPath . $item['file_path']);
                }
            }
        } catch  (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return $this->resultRedirectFactory->create()->setPath('marketplace/account/dashboard/', ['_current' => true]);
    }
}