<?php

namespace Branch8\Catalog\Controller\Ajax;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Helper\ImageFactory;

class Batches extends \Magento\Framework\App\Action\Action
{
    /** 
     * @var \Branch8\HotaiCore\Helper\VirtualProduct
     */
    protected $virtualHelper;
    
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $pageJsonFactory;

    /**
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory
     * @param \Branch8\HotaiCore\Helper\VirtualProduct $virtualHelper
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $pageJsonFactory,
        \Branch8\HotaiCore\Helper\VirtualProduct $virtualHelper
    )
    {
        parent::__construct($context);
        $this->pageJsonFactory = $pageJsonFactory;
        $this->virtualHelper = $virtualHelper;
    }

    public function execute(){
        $productId = $this->getRequest()->getParam('id');
        $resultPage = $this->pageJsonFactory->create();
        if (!$productId) {
            return $resultPage->setData([
                'error' => true,
                'message' => __('Product ID is required.')
            ]);
        }

        try {
            $availableBatches = $this->virtualHelper->getTicketAvailableBatchData($productId);
            return $resultPage->setData([
                'error' => false,
                'batches' => $availableBatches
            ]);
        } catch (\Exception $e){
            return $resultPage->setData([
                'error' => true,
                'message' => __('An error occurred while retrieving data: %1', $e->getMessage())
            ]);
        }
    }
}