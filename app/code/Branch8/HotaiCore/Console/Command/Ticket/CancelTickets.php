<?php

namespace Branch8\HotaiCore\Console\Command\Ticket;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiCore\Helper\VirtualProduct as CancelHelper;

class CancelTickets extends Command
{
    const LOG_FOLDER_NAME = 'HotaiCore/Console/CancelTickets';

    const OPTION_ORDER_ITEM_ID = 'order_item_id';

    /** @var State */
    protected $state;

    /** @var CancelHelper */
    protected $cancelHelper;

    public function __construct(
        State $state,
        CancelHelper $cancelHelper
    ) {
        $this->state        = $state;
        $this->cancelHelper = $cancelHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_core:cancel_tickets');
        $this->setDescription('Pass in --order_item_id=123, will execute cancelTickets function under app/code/Branch8/HotaiCore/Helper/VirtualProduct.php.');

        $this->addOption(
            self::OPTION_ORDER_ITEM_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_item_id like this: --order_item_id=123'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $orderItemId = $input->getOption(self::OPTION_ORDER_ITEM_ID);

        $this->checkOrderItemId($orderItemId);

        $this->cancelHelper->cancelTickets((int) $orderItemId);

        $output->writeln("<info>Done</info>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數--order_item_id
     *
     * @param string|null $orderItemId
     * @return void
     */
    protected function checkOrderItemId(?string $orderItemId): void
    {
        if (empty($orderItemId)) {
            throw new \Exception("Input " . self::OPTION_ORDER_ITEM_ID . " is madatory.");
        }
    }
}
