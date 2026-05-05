<?php

namespace Branch8\Sales\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Branch8\HotaiCore\Model\Order\State as OrderState;
use Branch8\HotaiCore\Model\Order\Status as OrderStatus;
use Ecpay\General\Helper\Services\Common\OrderService;
use Magento\Sales\Model\Order;

class CreateMagentoInvoiceForEcpayInvoiceDoneOrder extends Command
{
    const LOG_FOLDER_NAME = 'Sales/Console/Command/CreateMagentoInvoiceForEcpayInvoiceDoneOrder';
    const OPTION_ORDER_IDS = 'order_ids';
    const OLD_HOTAI_ORDER_PREFIX = 'hotai_order';

    /** @var State */
    protected $state;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var OrderCollectionFactory */
    protected $orderCollectionFactory;

    /** @var OrderService */
    protected $orderService;

    protected $connection;
    protected $orderIds;
    protected $executeAll;
    protected $handleLog  = [];
    protected $errorLog   = [];

    public function __construct(
        State $state,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ResourceConnection $resourceConnection,
        OrderCollectionFactory $orderCollectionFactory,
        OrderService $orderService
    ) {
        $this->state                  = $state;
        $this->hotaiCoreCommonHelper  = $hotaiCoreCommonHelper;
        $this->resourceConnection     = $resourceConnection;
        $this->connection             = $this->resourceConnection->getConnection();
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderService           = $orderService;
        $this->executeAll             = false;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('sales:CreateMagentoInvoiceForEcpayInvoiceDoneOrder');
        $this->setDescription('Create Magento invoice for ECPay invoice done order.');

        $this->addOption(
            self::OPTION_ORDER_IDS,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_ids like this: --order_ids="123,456"'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $this->handle();

        $this->writeLog();

        if (count($this->errorLog) > 0) {
            $output->writeln("<error>Error count: " . count($this->errorLog) . "</error>");
        }

        $output->writeln("<info>Success count: " . count($this->handleLog) . "</info>");

        $output->writeln("<info>Done.</info>");

        return Command::SUCCESS;
    }

    /**
     * Check input parameters.
     * @return void
     */
    protected function checkInputParameter(InputInterface $input): void
    {
        $this->orderIds = $input->getOption(self::OPTION_ORDER_IDS);

        if ($this->orderIds === 'all') {
            $this->executeAll = true;
            return;
        }

        if (empty($this->orderIds)) {
            throw new \Exception("Input " . self::OPTION_ORDER_IDS . " is mandatory.");
        }

        $orderIdsArray = explode(',', $this->orderIds);

        $checkLog = [];
        foreach ($orderIdsArray as $orderId) {
            if (!is_numeric($orderId)) {
                $checkLog[] = "Order ID '{$orderId}' is not a valid number.";
            }
        }

        if (!empty($checkLog)) {
            throw new \Exception(json_encode($checkLog));
        }
    }

    protected function handle()
    {
        $orderCollection = $this->getTargetOrderCollection();

        if (!$this->executeAll) {
            foreach ($orderCollection as $order) {
                $this->checkOrderFields($order);
            }
        }

        foreach ($orderCollection as $order) {
            $walkthroughLog = [];
            $orderId        = $order->getId();
            $oriState       = $order->getState();
            $oriStatus      = $order->getStatus();

            try {
                if ($this->executeAll && $this->checkIsOldHotaiOrder($order)) {
                    continue;
                }

                $walkthroughLog[] = "Order ID: {$orderId}, Original State: {$oriState}, Original Status: {$oriStatus}";

                $this->updateOrder(
                    $orderId,
                    [
                        'state'  => $this->getTempState(),
                        'status' => $this->getTempStatus(),
                    ]
                );

                $walkthroughLog[] = "Order ID: {$orderId} - Setting temporary state({$this->getTempState()}) and status({$this->getTempStatus()}).";

                $this->orderService->setOrderInvoice($orderId);

                $walkthroughLog[] = "Order ID: {$orderId} - setOrderInvoice function handle done.";

                $this->updateOrder(
                    $orderId,
                    [
                        'state'  => $oriState,
                        'status' => $oriStatus,
                    ]
                );

                $walkthroughLog[] = "Order ID: {$orderId} - Restored original state({$oriState}) and status({$oriStatus}).";

                $this->handleLog[] = [
                    'order_id'        => $orderId,
                    'walkthrough_log' => $walkthroughLog,
                ];
            } catch (\Exception $e) {
                $this->updateOrder(
                    $orderId,
                    [
                        'state'  => $oriState,
                        'status' => $oriStatus,
                    ]
                );

                $walkthroughLog[] = "Order ID: {$orderId} - Restored original state({$oriState}) and status({$oriStatus}) due to error.";

                $this->errorLog[] = [
                    'order_id'        => $orderId,
                    'error'           => $e->getMessage(),
                    'walkthrough_log' => $walkthroughLog,
                ];
            }
        }
    }

    protected function getTargetOrderCollection(): OrderCollection
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToSelect([
            'entity_id',
            'state',
            'status',
            'ecpay_invoice_tag',
            'increment_id',
        ]);
        $collection->getSelect()->joinLeft(
            ['sales_invoice' => $this->resourceConnection->getTableName('sales_invoice')],
            'main_table.entity_id = sales_invoice.order_id',
            [
                'order_id' => 'sales_invoice.order_id',
            ]
        );

        if ($this->executeAll) {
            $collection->addFieldToFilter(
                'ecpay_invoice_tag',
                ['eq' => 1]
            );

            $collection->addFieldToFilter(
                'main_table.state',
                ['in' => [OrderState::STATE_CANCELED, OrderState::STATE_COMPLETE]]
            );

            $collection->addFieldToFilter(
                'order_id',
                ['null' => true]
            );

            $collection->addFieldToFilter(
                'main_table.increment_id',
                ['nlike' => self::OLD_HOTAI_ORDER_PREFIX . '%'],
            );
        } else {
            $orderIds = explode(',', $this->orderIds);
            $collection->addFieldToFilter(
                'main_table.entity_id',
                ['in' => $orderIds]
            );
        }

        return $collection;
    }

    protected function checkOrderFields(Order $order)
    {
        if ((int) $order->getData('ecpay_invoice_tag') !== 1) {
            throw new \Exception("Order ID '{$order->getId()}' does not have ECPay invoice tag.");
        }

        if ($order->getInvoiceCollection()->getSize() > 0) {
            throw new \Exception("Order ID '{$order->getId()}' already has an invoice.");
        }
    }

    protected function checkIsOldHotaiOrder(Order $order): bool
    {
        $increment_id = $order->getIncrementId();

        if (empty($increment_id)) {
            throw new \Exception("Order ID '{$order->getId()}' has an empty increment ID.");
        }

        return str_starts_with($increment_id, self::OLD_HOTAI_ORDER_PREFIX);
    }

    protected function updateOrder($orderId, $updateData)
    {
        $orderTable = $this->resourceConnection->getTableName('sales_order');

        $this->connection->update(
            $orderTable,
            $updateData,
            ['entity_id = ?' => $orderId]
        );
    }

    protected function getTempState(): string
    {
        return OrderState::STATE_PROCESSING;
    }

    protected function getTempStatus(): string
    {
        return OrderStatus::STATUS_PROCESSING;
    }

    protected function writeLog()
    {
        $this->hotaiCoreCommonHelper->writeLog(
            json_encode(
                [
                    'handle_log' => $this->handleLog,
                    'error_log'  => $this->errorLog,
                ]
            ),
            self::LOG_FOLDER_NAME
        );
    }
}