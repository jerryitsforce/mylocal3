<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Cron;

use Branch8\MagentoVisualMerchandiser\Model\Config;
use Branch8\MagentoVisualMerchandiser\Model\Queue\ReindexRulePublisher;
use Branch8\MagentoVisualMerchandiser\Model\RuleIndex;
use Magento\Framework\App\ResourceConnection;

class PublishVmRulesNeedToRebuild
{

    private ResourceConnection $resourceConnection;
    /**
     * @var Config
     */
    private Config $config;

    private \Branch8\MagentoVisualMerchandiser\Model\ResourceModel\RuleIndex\CollectionFactory $ruleindexCollectionFactory;
    private ReindexRulePublisher $reindexRulePublisher;

    /**
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\MagentoVisualMerchandiser\Model\ResourceModel\RuleIndex\CollectionFactory $ruleindexCollectionFactory
     * @param ReindexRulePublisher $reindexRulePublisher
     * @param Config $config
     */
    public function __construct(
        ResourceConnection                                                                 $resourceConnection,
        \Branch8\MagentoVisualMerchandiser\Model\ResourceModel\RuleIndex\CollectionFactory $ruleindexCollectionFactory,
        ReindexRulePublisher                                                               $reindexRulePublisher,
        Config                                                                             $config
    )
    {
        $this->config = $config;
        $this->ruleindexCollectionFactory = $ruleindexCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->reindexRulePublisher = $reindexRulePublisher;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {
        $this->resourceConnection->getConnection()->select();
        $throttle = $this->config->getThrottle();
        if (!$throttle) {
            $throttle = 60;
        }
        $processIds = [];
        /**
         * @var $collection \Branch8\MagentoVisualMerchandiser\Model\ResourceModel\RuleIndex\Collection
         */

        $pageSize = 100;
        $currentPage = 1;
        $collection = $this->ruleindexCollectionFactory->create();
        $collection->getSelect()->join('visual_merchandiser_rule', 'visual_merchandiser_rule.rule_id = main_table.vm_rule_id', [])
            ->where('status = ?', RuleIndex::STATUS_PENDING)
            ->where('last_index < NOW() - INTERVAL ' . $throttle . ' MINUTE');
        $collection->setPageSize($pageSize)
            ->setCurPage($currentPage);
        $pageCount = $collection->setPageSize($pageSize)->getLastPageNumber();
        for ($page = 1; $page <= $pageCount; $page++) {
            $collection->setCurPage($page);
            foreach ($collection as $item) {
                $processIds[] = ['vm_rule_id' => $item->getData('vm_rule_id'), 'status' => RuleIndex::STATUS_PUBLISHED];
                $this->reindexRulePublisher->execute((int)$item->getData('vm_rule_id'));
            }
            $collection->clear();
        }
        if ($processIds) {
            $this->resourceConnection->getConnection()->insertOnDuplicate('visual_merchandiser_rule_index', $processIds, ['status']);
        }
    }
}
