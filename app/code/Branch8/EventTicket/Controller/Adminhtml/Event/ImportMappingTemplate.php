<?php

namespace Branch8\EventTicket\Controller\Adminhtml\Event;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
class ImportMappingTemplate extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directory;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @param Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $filepath = 'export/import_mapping_serial.csv';
        $this->directory->create('export');
        $stream = $this->directory->openFile($filepath, 'w+');
        $stream->lock();
        $header = ['serial_number','member_seq'];
        $stream->writeCsv($header);
        $initContent = ["serial01", "a50c2892-c177-42c8-991a-5ec10062f265"];
        $stream->writeCsv($initContent);
        $initContent = ["serial02", "a50c2892-c177-42c8-991a-5ec10062f265"];
        $stream->writeCsv($initContent);

        $filepath = 'export/import_mapping_serial.csv';
        $downloadedFileName = 'import_mapping_serial_template.csv';
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = 1;
        return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::VAR_DIR);
    }
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_EventTicket::event_ticket');
    }
}