<?php

namespace Branch8\Refund\Console\Command;

use Branch8\Refund\Cron\RefundScheduleExecute;
use Branch8\Refund\Cron\RefundStatusCheckInquiry;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI: manually trigger refund crons (inquiry or schedule execute).
 */
class RefundCron extends Command
{
    public const FUNCTION = 'function';

    private const LOG_CLASS_KEY = 'RefundCron';

    /** @var State */
    private $state;

    /** @var RefundStatusCheckInquiry */
    protected $refundStatusCheckInquiry;

    /** @var RefundScheduleExecute */
    protected $refundScheduleExecute;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param State $state Application state
     * @param RefundStatusCheckInquiry $refundStatusCheckInquiry Status polling cron
     * @param RefundScheduleExecute $refundScheduleExecute Execute pending refunds cron
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        State $state,
        RefundStatusCheckInquiry $refundStatusCheckInquiry,
        RefundScheduleExecute $refundScheduleExecute,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->state = $state;
        $this->refundStatusCheckInquiry = $refundStatusCheckInquiry;
        $this->refundScheduleExecute = $refundScheduleExecute;
        $this->refundLogger = $refundLogger;
        parent::__construct();
    }

    /**
     * Declares function name option (checkInquiry | executeSchedule).
     *
     * @return void
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::FUNCTION,
                null,
                InputOption::VALUE_REQUIRED,
                'function name'
            ),
        ];

        $this->setName('refund:manual:cron')
            ->setDescription('Hotai Refund Cron Manual')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Dispatch selected cron routine.
     *
     * @param InputInterface $input Console input
     * @param OutputInterface $output Console output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $functionName = $input->getOption(self::FUNCTION);
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        try {
            switch ($functionName) {
                case 'checkInquiry':
                    $this->refundStatusCheckInquiry->execute();
                    break;
                case 'executeSchedule':
                    $this->refundScheduleExecute->execute();
                    break;
                default:
                    print_r('no action');
                    break;
            }
        } catch (\Throwable $th) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $th, 'refund:manual:cron');
            $message = 'Exception message: ' . $th->getMessage();

            throw new \Exception($message);
        }

        return self::SUCCESS;
    }
}
