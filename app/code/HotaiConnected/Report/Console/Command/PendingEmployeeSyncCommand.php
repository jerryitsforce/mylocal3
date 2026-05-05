<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Console\Command;

use HotaiConnected\Report\Model\PendingEmployee\SyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PendingEmployeeSyncCommand extends Command
{
    private const ARGUMENT_ONE_IDS = 'one_ids';
    private const OPTION_DRY_RUN = 'dry-run';
    private const OPTION_NO_SLACK = 'no-slack';
    private const OPTION_MOCK_API = 'mock-api';

    /**
     * @var SyncService
     */
    private SyncService $syncService;

    /**
     * @param SyncService $syncService
     * @param string|null $name
     */
    public function __construct(
        SyncService $syncService,
        string $name = null
    ) {
        parent::__construct($name);
        $this->syncService = $syncService;
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('report:pending-employee:sync')
            ->setDescription('Sync pending employees by One IDs')
            ->addArgument(
                self::ARGUMENT_ONE_IDS,
                InputArgument::REQUIRED,
                'One IDs to sync (comma-separated)'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Only query, do not insert/update or send Slack'
            )
            ->addOption(
                self::OPTION_NO_SLACK,
                null,
                InputOption::VALUE_NONE,
                'Do not send Slack notification'
            )
            ->addOption(
                self::OPTION_MOCK_API,
                null,
                InputOption::VALUE_NONE,
                'Use mock API response instead of real API call'
            );
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $oneIdsInput = $input->getArgument(self::ARGUMENT_ONE_IDS);
        $dryRun = (bool)$input->getOption(self::OPTION_DRY_RUN);
        $sendSlack = !$input->getOption(self::OPTION_NO_SLACK);
        $mockApi = (bool)$input->getOption(self::OPTION_MOCK_API);

        // Parse One IDs
        $oneIds = array_filter(
            array_map('trim', explode(',', $oneIdsInput)),
            fn($id) => !empty($id)
        );

        if (empty($oneIds)) {
            $output->writeln('<error>No valid One IDs provided.</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Starting sync with %d One IDs...</info>', count($oneIds)));

        if ($dryRun) {
            $output->writeln('<comment>Dry run mode enabled - no changes will be made.</comment>');
        }

        if ($mockApi) {
            $output->writeln('<comment>Mock API mode enabled - using fake API response</comment>');
        }

        try {
            $result = $this->syncService->sync($oneIds, $dryRun, $sendSlack, $mockApi);

            $output->writeln('');
            $output->writeln('<info>=== Sync Result ===</info>');
            $output->writeln('');

            $output->writeln('<info>Group A (Existing Customers):</info>');
            $output->writeln(sprintf('  Total: %d', $result['group_a']['count']));
            $output->writeln(sprintf('  Inserted: %d', $result['group_a']['inserted']));
            $output->writeln(sprintf('  Updated: %d', $result['group_a']['updated']));
            $output->writeln(sprintf('  Failed: %d', $result['group_a']['failed']));

            if (!empty($result['group_a']['failed_one_ids'])) {
                $output->writeln('  <error>Failed One IDs:</error>');
                foreach ($result['group_a']['failed_one_ids'] as $oneId) {
                    $output->writeln(sprintf('    - %s', $oneId));
                }
            }

            $output->writeln('');

            $output->writeln('<info>Group B (Non-Customers - API Query):</info>');
            $output->writeln(sprintf('  Total: %d', $result['group_b']['count']));
            $output->writeln(sprintf('  API Query Count: %d', $result['group_b']['query_count']));
            $output->writeln(sprintf('  API Response Count: %d', $result['group_b']['response_count']));
            $output->writeln(sprintf('  Inserted: %d', $result['group_b']['inserted']));
            $output->writeln(sprintf('  Updated: %d', $result['group_b']['updated']));
            $output->writeln(sprintf('  Diff Count: %d', $result['group_b']['diff_count']));

            if (!empty($result['group_b']['missing_one_ids'])) {
                $output->writeln('');
                $output->writeln('<comment>Missing One IDs (not in API response):</comment>');
                foreach ($result['group_b']['missing_one_ids'] as $oneId) {
                    $output->writeln(sprintf('  - %s', $oneId));
                }
            }

            $output->writeln('');

            if ($result['slack_sent']) {
                $output->writeln('<info>Slack notification sent successfully.</info>');
            } elseif (!$sendSlack) {
                $output->writeln('<comment>Slack notification skipped (--no-slack).</comment>');
            } elseif ($dryRun) {
                $output->writeln('<comment>Slack notification skipped (--dry-run).</comment>');
            } else {
                $output->writeln('<error>Slack notification failed.</error>');
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
