<?php

namespace Branch8\Quote\Plugin\Model;

use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Framework\App\RequestInterface;

class Quote
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var bool $ifSubtotalCollected;
     */
    private $isSubtotalCollected = false;

    protected $discountStorage;

    /**
     * @param RequestInterface $request
     */
    public function __construct(
        RequestInterface $request,
        \Amasty\Rules\Model\Rule\DiscountStorage $discountStorage
    ) {
        $this->request = $request;
        $this->discountStorage = $discountStorage;
    }

    public function afterHasItems(
        \Magento\Quote\Model\Quote $subject,
                                   $result
    ) {
        $quoteItems = $subject->getItemsCollection();
        $disabledProduct = [];
        $cnt = 0;
        foreach ($quoteItems as $_item) {
            $cnt ++;
            $productItem = $_item->getProduct();
            if($productItem->getStatus() == ProductStatus::STATUS_DISABLED){
                $disabledProduct[] = $productItem->getSku();
            }
        }

        return !(count($disabledProduct) == $cnt);
    }

    /**
     * Keep disable product in cart items
     * @param $subject
     * @param $proceed
     * @return array
     */
    public function aroundGetAllItems($subject, $proceed){
        $items = [];
        /** @var \Magento\Quote\Model\Quote\Item $item */
        foreach ($subject->getItemsCollection() as $item) {
            $product = $item->getProduct();
            if (!$item->isDeleted() && ($product /*&& (int)$product->getStatus() !== ProductStatus::STATUS_DISABLED*/)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Around collect totals that decides whenever total should be calculated or no.
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Closure $proceed
     * @return \Magento\Quote\Model\Quote|mixed
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\RuntimeException
     */
    public function aroundCollectTotals(
        \Magento\Quote\Model\Quote $quote,
        \Closure $proceed
    ) {
        if ($this->isSubtotalCollected) {
            return $quote;
        }
        $urlPath = $this->request->getPathInfo();
        if (str_contains($urlPath, 'checkout/cart')) $this->isSubtotalCollected = true;
        $result = $proceed();
        $this->discountStorage->clearStorage();

        return $result;
    }

    /**
     * Check if is cart page.
     *=
     * @return bool
     */
    private function isCartPage (): bool
    {
        return $this->request->getFullActionName() != 'checkout_cart_add' && $this->request->getFullActionName() != '__';
    }
}
