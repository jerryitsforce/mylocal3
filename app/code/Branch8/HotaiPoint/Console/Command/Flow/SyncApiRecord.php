<?php

namespace Branch8\HotaiPoint\Console\Command\Flow;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Cron\SyncApiRecord as SyncApiRecordCron;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Branch8\HotaiPoint\Model\Config\Source\LogOption;

class SyncApiRecord extends Command
{
    const LOG_FOLDER_NAME           = 'HotaiPoint/Console/Command/Flow/SyncApiRecord';
    const OPTION_TARGET_DATE        = 'target_date';
    const OPTION_IGNORE_SYNC_STATUS = 'ignore_sync_status';
    private const DEBUG_LOG_OPTION = LogOption::LOG_CONSOLE_SYNC_API_RECORD;

    protected State                 $state;
    protected HotaiCoreCommonHelper $hotaiCoreCommonHelper;
    protected SyncApiRecordCron     $syncApiRecordCron;
    protected CommonHelper          $commonHelper;

    protected $targetDate;
    protected $ignoreSyncStatus = false;

    public function __construct(
        State $state,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        SyncApiRecordCron $syncApiRecordCron,
        CommonHelper $commonHelper
    ) {
        $this->state                 = $state;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->syncApiRecordCron     = $syncApiRecordCron;
        $this->commonHelper          = $commonHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:flow:SyncApiRecord');
        $this->setDescription('Pass in --target_date="2025-01-01" if you want to specify a target date, will execute function Branch8\HotaiPoint\Cron\SyncApiRecord::execute().');

        $this->addOption(
            self::OPTION_TARGET_DATE,
            null,
            InputOption::VALUE_REQUIRED,
            'input target_date like this: --target_date="2025-01-01"'
        );

        $this->addOption(
            self::OPTION_IGNORE_SYNC_STATUS,
            null,
            InputOption::VALUE_NONE,
            'input ignore_sync_status like this: --ignore_sync_status'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $this->commonHelper->writeLogIfEnabled(
            "Sync command start.",
            $this->syncApiRecordCron::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        try {
            $this->syncApiRecordCron->setForceExecute(true);

            if ($this->ignoreSyncStatus) {
                $this->syncApiRecordCron->setIgnoreSyncStatus(true);
            }

            if (!empty($this->targetDate)) {
                $this->syncApiRecordCron->setTargetTransDate($this->targetDate);
            }

            $this->syncApiRecordCron->execute();
        } catch (\Exception $e) {
            $this->commonHelper->writeLogIfEnabled(
                json_encode([
                    "Title"             => "Sync command fail.",
                    "Exception message" => $e->getMessage(),
                ]),
                $this->syncApiRecordCron::LOG_FOLDER_NAME,
                self::DEBUG_LOG_OPTION
            );
        }

        $this->commonHelper->writeLogIfEnabled(
            "Sync command end.",
            $this->syncApiRecordCron::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );

        $output->writeln("<info>Sync command done.</info>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數
     * @return void
     */
    protected function checkInputParameter(InputInterface $input): void
    {
        $this->targetDate       = $input->getOption(self::OPTION_TARGET_DATE);
        $this->ignoreSyncStatus = $input->getOption(self::OPTION_IGNORE_SYNC_STATUS);
    }
}
