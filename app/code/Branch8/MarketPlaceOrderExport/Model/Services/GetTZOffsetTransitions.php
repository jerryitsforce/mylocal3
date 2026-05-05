<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Query\Generator;
use Magento\Store\Model\StoreManager;
use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;

class GetTZOffsetTransitions
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone\Validator
     */
    private $timezoneValidator;
    private \Magento\Framework\DB\Adapter\AdapterInterface $connection;
    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;
    public  StoreManager $storeManager;
    public  LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Framework\Stdlib\DateTime\Timezone\Validator $timezoneValidator
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection                                    $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\Timezone\Validator $timezoneValidator,
        \Magento\Framework\Stdlib\DateTime\Timezone           $timezone,
        StoreManager                                          $storeManager,
        LoggerInterface                                       $logger
    )
    {

        $this->timezoneValidator = $timezoneValidator;
        $this->resourceConnection = $resourceConnection;
        $this->connection = $this->resourceConnection->getConnection();
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @param $timezone
     * @param $from
     * @param $to
     * @return array
     */
    public function get($timezone, $from = null, $to = null)
    {
        $tzTransitions = [];
        try {
            if (!empty($from)) {
                $from = $from instanceof \DateTimeInterface
                    ? $from->getTimestamp()
                    : (new \DateTime($from))->getTimestamp();
            }

            $to = $to instanceof \DateTimeInterface
                ? $to
                : new \DateTime($to ?? 'now');
            $nextPeriod = $this->resourceConnection->getConnection()->formatDate(
                $to->format('Y-m-d H:i:s')
            );
            $to = $to->getTimestamp();
            $dtz = new \DateTimeZone($timezone);
            $transitions = $dtz->getTransitions();
            for ($i = count($transitions) - 1; $i >= 0; $i--) {
                $tr = $transitions[$i];
                try {
                    $this->timezoneValidator->validate($tr['ts'], $to);
                } catch (\Magento\Framework\Exception\ValidatorException $e) {
                    continue;
                }

                $tr['time'] = $this->resourceConnection->getConnection()->formatDate(
                    (new \DateTime($tr['time']))->format('Y-m-d H:i:s')
                );
                $tzTransitions[$tr['offset']][] = ['from' => $tr['time'], 'to' => $nextPeriod];

                if (!empty($from) && $tr['ts'] < $from) {
                    break;
                }
                $nextPeriod = $tr['time'];
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
        return $tzTransitions;
    }
}
