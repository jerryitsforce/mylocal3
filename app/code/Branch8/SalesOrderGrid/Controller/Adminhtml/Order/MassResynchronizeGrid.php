<?php
declare (strict_types=1);

namespace Branch8\SalesOrderGrid\Controller\Adminhtml\Order;

use Branch8\Sales\Model\Actions\ResyncOrdersToGrid;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;


class MassResynchronizeGrid extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Magento_Sales::resync_order_data_grid';

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var ResyncOrdersToGrid
     */
    private ResyncOrdersToGrid $resyncOrdersToGrid;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param ResyncOrdersToGrid $resyncOrdersToGrid
     * @param OrderManagementInterface|null $orderManagement
     */
    public function __construct(
        Context                  $context,
        Filter                   $filter,
        CollectionFactory        $collectionFactory,
        ResyncOrdersToGrid       $resyncOrdersToGrid,
        OrderManagementInterface $orderManagement = null
    )
    {
        parent::__construct($context, $filter);
        $this->resyncOrdersToGrid = $resyncOrdersToGrid;
        $this->collectionFactory = $collectionFactory;
        $this->orderManagement = $orderManagement ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Sales\Api\OrderManagementInterface::class
        );
    }

    /**
     * @param AbstractCollection $collection
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    protected function massAction(AbstractCollection $collection)
    {
        $ids = $collection->getAllIds();
        $countCanResyncGrid = count($ids);
        $this->resyncOrdersToGrid->execute($ids);
        if ($countCanResyncGrid) {
            $this->messageManager->addSuccessMessage(__('We resynchronize %1 order(s) to grid.', $countCanResyncGrid));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
