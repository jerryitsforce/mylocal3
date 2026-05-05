<?php

namespace Branch8\Spin2Win\Model\Config\Source;

class RuleOptions extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    protected $ruleCollection;
    public function __construct(
        \Magento\Salesrule\Model\ResourceModel\Rule\CollectionFactory $ruleCollection
    ){
        $this->ruleCollection = $ruleCollection;
    }

    public function getAllOptions()
    {
        $rulesCol = $this->ruleCollection->create()
            ->addFieldToFilter('coupon_type', 3)
            ->addFieldToFilter('is_active', 1);
        $options = [];
        foreach($rulesCol as $_rule){
            $options[] = [
                'label' => $_rule->getName(),
                'value' => $_rule->getId()
            ];
        }

        return $options;

    }
}