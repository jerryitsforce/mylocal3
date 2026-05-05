<?php

namespace Branch8\GA4\Plugin;

use Branch8\HotaiCore\Model\Ticket\Status;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\CustomerData\Customer;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\Order;

class CustomerData
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    private GroupRepositoryInterface $groupRepository;
    private \Branch8\HotaiPoint\Helper\Data $hotaiPointHelper;

    /**
     * @var \Branch8\Customer\Helper\Data
     */
    protected $b8CustomerHelper;

    /**
     * @var OrderCollectionFactory
     */
    protected OrderCollectionFactory $orderCollectionFactory;

    /**
     * @var \Branch8\Customer\Helper\Ticket
     */
    protected $generalHelperTicket;

    /**
     * @var mixed
     */
    protected mixed $totalTicket = null;

    /**
     * @param Session $customerSession
     * @param GroupRepositoryInterface $groupRepository
     * @param \Branch8\HotaiPoint\Helper\Data $hotaiPointHelper
     * @param \Branch8\Customer\Helper\Data $b8CustomerHelper
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Branch8\HotaiPoint\Helper\Data $hotaiPointHelper,
        \Branch8\Customer\Helper\Data $b8CustomerHelper,
        OrderCollectionFactory $orderCollectionFactory,
        \Branch8\Customer\Helper\Ticket $generalHelperTicket
    )
    {
        $this->customerSession = $customerSession;
        $this->groupRepository = $groupRepository;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->generalHelperTicket = $generalHelperTicket;
    }
    /**
     * @param Customer $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(Customer $subject, array $result): array
    {
        if ($this->b8CustomerHelper->isLoggedInAndIsBuyer()) {
            $totalOrder = $this->getOrderTotals();
            $expireHotaiPoint = $this->hotaiPointHelper->getExpireHotaiPoint();
            $totalTicket = $this->getTotalTicket();
            $result['customer_id'] = $this->customerSession->getCustomerId();
            $result['customer_group_name'] = $this->getCustomerGroupName($this->customerSession->getCustomerGroupId());
            $result['customer_group_id'] = $this->customerSession->getCustomerGroupId();
            if($totalPoints = (float) $this->hotaiPointHelper->getHotaiPoint()) {
                $result['customer_point'] = $totalPoints;
                $result['customer_point_icon'] = $this->hotaiPointHelper->formatPoints($totalPoints, true);
                $result['customer_point_formated'] = $this->hotaiPointHelper->formatPoints($totalPoints, false, false, false);
                $result['totalpoints'] = $totalPoints;
            } else {
                $result['customer_point'] = 0;
                $result['customer_point_icon'] = $this->hotaiPointHelper->formatPoints(0, true);
                $result['customer_point_formated'] = $this->hotaiPointHelper->formatPoints(0, false, false, false);
                $result['totalpoints'] = 0;
            }
            $result['expirepoint'] = $expireHotaiPoint;
            $result['totalticket'] = $totalTicket;
            $result['totalorders'] = $totalOrder['count'];
            $result['totalamount'] = $totalOrder['total'];
        }
        return $result;
    }

    /**
     * @param $customerId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerGroupName($customerGroupId)
    {
        if($customerGroupId) {
            try {
                $groupEntity = $this->groupRepository->getById($customerGroupId);
                return $groupEntity->getCode();
            } catch (\Exception $e) {
                return "Guest";
            }

        }
        return "Guest";
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
                if ($this->b8CustomerHelper->isLoggedInAndIsBuyer()) {
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
        if ($this->b8CustomerHelper->isLoggedInAndIsBuyer()) {
            $orderTotals = $this->orderCollectionFactory->create()
                ->addAttributeToFilter('status', Order::STATE_COMPLETE)
                ->addAttributeToFilter('customer_id', $this->b8CustomerHelper->getCustomerId())
                ->addAttributeToSelect('grand_total')
                ->getColumnValues('grand_total');
            $totalSum = array_sum($orderTotals);
            $count = count($orderTotals);
        }
        return ['total' => $totalSum, 'count' => $count];
    }
}
