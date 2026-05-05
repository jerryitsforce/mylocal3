<?php

namespace Branch8\GeneralNotifyTicket\Controller\Adminhtml\Import;

use Magento\Backend\App\Action;

class DownloadSample extends Action
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $heads = "主序號";

        $colDataArray = [
            "MainSerialNumber-1",
            "MainSerialNumber-2",
            "MainSerialNumber-3",
        ];

        $data = $heads;

        foreach ($colDataArray as $colData) {
            $data .= PHP_EOL . $colData;
        }

        return $this->fileFactory->create(
            'yoxi_import_sample.csv',
            $data,
            \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR,
            'application/octet-stream'
        );
    }
}
