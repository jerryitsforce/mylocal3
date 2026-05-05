<?php

namespace Branch8\GiftToFriend\Controller\GiftBox;

use Branch8\GiftToFriend\Block\History\Listing;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\PromotionPage\Widget\CategoryList;
use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItems;
use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItem\SubOrderItemImage;
use Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\SubOrderItem\Item;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;

class ViewMore extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var CollectionFactory
     */
    protected $parentOrderCollectionFactory;

    protected $giftList;

    protected $customerSession;

    protected $timezone;

    protected $_pageFactory;

    protected $scopeConfig;

    protected $giftBoxHelper;


    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Listing $giftList
     * @param CollectionFactory $parentOrderCollectionFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Branch8\GiftToFriend\Helper\GiftBox $giftBoxHelper,
        JsonFactory $resultJsonFactory,
        Listing  $giftList
     )
     {
         $this->_pageFactory = $pageFactory;
         parent::__construct($context);
         $this->timezone = $timezone;
         $this->customerSession = $customerSession;
         $this->scopeConfig = $scopeConfig;
         $this->giftBoxHelper = $giftBoxHelper;
         $this->resultJsonFactory = $resultJsonFactory;
         $this->giftList = $giftList;
     }

    /**
     * Execute method to handle AJAX request
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $canLoad = true;
        $guestSession = $this->customerSession->getGuestGiftBoxSession();
        if(!$this->customerSession->isLoggedIn() && !$guestSession){
            $canLoad = false;
        }
        if($guestSession){
            $currentTime = $this->timezone->date()->format('Y-m-d H:i:s');
            $sessionEndTime = $guestSession['created_at'];
            if(strtotime($currentTime) > strtotime($sessionEndTime)){
                $canLoad = false;
            }
        }
    
        if(!$canLoad){
            return $resultJson->setData(['success' => false, 'message' => __('Your session has exppired.')]);
        }
        try {
            // Get parameters from request
            $page = (int)$this->getRequest()->getParam('event-page', 1);
            $pageSize = (int)$this->getRequest()->getParam('pageSize', CategoryList::DEFAULT_PAGE_SIZE);

            if ($page < 1 || $pageSize < 1) {
                throw new \InvalidArgumentException('Invalid pagination parameters.');
            }

            // Set pagination data
            $this->giftList->setData('page_size', $pageSize);
            $this->giftList->setCurrentPage($page);

            // Fetch subcategories
            $parentOrders = $this->giftList->getOrders();
            if (!$parentOrders) {
//                return $resultJson->setData(['success' => false, 'message' => __('An error occurred while loading more parent orders.')]);
                return $resultJson->setData(['success' => false, 'message' => __('Gift Orders not found.')]);
            }

            // Prepare HTML for response
            $html = '';
            // Prepare HTML for response
            $html = '';
            foreach ($parentOrders as $subOrder) {
                $html .= $this->giftList->getLayout()->createBlock(SubOrderItems::class)
                    // ->setChild('sub_order_items_image', $this->giftList->getLayout()->createBlock(SubOrderItemImage::class))
                    ->setChild('sub_order_items', $this->giftList->getLayout()->createBlock(Item::class)->setTemplate('Branch8_GiftToFriend::giftbox/suborder-items.phtml'))
                    ->setTemplate('Branch8_GiftToFriend::giftbox/suborder-item.phtml')
                    ->setOrder($subOrder)
                    ->setHistoryBlock($this->giftList)
                    ->setChillBlock($this->giftList->getLayout()->createBlock(SubOrderItems::class))
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
