<?php

namespace Branch8\SellerContactInformation\Controller\Adminhtml\ContractFile;

use Magento\Framework\App\Filesystem\DirectoryList;

class Download extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Branch8\SellerContactInformation\Model\ContractFilesFactory
     */
    protected $contractFilesFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Branch8\SellerContactInformation\Model\ContractFilesFactory $contractFilesFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->contractFilesFactory = $contractFilesFactory;
    }
    
    /**
     * Delete Contract file
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        // check if we know what should be deleted
        $fileId = $this->getRequest()->getParam('entity_id');
        $sellerId = $this->getRequest()->getParam('seller_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = $resultRedirect->setPath('customer/index/edit', ['id' => $sellerId, 'seller_panel' => 1]);
        if ($fileId && (int) $fileId > 0) {
            try {
                $model = $this->contractFilesFactory->create();
                $model->load($fileId);
                if ($model->getEntityId()) {
                    $downloadedFileName = $model->getData('file_name');
                    $filePath = 'marketplace/contracts_files/' .$sellerId . '/' . $downloadedFileName;
                    $content['type'] = 'filename';
                    $content['value'] = $filePath;
                    $content['rm'] = 0;
                    return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::MEDIA);
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $redirectUrl;
            }
        } else {
            $this->messageManager->addError(__('File to download was not found.'));
            return $redirectUrl;
        }
    }
}
