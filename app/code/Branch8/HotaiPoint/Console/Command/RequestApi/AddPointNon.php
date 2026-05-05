<?php

namespace Branch8\HotaiPoint\Console\Command\RequestApi;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Model\HotaiPointApiRecord;

class AddPointNon extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/RequestApi/AddPointNon';

    const OPTION_CUSTOMER_ID      = 'customer_id';
    const OPTION_ADD_POINT        = 'add_point';
    const OPTION_TRANS_S_N        = 'trans_s_n';
    const OPTION_TRANS_TIMESTAMP  = 'trans_timestamp'; // optional
    const OPTION_TRANS_DESC       = 'trans_desc'; // optional
    const OPTION_POINT_VALID_TYPE = 'point_valid_type'; // optional
    const OPTION_POINT_VALID_DATE = 'point_valid_date'; // optional

    const DEFAULT_POINT_VALID_TYPE = HotaiPointApiRecord::POINT_VALID_TYPE_NORMAL;

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    protected $customerId;
    protected $addPoint;
    protected $transSN;
    protected $transTimestamp;
    protected $transDesc;
    protected $pointValidType;
    protected $pointValidDate;

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
        $this->setName('hotai_point:request_api:AddPointNon');
        $this->setDescription('Pass in --customer_id=123, --add_point=100, --trans_s_n="transsn_example", will execute function Branch8\HotaiPoint\Helper\Api::requestApiAddPointNon(int $customerId, ...).');

        $this->addOption(
            self::OPTION_CUSTOMER_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input customer_id like this: --customer_id=123'
        );

        $this->addOption(
            self::OPTION_ADD_POINT,
            null,
            InputOption::VALUE_REQUIRED,
            'input add_point like this: --add_point=100'
        );

        $this->addOption(
            self::OPTION_TRANS_S_N,
            null,
            InputOption::VALUE_REQUIRED,
            'input trans_s_n like this: --trans_s_n="transsn_example"'
        );

        $this->addOption(
            self::OPTION_TRANS_TIMESTAMP,
            null,
            InputOption::VALUE_OPTIONAL,
            'input trans_timestamp like this: --trans_timestamp=1744080356'
        );

        $this->addOption(
            self::OPTION_TRANS_DESC,
            null,
            InputOption::VALUE_OPTIONAL,
            'input trans_desc like this: --trans_desc="desc_example"'
        );

        $this->addOption(
            self::OPTION_POINT_VALID_TYPE,
            null,
            InputOption::VALUE_OPTIONAL,
            'input point_valid_type like this: --point_valid_type="1"'
        );

        $this->addOption(
            self::OPTION_POINT_VALID_DATE,
            null,
            InputOption::VALUE_OPTIONAL,
            'input point_valid_date like this(pass in +8 date string): --point_valid_date="202503"'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $this->initOptionalParameters($input);

        $apiResponse = $this->apiHelper->requestApiAddPointNon(
            $this->customerId,
            $this->addPoint,
            $this->transSN,
            $this->transTimestamp,
            $this->transDesc,
            $this->pointValidType,
            $this->pointValidDate
        );

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

        $this->addPoint = (int) $input->getOption(self::OPTION_ADD_POINT);

        if (empty($this->addPoint)) {
            throw new \Exception("Input " . self::OPTION_ADD_POINT . " is madatory.");
        }

        $this->transSN = (string) $input->getOption(self::OPTION_TRANS_S_N);

        if (empty($this->transSN)) {
            throw new \Exception("Input " . self::OPTION_TRANS_S_N . " is madatory.");
        }
    }

    protected function initOptionalParameters(InputInterface $input): void
    {
        $this->transTimestamp = $input->getOption(self::OPTION_TRANS_TIMESTAMP);
        if (is_null($this->transTimestamp)) {
            $this->transTimestamp = time();
        }

        $this->transDesc = $input->getOption(self::OPTION_TRANS_DESC);
        if (is_null($this->transDesc)) {
            $this->transDesc = "";
        }

        $this->pointValidType = $input->getOption(self::OPTION_POINT_VALID_TYPE);
        if (is_null($this->pointValidType)) {
            $this->pointValidType = self::DEFAULT_POINT_VALID_TYPE;
        }

        $this->pointValidDate = $input->getOption(self::OPTION_POINT_VALID_DATE);
        if (is_null($this->pointValidDate)) {
            $this->pointValidDate = self::OPTION_POINT_VALID_DATE;
        }
    }
}
