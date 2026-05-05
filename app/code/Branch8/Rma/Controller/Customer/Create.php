<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Rma\Controller\Customer;

use Branch8\Customer\Model\GetCustomerNickname;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Branch8\Rma\Helper\Status as RmaStatus;
use Branch8\Sales\Helper\Order\UpdateOrderStatus as ItemStatus;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\Rma\Helper\RmaRecord;
use Magento\Sales\Model\OrderFactory;
use Psr\Log\LoggerInterface;

class Create extends \Webkul\MpRmaSystem\Controller\Customer\Create implements HttpPostActionInterface
{
    /**
     * Log option value for customer create controller.
     */
    private const LOG_OPTION = 'CustomerCreate';

    /** @var \Branch8\Rma\Helper\Data $helper */
    protected $helper;

    /** @var int $rmaId */
    private $rmaId;

    /** @var mixed $rmaData */
    protected $rmaData;

    /** @var \Branch8\Rma\Helper\Status $status */
    protected $status;

    /** @var \Branch8\Rma\Helper\Config\StatusLabel $statusLabel */
    protected $statusLabel;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $itemStatus */
    protected $itemStatus;

    /** @var \Magento\Framework\Controller\Result\JsonFactory $jsonFactory */
    protected $jsonFactory;

    /** @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface */
    protected $_localeDate;

    /** @var \Branch8\CTBC\Model\OrderManagement $orderManagement */
    protected $orderManagement;

    protected $rmaRecord;

    private GetCustomerNickname $customerNickname;
    private OrderFactory $orderFactory;
    private \Branch8\Rma\Helper\Email $emailHelper;
    private LoggerInterface $logger;
    private \Magento\Customer\Model\Session $customerSession;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_conn;

    /**
     * @param \Branch8\Rma\Helper\Data $helper
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Url $url
     * @param \Magento\Customer\Model\Session $session
     * @param \Webkul\MpRmaSystem\Model\DetailsFactory $details
     * @param \Webkul\MpRmaSystem\Model\ItemsFactory $items
     * @param \Magento\Customer\Model\Customer $customer
     * @param \Magento\Sales\Model\Order\Item $orderItem
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory
     * @param RmaStatus $status
     * @param \Branch8\Rma\Helper\Config\StatusLabel $statusLabel
     * @param ItemStatus $itemStatus
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Branch8\CTBC\Model\OrderManagement $orderManagement
     * @param RmaRecord $rmaRecord
     * @param OrderFactory $orderFactory
     * @param \Branch8\Rma\Helper\Email $emailHelper
     * @param LoggerInterface $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Branch8\Rma\Helper\Data $helper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Url $url,
        \Magento\Customer\Model\Session $session,
        \Webkul\MpRmaSystem\Model\DetailsFactory $details,
        \Webkul\MpRmaSystem\Model\ItemsFactory $items,
        \Magento\Customer\Model\Customer $customer,
        \Magento\Sales\Model\Order\Item $orderItem,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        RmaStatus $status,
        \Branch8\Rma\Helper\Config\StatusLabel $statusLabel,
        ItemStatus $itemStatus,
        JsonFactory $jsonFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Branch8\CTBC\Model\OrderManagement $orderManagement,
        RmaRecord $rmaRecord,
        OrderFactory $orderFactory,
        \Branch8\Rma\Helper\Email $emailHelper,
        LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->helper = $helper;
        $this->status = $status;
        $this->statusLabel = $statusLabel;
        $this->itemStatus = $itemStatus;
        $this->jsonFactory = $jsonFactory;
        $this->_localeDate = $localeDate;
        $this->orderManagement = $orderManagement;
        $this->rmaRecord = $rmaRecord;
        $this->orderFactory = $orderFactory;
        $this->emailHelper = $emailHelper;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        parent::__construct($context, $url, $session, $helper, $details, $items, $customer, $orderItem, $fileSystem, $fileUploaderFactory);
        $this->_conn = $resourceConnection->getConnection();
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        if (!$this->getRequest()->isPost()) {
            return $result->setData(['success' => false, 'message' => __('Something went wrong.')]);
        }

        $rmaData = $this->getRequest()->getParams();

        /**
         * validate ecpay_invoice_tag
         */
        $subOrderId = $rmaData['order_id'];
        $sqlOrder = $this->_conn->select()
            ->from(['or' => 'sales_order'], ['ecpay_invoice_tag', 'increment_id'])
            ->where('entity_id = ?', $subOrderId);
        $orderData = $this->_conn->fetchRow($sqlOrder);
        if((int)$orderData['ecpay_invoice_tag'] != 1){
            $parentOrder = $this->orderManagement->getParentOrder(true, (int)$subOrderId);
            return $result->setData(['success' => false, 'message' => __('Sorry, there was an operation error. Please try again later or contact customer service. Order Number:{%1（%2）}', $orderData['increment_id'], $parentOrder->getIncrementId())]);
        }

