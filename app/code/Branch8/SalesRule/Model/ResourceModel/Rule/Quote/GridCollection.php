<?php
declare(strict_types=1);

namespace Branch8\SalesRule\Model\ResourceModel\Rule\Quote;

class GridCollection extends \Magento\SalesRule\Model\ResourceModel\Rule\Quote\Collection
{
    /**
     * To hide campaign rules
     */
    public function _renderFiltersBefore()
    {
        $this->getSelect()->where('segment_id  IS NULL');
        parent::_renderFiltersBefore();
    }

    /**
     * @param $field
     * @param $condition
     * @return $this|GridCollection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'created_in') {
            if (isset($condition['from'])
                && $condition['from'] instanceof \DateTime) {
                $timestamp = $condition['from']->getTimestamp();
                $condition = ['gteq' => $timestamp];
            }
        } elseif ($field === 'updated_in') {
            if (isset($condition['from'])
                && $condition['from'] instanceof \DateTime) {
                $timestamp = $condition['from']->getTimestamp();
                $condition = ['lteq' => $timestamp];
            }
        }
        parent::addFieldToFilter($field, $condition);
        return $this;
    }

    public function _afterLoad()
    {
        parent::_afterLoad();
        $this->_logger->debug($this->getSelect()->__toString());
    }
}
