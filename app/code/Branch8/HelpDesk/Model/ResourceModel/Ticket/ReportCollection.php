<?php
declare(strict_types=1);


namespace Branch8\HelpDesk\Model\ResourceModel\Ticket;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;

class ReportCollection extends \Branch8\HelpDesk\Model\ResourceModel\Ticket\Collection
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface    $entityFactory,
        \Psr\Log\LoggerInterface                                     $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        ManagerInterface                                             $eventManager,
        ScopeConfigInterface                                         $scopeConfig,
        \Magento\Framework\DB\Adapter\AdapterInterface               $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb         $resource = null
    )
    {

        $this->scopeConfig = $scopeConfig;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
    }

    /**
     * Calculate From and To dates (or times) by given period
     *
     * @param string $range
     * @param string $customStart
     * @param string $customEnd
     * @param bool $returnObjects
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getDateRange($range, $customStart, $customEnd, $returnObjects = false)
    {
        $dateEnd = new \DateTime();
        $dateStart = new \DateTime();

        // go to the end of a day
        $dateEnd->setTime(23, 59, 59);

        $dateStart->setTime(0, 0, 0);

        switch ($range) {
            case 'today':
                $dateEnd->modify('now');
                break;
            case '24h':
                $dateEnd = new \DateTime();
                $dateEnd->modify('+1 hour');
                $dateStart = clone $dateEnd;
                $dateStart->modify('-1 day');
                break;
            case '3d':
                // substract 6 days we need to include
                // only today and not hte last one from range
                $dateStart->modify('-2 days');
                break;
            case '7d':
                // substract 6 days we need to include
                // only today and not hte last one from range
                $dateStart->modify('-6 days');
                break;

            case '1m':
                $dateStart->setDate(
                    (int)$dateStart->format('Y'),
                    (int)$dateStart->format('m'),
                    $this->scopeConfig->getValue(
                        'reports/dashboard/mtd_start',
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    )
                );
                break;

            case 'custom':
                $dateStart = $customStart ? $customStart : $dateStart;
                $dateEnd = $customEnd ? $customEnd : $dateEnd;
                break;
        }

        if ($returnObjects) {
            return [$dateStart, $dateEnd];
        } else {
            return ['from' => $dateStart, 'to' => $dateEnd, 'datetime' => true];
        }
    }


    /**
     * Add period filter by created_at attribute
     *
     * @param string $period
     * @return $this
     */
    public function addCreateAtPeriodFilter($period)
    {
        list($from, $to) = $this->getDateRange($period, 0, 0, true);
        $fieldToFilter = 'created_at';

        $fromFormat = $from->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $to = $to->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
        $expresion = new \Zend_Db_Expr("`created_at` BETWEEN '$fromFormat' AND '$to'");
        $expresion = $expresion->__toString();
        $this->getSelect()->where($expresion);
        return $this;
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    public function getSelectCountSql()
    {
        $countSelect = clone $this->getSelect();
        $countSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $countSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $countSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $countSelect->reset(\Magento\Framework\DB\Select::GROUP);
        $countSelect->reset(\Magento\Framework\DB\Select::HAVING);
        $countSelect->columns("COUNT(DISTINCT main_table.ticket_id)");

        return $countSelect;
    }

    /**
     * @param $isFilter
     * @return $this
     */
    public function calculateTotals()
    {
        $this->setMainTable('branch8_helpdesk_ticket');
        $this->removeAllFieldsFromSelect();
        $this->getSelect()->columns(
            [
                'ticket' => 'count(main_table.ticket_id)'
            ]
        );

        return $this;
    }
}
