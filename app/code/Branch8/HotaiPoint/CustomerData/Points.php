<?php
/**
 * CLEARgo
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the CLEARgo.com license that is
 * available through the world-wide-web at this URL:
 * http://cleargo.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category   CLEARgo
 * @package    Cleargo_RewardPoints
 * @copyright  Copyright (c) 2016 CLEARgo (http://www.cleargo.com/)
 * @license    http://www.cleargo.com/LICENSE-1.0.html
 */

namespace Branch8\HotaiPoint\CustomerData;

use Branch8\HotaiCore\Model\Ticket\Status;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Data as HotaiPointHelper;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Sales\Model\Order;
use Branch8\HotaiCore\Helper\VirtualProduct;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Webkul\SellerSubAccount\Helper\Data as HelperData;

class Points implements SectionSourceInterface
{
    /**
     * @var ApiHelper
     */
    protected ApiHelper $apiHelper;

    /**
     * @var VirtualProduct
     */
    protected VirtualProduct $virtualProductHelper;

    /**
     * @var HotaiPointHelper
     */
    protected HotaiPointHelper $hotaiPointHelper;

    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    /**
     * @var OrderCollectionFactory
     */
    protected OrderCollectionFactory $orderCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected TimezoneInterface $timezone;

    /**
     * @var mixed
     */
    protected mixed $totalTicket = null;
    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    /**
     * @var \Branch8\Customer\Helper\Ticket
     */
    protected $generalHelperTicket;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @param ApiHelper $apiHelper
     * @param VirtualProduct $virtualProductHelper
     * @param HotaiPointHelper $hotaiPointHelper
     * @param HttpContext $httpContext
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param TimezoneInterface $timezone
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     * @param \Branch8\Customer\Helper\Ticket $generalHelperTicket
     * @param HelperData $subAccountHelper
     */
	public function __construct(
        ApiHelper $apiHelper,
        VirtualProduct $virtualProductHelper,
        HotaiPointHelper $hotaiPointHelper,
        HttpContext $httpContext,
        OrderCollectionFactory $orderCollectionFactory,
        TimezoneInterface $timezone,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        \Branch8\Customer\Helper\Ticket $generalHelperTicket,
        HelperData $subAccountHelper
	) {
        $this->apiHelper = $apiHelper;
        $this->virtualProductHelper = $virtualProductHelper;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->httpContext = $httpContext;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->timezone = $timezone;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->generalHelperTicket = $generalHelperTicket;
        $this->subAccountHelper = $subAccountHelper;
	}

    /**
     * {@inheritdoc}
     */
    public function getSectionData(): array
    {
        $hour = $this->timezone->date()->format('G');
        if ($this->b8CustomerHelper->isLoggedInAndIsBuyer()) {
            $greeting = __("Good night").", ";
            if ($hour < 12) {
                $greeting = __("Good morning").", ";
            } else if ($hour < 18) {
                $greeting = __("Good afternoon").", ";
            } else if ($hour < 21) {
                $greeting = __("Good evening").", ";
            }
        }else {
            $greeting = __("Good night").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            if ($hour < 12) {
                $greeting = __("Good morning").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            } else if ($hour < 18) {
                $greeting = __("Good afternoon").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            } else if ($hour < 21) {
                $greeting = __("Good evening").", ".__("visitor")."<br>".__("Log in to view your Hotai Points!");
            }
        }

        if ($this->b8CustomerHelper->isLoggedInAndIsBuyer()) {
            $totalOrder = $this->getOrderTotals();
            $expireHotaiPoint = $this->hotaiPointHelper->getExpireHotaiPoint();
            $totalTicket = $this->getTotalTicket();
            if($totalPoints = (float) $this->hotaiPointHelper->getHotaiPoint()) {
                return [
                    'hotaipoints' => $this->hotaiPointHelper->formatPoints($totalPoints, true),
                    'hotaipoints_formated' => $this->hotaiPointHelper->formatPoints($totalPoints, false, false, false),
                    'totalpoints' => $totalPoints,
                    'expirepoint' => $expireHotaiPoint,
                    'totalticket' => $totalTicket,
                    'totalorders' => $totalOrder['count'],
                    'totalamount' => $totalOrder['total'],
                    'greeting' => $greeting
                ];
            } else {
                return [
                    'hotaipoints' => $this->hotaiPointHelper->formatPoints(0, true),
                    'hotaipoints_formated' => $this->hotaiPointHelper->formatPoints(0, false, false, false),
                    'totalpoints' => 0,
                    'expirepoint' => $expireHotaiPoint,
                    'totalticket' => $totalTicket,
                    'totalorders' => $totalOrder['count'],
                    'totalamount' => $totalOrder['total'],
                    'greeting' => $greeting
                ];
            }
        }
        return [
            'greeting' => $greeting
        ];
    }

    /**
     * Get Total Ticket
     *
     * @return int
     */
    public function getTotalTicket(): int
    {
        if (is_null($this->totalTicket)) {
            try {
                $this->totalTicket = 0;
                if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
                    $ticketCount = $this->generalHelperTicket->getTicketsCollectionCount(Status::STATUS_UNUSED);
                    if($ticketCount){
                        $this->totalTicket = $ticketCount;
                    }
                }
            } catch (\Exception $e) {
                $this->totalTicket = 0;
            }
        }
        return $this->totalTicket;
    }

    /**
     * Get Order Totals
     *
     * @return array
     */
    private function getOrderTotals()
    {
        $totalSum = 0;
        $count = 0;
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            $orderTotals = $this->orderCollectionFactory->create()
                ->addAttributeToFilter('status', Order::STATE_COMPLETE)
                ->addAttributeToFilter('customer_id', $this->httpContext->getValue('customer_id'))
                ->addAttributeToSelect('grand_total')
                ->getColumnValues('grand_total');
            $totalSum = array_sum($orderTotals);
            $count = count($orderTotals);
        }
        return ['total' => $totalSum, 'count' => $count];
    }
}
