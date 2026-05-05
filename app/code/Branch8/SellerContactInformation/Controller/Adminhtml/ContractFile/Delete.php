<?php

namespace Branch8\SellerContactInformation\Controller\Adminhtml\ContractFile;

use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $file;

    /**
     * @var \Branch8\SellerContactInformation\Model\ContractFilesFactory
     */
    protected $contractFilesFactory;
    /**
     * @var \Branch8\SellerContactInformation\Model\ContractLogFactory
     */
    protected $contractLogFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory
     * @param \Branch8\SellerContactInformation\Model\ContractLogFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory,
        \Branch8\SellerContactInformation\Model\ContractLogFactory $contractLogFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->contractFilesFactory = $contractFilesFactory;
        $this->contractLogFactory = $contractLogFactory;
        $this->timezone = $timezone;
    }
    
    /**
     * Delete Contract file
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $fileId = $this->getRequest()->getParam('entity_id');
        $sellerId = $this->getRequest()->getParam('seller_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = $resultRedirect->setPath('customer/index/edit', ['id' => $sellerId, 'seller_panel' => 1]);
        if ($fileId && (int) $fileId > 0) {
            try {
                // init model and delete
                $model = $this->contractFilesFactory->create();
                $model->load($fileId);
                if($model->getIsActive()){
                    $this->messageManager->addErrorMessage(__("Can't delete an active contract."));
                    return $redirectUrl;
                }
                if ($model->getEntityId()) {
                    $contractDataHistory = [
                        'contract_id' => $model->getId(),
                        'seller_id' => $model->getSellerId(),
                        'is_deleted' => 1,
                        'updated_at' => $this->timezone->convertConfigTimeToUtc($this->timezone->date())
                    ];
                    $fileName = $model->getData('file_name');
                    $sellerId = $model->getData('seller_id');
                    $filePath = 'marketplace/contracts_files/' . $sellerId . '/' . $fileName;
                    $model->delete();

                    /** Add log */
                    
                    $this->contractLogFactory->create()->setData($contractDataHistory)->save();

                    $mediaRootDir = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
                    if ($this->file->isExists($mediaRootDir . $filePath)) {
                        $this->file->deleteFile($mediaRootDir . $filePath);
                    }
                    if($fileName){
                        $this->messageManager->addSuccess(__('The "'.$fileName.'" has been deleted.'));
                    }else{
                        $this->messageManager->addSuccess(__('The contract has been deleted.'));
                    }
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        } else {
            $this->messageManager->addError(__('Contract to delete was not found.'));
        }
        
        return $redirectUrl;
    }
}
