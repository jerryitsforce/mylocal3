<?php
namespace Branch8\Sales\Block\Adminhtml\Order\View\History;

use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Sales\Api\OrderRepositoryInterface;

class CommentList extends \Magento\Backend\Block\Template
{

    const ITEM_PER_PAGE = 10;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;


    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Model\ResourceModel\Order\Status\History\CollectionFactory $historyCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
        $this->_coreRegistry = $registry;
        $this->_historyCollectionFactory = $historyCollectionFactory;
        $this->orderRepository = $orderRepository;
    }
    public function getOrder()
    {
        $order = $this->_coreRegistry->registry('sales_order');
        if($order){
            return $order;
        }
        $orderId = $this->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);

        return $order;
    }

    /**
     * Customer Notification Applicable check method
     *
     * @param  \Magento\Sales\Model\Order\Status\History $history
     * @return bool
     */
    public function isCustomerNotificationNotApplicable(\Magento\Sales\Model\Order\Status\History $history)
    {
        return $history->isCustomerNotificationNotApplicable();
    }

    public function getStatusHistoryCollection($page = 1){
        $order = $this->getOrder();
        $collection = $this->_historyCollectionFactory->create()->setOrderFilter($order)
            ->setOrder('created_at', 'desc')
            ->setOrder('entity_id', 'desc');
        $select = $collection->getSelect();
        $select->limitPage($page, self::ITEM_PER_PAGE);
        if ($order->getId()) {
            foreach ($collection as $status) {
                $status->setOrder($order);
            }
        }
        return $collection;
    }

    public function getCurrentPage(){
        $page = $this->getRequest()->getParam('cmt_page', 1);
        return $page;
    }

    public function getCollectionNoLimit($collection){
        $collection->reset(\Zend_Db_Select::LIMIT_COUNT)
            ->reset(\Zend_Db_Select::LIMIT_OFFSET);
        return $collection;
    }

    public function getPageList($itemCollection, $curPage = 1){
        $conn = $itemCollection->getConnection();
        $countSelect = $itemCollection->getSelectCountSql();
        $totalItems = $conn->fetchOne($countSelect);

        $totalPages = ceil($totalItems/self::ITEM_PER_PAGE);
        $pages = [];
        if($curPage - 2 > 0){
            $pages[] = $this->getPageLink(1, __('First'));
        }
        if($curPage - 2 > 1){
            $pages[] = $this->getPageNoLink();
        }
        $beforeCurrent = max(1, $curPage - 2);
        for($i = $beforeCurrent; $i < $curPage; $i++){
            $pages[] = $this->getPageLink($i);
        }
        $pages[] = $this->getCurrentLink($curPage);
        $nextPages = min($curPage + 2, $totalPages);
        for($i = $curPage + 1; $i <= $nextPages; $i ++){
            $pages[] = $this->getPageLink($i);
        }
        if($totalPages - $nextPages > 1){
            $pages[] = $this->getPageNoLink();
        }
        if($totalPages - $nextPages > 0){
            $pages[] = $this->getPageLink($totalPages, __('Last'));
        }

        return $pages;
    }

    protected function getPageLink($page, $text = ''){
        $text = $text == '' ? $page : $text;
        $url = '<a href="javascript:;" class="comment_pager_link" data-page="'.$page.'">'.$text.'</a>';
        return $url;
    }

    protected function getPageNoLink(){
        $url = '<span>......</span>';
        return $url;
    }

    protected function getCurrentLink($page){
        $url = '<span class="cur_page">'.$page.'</span>';
        return $url;
    }
}
