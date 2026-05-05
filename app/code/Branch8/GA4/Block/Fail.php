<?php

namespace Branch8\GA4\Block;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\ProductHelper;
use Branch8\GA4\Model\Storage;
use Branch8\HotaiPay\Model\MessageFactory;
use Branch8\HotaiPay\Model\MessageRepository;
use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class Fail extends \Branch8\GA4\Block\Core
{
    protected $_checkoutSession;

    /**
     * @var Registry
     */
    protected $registry;

    /** @var mixed $messageFactory */
    protected $messageFactory;

    public function __construct(
        Template\Context $context,
        Config $config,
        Storage $storage,
        Registry $registry,
        Session $checkoutSession,
        ProductHelper $productHelper,
        MessageFactory $messageFactory,
        array $data = []
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->registry = $registry;
        $this->messageFactory = $messageFactory;
        parent::__construct($context, $config, $storage, $productHelper, $data);
    }

    public function getRealOrderId()
    {
        return $this->_checkoutSession->getLastRealOrderId();
    }

    public function getQuoteGrandTotal()
    {
        $quote = $this->getQuote();
        return $quote->getGrandTotal();
    }

    public function getCouponCode()
    {
        $quote = $this->getQuote();
        return $quote->getCouponCode() ?? '';
    }

    public function getIncrementId()
    {
        return $this->getRealOrderId() ?? '';
    }


    /**
     * @return array|mixed
     */
    public function getCheckoutErrorMsg()
    {
        $errorMsg = $this->findCheckoutMessage($this->_checkoutSession->getData('errorMsg'));

        return $errorMsg ?? $this->_checkoutSession->getData('errorMsg');
    }

    /**
     * unsetCheckoutErrorMsg
     *
     * @return void
     */
    public function unsetCheckoutErrorMsg()
    {
        return $this->_checkoutSession->unsetData('errorMsg');
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

        } catch (\Exception $exception) {
            return $errorMsg;
        }

    }
}
