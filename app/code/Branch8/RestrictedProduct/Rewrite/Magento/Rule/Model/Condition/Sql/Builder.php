<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       14/04/2026
 */

namespace Branch8\RestrictedProduct\Rewrite\Magento\Rule\Model\Condition\Sql;

use Branch8\RestrictedProduct\Model\ConfigData;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Select;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Rule\Model\Condition\Combine;
use Magento\Rule\Model\Condition\Sql\ExpressionFactory;

class Builder extends \Magento\Rule\Model\Condition\Sql\Builder
{
    /**
     * @var array
     */
    private $stringConditionOperatorMap = [
        '{}' => ':field LIKE ?',
        '!{}' => ':field NOT LIKE ?',
    ];

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;
    private ConfigData $configData;

    /**
     * @param ExpressionFactory $expressionFactory
     * @param ConfigData $configData
     * @param AttributeRepositoryInterface|null $attributeRepository
     */
    public function __construct(
        ExpressionFactory $expressionFactory,
        ConfigData $configData,
        AttributeRepositoryInterface $attributeRepository = null
    ) {
        $this->configData = $configData;
        $this->_expressionFactory = $expressionFactory;
        $this->attributeRepository = $attributeRepository ?:
            ObjectManager::getInstance()->get(AttributeRepositoryInterface::class);
        parent::__construct($expressionFactory);
    }
    /**
     * @param Combine $combine
     * @param $tables
     * @return array|mixed
     */
    protected function _getChildCombineTablesToJoin(Combine $combine, $tables = [])
    {
        foreach ($combine->getConditions() as $condition) {
            if ($condition->getConditions()) {
                $tables = $this->_getChildCombineTablesToJoin($condition);
            } else {
                /** @var $condition AbstractCondition */
                $tableToJoins = $condition->getTablesToJoin();
                $enabled = $this->configData->enableOptimize();
                if ($enabled && $condition->getAttribute() === 'category_ids' && $condition->getOperator() === '()') {
                    $values = is_array($condition->getValueParsed()) ? implode(',', array_unique($condition->getValueParsed())) : $condition->getValueParsed();
                    $tableToJoins['category'] = [
                        'name' => 'catalog_category_product_index_store1',
                        'condition' => 'category.product_id = e.entity_id AND category.category_id IN (' . $values . ')',
                        'columns' => []
                    ];
                }
                foreach ($tableToJoins as $alias => $table) {
                    if (!isset($tables[$alias])) {
                        $tables[$alias] = $table;
                    }
                }
            }
        }
        return $tables;
    }
    /**
     * Get mapped sql combination.
     *
     * @param Combine $combine
     * @param string $value
     * @param bool $isDefaultStoreUsed
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getMappedSqlCombination(
        Combine $combine,
        string $value = '',
        bool $isDefaultStoreUsed = true
    ): string
    {
        $out = (!empty($value) ? $value : '');
        $value = ($combine->getValue() ? '' : ' NOT ');
        $getAggregator = $combine->getAggregator();
        $conditions = $combine->getConditions();
        $enableOptimize = $this->configData->enableOptimize();

        foreach ($conditions as $key => $condition) {
            /** @var $condition AbstractCondition|Combine */
            $con = ($getAggregator == 'any' ? Select::SQL_OR : Select::SQL_AND);
            $con = (isset($conditions[$key+1]) ? $con : '');
            if ($condition instanceof Combine) {
                $out .= $this->_getMappedSqlCombination($condition, $value, $isDefaultStoreUsed);
            } else {
                //ignore because we join table ,see line 66
                $ignore = $enableOptimize && $condition->getOperator() == '()' && $condition->getAttribute() == 'category_ids';
                if ($ignore === false) {
                    $out .= $this->_getMappedSqlCondition($condition, $value, $isDefaultStoreUsed);
                }
            }
            $out .=  $out ? (' ' . $con) : '';
        }

        return $this->_expressionFactory->create(['expression' => $out])->__toString();
    }

    /**
     * Returns sql expression based on rule condition.
     *
     * @param AbstractCondition $condition
     * @param string $value
     * @param bool $isDefaultStoreUsed no longer used because caused an issue about not existing table alias
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _getMappedSqlCondition(
        AbstractCondition $condition,
        string $value = '',
        bool $isDefaultStoreUsed = true
    ): string {
        $enabled = $this->configData->enableOptimize();
        if (!$enabled) {
            return parent::_getMappedSqlCondition($condition, $value, $isDefaultStoreUsed);
        }
        $argument = $condition->getMappedSqlField();
        // If rule hasn't valid argument - prevent incorrect rule behavior.
        if (empty($argument)) {
            return $this->_expressionFactory->create(['expression' => '1 = -1'])->__toString();
        } elseif (!$argument instanceof \Zend_Db_Expr && preg_match('/[^a-z0-9\-_\.\`]/i', $argument) > 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid field'));
        }

        $conditionOperator = $condition->getOperatorForValidate();

        if (!isset($this->_conditionOperatorMap[$conditionOperator])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Unknown condition operator'));
        }

        //operator 'contains {}' is mapped to 'IN()' query that cannot work with substrings
        // adding mapping to 'LIKE %%'
        if ($condition->getInputType() === 'string'
            && in_array($conditionOperator, array_keys($this->stringConditionOperatorMap), true)
        ) {
            $sql = str_replace(
                ':field',
                (string)$this->_connection->quoteIdentifier($argument),
                $this->stringConditionOperatorMap[$conditionOperator]
            );
            $bindValue = $condition->getBindArgumentValue();
            $expression = $value . $this->_connection->quoteInto($sql, "%$bindValue%");
        } else {
            $sql = str_replace(
                ':field',
                (string)$this->_connection->quoteIdentifier($argument),
                $this->_conditionOperatorMap[$conditionOperator]
            );
            $bindValue = $condition->getBindArgumentValue();
            $expression = $value . $this->_connection->quoteInto($sql, $bindValue);
        }
        // values for multiselect attributes can be saved in comma-separated format
        // below is a solution for matching such conditions with selected values
        $attribute = $condition->getAttributeObject();
        if ($attribute && $attribute->getFrontendInput() === 'multiselect') {
            if (is_array($bindValue) && \in_array($conditionOperator, ['()', '{}'], true)) {
                foreach ($bindValue as $item) {
                    $expression .= $this->_connection->quoteInto(
                        " OR (FIND_IN_SET (?, {$this->_connection->quoteIdentifier($argument)}) > 0)",
                        $item
                    );
                }
            }
        }
        return $this->_expressionFactory->create(
            ['expression' => $expression]
        )->__toString();
    }
}
