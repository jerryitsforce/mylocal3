<?php

namespace Branch8\CustomNotification\Model\OneId;

use Branch8\CustomNotification\Model\OneIdListImportHandler;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class ReadCsv implements ReadHandler
{

    private ValidateOneId|ValidateManager $validateManager;

    /**
     * @param ValidateOneId $validateManager
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
        $rowCount = 0;
        $limit = OneIdListImportHandler::MAX_ROW;
        $handle = fopen($path, 'r');
        $list = [];
        $err = [];
        $failed = 0;
        $importData = [];
        $result = new DataObject([
            'total' => 0,
            'failed' => 0,
            'list' => $list,
            'errors' => $err,
            'importData' => $importData,
        ]);
        if ($handle === false) {
            $result->setData('errors', [__('OneId File not readable.')->render()]);
            return $result;
        }
        while (($data = fgetcsv($handle)) !== false) {
            if ($rowCount == 0) {
                $rowCount++;
                continue;
            }
            $oneId = $data[0];
            if ($rowCount > $limit) {
                throw new LocalizedException(__('Maximum OneId allowance (%1).', $limit));
            }
            $validateResult = $this->validateManager->validate($oneId);
            if ($validateResult['error'] === false) {
                $list[$data[0]] = $data[0];
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
        fclose($handle);
        $result->setData('total', count($list));
        $result->setData('failed', $failed);
        $result->setData('list', array_values($list));
        $result->setData('errors', $err);
        $result->setData('importData', $importData);
        return $result;
    }

}
