<?php

namespace Branch8\SellerContactInformation\Controller\Adminhtml\DestructionDocuments;

use Magento\Framework\App\Filesystem\DirectoryList;
use Branch8\SellerDocument\Model\FileFactory as DestructionDocumentsFactory;

class DownloadCertificate extends \Magento\Backend\App\Action
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
     * @var DestructionDocumentsFactory
     */
    protected $destructionDocumentsFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param DestructionDocumentsFactory $destructionDocumentsFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        DestructionDocumentsFactory $destructionDocumentsFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->destructionDocumentsFactory = $destructionDocumentsFactory;
    }
    
    /**
     * Delete Contract file
     *
     * @return \Magento\Framework\View\Result\PageFactory
     */
    public function execute()
    {
        $fileId = $this->getRequest()->getParam('file_id');
        $sellerId = $this->getRequest()->getParam('seller_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = $resultRedirect->setPath('customer/index/edit', ['id' => $sellerId, 'seller_panel' => 1]);
        if ($fileId && (int) $fileId > 0) {
            try {
                $model = $this->destructionDocumentsFactory->create();
                $model->load($fileId);
                if ($model->getId()) {
                    $downloadedFileName = $model->getData('personal_data_destruction_certificate');
                    $filePath = 'marketplace/seller_destruction_documents/' .$sellerId . '/' . $downloadedFileName;
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
