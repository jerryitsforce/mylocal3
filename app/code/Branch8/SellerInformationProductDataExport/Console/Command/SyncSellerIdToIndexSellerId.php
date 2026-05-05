<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\MediaGallerySynchronizationApi\Api\SynchronizeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Synchronize files in media storage and media assets database records
 */
class SyncSellerIdToIndexSellerId extends Command
{
    private \Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId $sellerIdToIndexSellerId;

    /**
     * @param SynchronizeInterface $synchronizeAssets
     * @param \Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId $sellerIdToIndexSellerId
     */
    public function __construct(
        \Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId $sellerIdToIndexSellerId
    )
    {
        $this->sellerIdToIndexSellerId = $sellerIdToIndexSellerId;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('sync:sellerId:indexSellerId');
        $this->addOption(
            'productIds',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Product Ids'
        );
        $this->setDescription(
            'Sync sellerId to IndexSellerId attribute'
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
       // $output->writeln('Begin Sync Seller Id to Index Seller Id');
        $productIds = (string)$input->getOption('productIds');
        if ($productIds && $ids = explode(',', $productIds)) {
            $this->sellerIdToIndexSellerId->syncIds($ids);
            $this->sellerIdToIndexSellerId->syncShopNameIds($ids);
            $this->sellerIdToIndexSellerId->syncLivesearchCategoriesIds($ids);
            $this->sellerIdToIndexSellerId->syncLivesearchInstockIds($ids);
            $this->sellerIdToIndexSellerId->syncNeedToRefillIds($ids);
        } else {
            $this->sellerIdToIndexSellerId->syncAll();
            $this->sellerIdToIndexSellerId->syncShopNameAll();
            $this->sellerIdToIndexSellerId->syncLivesearchCategoriesAll();
            $this->sellerIdToIndexSellerId->syncLivesearchInstockAll();
            $this->sellerIdToIndexSellerId->syncNeedToRefillAll();
        }
        $output->writeln('Completed!');

        return Cli::RETURN_SUCCESS;
    }
}
