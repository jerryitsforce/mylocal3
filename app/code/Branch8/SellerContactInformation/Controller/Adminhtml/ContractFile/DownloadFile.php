<?php
namespace Branch8\SellerContactInformation\Controller\Adminhtml\ContractFile;

use Magento\Framework\App\Filesystem\DirectoryList;

class DownloadFile extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Magento_Customer::manage';

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    protected $_conn;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
       \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        parent::__construct($context);
        $this->fileFactory = $fileFactory;
        $this->_conn = $resourceConnection->getConnection();
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $logId = $this->getRequest()->getParam('log_id', 0);
        $sql = 'select * from seller_contract_logs where entity_id = '.$logId;
        $row = $this->_conn->fetchRow($sql);
        $sellerId = $row['seller_id'];
        $downloadedFileName = $row['file_name'];
        $filePath = 'marketplace/contracts_files/' .$sellerId . '/' . $downloadedFileName;
        $content['type'] = 'filename';
        $content['value'] = $filePath;
        $content['rm'] = 0;
        return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::MEDIA);
    }

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
