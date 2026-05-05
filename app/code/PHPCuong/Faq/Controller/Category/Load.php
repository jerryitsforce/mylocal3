<?php

namespace PHPCuong\Faq\Controller\Category;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPCuong\Faq\Block\Faq\Account\Faq as FaqBlock;
class Load extends Action
{
    protected $jsonFactory;
    protected $faqBlock;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        FaqBlock $faqBlock
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->faqBlock = $faqBlock;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $categoryId = $this->getRequest()->getParam('category_id');
        $page = $this->getRequest()->getParam('page', 1);
        $pageSize = 5;
        // Get the FAQ items for the current page
        $faqItems = $this->faqBlock->getFrequentlyAskedQuestionsByCategory($categoryId, $page, $pageSize);
        $totalItems = $this->faqBlock->getTotalFaqsByCategory($categoryId); // Total number of FAQs
        $totalPages = @ceil($totalItems / $pageSize); // Calculate total pages
        $html = $this->_view->getLayout()
            ->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setTemplate('PHPCuong_Faq::faq/list.phtml')
            ->setData('faq_items', $faqItems)
            ->setData('category_id', $categoryId)
            ->toHtml();
        // Pagination HTML for desktop
        $pagerHtml = '';
        if ($totalPages > 1) {
            $pagerHtml = $this->_view->getLayout()
                ->createBlock(\Magento\Framework\View\Element\Template::class)
                ->setTemplate('PHPCuong_Faq::faq/pager.phtml')
                ->setData('current_page', $page)
                ->setData('total_pages', $totalPages)
                ->toHtml();
        }
        // Return both HTML and pager HTML, also indicate if more content is available
        $resultJson = $this->jsonFactory->create();
        return $resultJson->setData([
            'success' => true,
            'html' => $html,
            'pagination' => $pagerHtml, // Pagination for desktop
            'has_more' => $page < $totalPages // Flag for "Load More" button on mobile
        ]);
    }

}
