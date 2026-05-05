<?php

namespace Branch8\HotaiPay\Block\Payment;

use Branch8\HotaiPay\Model\MessageFactory;
use Branch8\HotaiPay\Model\MessageRepository;
use Branch8\HotaiPay\Service\HotaiPay;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Exception;
use Magento\Framework\Session\SessionManager;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\View\Element\Template\Context;

class Fail extends \Magento\Framework\View\Element\Template
{

    /** @var \Branch8\HotaiPay\Service\HotaiPay $hotaiPayService */
    public $hotaiPayService;

    /** @var \Magento\Checkout\Model\Session $checkoutSession */
    private $checkoutSession;

    /** @var \Magento\Framework\Session\SessionManager $coreSession */
    private $coreSession;

    /** @var \Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface $parentOrderrRepository */
    private $parentOrderrRepository;

    /** @var mixed $messageFactory */
    protected $messageFactory;
    private HotaiPayLogHelper $hotaiPayLogHelper;

    /**
     * __construct
     *
     * @param HotaiPay $hotaiPayService
     * @param Context $context
     * @param Session $checkoutSession
     * @param SessionManager $coreSession
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param MessageFactory $messageFactory
     * @param HotaiPayLogHelper $hotaiPayLogHelper
     * @param array $data
     * @return void
     */
    public function __construct(
        HotaiPay $hotaiPayService,
        Context $context,
        Session $checkoutSession,
        SessionManager $coreSession,
        ParentOrderRepositoryInterface $parentOrderRepository,
        MessageFactory $messageFactory,
        HotaiPayLogHelper $hotaiPayLogHelper,
        array $data = []
    ) {
        $this->hotaiPayService = $hotaiPayService;
        $this->checkoutSession = $checkoutSession;
        $this->coreSession = $coreSession;
        $this->parentOrderrRepository = $parentOrderRepository;
        $this->messageFactory = $messageFactory;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
        parent::__construct($context, $data);
    }

    /**
     * getCheckoutErrorMsg
     *
     * @return void
     */
    public function getCheckoutErrorMsg()
    {
        $errorMsgFromSession = $this->checkoutSession->getData('errorMsg');
        $errorMsg = $this->findCheckoutMessage($errorMsgFromSession);

        return $errorMsg ?? ['error' => 'Payment Failed.'];
    }

    /**
     * unsetCheckoutErrorMsg
     *
     * @return void
     */
    public function unsetCheckoutErrorMsg()
    {
        return $this->checkoutSession->unsetData('errorMsg');
    }

    public function getParentOrder()
    {
        return $this->checkoutSession->getData('parentOrderId');
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|null
     */
    public function getOrder()
    {
        try {
            $id = (int) $this->checkoutSession->getData('parentOrderId');
            return $this->parentOrderrRepository->get($id);
        } catch (\Exception $exception) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $exception->getMessage(), __CLASS__);
            return null;
        }
    }

    /**
     * @return string
     */
    public function getOrderDetailUrl()
    {
        return $this->getUrl('sales/parentOrder/history', ['_query' => ['search' => $this->getOrder()->getDetail()->getData('increment_id')]]);
    }

    /**
     * finCheckoutMessage
     *
     * @param  array $errorMsg
     * @return array
     */
    public function findCheckoutMessage($errorMsg)
    {

        if (!isset($errorMsg['error'])) {
            return $errorMsg;
        }

        try {
            $oriErrorMsg = $errorMsg;

            if (isset($errorMsg['statusCode'])) {
                $record = $this->messageFactory->create()
                    ->addFieldToFilter(
                        'type',
                        MessageRepository::TYPE_CHECKOUT
                    )->addFieldToFilter(
                    'status',
                    $errorMsg['statusCode']
                );

                if ($record->getSize() > 0) {
                    $record = $record->getFirstItem();
                    $errorMsg['error'] = $record->getFrontDisplayDes();
                }

                return $errorMsg;
            }

            $record = $this->messageFactory->create()
                ->addFieldToFilter(
                    'type',
                    MessageRepository::TYPE_PAYMENT
                )->addFieldToFilter(
                'error_code',
                $errorMsg['error']
            );

            if ($record->getSize() > 0) {
                $record = $record->getFirstItem();
                $errorMsg['error'] = $record->getFrontDisplayDes();
            }

            return $errorMsg;

        } catch (Exception $exception) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $exception->getMessage(), __CLASS__);
            return $oriErrorMsg;
        }

    }

    public function isQuickCheckout(){
        return $this->checkoutSession->getData('quickCheckout');
    }
}
