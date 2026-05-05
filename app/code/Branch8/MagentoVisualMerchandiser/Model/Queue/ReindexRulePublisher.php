<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Model\Queue;

use Branch8\MagentoVisualMerchandiser\Model\Config;
use Magento\Framework\MessageQueue\PublisherInterface;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

/**
 * Publish media gallery synchronization queue.
 */
class ReindexRulePublisher
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC_PREFIX = 'vm.reindex.rules.by.id';

    /**
     * @var PublisherInterface
     */
    private $publisher;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private \Magento\Framework\Stdlib\DateTime\DateTime $date;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    private Config $config;

    /**
     * @param PublisherInterface $publisher
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        PublisherInterface                          $publisher,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        LoggerInterface                             $logger,
        Config                                      $config
    )
    {
        $this->date = $date;
        $this->publisher = $publisher;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @param int $ruleId
     * @return void
     */
    public function execute(int $ruleId): void
    {
        if (!$this->config->enabled()) {
            return;
        }
        try {
            $date = date('Y:m:d H:i:s');
            $data = json_encode([
                'rule_id' => $ruleId,
                'publish_date' => $date
            ]);
            $this->publisher->publish(
                self::TOPIC_PREFIX,
                $data
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
