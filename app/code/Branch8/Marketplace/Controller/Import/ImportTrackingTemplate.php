<?php

namespace Branch8\Marketplace\Controller\Import;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
class ImportTrackingTemplate extends \Magento\Framework\App\Action\Action
{

    protected $directory;

    protected $fileFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    public function execute()
    {
        $filepath = '/code/Branch8/Marketplace/Files/Sample/import_tracking_number.csv';
        $downloadedFileName = 'import_tracking_number_template.csv';
        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = 0;
        return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::APP);
    }
}