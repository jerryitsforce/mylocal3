<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends \Webkul\SpinToWin\Controller\Adminhtml\Manage\Delete
{

    protected $transaction;

    protected $_urlRewriteFactory;

    protected $draftCollectionFactory;

    public function __construct(
        Context $context,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Magento\Framework\DB\Transaction $transaction,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Branch8\Spin2Win\Model\ResourceModel\SpinDraft\CollectionFactory $draftCollectionFactory
    ) {
        parent::__construct($context, $infoFactory, $logger);
        $this->transaction = $transaction;
        $this->_urlRewriteFactory = $urlRewriteFactory;
        $this->draftCollectionFactory = $draftCollectionFactory;
    }

    /**
     * Execute
     *
     * @return void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {
                $infoModel = $this->infoFactory->create();
                $infoModel->load($id);
                if ($infoModel) {
                    $this->transaction->addObject($infoModel);
                    $targetPath = "spintowin/campaign/view/id/".$infoModel->getId();
                    $urlRewriteModel = $this->_urlRewriteFactory->create()->getCollection()
                        ->addFieldToFilter('target_path', $targetPath)
                        ->getFirstItem();
                    if($urlRewriteModel->getId()){
                        $this->transaction->addObject($urlRewriteModel);
                    }
                    /** Delete draft */
                    $draftCol = $this->draftCollectionFactory->create()
                    ->addFieldToFilter('spin_id', $infoModel->getId());
                    foreach($draftCol as $_draft){
                        $this->transaction->addObject($_draft);
                    }
                    $this->transaction->delete();
                }
                $this->messageManager->addSuccess(__(
                    "You have successfully deleted the %1 campaign.",
                    $infoModel->getname()
                ));
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                $this->messageManager->addError(
                    __('Something went wrong while deleting the spin info. Please review the error log.')
                );
            }
            $this->_redirect('spintowin/*/index');
            return;
        }
    }
}
