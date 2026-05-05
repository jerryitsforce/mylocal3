<?php

namespace Branch8\PromotionPage\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;

class DefaultConfigProvider
{

    /**
     * @var \Branch8\PromotionPage\Helper\Data
     */
    protected $data;

    protected $checkoutSession;
    public function __construct(
        \Branch8\PromotionPage\Helper\Data $data,
        CheckoutSession $checkoutSession
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->data = $data;
    }

    /**
     * @param \Magento\Checkout\Model\DefaultConfigProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        $result
    ) {
        if ($result) {
            $items = $result['totalsData']['items'];
            foreach ($items as $index => $item) {
                $result['quoteItemData'][$index]['is_vip'] = false;
            }
        }

        return $result;
    }

}
