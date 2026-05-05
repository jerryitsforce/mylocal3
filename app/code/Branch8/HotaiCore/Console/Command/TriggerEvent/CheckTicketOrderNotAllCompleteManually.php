<?php

namespace Branch8\HotaiCore\Console\Command\TriggerEvent;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Branch8\TicketOrderStatusChangeObserver\Helper\EventName;

class CheckTicketOrderNotAllCompleteManually extends Command
{
    const LOG_FOLDER_NAME = 'HotaiCore/Console/TriggerEvent/CheckTicketOrderNotAllCompleteManually';

    const OPTION_ORDER_ID = 'order_id';

    /** @var State */
    protected $state;

    /** @var EventManager */
    protected $eventManager;

    public function __construct(
        State $state,
        EventManager $eventManager
    ) {
        $this->state        = $state;
        $this->eventManager = $eventManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_core:trigger_event:check_ticket_order_not_all_complete_manually');
        $this->setDescription('Pass in --order_id=123, will trigger event "check_ticket_order_not_all_complete_manually".');

        $this->addOption(
            self::OPTION_ORDER_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_id like this: --order_id=123'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $orderId = $input->getOption(self::OPTION_ORDER_ID);

        $this->checkOrderId($orderId);

        $this->eventManager->dispatch(
            EventName::CHECK_TICKET_ORDER_NOT_ALL_COMPLETE_MANUALLY,
            [
                "orderId" => $orderId,
            ]
        );

        $output->writeln("<info>Done</info>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數--order_id
     * @param string|null $orderId
     * @return void
     */
    protected function checkOrderId(?string $orderId): void
    {
        if (empty($orderId)) {
            throw new \Exception("Input " . self::OPTION_ORDER_ID . " is madatory.");
        }
    }
}
