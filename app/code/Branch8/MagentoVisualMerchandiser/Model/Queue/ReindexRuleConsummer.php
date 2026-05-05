<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Model\Queue;

use Branch8\MagentoVisualMerchandiser\Model\Action\GetProductMatchRules;
use Branch8\MagentoVisualMerchandiser\Model\Action\ReindexRule;
use Branch8\MagentoVisualMerchandiser\Model\Config;
use Branch8\MagentoVisualMerchandiser\Model\RuleIndex;
use Magento\Framework\App\ResourceConnection;
use Branch8\MagentoVisualMerchandiser\Model\RuleIndexFactory;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

/**
 * Publish media gallery synchronization queue.
 */
class ReindexRuleConsummer
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var Config
     */
    private Config $config;
    /**
     * @var GetProductMatchRules
     */
    private GetProductMatchRules $getProductMatchingRules;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    private RuleIndexFactory $ruleIndexFactory;

    private ReindexRule $reindexRule;

    /**
     * @param GetProductMatchRules $getProductMatchRules
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     * @param RuleIndexFactory $reindexRuleFactory
     * @param ReindexRule $reindexRule
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetProductMatchRules $getProductMatchRules,
        ResourceConnection   $resourceConnection,
        Config               $config,
        RuleIndexFactory     $reindexRuleFactory,
        ReindexRule          $reindexRule,
        LoggerInterface      $logger
    )
    {
        $this->reindexRule = $reindexRule;
        $this->ruleIndexFactory = $reindexRuleFactory;
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
        $this->getProductMatchingRules = $getProductMatchRules;
        $this->logger = $logger;
    }

    /**
     * @param $jsonData
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute($jsonData)
    {
        if (!$this->config->enabled()) {
            return;
        }
        try {
            $data = @json_decode($jsonData, TRUE);
            if (empty($data) || empty($data['rule_id'])) {
                return;
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return;
        }
        $this->logger->info("Reindexing rules for: {$data['rule_id']}");
        /**
         * @var \Branch8\MagentoVisualMerchandiser\Model\RuleIndex $ruleIndex
         */
        $ruleIndex = $this->ruleIndexFactory->create();
        $ruleIndex->load($data['rule_id'], 'vm_rule_id');
        if (!$ruleIndex->getId()) {
            return;
        }
        $ruleIndex->setData('status', RuleIndex::STATUS_PROCESSING)->setDataChanges(true)->getResource()->save($ruleIndex);
        try{
            $this->reindexRule->execute((int)$data['rule_id']);
        }catch (\Exception $e){
            $this->logger->critical($e->getMessage());
            $this->logger->info("ReindexRuleConsummer rules for: {$data['rule_id']}");
        }
        $ruleIndex->setData('status', RuleIndex::STATUS_DONE)
            ->setData('last_index', date('Y-m-d H:i:s'))
            ->setDataChanges(true)->getResource()->save($ruleIndex);
    }
}
