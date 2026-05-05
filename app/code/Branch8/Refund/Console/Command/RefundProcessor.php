<?php

namespace Branch8\Refund\Console\Command;

use Branch8\CTBC\Model\Api;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Branch8\Refund\Helper\RefundOperation;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI: run {@see RefundOperation::execute} for a single order (manual processor).
 */
class RefundProcessor extends Command
{
    public const ORDER_ID = 'orderId';
    public const AMOUNT = 'amount';
    public const IS_CHILDORDER = 'is_childOrder';

    private const LOG_CLASS_KEY = 'RefundProcessor';

    /** @var Api */
    protected $api;

    /** @var State */
    private $state;

    /** @var RefundOperation */
    protected $refundOperation;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param Api $api CTBC API (reserved for future use / parity with legacy ctor)
     * @param RefundOperation $refundOperation Refund runner
     * @param State $state Application state for area code
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        Api $api,
        RefundOperation $refundOperation,
        State $state,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->state = $state;
        $this->api = $api;
        $this->refundOperation = $refundOperation;
        $this->refundLogger = $refundLogger;
        parent::__construct();
    }

    /**
     * Declares CLI options for order id, amount and child-order flag.
     *
     * @return void
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::ORDER_ID,
                null,
                InputOption::VALUE_REQUIRED,
                'order entity id'
            ),
            new InputOption(
                self::AMOUNT,
                null,
                InputOption::VALUE_OPTIONAL,
                'refund amount'
            ),
            new InputOption(
                self::IS_CHILDORDER,
                null,
                InputOption::VALUE_OPTIONAL,
                'is child order or not, default value is true'
            ),
        ];

        $this->setName('refund:manual:processor')
            ->setDescription('Hotai Refund Command Line')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Execute refund for the given options.
     *
     * @param InputInterface $input Console input
     * @param OutputInterface $output Console output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        $orderId = $input->getOption(self::ORDER_ID);
        $amount = $input->getOption(self::AMOUNT) ?? 0;
        $isChildOrder = $input->getOption(self::IS_CHILDORDER) ?? true;

        try {
            $this->refundOperation->execute($orderId, $amount, $isChildOrder);
        } catch (\Throwable $th) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $th, 'refund:manual:processor');
            $message = 'Exception message: ' . $th->getMessage();

            throw new \Exception($message);
        }

        return self::SUCCESS;
    }
}
