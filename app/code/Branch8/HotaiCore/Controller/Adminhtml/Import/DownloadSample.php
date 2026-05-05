<?php

namespace Branch8\HotaiCore\Controller\Adminhtml\Import;

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
        $fileName = 'Hotai_ticket_import_sample.csv';

        $fileData = [
            ["序號"],
            ["此列為範例，請從第三列開始輸入資料"],
            ["PinCode-11"],
            ["PinCode-22"],
            ["PinCode-33"],
        ];

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');

        $output = fopen('php://output', 'w');

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        foreach ($fileData as $rowContent) {
            fputcsv($output, $rowContent);
        }

        fclose($output);

        // 暫時不知道怎麼正確的處理這邊只好先用die()避免錯誤
        // 比較長的資料(或是資料有什麼特殊內容目前不明)會在檔案內容的最後寫入錯誤訊息
        // Cannot modify header information - headers already sent...
        die();
    }
}
