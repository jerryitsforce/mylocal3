<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Segment;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\SpinToWin\Model\ResourceModel\Segments\CollectionFactory;

class MassDelete extends \Webkul\SpinToWin\Controller\Adminhtml\Segment\MassDelete
{
    protected $spinDraftHelper;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    ) {
        parent::__construct($context, $filter, $collectionFactory, $ruleFactory, $helper, $logger);
        $this->spinDraftHelper = $spinDraftHelper;
    }

    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();

        try {
            $spinId = $params['id'];
            $segmentEntityIds = $params['segmentEntityIds'];
            $count = count($segmentEntityIds);
            $this->spinDraftHelper->addDeleteSegments($spinId, $segmentEntityIds);
            
            $this->messageManager->addSuccess(__('A total of %1 segment(s) have been deleted.', $count));
        } catch (\Exception $e) {
            $this->logger->info(
                "MassDelete::excute ".$e->getMessage()
            );
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/manage/edit', ['id'=>$spinId]);
    }

    /**
     * Check permission
     *
     * @return boolean
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_SpinToWin::manage');
    }

    /**
     * Delete Object
     *
     * @param Object $object
     * @return void
     */
    public function deleteObject($object)
    {
        $object->delete();
    }
}
