<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Model\ResourceModel\StatusPriority;
use Magento\Backend\App\Action;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;

class SaveStatusPriority extends ParentOrder implements HttpPostActionInterface
{
    private StatusPriority $resourceModel;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param StatusPriority $resoureModel
     * @param ParentOrderManagementInterface $parentOrderManagement
     */
    public function __construct(
        Action\Context                 $context,
        Registry                       $coreRegistry,
        PageFactory                    $resultPageFactory,
        LoggerInterface                $logger,
        ParentOrderRepositoryInterface $parentOrderRepository,
        StatusPriority                 $resoureModel,
        ParentOrderManagementInterface $parentOrderManagement
    )
    {
        parent::__construct(
            $context,
            $coreRegistry,
            $resultPageFactory,
            $logger,
            $parentOrderRepository,
            $parentOrderManagement
        );
        $this->resourceModel = $resoureModel;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $connection = $this->resourceModel->getConnection();
            $post = (array)$this->getRequest()->getPostValue('records');
            $insertData = [];
            foreach ($post as $row) {
                $insertData[] = ['status' => $row['status'], 'priority' => $row['priority']];
            }
            if ($insertData) {
                $connection->insertOnDuplicate($connection->getTableName('sales_parent_order_status_priority'),
                    $insertData,
                    ['status', 'priority']
                );
            }

            $this->messageManager->addSuccessMessage(__('Data save successfully'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            $this->_objectManager->get(\Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger::class)->critical($e);
        }
        return $resultRedirect->setPath('*/*/status');
    }
}
