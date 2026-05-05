<?php

namespace Branch8\HotaiShipping\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;

class CartTotalRepository
{

    /**
     * @var \Magento\Quote\Api\Data\TotalsExtensionFactory
     */
    private $extensionFactory;

    protected $quoteRepository;

    public function __construct(
        \Magento\Quote\Api\Data\TotalsExtensionFactory $extensionFactory,
        CartRepositoryInterface $quoteRepository,
    )
    {
        $this->extensionFactory = $extensionFactory;
        $this->quoteRepository = $quoteRepository;
    }
    /**
     * @param \Magento\Quote\Model\Cart\CartTotalRepository $subject
     * @param \Magento\Quote\Api\Data\TotalsInterface $result
     * @return void
     */
    public function afterGet(
        \Magento\Quote\Model\Cart\CartTotalRepository $subject,
        \Magento\Quote\Api\Data\TotalsInterface $result,
        $cartId
    ) {
        if ($result->getExtensionAttributes() === null) {
            $extensionAttributes = $this->extensionFactory->create();
            $result->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes = $result->getExtensionAttributes();
        $quote = $this->quoteRepository->getActive($cartId);
        $shippingAddress = $quote->getShippingAddress();
        $extensionAttributes->setDetailShippingFee($shippingAddress->getDetailShippingFee());
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }
}