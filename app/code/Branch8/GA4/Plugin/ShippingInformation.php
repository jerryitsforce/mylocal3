<?php

namespace Branch8\GA4\Plugin;

use Branch8\GA4\Model\Config;

class ShippingInformation
{
    /**
     * @var \Branch8\GA4\Model\Datalayer
     */
    private $datalayer;

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    private $config;

    /**
     * @param \Branch8\GA4\Model\Datalayer $datalayer
     * @param Config $config
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Branch8\GA4\Model\Datalayer               $datalayer,
        Config                                     $config,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Checkout\Model\Session            $checkoutSession)
    {
        $this->datalayer = $datalayer;
        $this->config = $config;
        $this->quoteRepository = $quoteRepository;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Checkout\Model\ShippingInformationManagement $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param $addressInformation
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function aroundSaveAddressInformation(
        \Magento\Checkout\Model\ShippingInformationManagement $subject,
        \Closure                                              $proceed,
                                                              $cartId,
                                                              $addressInformation
    )
    {
        $result = $proceed($cartId, $addressInformation);

        if (!$this->config->isEnabled()) {
            return $result;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->quoteRepository->getActive($cartId);
        $shippingDescription = $quote->getShippingAddress()->getShippingDescription();

        $this->_checkoutSession->setGA4CheckoutOptionsData(
            $this->datalayer->addCheckoutStepPushData('1', $shippingDescription)
        );

        return $result;
    }


}
