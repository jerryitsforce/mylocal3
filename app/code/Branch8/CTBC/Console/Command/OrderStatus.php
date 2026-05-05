<?php

namespace Branch8\CTBC\Console\Command;

use Branch8\CTBC\Model\Api;
use Branch8\CTBC\Model\OrderManagement;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\CTBC\Helper\Log as CtbcLog;
use Symfony\Component\Console\Style\SymfonyStyle;

class OrderStatus extends Command
{

    public const ORDER_ID = 'orderId';
    public const IS_CHILDORDER = 'is_childOrder';
    private const LOG_CLASS = 'OrderStatus';

    /** @var \Branch8\CTBC\Model\Api $api */
    protected $api;
    
    /** @var \Magento\Framework\App\State $state */
    protected $state;
    
    /** @var mixed $refundOperation */
    protected $refundOperation;
    
    /** @var \Branch8\CTBC\Model\OrderManagement $orderManagement */
    protected $orderManagement;

    /** @var CtbcLog */
    protected CtbcLog $ctbcLog;

    /**
     * @param Api $api CTBC API client.
     * @param State $state Magento application state.
     * @param OrderManagement $orderManagement CTBC order management.
     * @param CtbcLog $ctbcLog CTBC log facade.
     */
    public function __construct(
        Api $api,
        State $state,
        OrderManagement $orderManagement,
        CtbcLog $ctbcLog
    ) {
        $this->state = $state;
        $this->api = $api;
        $this->orderManagement = $orderManagement;
        $this->ctbcLog = $ctbcLog;
        parent::__construct();
    }

    /**
     * Configure CLI command options.
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
                'Order entity id (integer)'
            ),
            new InputOption(
                self::IS_CHILDORDER,
                null,
                InputOption::VALUE_REQUIRED,
                'Whether orderId is a child order id (1/0, true/false). Default: 1',
                '1'
            ),

        ];

        $this->setName('ctbc:order:status')
            ->setDescription('Ctbc Order Status Command Line')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Execute the CLI command.
     *
     * @param InputInterface $input Console input.
     * @param OutputInterface $output Console output.
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->ensureAreaCode(Area::AREA_CRONTAB);
        } catch (\Throwable $th) {
            $this->logException($th, ['context' => 'ensureAreaCode']);
            $io->error('Failed to initialize Magento area code.');
            return self::FAILURE;
        }

        $orderIdOption = $input->getOption(self::ORDER_ID);
        $orderId = is_numeric($orderIdOption) ? (int) $orderIdOption : 0;
        if ($orderId <= 0) {
            $io->error('Invalid --orderId. It must be a positive integer.');
            return self::FAILURE;
        }

        $isChildOrder = $this->parseBooleanOption(
            $input->getOption(self::IS_CHILDORDER),
            true
        );

        try {
            $parentOrder = $this->orderManagement->getParentOrder($isChildOrder, $orderId);
            $this->api->setOrderInfo($parentOrder);
            $inquiryResult = $this->orderManagement->inquiryOrderStatus($parentOrder);

            $resultJson = json_encode(
                $inquiryResult,
                JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ) ?: '[]';

            // Mirror CLI output to file log when admin CTBC debug log is enabled for this class.
            $this->ctbcLog->write(
                '[InquiryResult] orderId=' . $orderId
                . ' isChildOrder=' . ($isChildOrder ? '1' : '0')
                . ' ' . $resultJson,
                self::LOG_CLASS
            );

            $output->writeln($resultJson);
        } catch (\Throwable $th) {
            $this->logException($th, [
                'orderId' => (string) $orderId,
                'isChildOrder' => $isChildOrder ? '1' : '0',
            ]);
            $io->error('CTBC inquiry failed. Please check logs for details.');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Ensure Magento application area code is set.
     *
     * @param string $areaCode Magento area code.
     * @return void
     */
    private function ensureAreaCode(string $areaCode): void
    {
        try {
            $this->state->getAreaCode();
            return;
        } catch (\Throwable $th) {
            $this->state->setAreaCode($areaCode);
        }
    }

    /**
     * Parse a boolean-like CLI option value.
     *
     * @param mixed $value Option raw value.
     * @param bool $default Default boolean if value is null/empty or unrecognized.
     * @return bool
     */
    private function parseBooleanOption(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }

        return $default;
    }

    /**
     * Log an exception using CTBC log facade (respects admin configuration).
     *
     * @param \Throwable $throwable Throwable instance.
     * @param array<string,string> $context Extra context key-value pairs.
     * @return void
     */
    private function logException(\Throwable $throwable, array $context = []): void
    {
        $contextString = '';
        if (!empty($context)) {
            $contextString = ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $this->ctbcLog->write(
            sprintf(
                '[Exception] %s | %s:%d%s',
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine(),
                $contextString
            ),
            self::LOG_CLASS
        );
    }
}
