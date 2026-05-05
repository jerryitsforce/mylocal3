<?php

namespace Branch8\GiftToFriend\Helper;

use Magento\Sales\Model\Order as SalesOrder;
use Magento\Quote\Model\QuoteRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use Branch8\HotaiAuth\Service\HotaiAuthService;

class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
    /** @var QuoteRepository */
    protected QuoteRepository $quoteRepository;

    /** @var HotaiCoreCommonHelper */
    protected HotaiCoreCommonHelper $hotaiCoreCommonHelper;

    /** @var OrderRepositoryInterface */
    protected OrderRepositoryInterface $orderRepository;

    /** @var HotaiAuthService */
    protected HotaiAuthService $hotaiAuthService;

    public function __construct(
        QuoteRepository $quoteRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        OrderRepositoryInterface $orderRepository,
        HotaiAuthService $hotaiAuthService,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->orderRepository = $orderRepository;
        $this->hotaiAuthService = $hotaiAuthService;

        parent::__construct($context);
    }

    /**
     * isGiftOrder
     *
     * @param  mixed $order
     * @return bool
     */
    public function isGiftOrder($order)
    {
        return (bool) $order->getData('is_gift_order');
    }

    /**
     * isGiftOrderConfirmed
     *
     * @param  mixed $order
     * @return bool
     */
    public function isGiftOrderConfirmed($order)
    {
        return (bool) $order->getData('is_gift_confirmed') ?? false;
    }

    public function resolveCustomOwnerForGiftOrder(int|string $orderId): ?array
    {
        $memberSeq = null;

        /** @var SalesOrder $order */
        $order = $this->orderRepository->get((int) $orderId);

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $isGiftOrder = max((int)$order->getData('is_gift_order'), (int)$quote->getData('is_gift_order'));
        $isGiftConfirmed = max((int)$order->getData('is_gift_confirmed'), (int)$quote->getData('is_gift_confirmed'));

        if (!$isGiftOrder || !$isGiftConfirmed) {
            return null;
        }

        $telephone = $order->getData('recipient_telephone');
        if (empty($telephone)) {
            return null;
        }

        if (trim($telephone) != '') {
            /** Get Member seq for current telephone */
            $memberSeq = $this->hotaiAuthService->getHotaiOneIdByPhoneNumber($telephone);
        }

        return [
            'telephone' => $telephone,
            'member_seq' => $memberSeq,
            'customer_id' => 0
        ];
    }
}
