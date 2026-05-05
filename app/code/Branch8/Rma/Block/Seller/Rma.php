<?php

namespace Branch8\Rma\Block\Seller;

use Branch8\HotaiCore\Helper\VirtualProduct;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\Rma\Helper\Config\Flow;
use Branch8\Rma\Helper\Config\Shipping;
use Webkul\MpRmaSystem\Model\ResourceModel\Conversation\CollectionFactory;

class Rma extends \Webkul\MpRmaSystem\Block\Seller\Rma
{

    /** @var \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct */
    protected $virtualProduct;

    /** @var \Branch8\Rma\Helper\Config\Flow $flow */
    protected $flow;

    /** @var \Branch8\Rma\Helper\Config\Shipping $deliveredLabel */
    protected $deliveredLabel;

    /** @var \Branch8\Sales\Helper\Config $b8salesConfigHelper */
    private $b8salesConfigHelper;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        CollectionFactory $conversationCollection,
        \Branch8\Rma\Helper\Data $mpRmaHelper,
        Flow $flow,
        Shipping $deliveredLabel,
        VirtualProduct $virtualProduct,
        \Branch8\Sales\Helper\Config $b8salesConfigHelper
    ) {
        $this->flow = $flow;
        $this->deliveredLabel = $deliveredLabel;
        $this->virtualProduct = $virtualProduct;
        $this->b8salesConfigHelper = $b8salesConfigHelper;
        parent::__construct(
            $context,
            $conversationCollection,
            $mpRmaHelper
        );
    }

    /**
     * getAllNextStatusOptions
     *
     * @param  string|int $status
     * @param  bool $isTicket
     * @param  bool $isReturnAgain
     * @return array
     */
    public function getAllNextStatusOptions($status, $isTicket, $isReturnAgain)
    {
        return $this->flow->getAllNextStatusOptions($status, $isTicket, $isReturnAgain);
    }

    /**
     * canRefund
     *
     * @param  string|int $status
     * @return bool
     */
    public function canRefund($status)
    {
        return $this->flow->isRefundAvaliableStatus($status);
    }

    /**
     * getRmaDeliveryTime
     *
     * @param  string $deliveryTime
     * @return string
     */
    public function getRmaDeliveryTime($deliveryTime)
    {
        return implode(',', $this->deliveredLabel->getDeliveryTime($deliveryTime));
    }

    /**
     * isTicket
     *
     * @param  string $orderItemId
     * @return boolean
     */
    public function isTicket($orderItemId)
    {
        if (is_null($orderItemId)) {
            return false;
        }
        return $this->virtualProduct->checkIsProductTicketTypeByOrderItemId((int) $orderItemId);
    }
    
    /**
     * canReturnStock
     *
     * @param  int $orderItemId
     * @return bool
     */
    public function canReturnStock($orderItemId)
    {
        if (is_null($orderItemId)) {
            return false;
        }

        $type = $this->virtualProduct->getProductTicketTypeByOrderItemId((int) $orderItemId);

        /** Not ticket type */
        if (!$type) {
            return true;
        }

        /** Cannot return to stock */
        if (in_array(
            $type,
            [
                VirtualProductType::TYPE_YOXI_TICKET,
                VirtualProductType::TYPE_FAMILY_BONUS_PIN_TICKET,
                VirtualProductType::TYPE_GENERAL_NOTIFY_TICKET,
                VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET,
            ]
        )
        ) {
            return false;
        }

        return true;
    }

    /**
     * isReadyToBeShipped
     *
     * @param  string|int $status
     * @return bool
     */
    public function isReadyToBeShipped($status)
    {
        return $this->flow->isReadyToBeShippedStatus($status);
    }

    /**
     * @return \Branch8\Sales\Helper\Config
     */
    public function getB8salesConfigHelper()
    {
        return $this->b8salesConfigHelper;
    }

    /**
     * getAdminAllNextStatusOptions
     *
     * @param  string|int $status
     * @param  bool $isTicket
     * @param  bool $isReturnAgain
     * @return array
     */
    public function getAdminAllNextStatusOptions($status, $isTicket, $isReturnAgain)
    {
        return $this->flow->getAdminAllNextStatusOptions($status, $isTicket, $isReturnAgain);
    }
}
