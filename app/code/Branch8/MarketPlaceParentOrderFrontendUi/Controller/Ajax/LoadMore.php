<?php

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\Ajax;

use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\History;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\PromotionPage\Widget\CategoryList;
use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItems;
use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItem\SubOrderItemImage;
use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItem\Item;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class LoadMore extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CollectionFactory
     */
    protected $parentOrderCollectionFactory;

    protected $historyList;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param History $historyList
     * @param CollectionFactory $parentOrderCollectionFactory
     */
    public function __construct(
        Context                                                    $context,
        JsonFactory                                                $resultJsonFactory,
        History                                                    $historyList,
        CollectionFactory $parentOrderCollectionFactory
    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->historyList = $historyList;
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
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
            $page = (int)$this->getRequest()->getParam('event-page', 1);
            $pageSize = (int)$this->getRequest()->getParam('pageSize', CategoryList::DEFAULT_PAGE_SIZE);

            if ($page < 1 || $pageSize < 1) {
                throw new \InvalidArgumentException('Invalid pagination parameters.');
            }

            // Set pagination data
            $this->historyList->setData('page_size', $pageSize);
            $this->historyList->setCurrentPage($page);

            // Fetch subcategories
            $parentOrders = $this->historyList->getOrders();
            if (!$parentOrders) {
//                return $resultJson->setData(['success' => false, 'message' => __('An error occurred while loading more parent orders.')]);
                return $resultJson->setData(['success' => false, 'message' => __('Parent Orders not found.')]);
            }

            // Prepare HTML for response
            $html = '';
            // Prepare HTML for response
            $html = '';
            foreach ($parentOrders as $subOrder) {
                $html .= $this->historyList->getLayout()->createBlock(SubOrderItems::class)
                    ->setChild('sub_order_items_image', $this->historyList->getLayout()->createBlock(SubOrderItemImage::class))
                    ->setChild('sub_order_items', $this->historyList->getLayout()->createBlock(Item::class))
                    ->setTemplate('Branch8_MarketPlaceParentOrderFrontendUi::parent_order/suborder/suborder-item.phtml')
                    ->setOrder($subOrder)
                    ->setHistoryBlock($this->historyList)
                    ->setChillBlock($this->historyList->getLayout()->createBlock(SubOrderItems::class))
                    ->toHtml();
            }

            // Determine if there are more pages
            $hasMore = ($parentOrders->getSize() > ($page * $pageSize));

            // Return success response
            return $resultJson->setData(['success' => true, 'html' => $html, 'has_more' => $hasMore]);
        } catch (\InvalidArgumentException $e) {
            // Handle invalid arguments
            return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
//            return $resultJson->setData(['success' => false, 'message' => __('An error occurred while loading more categories.')]);
            return $resultJson->setData(['success' => false, 'message' => __($e->getMessage())]);
        }
    }
}
