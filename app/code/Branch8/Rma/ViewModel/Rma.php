<?php
declare(strict_types=1);

namespace Branch8\Rma\ViewModel;

use Branch8\Rma\Model\Actions\ReasonsByTags;
use Branch8\Rma\Model\Rma\Resolution;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Branch8\HotaiCore\Helper\VirtualProduct;
use Branch8\Rma\Helper\Config\Flow;
use Branch8\Rma\Helper\Config\Shipping;
use Webkul\MpRmaSystem\Helper\Data;

/**
 *
 */
class Rma implements ArgumentInterface
{
    const XML_PATH_ALLOW_SELLER_CHANGE_RESOLUTION_TYPE='mprmasystem/settings/seller_can_change_resolution_type';
    /*
     *
     */
    private ReasonsByTags $reasonsByTags;
    /**
     * @var Escaper
     */
    private Escaper $escaper;
    /**
     * @var \Branch8\Rma\Helper\Data
     */
    private \Branch8\Rma\Helper\Data $helper;
    private Flow $flow;
    /**
     * @var Shipping
     */
    private Shipping $deliveredLabel;
    /**
     * @var VirtualProduct
     */
    private VirtualProduct $virtualProduct;
    private RequestInterface $request;
    /**
     * @var \Webkul\MpRmaSystem\Model\DetailsFactory
     */
    protected $rma;
    private \Webkul\MpRmaSystem\Model\DetailsFactory $rmaFactory;
    private UrlInterface $url;
    private \Magento\Framework\Data\Form\FormKey $formkey;
    private \Magento\Sales\Model\Order\ItemFactory $orderItemFactory;
    private \Webkul\MpRmaSystem\Model\ResourceModel\Items\CollectionFactory $rmaItemCollectionFactory;
    private ScopeConfigInterface $scopeConfigInterface;

    /**
     * @param ReasonsByTags $reasonsByTags
     * @param \Branch8\Rma\Helper\Data $helper
     * @param Flow $flow
     * @param Shipping $deliveredLabel
     * @param VirtualProduct $virtualProduct
     * @param Escaper $escaper
     * @param RequestInterface $request
     * @param UrlInterface $url
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param \Webkul\MpRmaSystem\Model\DetailsFactory $rmaFactory
     * @param \Magento\Sales\Model\Order\ItemFactory $orderItemFactory
     * @param \Webkul\MpRmaSystem\Model\ResourceModel\Items\CollectionFactory $rmaItemcollectionFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     */
    public function __construct(
        ReasonsByTags                                                   $reasonsByTags,
        \Branch8\Rma\Helper\Data                                        $helper,
        Flow                                                            $flow,
        Shipping                                                        $deliveredLabel,
        VirtualProduct                                                  $virtualProduct,
        Escaper                                                         $escaper,
        RequestInterface                                                $request,
        UrlInterface                                                    $url,
        \Magento\Framework\Data\Form\FormKey                            $formKey,
        \Webkul\MpRmaSystem\Model\DetailsFactory                        $rmaFactory,
        \Magento\Sales\Model\Order\ItemFactory                          $orderItemFactory,
        \Webkul\MpRmaSystem\Model\ResourceModel\Items\CollectionFactory $rmaItemcollectionFactory,
        ScopeConfigInterface $scopeConfigInterface
    )
    {
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->flow = $flow;
        $this->deliveredLabel = $deliveredLabel;
        $this->reasonsByTags = $reasonsByTags;
        $this->virtualProduct = $virtualProduct;
        $this->request = $request;
        $this->rmaFactory = $rmaFactory;
        $this->url = $url;
        $this->formkey = $formKey;
        $this->orderItemFactory = $orderItemFactory;
        $this->rmaItemCollectionFactory = $rmaItemcollectionFactory;
        $this->scopeConfigInterface = $scopeConfigInterface;
    }

    /**
     * Return all DeclineRmaStatus
     * @return array
     */
    public function getDeclineRmaStatuses()
    {
        return RmaStatus::declinedStatus();
    }

    /**
     * @param $productDetails
     * @return array
     */
    public function calTotalPointAndTotalPrice($productDetails)
    {
        $totalPrice = $totalPoint = 0;
        foreach ($productDetails as $product) {
            $totalPrice += $this->helper->getItemFinalPrice($product);
            $totalPoint += $this->helper->getItemFinalPoint($product);
        }
        return [$totalPoint, $totalPrice];
    }

