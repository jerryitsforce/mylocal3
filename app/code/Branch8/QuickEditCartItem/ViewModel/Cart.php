<?php
declare(strict_types=1);

namespace Branch8\QuickEditCartItem\ViewModel;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Cart form view model.
 */
class Cart implements ArgumentInterface
{
    private UrlInterface $url;

    /**
     * @param UrlInterface $url
     */
    public function __construct(
        UrlInterface $url
    )
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getConfigureUrl($item)
    {
        return $this->url->getUrl(
            'checkout/cart/quickConfigure',
            [
                'id' => $item->getId(),
                'product_id' => $item->getProduct()->getId()
            ]
        );
    }

    /**
     * @param $item
     * @return bool
     */
    public function shouldShowEditLink($item)
    {
        return (bool)$item->getProduct()->isVisibleInSiteVisibility();
    }
}
