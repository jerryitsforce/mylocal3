<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Branch8\ProductCertification\Model\CertificationTypeFactory;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType as ResourceModel;
use Branch8\ProductCertification\Helper\ImageUploader;

/**
 * Admin: Save controller
 */
class Save extends Action
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
     * @var DataPersistorInterface
     */
    private DataPersistorInterface $dataPersistor;

    /**
     * @var ImageUploader
     */
    private ImageUploader $imageUploader;

    /**
     * @param Context $context
     * @param CertificationTypeFactory $modelFactory
     * @param ResourceModel $resourceModel
     * @param DataPersistorInterface $dataPersistor
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        CertificationTypeFactory $modelFactory,
        ResourceModel $resourceModel,
        DataPersistorInterface $dataPersistor,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->modelFactory  = $modelFactory;
        $this->resourceModel = $resourceModel;
        $this->dataPersistor = $dataPersistor;
        $this->imageUploader = $imageUploader;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data     = $this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/index');
        }

        // Unwrap data from dataScope if it exists
        // Flatten UI-wrapped structure if needed
        if (isset($data['data'])) {
            $data = array_replace($data, $data['data']);
            unset($data['data']);
        }

        // Handle category_ids JSON encoding
        if (isset($data['category_ids'])) {
            $ids = (array)$data['category_ids'];
            $data['category_ids'] = json_encode(array_values($ids));
        }
        
        $id    = (int)($data['entity_id'] ?? $this->getRequest()->getParam('entity_id', 0));
        $model = $this->modelFactory->create();

        if ($id) {
            $this->resourceModel->load($model, $id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This certification type no longer exists.'));
                return $redirect->setPath('*/*/index');
            }
        }

        // Handle category IDs (multiselect or tree)
        if (isset($data['category_ids']) && is_array($data['category_ids'])) {
            $model->setCategoryIdsArray($data['category_ids']);
            unset($data['category_ids']);
        }

        // Handle icon upload/move
        if (isset($data['icon']) && is_array($data['icon'])) {
            if (!empty($data['icon'][0]['tmp_name'])) {
                // New file uploaded
                $data['icon'] = $this->imageUploader->moveFileFromTmp($data['icon'][0]['name']);
            } elseif (isset($data['icon'][0]['name'])) {
                // Keep the icon name/path if it was already saved
                $data['icon'] = $data['icon'][0]['file'] ?? $data['icon'][0]['name'];
            } else {
                $data['icon'] = null;
            }
        }

        // Ensure entity_id is not an empty string if it's a new record
        if (isset($data['entity_id']) && empty($data['entity_id'])) {
            unset($data['entity_id']);
        }
        
        // Remove non-database fields
        unset($data['form_key']);
        unset($data['key']);
        unset($data['back']);

        $model->addData($data);

        try {
            $this->resourceModel->save($model);
            $this->messageManager->addSuccessMessage(__('The certification type has been saved.'));
            $this->dataPersistor->clear('branch8_certification_type');

            if ($this->getRequest()->getParam('back') === 'edit') {
                return $redirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
            }
            return $redirect->setPath('*/*/index');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->dataPersistor->set('branch8_certification_type', $data);
            return $redirect->setPath('*/*/edit', ['entity_id' => $id]);
        }
    }
}
