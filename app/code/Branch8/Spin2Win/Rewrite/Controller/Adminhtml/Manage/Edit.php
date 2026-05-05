<?php

namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;

use Magento\Framework\Locale\Resolver;
use Webkul\SpinToWin\Model\InfoFactory;
use Magento\Framework\Registry;

class Edit extends \Webkul\SpinToWin\Controller\Adminhtml\Manage\Edit
{

    protected $_conn;

    /**
     * @var \Webkul\SpinToWin\Model\InfoFactory
     */
    private $infoFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    private $coreRegistry;

    protected $spinDraftHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        InfoFactory $infoFactory,
        Registry $registry,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    ) {
        parent::__construct($context, $resultPageFactory, $infoFactory, $registry);
        $this->_conn = $resourceConnection->getConnection();
        $this->infoFactory = $infoFactory;
        $this->coreRegistry = $registry;
        $this->spinDraftHelper = $spinDraftHelper;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('id');
        $spininfo = $this->infoFactory->create();
        if ($id) {
            $spininfo->load($id);
            if (!$spininfo->getEntityId()) {
                $this->messageManager->addError(__('This campaign no longer exists.'));
                $this->_redirect('*/*/index');
                return;
            }
        }
        $checkPrize100Invalid = $this->spinDraftHelper->checkPrize100Invalid($id);
        if($checkPrize100Invalid['result']){
            $this->messageManager->addWarningMessage(__('The total probability is incorrect. Current total: %1%. Please adjust to 100%.', $checkPrize100Invalid['total']));
        }else{
            $responseData['pageMessage'] = '';
        }
        $spinDataDB = $spininfo->getData();
        /** Load Draft */
        $informationDraft = $this->spinDraftHelper->getDraftByType($id, 'information');
        if($informationDraft){
            $spinData = array_merge($spinDataDB, $informationDraft);
        }else{
            $spinData = $spinDataDB;
        }
        $spininfo->setData($spinData);

        $this->coreRegistry->register('spininfo', $spininfo);
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit %1', $spininfo->getName()) : __('New Campaign'),
            $id ? __('Edit Info') : __('New Info')
        );
        $resultPage->getConfig()->getTitle()->prepend($id ?__('Edit %1', $spininfo->getName()) : __('New Campaign'));
        return $resultPage;
    }

}
