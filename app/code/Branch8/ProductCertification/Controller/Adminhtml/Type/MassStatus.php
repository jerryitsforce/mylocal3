<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType\CollectionFactory;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType as ResourceModel;

/**
 * Mass Status (enable/disable) controller
 */
class MassStatus extends Action
{
    public const ADMIN_RESOURCE = 'Branch8_ProductCertification::certification_manage';

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var ResourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ResourceModel $resourceModel
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        ResourceModel $resourceModel
    ) {
        parent::__construct($context);
        $this->filter            = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->resourceModel     = $resourceModel;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $status     = (int)$this->getRequest()->getParam('status');
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $updated    = 0;

        /** @var \Branch8\ProductCertification\Model\CertificationType $item */
        foreach ($collection as $item) {
            try {
                $item->setData('status', $status);
                $this->resourceModel->save($item);
                $updated++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        $label = $status ? __('enabled') : __('disabled');
        $this->messageManager->addSuccessMessage(
            __('%1 certification type(s) have been %2.', $updated, $label)
        );
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
    }
}
