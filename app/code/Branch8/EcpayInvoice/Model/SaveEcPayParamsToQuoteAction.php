<?php
declare(strict_types=1);


namespace Branch8\EcpayInvoice\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;
use Branch8\EcpayInvoice\Helper\Logger as LoggerInterface;

class SaveEcPayParamsToQuoteAction
{
    private QuoteRepository $quoteRepository;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string[]
     */
    private $attributes = [
        'ecpay_invoice_carruer_type',
        'ecpay_invoice_type',
        'ecpay_invoice_carruer_num',
        'ecpay_invoice_love_code',
        'ecpay_invoice_customer_company',
        'ecpay_invoice_customer_identifier'
    ];

    /**
     * @param QuoteRepository $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        QuoteRepository $quoteRepository,
        LoggerInterface $logger

    )
    {
        $this->logger = $logger;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param $cartId
     * @param $params
     * @return void
     * @throws NoSuchEntityException
     */
    public function saveEcPayInvoiceParams($cartId, $params = [])
    {
        try {
            $quote = $this->quoteRepository->getActive($cartId);
            if (!$quote->getItemsCount()) {
                throw new NoSuchEntityException(__('Cart %1 doesn\'t contain products', $cartId));
            }
            foreach ($this->attributes as $attribute) {
                if ($quote->hasData($attribute) && isset($params[$attribute])) {
                    $quote->setData($attribute, $params[$attribute]);
                }
            }
            if ($quote->getData('ecpay_invoice_type') == '公司' || $quote->getData('ecpay_invoice_type') == '捐贈') {
                $quote->setData('ecpay_invoice_carruer_type', '');
            }
            //$this->quoteRepository->save($quote);
        } catch (\Exception $exception) {
            $this->logger->critical('Error when saving saveEcPayInvoiceParams:' . $exception->getMessage());
            $this->logger->info($exception->getTraceAsString());
        }
    }
}
