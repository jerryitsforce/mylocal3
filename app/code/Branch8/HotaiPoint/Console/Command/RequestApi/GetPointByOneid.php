<?php

namespace Branch8\HotaiPoint\Console\Command\RequestApi;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;

class GetPointByOneid extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/RequestApi/GetPointByOneid';

    const OPTION_CUSTOMER_ID = 'customer_id';
    const OPTION_QUERY_TYPE  = 'query_type';
    const OPTION_PAGE_INDEX  = 'page_index';
    const OPTION_PAGE_SIZE   = 'page_size';
    const OPTION_START_DATE  = 'start_date';
    const OPTION_END_DATE    = 'end_date';

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    protected $customerId;
    protected $queryType;
    protected $pageIndex;
    protected $pageSize;
    protected $startDateObj;
    protected $endDateObj;

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
        $this->setName('hotai_point:request_api:GetPointByOneid');
        $this->setDescription('Pass in --customer_id=123, will execute function Branch8\HotaiPoint\Helper\Api::requestApiGetPointByOneid(int $customerId, ...).');

        $this->addOption(
            self::OPTION_CUSTOMER_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input customer_id like this: --customer_id=123'
        );

        $this->addOption(
            self::OPTION_QUERY_TYPE,
            null,
            InputOption::VALUE_OPTIONAL,
            'input query_type like this: --query_type=' . ApiHelper::QUERY_TYPE_TOTAL_AND_POINT_DETAIL
        );

        $this->addOption(
            self::OPTION_PAGE_INDEX,
            null,
            InputOption::VALUE_OPTIONAL,
            'input page_index like this: --page_index=' . ApiHelper::DEFAULT_PAGE_INDEX
        );

        $this->addOption(
            self::OPTION_PAGE_SIZE,
            null,
            InputOption::VALUE_OPTIONAL,
            'input page_size like this: --page_size=' . ApiHelper::DEFAULT_PAGE_SIZE
        );

        $this->addOption(
            self::OPTION_START_DATE,
            null,
            InputOption::VALUE_OPTIONAL,
            'input start_date like this: --start_date=20250101'
        );

        $this->addOption(
            self::OPTION_END_DATE,
            null,
            InputOption::VALUE_OPTIONAL,
            'input end_date like this: --end_date=20250102'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $this->initOptionalParameters($input);

        $apiResponse = $this->apiHelper->requestApiGetPointByOneid(
            $this->customerId,
            (int) $this->queryType,
            (int) $this->pageIndex,
            (int) $this->pageSize,
            $this->startDateObj,
            $this->endDateObj
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
    }

    protected function initOptionalParameters(InputInterface $input): void
    {
        $this->queryType = $input->getOption(self::OPTION_QUERY_TYPE);
        if (is_null($this->queryType)) {
            $this->queryType = ApiHelper::QUERY_TYPE_TOTAL_AND_POINT_DETAIL;
        }

        $this->pageIndex = $input->getOption(self::OPTION_PAGE_INDEX);
        if (is_null($this->pageIndex)) {
            $this->pageIndex = ApiHelper::DEFAULT_PAGE_INDEX;
        }

        $this->pageSize = $input->getOption(self::OPTION_PAGE_SIZE);
        if (is_null($this->pageSize)) {
            $this->pageSize = ApiHelper::DEFAULT_PAGE_SIZE;
        }

        $startDateInput = $input->getOption(self::OPTION_START_DATE);
        $startDateObj   = new \DateTime();
        $startDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $this->startDateObj = (is_null($startDateInput)) ? null : $startDateObj->setTimestamp(strtotime("{$startDateInput} Asia/Taipei"));

        $endDateInput = $input->getOption(self::OPTION_END_DATE);
        $endDateObj   = new \DateTime();
        $endDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));
        $this->endDateObj = (is_null($endDateInput)) ? null : $endDateObj->setTimestamp(strtotime("{$endDateInput} Asia/Taipei"));
    }
}
