<?php
namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Magento\Framework\Controller\Result\JsonFactory;

class Comments extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::parent_orders';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $jsonFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       JsonFactory $jsonFactory
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
        $layout = $resultPage->getLayout();
        try{
            $content = $layout->getBlock('order.comments.list');
            $html = $content->toHtml();

            echo $html;
            exit(0);
        }catch(\Exception $e){
            echo '';
            exit(0);
        }
        echo '';
        exit(0);
    }

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
