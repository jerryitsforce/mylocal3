<?php

namespace Branch8\Sales\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;
use Branch8\HotaiCore\Helper\Curl;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class FillInPostcodeForOldRmaAddress extends Command
{
    const LOG_FOLDER_NAME = 'Sales/Console/Command/FillInPostcodeForOldRmaAddress';

    const OPTION_ENTITY_IDS = 'entity_ids';
    const OPTION_REMOVE_LOG = 'remove_log';

    const API_URL_HANDLE_ADDRESS = 'https://www.road8.tw/address/Main/Parse';
    const API_URL_GET_POSTCODE   = 'https://zip5.5432.tw/zip5json.py';

    /** @var State */
    protected $state;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var Curl */
    protected $curl;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    protected $output;
    protected $connection;
    protected $entityIds;
    protected $removeLog          = false;
    protected $executeAll;
    protected $searchSuccessCache = [];
    protected $searchFailCache    = [];
    protected $skipLog            = [];
    protected $handleLog          = [];
    protected $errorLog           = [];

    public function __construct(
        State $state,
        ResourceConnection $resourceConnection,
        Curl $curl,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->state                 = $state;
        $this->resourceConnection    = $resourceConnection;
        $this->connection            = $this->resourceConnection->getConnection();
        $this->curl                  = $curl;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->executeAll            = false;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('sales:FillInPostcodeForOldRmaAddress');
        $this->setDescription('Pass in --entity_ids="123,456", will fill in postcode for old RMA addresses, or pass in --entity_ids="all" will try to fill in postcode for all old RMA addresses.');

        $this->addOption(
            self::OPTION_ENTITY_IDS,
            null,
            InputOption::VALUE_REQUIRED,
            'input entity_ids like this: --entity_ids=123,456'
        );

        $this->addOption(
            self::OPTION_REMOVE_LOG,
            null,
            InputOption::VALUE_OPTIONAL,
            'input remove_log like this: --remove_log="true"'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->removeLog = $input->getOption(self::OPTION_REMOVE_LOG) === 'true' ? true : false;
        if ($this->removeLog) {
            $this->hotaiCoreCommonHelper->removeLog(self::LOG_FOLDER_NAME);
            $output->writeln("<info>Remove log done.</info>");
            return Command::SUCCESS;
        }

        $this->checkInputParameter($input);

        $this->handle();

        $output->writeln("<info>Skip count: " . count($this->skipLog) . "</info>");
        $output->writeln("<info>Handle count: " . count($this->handleLog) . "</info>");
        $output->writeln("<error>Error count: " . count($this->errorLog) . "</error>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數
     * @return void
     */
    protected function checkInputParameter(InputInterface $input): void
    {
        $this->entityIds = $input->getOption(self::OPTION_ENTITY_IDS);

        if ($this->entityIds === 'all') {
            $this->executeAll = true;
            return;
        }

        if (empty($this->entityIds)) {
            throw new \Exception("Input " . self::OPTION_ENTITY_IDS . " is mandatory.");
        }

        $entityIdsArray = explode(',', $this->entityIds);

        $checkLog = [];
        foreach ($entityIdsArray as $entityId) {
            if (!is_numeric($entityId)) {
                $checkLog[] = "Entity ID '{$entityId}' is not a valid number.";
            }
        }

        if (!empty($checkLog)) {
            throw new \Exception(json_encode($checkLog));
        }
    }

    public function handle()
    {
        $orderAddressArray = $this->getValidOrderAddresses();

        foreach ($orderAddressArray as $orderAddress) {
            try {
                $address = $orderAddress['rma_address'];

                if ($this->checkIfAddressHasPostcode($address)) {
                    $this->skipLog[] = [
                        'id'      => $orderAddress['id'],
                        'address' => $address
                    ];

                    continue;
                }

                $postcodeFromSuccessCache = $this->getPostcodeFromSearchSuccessCache($address);
                if (!empty($postcodeFromSuccessCache)) {
                    $this->updateRecord($orderAddress['id'], $this->prependPostcodeToAddress($postcodeFromSuccessCache, $address), $address);
                    continue;
                }

                if ($this->checkAddressFailBeforeFromSearchFailCache($address)) {
                    $this->errorLog[] = [
                        'id'      => $orderAddress['id'],
                        'address' => $address,
                        'error'   => "Address already failed in previous search, skip."
                    ];

                    continue;
                }

                $fixedResponse        = $this->requestFixAddressApi($address);
                $postcodeFromFixedApi = $this->getPostcodeFromFixAddressApi($fixedResponse);

                if (!empty($postcodeFromFixedApi)) {
                    $fixedAddress = $this->prependPostcodeToAddress($postcodeFromFixedApi, $address);
                    $this->updateRecord($orderAddress['id'], $fixedAddress, $address);
                    $this->addResultToSearchSuccessCache($address, $postcodeFromFixedApi);

                    continue;
                }

                $fixedAddress = $this->getFixedAddressFromFixAddressApi($fixedResponse);

                if (empty($fixedAddress)) {
                    $this->errorLog[] = [
                        'id'               => $orderAddress['id'],
                        'error'            => "Fix address response empty, fail",
                        'original address' => $address
                    ];
                    $this->addAddressToSearchFailCache($address);

                    continue;
                }

                $purifiedAddress = $this->purifyAddressForSearch($fixedAddress);
                $postcode        = $this->searchPostcodeByApi($purifiedAddress);

                if (empty($postcode)) {
                    $this->errorLog[] = [
                        'id'               => $orderAddress['id'],
                        'error'            => "Search postcode response empty, fail",
                        'original address' => $address,
                        'fixed address'    => $fixedAddress,
                        'purified address' => $purifiedAddress
                    ];
                    $this->addAddressToSearchFailCache($address);

                    continue;
                }

                $prependAddress = $this->prependPostcodeToAddress($postcode, $address);

                $this->updateRecord($orderAddress['id'], $prependAddress, $address);
                $this->addResultToSearchSuccessCache($address, $postcode);
            } catch (\Exception $e) {
                $this->errorLog[] = [
                    'id'      => $orderAddress['id'],
                    'address' => $address ?? "",
                    'error'   => $e->getMessage()
                ];
            }
        }

        $this->writeLog();
    }

    protected function getValidOrderAddresses(): array
    {
        $tableName = $this->resourceConnection->getTableName('marketplace_rma_details');

        $select = $this->connection->select()
            ->from(
                $tableName,
                [
                    'id',
                    'order_id',
                    'rma_address'
                ]
            )->where(
                'rma_address IS NOT NULL AND rma_address != ""'
            );

        if (!$this->executeAll) {
            $entityIds = explode(',', $this->entityIds);
            $select->where('id IN (?)', $entityIds);
        }

        return $this->connection->fetchAll($select);
    }

    protected function addResultToSearchSuccessCache(string $address, ?string $postcode): void
    {
        if (isset($this->searchSuccessCache[$address])) {
            return;
        }

        $this->searchSuccessCache[$address] = $postcode;
    }

    protected function getPostcodeFromSearchSuccessCache(string $address): ?string
    {
        if (isset($this->searchSuccessCache[$address])) {
            return $this->searchSuccessCache[$address];
        }

        return null;
    }

    protected function addAddressToSearchFailCache(string $address): void
    {
        $this->searchFailCache[] = $address;
    }

    protected function checkAddressFailBeforeFromSearchFailCache(string $address): ?string
    {
        return in_array($address, $this->searchFailCache, true);
    }

    protected function updateRecord(int|string $id, string $afterAddress, string $beforeAddress): void
    {
        $tableName = $this->resourceConnection->getTableName('marketplace_rma_details');

        $this->connection->update(
            $tableName,
            ['rma_address' => $afterAddress],
            ['id = ?' => $id]
        );

        $this->handleLog[] = [
            'id'             => $id,
            'before address' => $beforeAddress,
            'after address'  => $afterAddress
        ];
    }

    protected function checkIfAddressHasPostcode(null|string $address): bool
    {
        if (empty($address)) {
            return false;
        }

        $addressData = explode(',', $address);

        if (!isset($addressData[0])) {
            return false;
        }

        if ($addressData[0] === '-' || $addressData[0] === '000') {
            return false;
        }

        $first3Chars = mb_substr($addressData[0], 0, 3, 'UTF-8');

        return is_numeric($first3Chars);
    }

    protected function requestFixAddressApi(string $address): null|array
    {
        $params = [
            'lang' => 'zhTW',
            'addr' => $address,
            'zip'  => 'zip33',
            'ctrl' => 'false',
            'alt'  => 'false'
        ];

        $this->curl->post(self::API_URL_HANDLE_ADDRESS, $params);

        $response = json_decode($this->curl->getBody(), true);

        return $response;
    }

    protected function getPostcodeFromFixAddressApi(array $response): null|string
    {
        if (!isset($response['FIXED']['ZIPFOUND'])) {
            return null;
        }

        return $response['FIXED']['ZIPFOUND'];
    }

    protected function getFixedAddressFromFixAddressApi(array $response): null|string
    {
        if (!isset($response['FIXED']['STRING'])) {
            return null;
        }

        return $response['FIXED']['STRING'];
    }

    protected function purifyAddressForSearch(string $address): string
    {
        // 全形數字轉半形數字
        $fullwidthDigits = ['０', '１', '２', '３', '４', '５', '６', '７', '８', '９'];
        $halfwidthDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $conversionMap   = array_combine($fullwidthDigits, $halfwidthDigits);
        $handleAddress   = strtr($address, $conversionMap);

        // 移除所有逗號
        $handleAddress = str_replace(",", "", $handleAddress);

        // 移除兩邊的破折號
        $handleAddress = trim($handleAddress, "-");

        return $handleAddress;
    }

    protected function searchPostcodeByApi(string $address): null|string
    {
        $params = [
            'adrs' => $address
        ];

        $queryString = http_build_query($params);

        $this->curl->get(self::API_URL_GET_POSTCODE . "?" . $queryString);

        $response = json_decode($this->curl->getBody(), true);

        return $response['zipcode6'] ?? null;
    }

    protected function prependPostcodeToAddress(string $postcode, string $address): string
    {
        $handledAddress = $address;

        $target = '-';
        if (str_starts_with($handledAddress, $target)) {
            $handledAddress = substr($handledAddress, strlen($target));
        }

        $target = '000';
        if (str_starts_with($handledAddress, $target)) {
            $handledAddress = substr($handledAddress, strlen($target));
        }

        $target = ',';
        if (str_starts_with($handledAddress, $target)) {
            $handledAddress = substr($handledAddress, strlen($target));
        }

        return "{$postcode},{$handledAddress}";
    }

    public function setExecuteAll(bool $executeAll): void
    {
        $this->executeAll = $executeAll;
    }

    protected function writeLog(): void
    {
        $this->hotaiCoreCommonHelper->writeLog(
            json_encode([
                'skip_log'             => $this->skipLog,
                'handle_log'           => $this->handleLog,
                'error_log'            => $this->errorLog,
                'search_success_cache' => $this->searchSuccessCache,
                'search_fail_cache'    => $this->searchFailCache,
            ], JSON_UNESCAPED_UNICODE),
            self::LOG_FOLDER_NAME,
        );
    }
}
