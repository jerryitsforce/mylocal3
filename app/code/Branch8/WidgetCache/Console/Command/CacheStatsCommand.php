<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

/**
 * Class CacheStatsCommand
 * 
 * Console command to display widget cache statistics
 */
class CacheStatsCommand extends Command
{
    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;

    /**
     * CacheStatsCommand constructor
     *
     * @param WidgetCacheHelper $widgetCacheHelper
     * @param string|null $name
     */
    public function __construct(
        WidgetCacheHelper $widgetCacheHelper,
        string $name = null
    ) {
        parent::__construct($name);
        $this->widgetCacheHelper = $widgetCacheHelper;
    }

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName('widget:cache:stats')
            ->setDescription('Display widget cache statistics');
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Widget Cache Statistics</info>');
        $output->writeln('========================');

        $stats = $this->widgetCacheHelper->getCacheStatistics();

        foreach ($stats as $key => $value) {
            $output->writeln(sprintf('<comment>%s:</comment> %s', ucwords(str_replace('_', ' ', $key)), $value ? 'Yes' : 'No'));
        }

        $output->writeln('');
        $output->writeln('<info>Widget cache is ' . ($stats['config_enabled'] ? 'enabled' : 'disabled') . '</info>');

        return 0;
    }
}
