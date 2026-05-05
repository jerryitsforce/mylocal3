<?php
namespace Branch8\PromotionPage\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\PromotionPage\Widget\CategoryList;

class LoadMore extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CategoryList
     */
    protected $categoryList;

    /**
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CategoryList $categoryList
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        CategoryList $categoryList
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->categoryList = $categoryList;
    }

    /**
     * Execute method to handle AJAX request
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            // Get parameters from request
            $page = (int) $this->getRequest()->getParam('event-page', 1);
            $pageSize = (int) $this->getRequest()->getParam('pageSize', CategoryList::DEFAULT_PAGE_SIZE);

            if ($page < 1 || $pageSize < 1) {
                throw new \InvalidArgumentException('Invalid pagination parameters.');
            }

            // Set pagination data
            $this->categoryList->setData('page_size', $pageSize);
            $this->categoryList->setCurrentPage($page);

            // Fetch subcategories
            $categories = $this->categoryList->getSubcategories();
            if (!$categories) {
                return $resultJson->setData(['success' => false, 'message' => __('An error occurred while loading more categories.')]);
            }

            // Prepare HTML for response
            $html = '';
            foreach ($categories as $category) {
                $html .= $this->categoryList->getLayout()->createBlock(CategoryList::class)
                    ->setTemplate('Branch8_PromotionPage::widget/category_item.phtml')
                    ->setCategory($category)
                    ->toHtml();
            }

            // Determine if there are more pages
            $hasMore = ($categories->getSize() > ($page * $pageSize));

            // Return success response
            return $resultJson->setData(['success' => true, 'html' => $html, 'has_more' => $hasMore]);

        } catch (\InvalidArgumentException $e) {
            // Handle invalid arguments
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => __('An error occurred while loading more categories.')]);
        }
    }
}
