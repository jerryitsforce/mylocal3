<?php
declare(strict_types=1);

namespace HotaiConnected\ManualInvoice\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use HotaiConnected\ManualInvoice\Api\ManualInvoiceRepositoryInterface;
use HotaiConnected\ManualInvoice\Api\Data\ManualInvoiceInterfaceFactory;
use Magento\Backend\Model\Auth\Session;
use Psr\Log\LoggerInterface;

class SaveInvoice extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'HotaiConnected_ManualInvoice::save';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ManualInvoiceRepositoryInterface
     */
    private $manualInvoiceRepository;

    /**
     * @var ManualInvoiceInterfaceFactory
     */
    private $manualInvoiceFactory;

    /**
     * @var Session
     */
    private $authSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * SaveInvoice constructor.
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param ManualInvoiceRepositoryInterface $manualInvoiceRepository
     * @param ManualInvoiceInterfaceFactory $manualInvoiceFactory
     * @param Session $authSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        OrderRepositoryInterface $orderRepository,
        ManualInvoiceRepositoryInterface $manualInvoiceRepository,
        ManualInvoiceInterfaceFactory $manualInvoiceFactory,
        Session $authSession,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderRepository = $orderRepository;
        $this->manualInvoiceRepository = $manualInvoiceRepository;
        $this->manualInvoiceFactory = $manualInvoiceFactory;
        $this->authSession = $authSession;
        $this->logger = $logger;
    }

    /**
     * Save manual invoice
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        
        try {
            $orderId = (int) $this->getRequest()->getParam('order_id');
            $invoiceData = $this->getRequest()->getPost('invoice');
            
            if (!$orderId) {
                throw new LocalizedException(__('Order ID is required.'));
            }
            
            if (!$invoiceData) {
                throw new LocalizedException(__('Invoice data is required.'));
            }
            
            // 驗證訂單是否存在
            $order = $this->orderRepository->get($orderId);
            
            // 檢查是否已經有手開發票記錄
            $existingInvoice = $this->manualInvoiceRepository->getByOrderId($orderId);
            
            if ($existingInvoice) {
                // 更新現有記錄
                $manualInvoice = $existingInvoice;
            } else {
                // 建立新記錄
                $manualInvoice = $this->manualInvoiceFactory->create();
                $manualInvoice->setOrderId($orderId);
            }
            
            // 設定資料
            $this->setInvoiceData($manualInvoice, $invoiceData);
            
            $user = $this->authSession->getUser();
            if (!$user) {
                throw new LocalizedException(__('User session not found.'));
            }
            $manualInvoice->setUpdatedBy((int) $user->getId());
            
            // 儲存
            $this->manualInvoiceRepository->save($manualInvoice);
            
            // 更新訂單的 is_manual_invoice 欄位
            $order->setData('is_manual_invoice', 1);
            $this->orderRepository->save($order);
            
            return $resultJson->setData([
                'success' => true,
                'message' => __('Manual invoice saved successfully.')
            ]);
            
        } catch (LocalizedException $e) {
            $this->logger->error('Manual Invoice Save Error: ' . $e->getMessage());
            return $resultJson->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Manual Invoice Save Error: ' . $e->getMessage());
            return $resultJson->setData([
                'error' => true,
                'message' => __('Unable to save manual invoice. Please try again.')
            ]);
        }
    }
    
    /**
     * Set invoice data
     *
     * @param \HotaiConnected\ManualInvoice\Api\Data\ManualInvoiceInterface $manualInvoice
     * @param array $data
     * @return void
     */
    private function setInvoiceData($manualInvoice, array $data): void
    {
        if (isset($data['invoice_number'])) {
            $manualInvoice->setInvoiceNumber($data['invoice_number']);
        }
        
        if (isset($data['invoice_date'])) {
            $manualInvoice->setInvoiceCreatedAt($data['invoice_date']);
        }
        
        if (isset($data['random_number'])) {
            $manualInvoice->setRandomCode((int) $data['random_number']);
        }
        
        if (isset($data['invoice_type'])) {
            $invoiceType = $data['invoice_type'] === 'company' ? 'c' : 'p';
            $manualInvoice->setInvoiceType($invoiceType);
        }
        
        if (isset($data['invoice_info_type'])) {
            $invoiceStatus = $this->mapInvoiceType($data['invoice_info_type']);
            $manualInvoice->setInvoiceStatus($invoiceStatus);
        }
        
        if (isset($data['transaction_number'])) {
            $manualInvoice->setTransNo($data['transaction_number']);
        }
        
        if (isset($data['company_name'])) {
            $manualInvoice->setCompanyName($data['company_name']);
        }
        
        if (isset($data['tax_id'])) {
            $manualInvoice->setInvoiceCustomerIdentifier($data['tax_id']);
        }
    }
    
    /**
     * Map invoice type to status
     *
     * @param string $type
     * @return int
     */
    private function mapInvoiceType(string $type): int
    {
        switch ($type) {
            case 'issue':
                return 1; // 開立
            case 'void':
                return 2; // 作廢
            case 'allowance':
                return 3; // 折讓
            default:
                return 1; // 預設開立
        }
    }
}