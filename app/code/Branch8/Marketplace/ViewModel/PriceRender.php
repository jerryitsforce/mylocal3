<?php
declare(strict_types=1);

namespace Branch8\Marketplace\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 *
 */
class PriceRender implements ArgumentInterface
{
    const DEFAULT_TYPE = 'default';
    private $renderList;
    private \Magento\Framework\View\LayoutInterface $layout;

    /**
     * @param \Magento\Framework\View\LayoutInterface $layout
     */
    public function __construct(
        \Magento\Framework\View\LayoutInterface $layout
    )
    {
        $this->layout = $layout;
    }

    /**
     * @param $renderList
     * @return void
     */
    public function setRenderList($renderList)
    {
        $this->renderList = $renderList;
    }

    /**
     * @param $type
     * @return bool|\Magento\Framework\View\Element\AbstractBlock|\Magento\Framework\View\Element\Template
     */
    public function getItemRenderer($type)
    {
        /** @var \Magento\Framework\View\Element\RendererList $rendererList */
        return $this->renderList->getRenderer($type, self::DEFAULT_TYPE);
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     */
    public function renderItemPrice(\Magento\Sales\Model\Order\Item $item)
    {
        $type = $item->getProductType();
        $block = $this->getItemRenderer($type)->setItem($item);
        return $block->getItemPriceHtml();
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return mixed
     */
    public function renderItemRowTotalHtml(\Magento\Sales\Model\Order\Item $item)
    {
        $type = $item->getProductType();
        $block = $this->getItemRenderer($type)->setItem($item);
        return $block->getItemRowTotalHtml();
    }
}
