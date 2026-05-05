<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel\Team;

use Branch8\HelpDesk\Api\Data\MessageSearchResultInterface;
use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Team Collection
 */
class Collection extends AbstractCollection implements OptionSourceInterface, SearchResultInterface
{
    /**
     * @var string
     */
    protected $_idFieldName = 'team_id';
    /**
     * @var
     */
    protected $searchCriteria;

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Branch8\HelpDesk\Model\Team',
            'Branch8\HelpDesk\Model\ResourceModel\Team'
        );
    }

    /**
     * @return $this|Collection|void
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @inheritdoc
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * @return null
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return $this|\Branch8\HelpDesk\Model\ResourceModel\Team\Collection
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null)
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->getSize();
    }

    /**
     * @param $totalCount
     * @return $this|Collection
     */
    public function setTotalCount($totalCount)
    {
        return $this;
    }

    /**
     * @param array|null $items
     * @return $this|Collection
     */
    public function setItems(array $items = null)
    {
        return $this;
    }

    protected function _afterLoad()
    {
        $linkField = 'team_id';
        $linkedIds = $this->getColumnValues($linkField);
        if (count($linkedIds)) {
            $allMembers = $this->getTeamMembers($linkField, $linkedIds);
            if ($allMembers) {
                foreach ($this->getItems() as $item) {
                    $linkedId = $item->getData($linkField);
                    if (!isset($allMembers[$linkedId])) {
                        continue;
                    }
                    $item->setData('assigned_members', $allMembers[$linkedId]);
                }
            }
        }
        return parent::_afterLoad();
    }

    /**
     * Get all Team Member of Collection
     * @param $linkField
     * @param $linkedIds
     * @return array
     */
    protected function getTeamMembers($linkField, $linkedIds)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['branch8_helpdesk_team_user' => $this->getTable('branch8_helpdesk_team_user')])
            ->where('branch8_helpdesk_team_user.' . $linkField . ' IN (?)', $linkedIds);
        $result = $connection->fetchAll($select);
        $teamMembers = [];
        foreach ($result as $item) {
            $teamMembers[$item[$linkField]][] = $item['user_id'];
        }
        return $teamMembers;
    }
}
