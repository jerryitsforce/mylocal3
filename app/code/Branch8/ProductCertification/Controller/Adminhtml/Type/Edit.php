<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Branch8\ProductCertification\Model\CertificationTypeFactory;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType as ResourceModel;

/**
 * Admin: New / Edit form controller
 */
class Edit extends Action
{
    public const ADMIN_RESOURCE = 'Branch8_ProductCertification::certification_manage';

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var CertificationTypeFactory
     */
    private CertificationTypeFactory $modelFactory;

    /**
     * @var ResourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param CertificationTypeFactory $modelFactory
     * @param ResourceModel $resourceModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        CertificationTypeFactory $modelFactory,
        ResourceModel $resourceModel
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->modelFactory      = $modelFactory;
        $this->resourceModel     = $resourceModel;
    }

    /**
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $id    = (int)$this->getRequest()->getParam('entity_id', 0);
        $model = $this->modelFactory->create();

        if ($id) {
            $this->resourceModel->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This certification type no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/index');
            }
        }

        // Store model in registry for the form
        $this->_session->setBranch8CertificationTypeData($model->getData());

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Branch8_ProductCertification::certification_manage');
        $title = $id
            ? __('Edit Certification Type: %1', $model->getName())
            : __('New Certification Type');
        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }
}
