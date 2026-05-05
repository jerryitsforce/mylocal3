<?php

namespace Branch8\SplitCart\Model\Services;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\LayoutFactory;
use Magento\Framework\View\LayoutInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Build Cart Item Data optionsHtml,Qty used for ajax call
 */
class BuildCartItemData
{
    private LayoutFactory $layoutFactory;
    private \Branch8\QuickEditCartItem\ViewModel\Cart $quickCart;
    private $configuration;

    private ResultFactory $resultFactory;

    /**
     * @param LayoutFactory $layoutFactory
     * @param \Branch8\QuickEditCartItem\ViewModel\Cart $quickCart
     * @param \Magento\Catalog\Helper\Product\Configuration $configuration
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        LayoutFactory                                 $layoutFactory,
        \Branch8\QuickEditCartItem\ViewModel\Cart     $quickCart,
        \Magento\Catalog\Helper\Product\Configuration $configuration,
        ResultFactory                                 $resultFactory
    )
    {
        $this->resultFactory = $resultFactory;
        $this->configuration = $configuration;
        $this->quickCart = $quickCart;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * @param array $items
     * @return array
     */
    public function build(array $items)
    {
        /**
         * @var $layout LayoutInterface
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $layout = $resultPage->getLayout();
        $layout->getUpdate()->addHandle(['checkout_cart_index']);
        /**
         * @var $renderer \Magento\Framework\View\Element\RendererList
         */
        $renderer = $layout->getBlock('checkout.cart.item.renderers');
        //  $optionHtml = $layout->createBlock();
        $return = [];
        /**
         * @var Item $item
         */
        foreach ($items as $item) {
            $return[] = [
                'id' => $item->getId(),
                'html' => $renderer->getRenderer($item->getProductType())->setItem($item)->toHtml(),
                'qty' => $item->getQty(),
                /*'optionHtml' => $optionHtml,
                'subTotal' => '10000$'*/
            ];
        }
        return $return;
    }

    /**
     * @return void
     */
    private function getOptionHtml()
    {

    }
}
