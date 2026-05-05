<?php

declare(strict_types=1);

namespace HotaiConnected\Report\Console\Command;

use HotaiConnected\Report\Model\PendingEmployee\ReportGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PendingEmployeeReportCommand extends Command
{
    private const OPT_DRY_RUN = 'dry-run';
    private const OPT_NO_SLACK = 'no-slack';
    private const OPT_NO_CSV = 'no-csv';
    private const OPT_MOCK_API = 'mock-api';

    /**
     * @var ReportGenerator
     */
    private ReportGenerator $reportGenerator;

    /**
     * @param ReportGenerator $reportGenerator
     * @param string|null $name
     */
    public function __construct(
        ReportGenerator $reportGenerator,
        string $name = null
    ) {
        $this->reportGenerator = $reportGenerator;
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName('report:pending-employee:generate')
            ->setDescription('Generate pending employee report and send to Slack')
            ->addOption(
                self::OPT_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Only run queries, do not call API, export CSV, or send Slack'
            )
            ->addOption(
                self::OPT_NO_SLACK,
                null,
                InputOption::VALUE_NONE,
                'Do not send Slack notification'
            )
            ->addOption(
                self::OPT_NO_CSV,
                null,
                InputOption::VALUE_NONE,
                'Do not export CSV file'
            )
            ->addOption(
                self::OPT_MOCK_API,
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
        $dryRun = (bool)$input->getOption(self::OPT_DRY_RUN);
        $sendSlack = !$input->getOption(self::OPT_NO_SLACK);
        $exportCsv = !$input->getOption(self::OPT_NO_CSV);
        $mockApi = (bool)$input->getOption(self::OPT_MOCK_API);

        $output->writeln('<info>Starting Pending Employee Report...</info>');
        $output->writeln('');

        if ($dryRun) {
            $output->writeln('<comment>Running in dry-run mode (no API call, no export, no Slack)</comment>');
            $output->writeln('');
        }

        if ($mockApi) {
            $output->writeln('<comment>Mock API mode enabled - using fake API response</comment>');
            $output->writeln('');
        }

        try {
            $result = $this->reportGenerator->generate($dryRun, $sendSlack, $exportCsv, $mockApi);

            // Display results
            $output->writeln('<info>數據統計</info>');
            $output->writeln(sprintf('• 和泰購員工：<comment>%d</comment> 筆', $result['registered_count']));
            $output->writeln(sprintf('• 非和泰購會員查詢：<comment>%d</comment> 筆', $result['query_count']));
            $output->writeln(sprintf('• 回應總數：<comment>%d</comment> 筆', $result['response_count']));
            $output->writeln(sprintf('• 差異數量：<comment>%d</comment> 筆', $result['diff_count']));
            $output->writeln('');

            if (!empty($result['missing_phones'])) {
                $output->writeln('<info>差異清單 (未在 API 回應中的 cellphone):</info>');
                foreach (array_slice($result['missing_phones'], 0, 20) as $phone) {
                    $output->writeln(sprintf('  - %s', $phone));
                }
                if (count($result['missing_phones']) > 20) {
                    $output->writeln(sprintf('  ... and %d more', count($result['missing_phones']) - 20));
                }
                $output->writeln('');
            }

            if ($result['export_file_path']) {
                $output->writeln(sprintf('<info>匯出檔案路徑:</info> %s', $result['export_file_path']));
            }

            if ($result['slack_sent']) {
                $output->writeln('<info>Slack 通知已發送</info>');
            } elseif ($sendSlack && !$dryRun) {
                $output->writeln('<error>Slack 通知發送失敗</error>');
            }

            $output->writeln('');
            $output->writeln('<info>Report generation completed successfully.</info>');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>Error: %s</error>', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
