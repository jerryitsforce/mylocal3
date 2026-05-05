<?php
namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;
use Magento\Ui\Component\Listing\Columns\Column;

class ApprovalLog extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }
        $fieldName = $this->getData('name');
        
        foreach ($dataSource['data']['items'] as &$item) {
            $html = '';
            $approvalLog = $item[$fieldName];
            if(!$approvalLog == ''){
                $logData = json_decode($approvalLog, true);
                foreach($logData as $_log){
                    $html .= $_log['created_at'].' - '.$_log['user_name'].' - '.$_log['status_updated'].'<br />';
                }
            }

            $item[$fieldName] = $html;
        }

        return $dataSource;
    }
}
