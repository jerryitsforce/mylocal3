<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;
use Branch8\WidgetCache\Helper\Log as ModuleLog;

/**
 * Class CacheClearCommand
 * 
 * Console command to clear widget cache
 */
class CacheClearCommand extends Command
{
    /**
     * Option name for tags
     */
    const OPTION_TAGS = 'tags';
    private const LOG_OPTION = 'CacheClearCommand';

    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;
    private ModuleLog $moduleLog;

    /**
     * CacheClearCommand constructor
     *
     * @param WidgetCacheHelper $widgetCacheHelper
     * @param ModuleLog $moduleLog Widget cache module logger helper.
     * @param string|null $name
     */
    public function __construct(
        WidgetCacheHelper $widgetCacheHelper,
        ModuleLog $moduleLog,
        string $name = null
    ) {
        parent::__construct($name);
        $this->widgetCacheHelper = $widgetCacheHelper;
        $this->moduleLog = $moduleLog;
    }

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName('widget:cache:clear')
            ->setDescription('Clear widget cache')
            ->addOption(
                self::OPTION_TAGS,
                't',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Clear cache by specific tags (comma-separated). If not specified, clears all widget cache.'
            );
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
        $output->writeln('<info>Clearing Widget Cache...</info>');
        $output->writeln('');

        try {
            $tags = $input->getOption(self::OPTION_TAGS);
            
            if (!empty($tags)) {
                // Parse tags if provided as comma-separated string
                $tagArray = [];
                foreach ($tags as $tagString) {
                    $parsedTags = array_map('trim', explode(',', $tagString));
                    $tagArray = array_merge($tagArray, $parsedTags);
                }
                
                $output->writeln(sprintf(
                    '<comment>Clearing cache for tags: %s</comment>',
                    implode(', ', $tagArray)
                ));
                
                $result = $this->widgetCacheHelper->clearWidgetCache($tagArray);
                
                if ($result) {
                    $output->writeln('<info>✓ Widget cache cleared successfully for specified tags.</info>');
                } else {
                    $output->writeln('<error>✗ Failed to clear widget cache.</error>');
                    return 1;
                }
            } else {
                $output->writeln('<comment>Clearing all widget cache...</comment>');
                
                $result = $this->widgetCacheHelper->clearWidgetCache();
                
                if ($result) {
                    $output->writeln('<info>✓ All widget cache cleared successfully.</info>');
                } else {
                    $output->writeln('<error>✗ Failed to clear widget cache.</error>');
                    return 1;
                }
            }
            
            $output->writeln('');
            $output->writeln('<info>Widget cache operation completed.</info>');
            
            return 0;
        } catch (\Exception $e) {
            $this->moduleLog->exception($e, self::LOG_OPTION, __METHOD__);
            $output->writeln('');
            $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
            return 1;
        }
    }
}

