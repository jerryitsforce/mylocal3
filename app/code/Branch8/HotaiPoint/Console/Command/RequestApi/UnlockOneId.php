<?php

namespace Branch8\HotaiPoint\Console\Command\RequestApi;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;

class UnlockOneId extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/RequestApi/UnlockOneId';

    const OPTION_CUSTOMER_ID = 'customer_id';

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    protected $customerId;

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
        $this->setName('hotai_point:request_api:UnlockOneId');
        $this->setDescription('Pass in --customer_id=123, will execute function Branch8\HotaiPoint\Helper\Api::requestApiUnlockOneId(int $customerId).');

        $this->addOption(
            self::OPTION_CUSTOMER_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input customer_id like this: --customer_id=123'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $apiResponse = $this->apiHelper->requestApiUnlockOneId($this->customerId, true);

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
        $this->customerId = (int) $input->getOption(self::OPTION_CUSTOMER_ID);

        if (empty($this->customerId)) {
            throw new \Exception("Input " . self::OPTION_CUSTOMER_ID . " is madatory.");
        }
    }
}
