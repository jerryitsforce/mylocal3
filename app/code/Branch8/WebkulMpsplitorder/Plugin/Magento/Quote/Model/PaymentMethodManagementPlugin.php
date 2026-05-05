<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       07/03/2026
 */

namespace Branch8\WebkulMpsplitorder\Plugin\Magento\Quote\Model;

use Magento\Checkout\Model\PaymentInformationManagement;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\SubmitQuoteValidator;
use Psr\Log\LoggerInterface;

class PaymentMethodManagementPlugin
{
    /**
     * @var SubmitQuoteValidator
     */
    private SubmitQuoteValidator $submitQuoteValidator;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private \Magento\Quote\Api\CartRepositoryInterface $quoteRepository;

    private LoggerInterface $logger;

    /**
     * @param SubmitQuoteValidator $submitQuoteValidator
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        SubmitQuoteValidator                       $submitQuoteValidator,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        LoggerInterface                            $logger
    )
    {
        $this->logger = $logger;
        $this->submitQuoteValidator = $submitQuoteValidator;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param PaymentInformationManagement $subject
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagement             $subject,
                                                 $cartId,
        PaymentInterface                         $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    )
    {
        /**
         * @var $quote Quote
         */
        $quote = $this->quoteRepository->getActive($cartId);
        $availableItems = count($quote->getAllVisibleItems());
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if (!$quoteItem->getAvailableToCheckout()) {
                $availableItems--;
            }
        }
        if ($availableItems <= 0) {
            throw new ValidatorException(__('Something went wrong. Please try to place the order again.'));
        }
        return [$cartId, $paymentMethod, $billingAddress];
    }
}