    /**
     * Return All Reasons For Rma Declination
     * @return array
     */
    public function getAllReasonsForDecline()
    {
        $reasons = [];
        foreach ($this->reasonsByTags->find(ReasonsByTags::RMA_DECLINE_REASON) as $row) {
            $reasons[$row['id']] = $this->escaper->escapeHtml($row['reason']);
        }
        return $reasons;
    }

    /**
     * @return array
     */
    public function getAllBuyerReasons()
    {
        $reasons = [];
        $result = $this->reasonsByTags->find(ReasonsByTags::FRONTEND_TAG);
        foreach ($result as $row) {
            $reasons[$row['id']] = $this->escaper->escapeHtml($row['reason']);
        }
        return $reasons;
    }
    /**
     * @return array
     */
    public function getResolutionOptions()
    {
        return [
            Data::RESOLUTION_REFUND => __('Return & Refund'),
            Data::RESOLUTION_REPLACE => __('Exchange'),
        ];
    }

    /**
     * @return \Webkul\MpRmaSystem\Model\Details
     */
    public function getRmaDetails()
    {
        return $this->rmaFactory->create()->load($this->request->getParam('id'));
    }

    /**
     * @return \Branch8\Rma\Helper\Data
     */
    public function getHelper()
    {
        return $this->helper;
    }

    /**
     * @param $id
     * @return string
     */
    public function getLabelResolutionById($id)
    {
        return $this->getResolutionOptions()[$id];
    }

    /**
     * @param $status
     * @param $isTicket
     * @param $isReturnAgain
     * @param $canAdjustShippingStatus
     * @param $hasCreditNemo
     * @return array
     */
    public function getAllNextStatusOptions(
        $status,
        $isTicket,
        $isReturnAgain,
        $canAdjustShippingStatus = false,
        $hasCreditNemo = false
    )
    {
        return $this->flow->getAllNextStatusOptions($status, $isTicket, $isReturnAgain, $canAdjustShippingStatus, $hasCreditNemo);
    }

    /**
     * @param $status
     * @param $isTicket
     * @param $isReturnAgain
     * @return array
     */
    public function getAdminAllNextStatusOptions($status, $isTicket, $isReturnAgain)
    {
        return $this->flow->getAdminAllNextStatusOptions($status, $isTicket, $isReturnAgain);
    }
    /**
     * canRefund
     *
     * @param string|int $status
     * @return bool
     */
    public function canRefund($status)
    {
        return $this->flow->isRefundAvaliableStatus($status);
    }

    /**
     * getRmaDeliveryTime
     *
     * @param string $deliveryTime
     * @return string
     */
    public function getRmaDeliveryTime($deliveryTime)
    {
        return implode(',', $this->deliveredLabel->getDeliveryTime($deliveryTime));
    }

    /**
     * isTicket
     *
     * @param string $orderItemId
     * @return boolean
     */
    public function isTicket($orderItemId)
    {
        if (is_null($orderItemId) || empty($orderItemId)) {
            return false;
        }
        return $this->virtualProduct->checkIsProductTicketTypeByOrderItemId((int)$orderItemId);
    }

    /**
     * isReadyToBeShipped
     *
     * @param string|int $status
     * @return bool
     */
    public function isReadyToBeShipped($status)
    {
        return $this->flow->isReadyToBeShippedStatus($status);
    }

    /**
     * @param array $data
     * @return string
     */
    public function jsonEncode(array $data)
    {
        return $this->helper->jsonEncodeData($data);
    }

    /**
     * @return string
     */
    public function getChangResolutionTypeUrl()
    {
        return $this->url->getUrl('reqrma/rma/changeResolution');
    }

    /**
     * @return string
     */
    public function getFormKey()
    {
        return $this->formkey->getFormKey();
    }

    /**
     *
     */
    public function canShowShippingForm(\Webkul\MpRmaSystem\Model\Details $rmaDetail)
    {
        $canShow = false;
        $items = $this->rmaItemCollectionFactory->create()->addFieldToFilter('rma_id', $rmaDetail->getId());
        /**
         * @var  $item \Webkul\MpRmaSystem\Model\Items
         * @var  $orderItem \Magento\Sales\Model\Order\Item
         */
        foreach ($items as $item) {
            $orderItem = $this->orderItemFactory->create()->load($item->getItemId());
            if ($orderItem && $orderItem->getItemId() && !$orderItem->getIsVirtual()) {
                $canShow = true;
                break;
            }
        }
        return $canShow;
    }

    /**
     * @return mixed
     */
    public function allowChangeResotionStype()
    {
        return (bool)$this->scopeConfigInterface->getValue(self::XML_PATH_ALLOW_SELLER_CHANGE_RESOLUTION_TYPE);
    }
}
