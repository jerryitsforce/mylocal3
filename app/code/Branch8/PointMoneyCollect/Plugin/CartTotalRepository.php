<?php

namespace Branch8\PointMoneyCollect\Plugin;

use Magento\Quote\Api\CartRepositoryInterface;

class CartTotalRepository
{
    /**
     * @var \Magento\Quote\Api\Data\TotalsExtensionFactory
     */
    private $extensionFactory;
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var \Branch8\PointMoneyCollect\Helper\Data
     */
    protected $pointHelper;

    /**
     * @param \Magento\Quote\Api\Data\TotalsExtensionFactory $extensionFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param \Branch8\PointMoneyCollect\Helper\Data $pointHelper
     */
    public function __construct(
        \Magento\Quote\Api\Data\TotalsExtensionFactory $extensionFactory,
        CartRepositoryInterface $quoteRepository,
        \Branch8\PointMoneyCollect\Helper\Data $pointHelper
    )
    {
        $this->extensionFactory = $extensionFactory;
        $this->quoteRepository = $quoteRepository;
        $this->pointHelper = $pointHelper;
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

        $pointLimit = $this->pointHelper->getPointLimit();
        $minPoint = (int)$pointLimit['min_point'];
        $maxpoint = (int)$pointLimit['max_point'];

        $extensionAttributes->setPointRange([$minPoint, $maxpoint]);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }
}