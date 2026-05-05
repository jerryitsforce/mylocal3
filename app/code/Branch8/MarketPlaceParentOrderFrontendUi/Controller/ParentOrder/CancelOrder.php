<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;

use Branch8\GA4\Model\Event;
use Branch8\GA4\Model\ProductHelper;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class CancelOrder extends Action\Action implements Action\HttpPostActionInterface
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var ParentOrderRepositoryInterface
     */
    protected $parentOrderRepository;

    /**
     * @var ParentOrderManagementInterface
     */
    protected ParentOrderManagementInterface $parentOrderManagement;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $jsonFactory;

    protected $orderRepository;

    private Session $customerSession;
    private ProductHelper $ga4ProductHelper;
    private LoggerInterface $logger;

    /**
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $jsonFactory,
        Session $customerSession,
        ProductHelper $ga4ProductHelper,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->jsonFactory = $jsonFactory;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->ga4ProductHelper = $ga4ProductHelper;
        $this->logger = $logger;
    }

    /**
     * Order view page
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        $id = (int)$this->getRequest()->getParam('order_id');
        $reason = $this->getRequest()->getParam('reason');
        $reasonDescription = $this->getRequest()->getParam('reason_description');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Order ID is required.'));
            return $this->_redirect('sales/parentOrder/history');
        }

        try {
            $parentOrder = $this->parentOrderRepository->get($id);

            /**
             * Get list of child order, make sure the all childs order has ecpay_invoice_tag=1
             */
            $isValid = $this->validateCancelParentOrder($parentOrder);
            if(!$isValid){
                return $this->_redirect('sales/parentOrder/history');
            }

            $comment = $reason . ': ' . $reasonDescription;
            $this->parentOrderManagement->setComment($comment);
            $this->parentOrderManagement->cancel($parentOrder);

            if (!in_array($parentOrder->getDetail()->getStatus(), ['pending', 'pending_payment'])) {
                $this->customerSession->setGA4RefundEventData(
                    $this->getGA4RefundEventData($parentOrder)
                );
            }

            $this->messageManager->addSuccessMessage(__('You canceled the order.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while canceling the order.'));
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketPlaceParentOrderFrontendUi', 'exceptionlog')){
                $this->logger->critical($e);
            }
        }

        return $this->_redirect('sales/parentOrder/history');
    }

    /**
     * @param $parentOrder ParentOrderInterface
     * @return bool
     * @throws LocalizedException
     */
    protected function validateCancelParentOrder($parentOrder)
    {
        $isValid = true;
        $subOrders = $parentOrder->getSubOrders();
        $notSubOrderExceptions = new LocalizedException(
            __('Parent Order "%1" can not cancel : no sub-orders found',
                $parentOrder->getDetail()->getIncrementId()
            )
        );

        if (empty($subOrders)) {
            throw $notSubOrderExceptions;
        }

        foreach ($subOrders as $_order) {
            if($_order->getData('ecpay_invoice_tag') != 1){
                $isValid = false;
                $this->messageManager->addErrorMessage(__('Sorry, there was an operation error. Please try again later or contact customer service. Order Number:{%1（%2）}', $_order->getIncrementId(), $parentOrder->getDetail()->getIncrementId()));
            }
        }

        return $isValid;
    }

    private function getGA4RefundEventData(ParentOrderInterface $parentOrder) {
        $result = [];
        $result['event'] = Event::REFUND;
        $result['ecommerce'] = [];
        $result['ecommerce']['transaction_id'] = $parentOrder->getDetail()->getIncrementId();
        $result['ecommerce']['currency'] = $parentOrder->getDetail()->getOrderCurrencyCode();
        $result['ecommerce']['value'] = $this->ga4ProductHelper->formatMoney(
            $this->parentOrderManagement->getTotalByKey($parentOrder, 'grand_total')?->getValue() ?? 0
        );
        $result['ecommerce']['items'] = [];

        $suborders = $parentOrder->getSubOrders();

        foreach ($suborders as $order) {
            /** @var \Magento\Sales\Api\Data\OrderItemInterface[] $items */
            $items = $order->getAllVisibleItems();
            foreach ($items as $item) {
                $productData = $this->ga4ProductHelper->getDetailProductPush($item->getProduct());
                unset($productData['item_list_id']);
                unset($productData['item_list_name']);
                unset($productData['promotion_id']);
                unset($productData['promotion_name']);
                $productData['quantity'] = $this->ga4ProductHelper->formatQty($item->getQtyOrdered());
                $productData['price'] = $this->ga4ProductHelper->formatMoney($item->getPriceInclTax());
                $productData['discount'] = $this->ga4ProductHelper->formatMoney($item->getDiscountAmount());
                $productData['affiliation'] = $this->ga4ProductHelper->getSellerByProductId($item->getProduct()->getRowId());

                $productData['item_variant'] = $this->ga4ProductHelper->checkVariantForProduct(
                    $item->getProduct(),
                    $item->getProductOptions()['info_buyRequest'] ?? [],
                );
                $result['ecommerce']['items'][] = $productData;
            }
        }

        return $result;
    }
}
