<?php

declare(strict_types=1);

namespace Branch8\MagentoVisualMerchandiser\Observer;

use Branch8\MagentoVisualMerchandiser\Model\Config;
use Branch8\MagentoVisualMerchandiser\Model\Queue\CalculateMatchingRuleForProductPublisher;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\State;
use Magento\Framework\Event\ObserverInterface;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

/**
 * Resize product images after the product is saved
 */
class CalculateProductMatchRulesPublisher implements ObserverInterface
{
    /**
     * @var CalculateMatchingRuleForProductPublisher
     */
    private CalculateMatchingRuleForProductPublisher $publisher;
    /**
     * @var Config
     */
    private Config $config;
    /**
     * @var State
     */
    private State $state;
    private LoggerInterface $logger;

    /**
     * @param CalculateMatchingRuleForProductPublisher $publisher
     * @param State $state
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        CalculateMatchingRuleForProductPublisher $publisher,
        State                                    $state,
        Config                                   $config,
        LoggerInterface                          $logger
    )
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->publisher = $publisher;
        $this->state = $state;
    }

    /**
     * Process event on 'save_commit_after' event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var Product $product
         */
        $product = $observer->getEvent()->getProduct();
        if ($this->state->isAreaCodeEmulated()) {
            return;
        }
        if ($this->config->enabled() && $product) {
            $this->publisher->execute($product);
        }
    }
}
