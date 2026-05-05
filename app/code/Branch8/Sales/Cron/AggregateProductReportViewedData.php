<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Sales\Cron;

use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class AggregateProductReportViewedData
 */
class AggregateProductReportViewedData
{
    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Reports\Model\ResourceModel\Report\Product\ViewedFactory
     */
    protected $viewedFactory;

    /**
     * @param ResolverInterface $localeResolver
     * @param TimezoneInterface $timezone
     * @param \Magento\Reports\Model\ResourceModel\Report\Product\ViewedFactory $viewedFactory
     */
    public function __construct(
        ResolverInterface $localeResolver,
        TimezoneInterface $timezone,
        \Magento\Reports\Model\ResourceModel\Report\Product\ViewedFactory $viewedFactory
    ) {
        $this->localeResolver = $localeResolver;
        $this->localeDate = $timezone;
        $this->viewedFactory = $viewedFactory;
    }

    /**
     * Refresh product viewed report statistics for last day
     *
     * @return void
     */
    public function execute()
    {
        $this->localeResolver->emulate(0);
        $currentDate = $this->localeDate->date();
        $date = $currentDate->sub(new \DateInterval('PT25H'));
        $this->viewedFactory->create()->aggregate($date);
        $this->localeResolver->revert();
    }
}
