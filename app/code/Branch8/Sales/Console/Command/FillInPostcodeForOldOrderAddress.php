<?php

namespace Branch8\Sales\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\ResourceConnection;
use Branch8\Sales\Helper\Data as DataHelper;

class FillInPostcodeForOldOrderAddress extends Command
{
    const LOG_FOLDER_NAME = 'Sales/Console/Command/FillInPostcodeForOldOrderAddress';

    const OPTION_ORDER_IDS = 'order_ids';

    /** @var State */
    protected $state;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var DataHelper */
    protected $dataHelper;

    protected $queryCache = [];
    protected $connection;
    protected $orderIds;
    protected $executeAll;
    protected $handleLog  = [];
    protected $errorLog   = [];

    public function __construct(
        State $state,
        ResourceConnection $resourceConnection,
        DataHelper $dataHelper
    ) {
        $this->state              = $state;
        $this->resourceConnection = $resourceConnection;
        $this->dataHelper         = $dataHelper;
        $this->connection         = $this->resourceConnection->getConnection();
        $this->executeAll         = false;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('sales:FillInPostcodeForOldOrderAddress');
        $this->setDescription('Pass in --order_ids="123,456", will fill in postcode for old order addresses, or pass in --order_ids="all" will try to fill in postcode for all old order addresses.');

        $this->addOption(
            self::OPTION_ORDER_IDS,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_ids like this: --order_ids=123,456'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $this->handle();

        if (count($this->errorLog) > 0) {
            $output->writeln("<error>" . base64_encode(json_encode($this->errorLog)) . "</error>");
            $output->writeln("<error>" . count($this->errorLog) . "</error>");
            $output->writeln("<error>Execute fail.</error>");
        }

        $output->writeln("<info>" . base64_encode(json_encode($this->handleLog)) . "</info>");
        $output->writeln("<info>" . count($this->handleLog) . "</info>");
        $output->writeln("<info>Execute success.</info>");

        return Command::SUCCESS;
    }

    /**
     * 檢查傳入參數
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
        $orderAddressArray = $this->getValidOrderAddresses();

        $this->tryToUpdatePostcode($orderAddressArray);
    }

    protected function getValidOrderAddresses(): array
    {
        $tableName = $this->resourceConnection->getTableName('sales_order_address');

        $select = $this->connection->select()->from(
            $tableName,
            [
                'entity_id',
                'parent_id',
                'city',
                'region',
                'postcode'
            ]
        );

        if ($this->executeAll) {
            $select->where(
                "region is not null and city is not null and (postcode is null or postcode = '000' or postcode = '-')"
            );
        } else {
            $orderIds = explode(',', $this->orderIds);
            $select->where('parent_id IN (?)', $orderIds);
        }

        return $this->connection->fetchAll($select);
    }

    protected function tryToUpdatePostcode(array $orderAddressArray): void
    {
        try {
            $this->connection->beginTransaction();

            foreach ($orderAddressArray as $orderAddress) {
                try {
                    $city   = $orderAddress['city'];
                    $region = $orderAddress['region'];

                    $cachePostcode = $this->getPostcodeFromCache($city, $region);
                    if (!empty($cachePostcode)) {
                        $this->update($orderAddress['entity_id'], $cachePostcode);
                        continue;
                    }

                    $postcode = $this->dataHelper->queryPostcodeForAddress($city, $region);

                    $this->update($orderAddress['entity_id'], $postcode);

                    $this->addResultToCache($city, $region, $postcode);
                } catch (\Exception $e) {
                    $this->errorLog[] = [
                        'entity_id' => $orderAddress['entity_id'],
                        'error'     => $e->getMessage()
                    ];
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            $this->errorLog[] = [
                'error' => "Transaction failed: " . $e->getMessage()
            ];
        }
    }

    protected function update(int|string $entityId, string $postcode)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName  = $this->resourceConnection->getTableName('sales_order_address');

        $connection->update(
            $tableName,
            ['postcode' => $postcode],
            ['entity_id = ?' => $entityId]
        );

        $this->handleLog[] = [
            'entity_id' => $entityId,
            'postcode'  => $postcode
        ];
    }

    protected function getCacheKey(string $city, string $region): string
    {
        return "{$city}_{$region}";
    }

    protected function addResultToCache(string $city, string $region, string $postcode): void
    {
        $cacheKey = $this->getCacheKey($city, $region);

        $this->queryCache[$cacheKey] = $postcode;
    }

    protected function getPostcodeFromCache(string $city, string $region): ?string
    {
        $cacheKey = $this->getCacheKey($city, $region);

        if (isset($this->queryCache[$cacheKey])) {
            return $this->queryCache[$cacheKey];
        }

        return null;
    }
}
