<?php

namespace Branch8\CatalogCustom\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class CategoryProducts extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PageFactory $resultPageFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $categoryId = $this->getRequest()->getParam('category_id');

        if (!$categoryId) {
            return $resultJson->setData(['error' => true, 'message' => 'Category ID is missing']);
        }

        try {
            $resultPage = $this->resultPageFactory->create();
            $block = $resultPage->getLayout()
                ->createBlock(\Branch8\CatalogCustom\Block\Widget\CategoryList::class)
                ->setTemplate('Branch8_CatalogCustom::widget/category_products_content.phtml')
                ->setData($this->getRequest()->getParams());

            // Explicitly set category_id in case it was renamed or lost
            $block->setData('category_id', $categoryId);

            $html = $block->toHtml();

            return $resultJson->setData(['html' => $html]);
        } catch (\Exception $e) {
            return $resultJson->setData(['error' => true, 'message' => $e->getMessage()]);
        }
    }
}
