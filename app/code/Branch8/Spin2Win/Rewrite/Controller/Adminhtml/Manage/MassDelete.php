<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\SpinToWin\Model\ResourceModel\Info\CollectionFactory;

class MassDelete extends \Webkul\SpinToWin\Controller\Adminhtml\Manage\MassDelete
{

    protected $transaction;

    protected $_urlRewriteFactory;

    protected $draftCollectionFactory;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Branch8\Spin2Win\Model\ResourceModel\SpinDraft\CollectionFactory $draftCollectionFactory
    ) {
        parent::__construct($context, $filter, $collectionFactory, $helper, $logger);
        $this->transaction = $transaction;
        $this->_urlRewriteFactory = $urlRewriteFactory;
        $this->draftCollectionFactory = $draftCollectionFactory;
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
            $temp = '';
            foreach ($collection as $spins) {
                $this->transaction->addObject($spins);

                $targetPath = "spintowin/campaign/view/id/".$spins->getId();
                $urlRewriteModel = $this->_urlRewriteFactory->create()->getCollection()
                        ->addFieldToFilter('target_path', $targetPath)
                        ->getFirstItem();
                if($urlRewriteModel->getId()){
                    $this->transaction->addObject($urlRewriteModel);
                }

                /** Delete draft */
                $draftCol = $this->draftCollectionFactory->create()
                    ->addFieldToFilter('spin_id', $spins->getId());
                foreach($draftCol as $_draft){
                    $this->transaction->addObject($_draft);
                }
                $count++;
                $temp .= $spins->getName() . ', ';
            }
            $this->transaction->delete();
            $this->messageManager->addSuccess(__('A total of %1 campaign have been deleted.', $count));
        } catch (\Exception $e) {
            $this->logger->info(
                "MassDelete::excute ".$e->getMessage()
            );
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/manage/index');
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
     * Delete
     *
     * @param Object $object
     * @return void
     */
    public function deleteObject($object)
    {
        $object->delete();
    }
}
