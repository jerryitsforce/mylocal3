<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\AbstractMassAction;
use Branch8\MarketPlaceParentOrderAdminUi\Model\Actions\ParentOrderCancelAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\Component\MassAction\Filter;

class MassCancel extends AbstractMassAction implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::cancel';

    private ParentOrderCancelAction $parentOrderCancelAction;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory
     * @param ParentOrderCancelAction $parentOrderCancelAction
     */
    public function __construct(
        Context                                                                           $context,
        Filter                                                                            $filter,
        \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory $parentOrderCollectionFactory,
        ParentOrderCancelAction                                                           $parentOrderCancelAction
    )
    {
        $this->collectionFactory = $parentOrderCollectionFactory;
        parent::__construct($context, $filter);
        $this->parentOrderCancelAction = $parentOrderCancelAction;
    }

    /**
     * Cancel selected orders
     *
     * @param AbstractCollection $collection
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    protected function massAction(AbstractCollection $collection)
    {
        $countCancelOrder = 0;
        foreach ($collection->getItems() as $parentOrder) {
            try {
                $isCanceled = $this->parentOrderCancelAction->execute($parentOrder);
            } catch (\Exception $exception) {
                $isCanceled = false;
            }
            if ($isCanceled === false) {
                continue;
            }
            $countCancelOrder++;
        }
        $countNonCancelOrder = $collection->count() - $countCancelOrder;
        if ($countNonCancelOrder && $countCancelOrder) {
            $this->messageManager->addErrorMessage(__('%1 order(s) cannot be canceled.', $countNonCancelOrder));
        } elseif ($countNonCancelOrder) {
            $this->messageManager->addErrorMessage(__('You cannot cancel the order(s).'));
        }

        if ($countCancelOrder) {
            $this->messageManager->addSuccessMessage(__('We canceled %1 order(s).', $countCancelOrder));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
