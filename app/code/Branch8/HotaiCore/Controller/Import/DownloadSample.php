<?php

namespace Branch8\HotaiCore\Controller\Import;

use Magento\Framework\App\Action\Action;

class DownloadSample extends Action
{
    /**
     * @inheritDoc
     */
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

        die();
    }
}
