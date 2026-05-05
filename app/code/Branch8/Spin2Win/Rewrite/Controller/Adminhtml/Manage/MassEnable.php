<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\SpinToWin\Model\ResourceModel\Info\CollectionFactory;

class MassEnable extends \Webkul\SpinToWin\Controller\Adminhtml\Manage\MassEnable
{
    /**
     * @var Filter
     */
    public $filter;

    /**
     * @var CollectionFactory
     */
    public $collectionFactory;

    /**
     * @var \Webkul\SpinToWin\Logger\Logger
     */
    public $logger;

    /**
     * @var \Webkul\SpinToWin\Helper\Data
     */
    public $helper;

    protected $draftCollectionFactory;

    protected $transaction;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param \Webkul\SpinToWin\Helper\Data $helper
     * @param \Webkul\SpinToWin\Logger\Logger $logger
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Branch8\Spin2Win\Model\ResourceModel\SpinDraft\CollectionFactory $draftCollectionFactory,
        \Magento\Framework\DB\Transaction $transaction
    ) {
        parent::__construct($context, $filter, $collectionFactory, $helper, $logger);
        $this->draftCollectionFactory = $draftCollectionFactory;
        $this->transaction = $transaction;
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
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $spins) {
                $status = 1;
                $spins->setStatus($status);
                $this->transaction->addObject($spins);

                /** Update draft */
                $draftCol = $this->draftCollectionFactory->create()
                    ->addFieldToFilter('spin_id', $spins->getId());
                foreach($draftCol as $_draft){
                    $_draftData = $_draft->getData('data');
                    $_draftArr = json_decode($_draftData, true);
                    
                    if(isset($_draftArr['information'])){
                        $_draftInfor = $_draftArr['information'];
                        $_draftInfor['status'] = $status;
                        $_draftArr['information'] = $_draftInfor;
                        $_draftData = json_encode($_draftArr);
                        $_draft->setData('data', $_draftData);
                        $this->transaction->addObject($_draft);
                    }
                    
                }
                $count++;
            }
            $this->transaction->save();
            $this->messageManager->addSuccess(__('A total of %1 spin(s) have been enabled.', $count));
        } catch (\Exception $e) {
            $this->logger->info(
                "MassDisable::excute ".$e->getMessage()
            );
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/manage/index');
    }

    /**
     * Check Permission
     *
     * @return boolean
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_SpinToWin::manage');
    }
}
