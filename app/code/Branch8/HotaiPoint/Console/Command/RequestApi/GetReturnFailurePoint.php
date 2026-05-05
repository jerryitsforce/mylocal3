<?php

namespace Branch8\HotaiPoint\Console\Command\RequestApi;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;

class GetReturnFailurePoint extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/RequestApi/GetReturnFailurePoint';

    const OPTION_ORDER_ITEM_ID = 'order_item_id';

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    protected $orderItemId;

    public function __construct(
        State $state,
        ApiHelper $apiHelper
    ) {
        $this->state     = $state;
        $this->apiHelper = $apiHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:request_api:GetReturnFailurePoint');
        $this->setDescription('Pass in --order_item_id=123, will execute function Branch8\HotaiPoint\Helper\Api::requestApiGetReturnFailurePoint(int $orderItemId).');

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

        $this->checkInputParameter($input);

        $apiResponse = $this->apiHelper->requestApiGetReturnFailurePoint($this->orderItemId);

        $output->writeln("<info>Api request success, response:</info>");
        $output->writeln("<info>" . json_encode($apiResponse) . "</info>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數
     * @return void
     */
    protected function checkInputParameter(InputInterface $input): void
    {
        $this->orderItemId = (int) $input->getOption(self::OPTION_ORDER_ITEM_ID);

        if (empty($this->orderItemId)) {
            throw new \Exception("Input " . self::OPTION_ORDER_ITEM_ID . " is madatory.");
        }
    }
}
