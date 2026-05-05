<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Ticket;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;

class DownloadLogMapping extends \Magento\Backend\App\Action
{

    protected $directory;

    protected $fileFactory;

    protected $_conn;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_conn = $resourceConnection->getConnection();
    }

    public function execute()
    {
        $poolId = (int)$this->getRequest()->getParam('poolId');
        $filepath = '/export/import_pool_mapping/mapping_pool_'.$poolId.'.log';
        $downloadedFileName = 'mapping_pool_'.$poolId.'.log';

        $logPath = 'export/import_pool_mapping';
        if(!$this->directory->isDirectory($logPath)){
            $this->directory->create($logPath);
        }
        $fullFilePath = $this->directory->getAbsolutePath($filepath);
        $handle = fopen($fullFilePath,'a+');
        $sql = 'select * from ticket_import_mapping_log where pool_id='.$poolId;
        $result = $this->_conn->query($sql);
        while($row = $result->fetch()){
            fwrite($handle, $row['content']."\r\n");
        }
        fclose($handle);

        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = 1;
        return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::VAR_DIR);
    }
}