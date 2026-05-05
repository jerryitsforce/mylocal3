<?php

namespace Branch8\Refund\Console\Command;

use Branch8\CTBC\Model\Api;
use Branch8\CTBC\Model\OrderManagement;
use Branch8\Refund\Helper\ConfigurableRefundLogger;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI: call individual CTBC refund API methods with a raw inquiry payload (manual tooling).
 */
class RefundManualApi extends Command
{
    public const ORDER_ID = 'orderId';
    public const FUNCTION = 'function';
    public const IS_CHILDORDER = 'is_childOrder';
    public const AMOUNT = 'amount';

    private const LOG_CLASS_KEY = 'RefundManualApi';

    /** @var Api */
    protected $api;

    /** @var State */
    protected $state;

    /** @var OrderManagement */
    protected $orderManagement;

    /** @var ConfigurableRefundLogger */
    protected $refundLogger;

    /**
     * @param Api $api CTBC API client
     * @param State $state Application state
     * @param OrderManagement $orderManagement CTBC order helper
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        Api $api,
        State $state,
        OrderManagement $orderManagement,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->state = $state;
        $this->api = $api;
        $this->orderManagement = $orderManagement;
        $this->refundLogger = $refundLogger;
        parent::__construct();
    }

    /**
     * Declares options for order id, function name, child flag and amount.
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
                self::IS_CHILDORDER,
                null,
                InputOption::VALUE_OPTIONAL,
                'is child order or not, default value is true'
            ),
            new InputOption(
                self::FUNCTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'function name'
            ),
            new InputOption(
                self::AMOUNT,
                null,
                InputOption::VALUE_OPTIONAL,
                'amount'
            ),
        ];

        $this->setName('refund:manual:api')
            ->setDescription('Hotai Manual Refund Swicher')
            ->setDefinition($options);

        parent::configure();
    }

    /**
     * Dispatch selected CTBC method and print the response.
     *
     * @param InputInterface $input Console input
     * @param OutputInterface $output Console output
     * @return int Command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(Area::AREA_CRONTAB);

        $orderId = $input->getOption(self::ORDER_ID);
        $isChildOrder = $input->getOption(self::IS_CHILDORDER) ?? true;
        $functionName = $input->getOption(self::FUNCTION) ?? '';
        $amount = $input->getOption(self::AMOUNT) ?? 0;

        try {
            $parentOrder = $this->orderManagement->getParentOrder($isChildOrder, $orderId);
            $this->api->setOrderInfo($parentOrder);

            $inquiryResult = $this->orderManagement->inquiryOrderStatus($parentOrder);
            $inquiryResult['orgAmt'] = $inquiryResult['amount'];
            $inquiryResult['AuthRRPID'] = trim($inquiryResult['XID']);

            switch ($functionName) {
                case 'authRevTransacOrder':
                    $inquiryResult['authnewAmt'] = $inquiryResult['amount'] - $amount;
                    $return = $this->api->authRevTransacOrder($inquiryResult);
                    break;
                case 'capRevTransacOrder':
                    $return = $this->api->capRevTransacOrder($inquiryResult);
                    break;
                case 'credTransacOrder':
                    $inquiryResult['credAmt'] = $amount;
                    $return = $this->api->credTransacOrder($inquiryResult);
                    break;
                case 'credRevTransacOrder':
                    $inquiryResult['orgAmt'] = $amount;
                    $return = $this->api->credRevTransacOrder($inquiryResult);
                    break;
                default:
                    $return = $inquiryResult;
                    break;
            }

            print_r($return);
        } catch (\Throwable $th) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $th, 'refund:manual:api');
            $message = 'Exception message: ' . $th->getMessage();

            throw new \Exception($message);
        }

        return self::SUCCESS;
    }
}
