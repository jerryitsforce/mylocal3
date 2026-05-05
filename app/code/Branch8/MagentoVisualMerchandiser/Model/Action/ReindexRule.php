<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Model\Action;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\VisualMerchandiser\Model\Category\Builder;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

/**
 *
 */
class ReindexRule
{
    private $timeStart = 0;
    private $timeEnd = 0;
    private $executeTime = 0;
    private LoggerInterface $logger;
    private $startMem = 0;
    private $usedMem = 0;

    private ResourceConnection $resourceConnection;
    /**
     * @var \Magento\VisualMerchandiser\Model\RulesFactory
     */
    private \Magento\VisualMerchandiser\Model\RulesFactory $rulesFactory;
    /**
     * @var Builder
     */
    private Builder $categoryBuilder;
    /**
     * @var LoggerInterface
     */
    /**
     * @var CategoryFactory
     */
    private CategoryFactory $categoryFactory;
    private CategoryRepository $categoryRepository;
    private \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder $advanceRuleBuilder;

    /**
     * @param \Magento\VisualMerchandiser\Model\RulesFactory $rulesFactory
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\MagentoVisualMerchandiser\Override\Magento\VisualMerchandiser\Model\Category\Builder $categoryBuilder
     * @param \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder $advanceRuleBuilder
     * @param CategoryRepository $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        \Magento\VisualMerchandiser\Model\RulesFactory                                                $rulesFactory,
        ResourceConnection                                                                            $resourceConnection,
        \Branch8\MagentoVisualMerchandiser\Override\Magento\VisualMerchandiser\Model\Category\Builder $categoryBuilder,
        \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Builder                                  $advanceRuleBuilder,
        CategoryRepository                                                                            $categoryRepository,
        CategoryFactory                                                                               $categoryFactory,
        LoggerInterface                                                                               $logger
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->rulesFactory = $rulesFactory;
        $this->logger = $logger;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryBuilder = $categoryBuilder;
        $this->advanceRuleBuilder = $advanceRuleBuilder;
    }

    /**
     * @param int $ruleId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Exception
     */
    public function execute(int $ruleId)
    {
        /**
         * @var $rules \Magento\VisualMerchandiser\Model\Rules
         */
        $this->resetAudit()->beginAudit();
        $this->log('Begin reindex rule:' . $ruleId);
        $rules = $this->rulesFactory->create()->load($ruleId);
        if (!$rules->getId()
            || !$rules->getIsActive()
            || !($category = $this->categoryRepository->get($rules->getCategoryId(), $rules->getStoreId()))
            || !$category->getId()
        ) {
            $this->endAudit()->summarize();
            return false;
        }
        $useAdvanceRule = (bool)$rules->getData('use_advance_rule');
        if ($useAdvanceRule) {
            $postedProducts = $this->advanceRuleBuilder->buildCategory($category);
        } else {
            $postedProducts = $this->categoryBuilder->buildCategory($category);
        }

        $category->setIgnoreRebuildSmartCategory(1);
        $category->setPostedProducts($postedProducts);
        $category->setDataChanges(true)->save();
        $this->endAudit()->summarize();
        return true;
    }

    /**
     * @return $this
     */
    private function beginAudit()
    {
        $this->logger->info("=======================================================================");
        $this->timeStart = microtime(true);
        $this->startMem = memory_get_usage();
        return $this;
    }

    /**
     * @return ReindexRule
     */
    private function endAudit()
    {
        $this->timeEnd = microtime(true);
        $this->executeTime = ($this->timeEnd - $this->timeStart) / 60;
        $this->usedMem = memory_get_usage() - $this->startMem;
        $this->summarize();
        return $this;
    }

    /**
     * @return $this
     */
    private function resetAudit()
    {
        $this->timeStart = 0;
        $this->timeEnd = 0;
        $this->startMem = 0;
        $this->usedMem = 0;
        $this->executeTime = 0;
        return $this;
    }

    /**
     * @param $message
     * @return ReindexRule
     */
    private function log($message)
    {
        $this->logger->info($message);
        return $this;
    }

    /**
     * @return $this
     */
    private function summarize()
    {
        $peak = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $usedMem = round($this->usedMem / 1024 / 1024, 2);
        $minutes = round($this->executeTime / 60);
        $this->logger->info("Execution time: {$this->executeTime} seconds - {$minutes} minutes");
        $this->logger->info("Memory Usage: {$usedMem} MB");
        $this->logger->info("Memory Peak: {$peak} MB");
        return $this;
    }
}
