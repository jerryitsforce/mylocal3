<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Exception;
use Magento\Framework\Authorization\PolicyInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\AuthorizationInterface;

class ReviewerStage extends Column
{
    protected PolicyInterface $aclPolicy;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        PolicyInterface $aclPolicy,
        array $components = [],
        array $data = []
    ) {
        $this->aclPolicy = $aclPolicy;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
                if (!$item['dealer_processed'] && $this->aclPolicy->isAllowed($item[$fieldName],'Branch8_MarketplaceProduct::approve')) {
                    $item[$fieldName] = __('Headquarters');
                } else {
                    $item[$fieldName] = __('Dealer');
                }
            }else{/** New process */
                $approvalLog = $item['approval_log'];
                if($approvalLog == ''){
                    $item[$fieldName] = '';
                }else{
                    try{
                    $approvalLogData = json_decode($approvalLog, true);
                    $lastLogItem = array_pop($approvalLogData);
                    $item[$fieldName] = isset($lastLogItem['user_stage']) ? $lastLogItem['user_stage'] : '';
                    }catch(\Exception $e){
                        $item[$fieldName] = '';
                    }
                }
            }
            
        }

        return $dataSource;
    }
}
