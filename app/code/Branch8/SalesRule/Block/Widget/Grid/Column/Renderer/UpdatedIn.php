<?php
declare(strict_types=1);

namespace Branch8\SalesRule\Block\Widget\Grid\Column\Renderer;

use Branch8\SalesRule\Model\Actions\GetSaleRuleUpComingEvent;
use Magento\Backend\Block\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Model\VersionManager;

class UpdatedIn extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    private $timezoneInterface;

    private $getSaleRuleUpComingEvent;

    /**
     * @param Context $context
     * @param TimezoneInterface $timezoneInterface
     * @param GetSaleRuleUpComingEvent $getSaleRuleUpComingEvent
     * @param array $data
     */
    public function __construct(
        Context                  $context,
        TimezoneInterface        $timezoneInterface,
        GetSaleRuleUpComingEvent $getSaleRuleUpComingEvent,
        array                    $data = []
    )
    {
        $this->getSaleRuleUpComingEvent = $getSaleRuleUpComingEvent;
        $this->timezoneInterface = $timezoneInterface;
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return string
     * @throws \DateMalformedStringException
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $format = $this->getColumn()->getFormat();
        $upcomingEvent = $this->getSaleRuleUpComingEvent->get($row->getRuleId());
        if (empty($upcomingEvent) || empty($upcomingEvent['end_time'])) {
            return;
        }
        $date = new \DateTime($upcomingEvent['end_time'], new \DateTimeZone('UTC'));
        if ($date) {
            $format = $this->_localeDate->formatDateTime(
                $date,
                $format ?: \IntlDateFormatter::MEDIUM,
                $format ?: \IntlDateFormatter::MEDIUM,
                null,
                $this->getColumn()->getTimezone() === false ? 'UTC' : null
            );
            return $format;
        }
        return $this->getColumn()->getDefault();
    }
}
