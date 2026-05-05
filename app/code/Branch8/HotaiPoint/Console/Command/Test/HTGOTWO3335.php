<?php

namespace Branch8\HotaiPoint\Console\Command\Test;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\App\State;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HTGOTWO3335 extends Command
{
    const OPTION_ORDER_ITEM_ID                     = 'order_item_id';
    const OPTION_RETURN_POINT_TRACE_NO             = 'hotai_point_return_point_trace_no';
    const OPTION_RETURN_POINT_TRANS_DATETIME       = 'hotai_point_return_point_trans_datetime';

    /** @var int */
    protected $orderItemId;

    protected State                 $state;
    protected OrderItemRepository   $orderItemRepository;
    protected HotaiCoreCommonHelper $hotaiCoreCommonHelper;

    public function __construct(
        State $state,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->state                 = $state;
        $this->orderItemRepository   = $orderItemRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:test:HTGOTWO3335');
        $this->setDescription('HTGOTWO3335 test command with required --order_item_id and optional flags.');

        $this->addOption(
            self::OPTION_ORDER_ITEM_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_item_id like this: --order_item_id=123'
        );

        $this->addOption(
            self::OPTION_RETURN_POINT_TRACE_NO,
            null,
            InputOption::VALUE_NONE,
            'update hotai_point_return_point_trace_no field'
        );

        $this->addOption(
            self::OPTION_RETURN_POINT_TRANS_DATETIME,
            null,
            InputOption::VALUE_NONE,
            'update hotai_point_return_point_trans_datetime field'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->orderItemId = (int) $input->getOption(self::OPTION_ORDER_ITEM_ID);

        if (empty($this->orderItemId)) {
            $output->writeln('<error>Input --order_item_id is mandatory.</error>');

            return Command::FAILURE;
        }

        $updateTraceNo       = (bool) $input->getOption(self::OPTION_RETURN_POINT_TRACE_NO);
        $updateTransDatetime = (bool) $input->getOption(self::OPTION_RETURN_POINT_TRANS_DATETIME);

        if (!$updateTraceNo && !$updateTransDatetime) {
            $output->writeln('<error>At least one of --' . self::OPTION_RETURN_POINT_TRACE_NO . ' or --' . self::OPTION_RETURN_POINT_TRANS_DATETIME . ' must be provided.</error>');

            return Command::FAILURE;
        }

        try {
            /** @var OrderItem $orderItem */
            $orderItem = $this->orderItemRepository->get($this->orderItemId);
        } catch (\Throwable $th) {
            $output->writeln('<error>Fail to load order item by ID ' . $this->orderItemId . '.</error>');
            $output->writeln('<error>' . $th->getMessage() . '</error>');

            return Command::FAILURE;
        }

        if ($updateTraceNo) {
            $currentTraceNo = $orderItem->getData('hotai_point_return_point_trace_no');
            $newTraceNo     = empty($currentTraceNo) ? 'testingTraceNo' : null;

            $orderItem->setData('hotai_point_return_point_trace_no', $newTraceNo);
        }

        if ($updateTransDatetime) {
            $currentTransDatetime = $orderItem->getData('hotai_point_return_point_trans_datetime');
            $newTransDatetime     = null;

            if (empty($currentTransDatetime)) {
                $taiwanDateObj    = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();
                $newTransDatetime = $taiwanDateObj->format('Y-m-d H:i:s');
            }

            $orderItem->setData('hotai_point_return_point_trans_datetime', $newTransDatetime);
        }

        try {
            $this->orderItemRepository->save($orderItem);
        } catch (\Throwable $th) {
            $output->writeln('<error>Fail to save order item ' . $this->orderItemId . '.</error>');
            $output->writeln('<error>' . $th->getMessage() . '</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>HTGOTWO3335 command executed. order_item_id = ' . $this->orderItemId . '</info>');

        return Command::SUCCESS;
    }
}

