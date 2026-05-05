<?php
namespace Branch8\Catalog\Controller\Ajax;

use Magento\CatalogWidget\Block\Product\ProductsList;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Magento\Framework\View\Layout;

class Widget extends Action
{
    /**
     * @var ResultRawFactory
     */
    private ResultRawFactory $_resultRawFactory;

    /**
     * Widget constructor.
     * @param Context $context
     * @param ResultRawFactory $resultRawFactory
     */
    public function __construct(
        Context $context,
        ResultRawFactory $resultRawFactory
    ){
        parent::__construct($context);
        $this->_resultRawFactory = $resultRawFactory;
    }

    /**
     * @return Raw
     */
    public function execute()
    {
        $result = $this->_resultRawFactory->create();
        $result->setHeader('Content-Type', 'text/html');
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        if ($this->getRequest()->isAjax()) {
            /** @var Layout $layout */
            $layout = $this->_view->getLayout();
            /** @var ProductsList $block */
            $block = $layout->createBlock(ProductsList::class);
            foreach ($this->getRequest()->getParams() as $key => $value) {
                if ($key === 'template') {
                    $block->setTemplate($value);
                }
                $block->setData($key, $value);
            }
            $result->setContents($block->toHtml());
        }

        return $result;
    }
}
