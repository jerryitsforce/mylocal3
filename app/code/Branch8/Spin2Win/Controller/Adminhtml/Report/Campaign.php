<?php
namespace Branch8\Spin2Win\Controller\Adminhtml\Report;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Campaign extends \Magento\Backend\App\Action
{
    /** @var PageFactory */
    private $pageFactory;

    protected $_conn;

    /**
     * @param Context $context
     * @param PageFactory $rawFactory
     */
    public function __construct(
        Context $context,
        PageFactory $rawFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->pageFactory = $rawFactory;

        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Magento_Backend::marketing');
        $resultPage->getConfig()->getTitle()->prepend(__('Event Report'));

        /**
         * Index data
         */
        $this->indexData();

        return $resultPage;
    }

    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_Spin2Win::reports');
    }
    
    protected function indexData(){
        $sql = 'update spintowin_info set total_draws=(select count(*) from spintowin_reports where spintowin_reports.spin_id = spintowin_info.entity_id),
                num_of_participiants=(select count(distinct customer_id) from spintowin_reports where spintowin_reports.spin_id = spintowin_info.entity_id),
                points_redeemed=(select sum(point) from spintowin_redemption where spintowin_redemption.spin_id = spintowin_info.entity_id)';
        $this->_conn->query($sql);
    }
}