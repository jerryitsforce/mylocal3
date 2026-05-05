<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\PointMoneyConfig\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\PointMoneyConfig\Model\Services\SyncPointMoney as Services;

/**
 * Synchronize files in media storage and media assets database records
 */
class SetPointMoney extends Command
{
    /** @var \Magento\Framework\App\State $state */
    protected $state;

    /**
     * @var Services
     */
    private $services;

    /**
     * @param Services $services
     */
    public function __construct(
        Services $services,
        State $state
    )
    {
        $this->services = $services;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('sync:setPointMoney');
        $this->addOption(
            'productIds',
            'p',
            InputOption::VALUE_OPTIONAL,
            'Product Ids'
        );
        $this->setDescription(
            'set Point Money attribute'
        );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_ADMINHTML);

        $productIds = (string)$input->getOption('productIds');
        if ($productIds && $ids = explode(',', $productIds)) {
            $this->services->syncIds($ids);
        } else {
            $this->services->syncAll();
        }
        $output->writeln('Completed!');

        return Cli::RETURN_SUCCESS;
    }
}
