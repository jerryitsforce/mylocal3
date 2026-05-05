<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Rma\Controller\Seller;

use \Branch8\Rma\Helper\Data;
use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Branch8\Rma\Helper\Status as RmaStatus;
use Branch8\Sales\Helper\Order\UpdateOrderStatus as ItemStatus;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\RmaAdminUi\Model\AdminRma\PostDataToRmaObject;
use Magento\Framework\Exception\LocalizedException;
use Branch8\Rma\Model\SenderType;
use Branch8\Rma\Helper\RmaRecord;

class Create extends \Webkul\MpRmaSystem\Controller\Customer\Create implements HttpPostActionInterface
{
    /**
     * Log option value for seller create controller.
     */
    private const LOG_OPTION = 'SellerCreate';

    /** @var \Branch8\Rma\Helper\Data $helper */
    protected $helper;

    /** @var int $rmaId */
    private $rmaId;

    /** @var mixed $rmaData */
    protected $rmaData;

    protected $status;

    protected $statusLabel;

    protected $itemStatus;

    protected $jsonFactory;

    protected $postDataToRmaObject;

    private $resultPageFactory;

    private $itemFactory;

    protected $iniRmaDetails;

    protected $orderFactory;

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
        PostDataToRmaObject $postDataToRmaObject,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Webkul\MpRmaSystem\Model\ItemsFactory     $itemsFactory,
        RmaRecord $iniRmaDetails,
        \Magento\Sales\Model\OrderFactory $orderFactory
    ) {
        $this->helper = $helper;
        $this->status = $status;
        $this->statusLabel = $statusLabel;
        $this->itemStatus = $itemStatus;
        $this->jsonFactory = $jsonFactory;
        $this->postDataToRmaObject = $postDataToRmaObject;
        $this->resultPageFactory = $resultPageFactory;
        $this->itemFactory = $itemsFactory;
        $this->iniRmaDetails = $iniRmaDetails;
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $url, $session, $helper, $details, $items, $customer, $orderItem, $fileSystem, $fileUploaderFactory);
    }

    /**
     * @return Json
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->getRequest()->isPost()) {
            $this->messageManager->addErrorMessage(__('Something went wrong while creating rma.'));
            return $resultRedirect->setPath('marketplace/order/history');
        }

        $rmaData = $this->getRequest()->getParams();

        $orderId = $rmaData['order_id'];
        $order = $this->orderFactory->create()->load($orderId);
        $ecpayInvoiceTag = $order->getData('ecpay_invoice_tag');
        if(!$ecpayInvoiceTag){
            $this->messageManager->addErrorMessage(__('Sorry, there was an operation error. Please try again later or contact customer service. Order Number:{%1}', $order->getIncrementId()));
            return $resultRedirect->setPath('marketplace/order/history');
        }
        $invoiceCollection = $order->getInvoiceCollection();
        if(!$invoiceCollection->getSize()){
            $this->messageManager->addErrorMessage(__('Sorry, there was an operation error. Please try again later or contact customer service. Order Number:{%1}', $order->getIncrementId()));
            return $resultRedirect->setPath('marketplace/order/history');
        }


        if (!empty($rmaData)) {
            try {

                if(isset($rmaData['rma_phone']) && strlen($rmaData['rma_phone']) > 10) {
                    throw new Exception(_('Phone number must be less or equal to ten.'));
                }

                if(!isset($rmaData['order_items'])) {
                    throw new Exception(_('There is no rma item selected.'));
                }

                $this->iniRmaDetails->createBySeller($rmaData);
                $this->setRmaId($this->iniRmaDetails->getRmaDetailRecordId());
                $this->sendRmaEmail($this->iniRmaDetails->getRmaDetailData());
                $this->messageManager->addSuccessMessage(__('Save successfully.'));
                $this->messageManager->addSuccessMessage(__('Create Rma Successfully.'));
                return $resultRedirect->setPath('mprmasystem/seller/allrma');
            } catch (\Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Rma\Helper\Log::class)
                    ->exception($e, self::LOG_OPTION, __METHOD__, ['order_id' => $rmaData['order_id'] ?? null]);
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
                return $resultRedirect->setPath('marketplace/order/history');
            }
        } else {
            $this->messageManager->addErrorMessage(__('Something went wrong while creating rma.'));
            return $resultRedirect->setPath('marketplace/order/history');
        }
    }

    public function setRmaId($rmaId)
    {
        $this->rmaId = $rmaId;
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

//        try {
//            $this->notifySeller($details);
//        } catch (\Exception $e) {
//            $this->logger->info("[NEW RMA] Can't send email to seller: " . $e->getMessage());
//        }

        try {
            $this->helper->sendNewRmaEmail($details);
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__);
            $this->messageManager->addError(__($e->getMessage()));
        }
    }
}
