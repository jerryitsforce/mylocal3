<?php

namespace Branch8\CustomNotification\Model\OneId;

use Branch8\CustomNotification\Model\OneIdListImportHandler;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class ReadExcel implements ReadHandler
{
    private ValidateManager $validateManager;

    /**
     * @param ValidateManager $validateManager
     */
    public function __construct(
        ValidateManager $validateManager
    )
    {
        $this->validateManager = $validateManager;
    }

    /**
     * @param string $path
     * @return DataObject
     * @throws LocalizedException
     */
    public function read(string $path)
    {
        try {
            $list = [];
            $err = [];
            $limit = OneIdListImportHandler::MAX_ROW;
            $failed = 0;
            $importData = [];
            $result = new DataObject([
                'total' => 0,
                'failed' => 0,
                'list' => $list,
                'errors' => $err,
                'importData' => $importData
            ]);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if ($extension == 'xls') {
                $inputFileType = 'Xls';
            } else {
                $inputFileType = 'Xlsx';
            }
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
            $spreadsheet = $reader->load($path);
            $inputSheet = $spreadsheet->getSheet(0);
            $rowCount = 2;
            $maxRow = $inputSheet->getHighestDataRow();
            if ($maxRow - 1 > $limit) {
                throw new LocalizedException(__('Maximum OneId allowance (%1).', $limit));
            }
            while ($rowCount <= $maxRow) {
                $range = sprintf('A%d', $rowCount);
                $oneId = $inputSheet->rangeToArray($range, null, false)[0][0];
                $validateResult = $this->validateManager->validate($oneId);
                if ($validateResult['error'] === false) {
                    $list[$oneId] = $oneId;
                    $importData[] = ['one_id' => $oneId, 'error' => ''];
                } else {
                    $failed++;
                    foreach ($validateResult['errors'] as $error) {
                        $err[] = $error;
                    }
                    $importData[] = ['one_id' => $oneId, 'error' => $error];
                }
                $rowCount++;
            }
        } catch (\Exception $exception) {
            throw $exception;
        }
        $result->setData('total', count($list));
        $result->setData('list', array_values($list));
        $result->setData('failed', $failed);
        $result->setData('errors', $err);
        $result->setData('importData', $importData);
        return $result;
    }
}
