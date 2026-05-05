<?php
namespace Branch8\AdvancedIsNotRule\Plugin;

use Magento\SalesRule\Model\Rule as CoreRule;
use Branch8\AdvancedIsNotRule\Model\FilterFactory;
use Branch8\AdvancedIsNotRule\Model\ResourceModel\Filter as FilterResource;

class Rule
{
    protected $filterFactory;
    protected $filterResource;

    public function __construct(FilterFactory $filterFactory, FilterResource $filterResource)
    {
        $this->filterFactory = $filterFactory;
        $this->filterResource = $filterResource;
    }

    public function afterSave(CoreRule $subject, $result)
    {
        $ruleId = (int)$subject->getId();
        $conditions = $subject->getConditions()->asArray();
        if (!$ruleId || empty($conditions)) {
            return $result;
        }

        if (!is_array($conditions)) {
            return $result;
        }

        $connection = $this->filterResource->getConnection();
        $connection->delete($this->filterResource->getMainTable(), ['rule_id = ?' => $ruleId]);

        $this->parseConditions($ruleId, $conditions, (bool)$subject->getCouponCode());
        return $result;
    }

    private function parseConditions(int $ruleId, array $condition, bool $isCoupon)
    {
        if (isset($condition['attribute'], $condition['operator'])) {
            if ($condition['type'] !== 'Magento\SalesRule\Model\Rule\Condition\Product') {
                // Skip product attribute conditions
                return;
            }
            $operator = strtolower($condition['operator']);
            if (in_array($operator, ['!=', '()', '!()'])) {
                if ($operator == '()' && str_contains($condition['attribute'], 'category_ids')) {
                    // Skip category "is one of" conditions
                    return;
                }
                if (in_array($operator, ['()', '!()']) && str_contains($condition['value'], ',')) {
                    $values = explode(',', $condition['value']);
                    foreach ($values as $value) {
                        $model = $this->filterFactory->create();
                        $model->setData([
                            'rule_id' => $ruleId,
                            'attribute' => $condition['attribute'],
                            'is_one_of' => ($operator == '()') ? 1 : 0,
                            'filter_text' => $condition['attribute'].':'.$value,
                            'is_coupon' => (int)$isCoupon,
                        ]);
                        $this->filterResource->save($model);
                    }
                } else {
                    $model = $this->filterFactory->create();
                    $model->setData([
                        'rule_id' => $ruleId,
                        'attribute' => $condition['attribute'],
                        'is_one_of' => ($operator == '()') ? 1 : 0,
                        'filter_text' => $condition['attribute'] . ':' . ($condition['value'] ?? ''),
                        'is_coupon' => (int)$isCoupon,
                    ]);
                    $this->filterResource->save($model);
                }
            } elseif (in_array($operator, ['{}', '!{}'])) {
                if (strpos($condition['value'], ',') !== false) {
                    $values = explode(',', $condition['value']);
                    foreach ($values as $value) {
                        $model = $this->filterFactory->create();
                        $model->setData([
                            'rule_id' => $ruleId,
                            'attribute' => $condition['attribute'],
                            'filter_text' => $value,
                            'is_contain' => 1,
                            'flag_contain' => ($operator == '{}') ? 1 : 0,
                            'is_coupon' => (int)$isCoupon,
                        ]);
                        $this->filterResource->save($model);
                    }
                } else {
                    $model = $this->filterFactory->create();
                    $model->setData([
                        'rule_id' => $ruleId,
                        'attribute' => $condition['attribute'],
                        'filter_text' => $condition['value'] ?? '',
                        'is_contain' => 1,
                        'flag_contain' => ($operator == '{}') ? 1 : 0,
                        'is_coupon' => (int)$isCoupon,
                    ]);
                    $this->filterResource->save($model);
                }
            }
        }

        if (!empty($condition['conditions']) && is_array($condition['conditions'])) {
            foreach ($condition['conditions'] as $subCondition) {
                $this->parseConditions($ruleId, $subCondition, $isCoupon);
            }
        }
    }
}
