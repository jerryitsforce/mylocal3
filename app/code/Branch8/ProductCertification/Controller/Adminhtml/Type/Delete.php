<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Branch8\ProductCertification\Model\CertificationTypeFactory;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType as ResourceModel;

/**
 * Admin: Delete controller
 */
class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Branch8_ProductCertification::certification_manage';

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
     * @param CertificationTypeFactory $modelFactory
     * @param ResourceModel $resourceModel
     */
    public function __construct(
        Context $context,
        CertificationTypeFactory $modelFactory,
        ResourceModel $resourceModel
    ) {
        parent::__construct($context);
        $this->modelFactory  = $modelFactory;
        $this->resourceModel = $resourceModel;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $id       = (int)$this->getRequest()->getParam('entity_id');

        if (!$id) {
            $this->messageManager->addErrorMessage(__('No ID specified.'));
            return $redirect->setPath('*/*/index');
        }

        $model = $this->modelFactory->create();
        $this->resourceModel->load($model, $id);

        if (!$model->getId()) {
            $this->messageManager->addErrorMessage(__('The certification type no longer exists.'));
            return $redirect->setPath('*/*/index');
        }

        try {
            $this->resourceModel->delete($model);
            $this->messageManager->addSuccessMessage(__('The certification type has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $redirect->setPath('*/*/index');
    }
}
