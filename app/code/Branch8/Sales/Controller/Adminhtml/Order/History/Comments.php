<?php
namespace Branch8\Sales\Controller\Adminhtml\Order\History;

use Magento\Framework\Controller\Result\JsonFactory;

class Comments extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::sales_order';
   
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
        $resultJson = $this->jsonFactory->create();
        $layout = $resultPage->getLayout();
        try{
            $content = $layout->getBlock('order.comments.list');
            $html = $content->toHtml();

            $data = ['success' => true, 'html' => $html];
            return $resultJson->setData($data);
        }catch(\Exception $e){
            $data = ['success' => false];
            return $resultJson->setData($data);
        }
        $data = ['success' => false];
        return $resultJson->setData($data);
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
