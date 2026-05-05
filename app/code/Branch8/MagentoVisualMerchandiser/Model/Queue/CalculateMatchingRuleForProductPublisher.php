<?php
declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Model\Queue;

use Branch8\MagentoVisualMerchandiser\Model\Config;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile;
use Magento\Catalog\Model\Product;
use Magento\Framework\MessageQueue\PublisherInterface;
use mysql_xdevapi\XSession;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

/**
 * Publish media gallery synchronization queue.
 */
class CalculateMatchingRuleForProductPublisher
{
    /**
     * Media gallery synchronization queue topic name.
     */
    private const TOPIC_PREFIX = 'vm.find.rules.by.product.update';

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
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        PublisherInterface                          $publisher,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        Config                                      $config,
        LoggerInterface                             $logger
    )
    {
        $this->config = $config;
        $this->date = $date;
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * @param Product $product
     * @return void
     */
    public function execute(Product $product): void
    {
        if (!$this->config->enabled()) {
            return;
        }
        try {
            $date = date('Y:m:d H:i:s');
            $data = json_encode([
                'product_id' => $product->getId(),
                'publish_date' => $date
            ]);
            $this->logger->info("CalculateMatchingRuleForProductPublisher:".$data);
            $this->publisher->publish(
                self::TOPIC_PREFIX,
                $data
            );
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }
}
