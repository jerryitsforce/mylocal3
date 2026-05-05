<?php
namespace Branch8\RewardSystem\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\RewardSystem\Model\Config\Source\Conditions;
use Kreait\Firebase\Messaging\Condition;

class Trigger extends Column
{
    protected $conditions;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Conditions $conditions,
        array $components = [],
        array $data = []
    ) {
        $this->uiComponentFactory = $uiComponentFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->conditions = $conditions;
    }
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $allConditions = $this->conditions->getAllOptions();
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['entity_id'])) {
                    try{
                        $triggerData = json_decode($item['trigger_condition'], true);
                        if(!isset($triggerData['triggerType'])){
                            $item['trigger_condition'] = '';
                            continue;
                        }
                        $triggerType = $triggerData['triggerType'];
                        $condTxt = '';
                        if($triggerType == \Branch8\RewardSystem\Model\Config\Source\TriggerType::TYPE_AND){
                            $condTxt = '<b> AND </b>';
                        }
                        if($triggerType == \Branch8\RewardSystem\Model\Config\Source\TriggerType::TYPE_OR){
                            $condTxt = '<b> OR </b>';
                        }
                        $conditions = $triggerData['conditions'];
                        $condArr = [];
                        foreach($conditions as $_cond){
                            $condArr[] = '<span class="cond_item">'.$allConditions[$_cond['condition']].'</span>';
                        }
                        $condStr = implode($condTxt, $condArr);
                        $item['trigger_condition'] = $condStr;
                    }catch(\Exception $e){

                    }
                }
            }
        }

        return $dataSource;
    }
}
