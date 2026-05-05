<?php
declare(strict_types=1);

namespace Branch8\SalesReports\Model\ResourceModel\Report;

class Rule extends \Magento\SalesRule\Model\ResourceModel\Report\Rule
{
    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Reports\Model\FlagFactory $reportsFlagFactory
     * @param \Magento\Framework\Stdlib\DateTime\Timezone\Validator $timezoneValidator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\SalesRule\Model\ResourceModel\Report\Rule\CreatedatFactory $originCreateFactory
     * @param \Magento\SalesRule\Model\ResourceModel\Report\Rule\UpdatedatFactory $originUpdatedatFactory
     * @param Rule\CreatedatFactory $createdatFactory
     * @param Rule\UpdatedatFactory $updatedatFactory
     * @param $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context                      $context,
        \Psr\Log\LoggerInterface                                               $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface                   $localeDate,
        \Magento\Reports\Model\FlagFactory                                     $reportsFlagFactory,
        \Magento\Framework\Stdlib\DateTime\Timezone\Validator                  $timezoneValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime                            $dateTime,
        \Magento\SalesRule\Model\ResourceModel\Report\Rule\CreatedatFactory    $originCreateFactory,
        \Magento\SalesRule\Model\ResourceModel\Report\Rule\UpdatedatFactory    $originUpdatedatFactory,
        \Branch8\SalesReports\Model\ResourceModel\Report\Rule\CreatedatFactory $createdatFactory,
        \Branch8\SalesReports\Model\ResourceModel\Report\Rule\UpdatedatFactory $updatedatFactory,
                                                                               $connectionName = null
    )
    {
        parent::__construct(
            $context,
            $logger,
            $localeDate,
            $reportsFlagFactory,
            $timezoneValidator,
            $dateTime,
            $originCreateFactory,
            $originUpdatedatFactory,
            $connectionName
        );
        $this->_createdatFactory = $createdatFactory;
        $this->_updatedatFactory = $updatedatFactory;
    }

    public function aggregate($from = null, $to = null)
    {
        $this->_createdatFactory->create()->aggregate($from, $to);
        $this->_updatedatFactory->create()->aggregate($from, $to);
        $this->_setFlagData(\Magento\Reports\Model\Flag::REPORT_COUPONS_FLAG_CODE);
        return $this;
    }

}
