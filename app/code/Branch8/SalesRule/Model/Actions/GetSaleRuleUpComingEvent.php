<?php

namespace Branch8\SalesRule\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Entity\Upcoming\SearchResultFactory;
use Magento\Staging\Model\VersionManager;

class GetSaleRuleUpComingEvent
{

    protected $searchResultFactory;
    private ResourceConnection $resourceConnection;
    private VersionManager $versionManager;
    private $ruleToEvent = [];

    /**
     * @param SearchResultFactory $searchResultFactory
     * @param ResourceConnection $resourceConnection
     * @param VersionManager $versionManager
     */
    public function __construct(
        SearchResultFactory $searchResultFactory,
        ResourceConnection  $resourceConnection,
        VersionManager      $versionManager,
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->searchResultFactory = $searchResultFactory;
        $this->versionManager = $versionManager;
    }

    /**
     * @param $id
     * @return array|string
     */
    public function get($id)
    {
        if (isset($this->ruleToEvent[$id])) {
            return $this->ruleToEvent[$id];
        }
        $upcomingEvent = [];
        try {
            $upcomingEvent = $this->getUpcommingEvents($id);
        } catch (\Exception $e) {
            return $upcomingEvent;
        }
        $this->ruleToEvent[$id] = $upcomingEvent;
        return $this->ruleToEvent[$id];
    }

    /**
     * @param $id
     * @return string
     * @throws \Zend_Db_Select_Exception
     */
    private function getUpcommingEvents($id)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from(['main_table' => 'staging_update'], ['*', 'start_time']);
        $connection = $this->resourceConnection->getConnection();
        $select->where(sprintf('main_table.%s IS NULL', UpdateInterface::IS_ROLLBACK));
        $select->joinInner(
            ['entity_table' => $connection->getTableName('salesrule')],
            'main_table.id = entity_table.created_in',
            ['rule_id' => 'rule_id']
        );
        $select->where(
            'entity_table.created_in > ' . $this->versionManager->getCurrentVersion()->getId() .
            ' OR (entity_table.updated_in > ' . $this->versionManager->getCurrentVersion()->getId() .
            ' AND entity_table.updated_in < ' . VersionManager::MAX_VERSION . ' )'
        );
        $select->where(
            'entity_table.rule_id' . ' = ?',
            $id
        );

        $select->joinLeft(
            ['rollbacks' => $connection->getTableName('staging_update')],
            sprintf(
                '%s.%s = %s.%s',
                'main_table',
                'rollback_id',
                'rollbacks',
                'id'
            ),
            [
                'end_time' => 'start_time',
            ]
        );
        $select->setPart('disable_staging_preview', true);
        return $connection->fetchRow($select);
    }
}