        if (!empty($rmaData)) {
            try {
                $this->createRma($rmaData);
                if ($rmaData['resolution_type'] == 2) {
                    $message = __('Your exchange request has been sent and we will process it as soon as possible. If you have received the goods, please package them properly and we will arrange for home delivery personnel to pick them up at your door. If you have not received the item yet, please refuse it when it arrives. After retrieving the product, we will confirm the condition of the product and arrange for the exchange product to be shipped. If you have any questions, please feel free to contact the seller.');
                }else{
                    if(isset($rmaData['rma_phone'])){
                        if(strlen($rmaData['rma_phone']) > 10) {
                            throw new \Exception(_('Phone number must be less or equal to ten.'));
                        }
                        $message = __('你的退貨申請已送出，我們將盡快處理。若你已收到商品，請妥善包裝，我們將安排宅配人員上門取件。若你尚未收到商品，請在商品送達時拒收。取回商品後，我們將確認商品狀況並安排退款。若有任何問題，請隨時聯繫賣家。');
                    } else {
                        // $message = __('退貨申請已送出');
                        $message = __('');
                    }
                }
                if ($rmaData['resolution_type'] == 1) {
                    $this->setGa4Data();
                }
                return $result->setData(['success' => true, 'message' => $message]);
            } catch (\Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Rma\Helper\Log::class)
                    ->exception($e, self::LOG_OPTION, __METHOD__, ['order_id' => $rmaData['order_id'] ?? null]);
                $message = __('There is some problem in generating RMA.');
                if ($e->getMessage() == __('The RMA has already been created and cannot be created again.')) {
                    $message = $e->getMessage();
                }
                return $result->setData(['success' => false, 'message' => $message, 'error' => $e->getMessage()]);
            }
        } else {
            return $result->setData(['success' => false, 'message' => __('Something went wrong.')]);
        }
    }

    /**
     * @param $id
     * @return string|null
     */
    public function getOrderUrlDetail($parentOrder){
        if($parentOrder){
            return $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $parentOrder]]);
        }
        return $this->_url->getUrl('sales/parentOrder/history');
    }

    /**
     * createRma
     *
     * @param  array $rmaData
     * @return void
     */
    public function createRma($rmaData)
    {
        $rmaData['order_items'] = [$rmaData['item_id']];
        $this->rmaRecord->createByCustomer($rmaData);
        $this->setRmaId($this->rmaRecord->getRmaDetailRecordId());
        $this->sendRmaEmail($this->rmaRecord->getRmaDetailData());
        $this->helper->setRegistry("rma_id", $this->rmaRecord->getRmaDetailRecordId());
        $this->messageManager->addSuccess(__('New RMA request generated.'));
    }

    /**
     * @param $createdAt
     * @return string
     */
    public function getOrderDate($createdAt)
    {
        try {
            return $this->_localeDate->date($createdAt)->format('Y/m/d H:i');
        }catch (\Exception $exception){
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__);
            return '';
        }
    }

    /**
     * sendRmaEmail
     *
     * @param  array $rmaData
     * @return void
     */
    private function sendRmaEmail($rmaData)
    {
        $rmaInfo = $rmaData;
        $rmaInfo['rma_id'] = $this->rmaId;
        $details = [
            'type' => 0,
            'name' => $rmaData['customer_name'],
            'order_id' => $rmaData['order_id'],
            'rma' => $rmaInfo,
            'order_data' => $rmaData['order_data'],
        ];

        try {
            $this->notifySeller($details);
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
        }

        try {
            $this->helper->sendNewRmaEmail($details);
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
            $this->messageManager->addError(__($e->getMessage()));
        }
    }

    /**
     * Notify seller after creating customer RMA request.
     *
     * @param array $details RMA details payload.
     * @return void
     */
    public function notifySeller($details)
    {
        $rmaDetail = $details['rma'];
        $order = $this->orderFactory->create()->load($rmaDetail['order_id']);
        $orderItem = $order->getItemById($rmaDetail['item_id']);
        $requestItemInformation = [
            $rmaDetail['item_id'] => [
                [
                    'item_id' => $rmaDetail['item_id'],
                    'product_id' => $rmaDetail['product_id'],
                    'qty' => $orderItem->getQtyOrdered(),
                    'reason_id' => $rmaDetail['reason_id'],
                    'sku' => $orderItem->getSku(),
                    'price' => $orderItem->getPrice()
                ]
            ]
        ];

        $postData = [
            'create_by_customer' => true,
            'order_id' => $rmaDetail['order_id'],
            'resolution_type' => $rmaDetail['resolution_type'],
            'order_increment_id' => $details['order_data']['increment_id'],
            'rma_order_choose_grid' => [
                'created_at' => $details['order_data']['created_at']
            ],
            'list_rma_items' => [
                [
                    'item_id' => $rmaDetail['item_id'],
                    'sku' => $orderItem->getSku(),
                    'name' => $orderItem->getName(),
                ]
            ]
        ];

        $this->emailHelper->sendNewRmaNotifyEmailToSeller($requestItemInformation, $postData);
    }

    /**
     * setRmaId
     *
     * @param  int $rmaId
     * @return void
     */
    private function setRmaId($rmaId)
    {
        $this->rmaId = $rmaId;
    }

    /**
     * Save GA4 refund event data into customer session.
     *
     * @return void
     */
    private function setGa4Data()
    {
        if ($this->rmaRecord->getGA4RefundData()) {
            $this->customerSession->setGA4RefundEventData(
                $this->rmaRecord->getGA4RefundData()
            );
        }
    }
}
