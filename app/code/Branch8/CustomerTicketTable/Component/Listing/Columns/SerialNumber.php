<?php

namespace Branch8\CustomerTicketTable\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class SerialNumber extends Column
{
    const HIDE_CHAR = 3;
    public function prepareDataSource(array $dataSource)
    {

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['ticket_unique_content'])) {
                    $serialNumber = $item['ticket_unique_content'];
                    $serialNumber = '******'.substr($serialNumber, -3);
                    $item['ticket_unique_content'] = $serialNumber;
                } else {
                    $item['ticket_unique_content'] = '';
                }
            }
        }
        return $dataSource;
    }
}