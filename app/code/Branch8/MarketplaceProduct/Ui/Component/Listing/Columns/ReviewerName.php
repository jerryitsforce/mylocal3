<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

class ReviewerName extends Column
{
    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$fieldName])) {
                continue;
            }
            if($item['approval_flow_status'] == 0){/** Old process */
                $item[$fieldName] .= match ($item['status']) {
                    '0', '1' => ' ' . __('Approved'),
                    '2' => ' ' . __('Rejected'),
                    default => ' ' . __('Denied'),
                };
            }else{/** New process */
                $approvalLog = $item['approval_log'];
                if($approvalLog == ''){
                    $item[$fieldName] = '';
                }else{
                    try{
                    $approvalLogData = json_decode($approvalLog, true);
                    $lastLogItem = array_pop($approvalLogData);
                    $item[$fieldName] = isset($lastLogItem['user_name']) ? $lastLogItem['user_name'] : '';
                    }catch(\Exception $e){
                        $item[$fieldName] = '';
                    }
                }
            }
        }

        return $dataSource;
    }
}
