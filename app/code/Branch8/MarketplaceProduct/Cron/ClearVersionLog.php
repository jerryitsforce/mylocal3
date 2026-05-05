<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Cron;

use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\CollectionFactory as ProductVersionCollectionFactory;
use Branch8\MarketplaceProduct\Model\Config as B8MarketplaceConfig;

/**
 * Class ClearVersionLog
 * @package Branch8\MarketplaceProduct\Cron
 */
class ClearVersionLog
{
    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var ProductVersionCollectionFactory
     */
    private ProductVersionCollectionFactory $productVersionCollectionFactory;

    /**
     * @var B8MarketplaceConfig
     */
    private B8MarketplaceConfig $b8MarketplaceConfig;

    /**
     * @var \Branch8\MarketplaceProduct\Helper\Data
     */
    private \Branch8\MarketplaceProduct\Helper\Data $helper;

    /**
     * ClearVersionLog Constructor.
     *
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param ProductVersionCollectionFactory $productVersionCollectionFactory
     * @param B8MarketplaceConfig $b8MarketplaceConfig
     * @param \Branch8\MarketplaceProduct\Helper\Data $helper
     */
    public function __construct(
        ProductVersionRepositoryInterface $productVersionRepository,
        ProductVersionCollectionFactory $productVersionCollectionFactory,
        B8MarketplaceConfig $b8MarketplaceConfig,
        \Branch8\MarketplaceProduct\Helper\Data $helper
    ) {
        $this->productVersionRepository = $productVersionRepository;
        $this->productVersionCollectionFactory = $productVersionCollectionFactory;
        $this->b8MarketplaceConfig = $b8MarketplaceConfig;
        $this->helper = $helper;
    }

    /**
     * Clear Version Log Cron Job over 90 days.
     *
     * @return void
     * @throws \Zend_Log_Exception
     */
    public function execute()
    {
        try {
            $lifetime = $this->b8MarketplaceConfig->getVersionLifetime();
            $productVersionList = $this->productVersionCollectionFactory->create();
            $productVersionList->addFieldToFilter('status', ['neq' => 0]);
            $productVersionList->getSelect()->where(
                new \Zend_Db_Expr('DATE_SUB(NOW(), INTERVAL '. $lifetime .' DAY) > created_at')
            );
            if ($productVersionList->getSize() > 0) {
                if ($this->helper->isLogTypeEnabled('marketplace_version.log')) {
                    $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/marketplace_version.log');
                    $logger = new \Zend_Log();
                    $logger->addWriter($writer);
                    $logger->info(print_r('=== Start ===', true));
                } else {
                    $logger = false;
                }

                foreach ($productVersionList as $productVersion) {
                    if ($logger) {
                        $logger->info(print_r('ID = ' . $productVersion->getId() .
                            ' - PID = ' . $productVersion->getProductId() . ' - ' . $productVersion->getCreatedAt() .
                            ' - FROM = ' . $productVersion->getCreatedFrom() . ' - STATUS = ' . $productVersion->getStatus() .
                            ' - REVIEWER = ' . $productVersion->getReviewerId(), true));
                        $logger->info(print_r($productVersion->getAdditionalInformation(), true));
                    }
                    $this->productVersionRepository->deleteById($productVersion->getId());
                }
                if ($logger) {
                    $logger->info(print_r('=== End ===', true));
                }
            }
        } catch (\Exception $e) {
            if ($this->helper->isLogTypeEnabled('marketplace_version.log')) {
                $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/marketplace_version.log');
                $logger = new \Zend_Log();
                $logger->addWriter($writer);
                $logger->info(print_r($e->getMessage(), true));
            }
        }
    }
}
