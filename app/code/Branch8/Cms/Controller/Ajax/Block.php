<?php
namespace Branch8\Cms\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory as ResultRawFactory;
use Magento\Framework\View\Layout;

class Block extends Action
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
        $blockId = $this->getRequest()->getParam('block_id');

        if (!$blockId) {
            return $this->getResponse()->setBody('Missing block_id');
        }
        $layout = $this->_view->getLayout();
        $block = $layout
            ->createBlock(\Magento\Cms\Block\Block::class)
            ->setBlockId($blockId);

        $html = $block->toHtml();

        return $this->getResponse()->setBody($html);
    }
}
