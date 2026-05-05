<?php

namespace Branch8\GiftToFriend\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\GiftToFriend\Cron\CancelNotConfirmedOrders as Cron;

class GiftOrderCancelByOrderIds extends Command
{
    const LOG_FOLDER_NAME = 'GiftToFriend/Console/Command/GiftOrderCancelByOrderIds';

    const OPTION_ORDER_IDS = 'order_ids';

    /** @var State */
    protected $state;

    /** @var Cron */
    protected $cron;

    protected $orderIds;

    public function __construct(
        State $state,
        Cron $cron
    ) {
        $this->state = $state;
        $this->cron  = $cron;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('gift_to_friend:GiftOrderCancelByOrderIds');
        $this->setDescription('Pass in --order_ids="123,456,789", will execute function Branch8\GiftToFriend\Cron\CancelNotConfirmedOrders::execute($targetOrderIds).');

        $this->addOption(
            self::OPTION_ORDER_IDS,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_ids like this: --order_ids="123,456,789"'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $this->cron->setTargetOrderIds($this->orderIds);
        $this->cron->execute();

        $output->writeln("<info>Done</info>");

        return Command::SUCCESS;
    }

    protected function checkInputParameter(InputInterface $input): void
    {
        $this->orderIds = $input->getOption(self::OPTION_ORDER_IDS);

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
}
