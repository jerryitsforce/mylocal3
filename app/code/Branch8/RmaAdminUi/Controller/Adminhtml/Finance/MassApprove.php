<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Finance;

use Branch8\Rma\Model\Actions\ApproveFinance;
use  Branch8\Rma\Model\Rma\Status as RmaStatus;
use Branch8\Marketplace\Model\Actions\CancelOrderAction;
use Branch8\Marketplace\Model\Actions\GetSellerByOrder;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class MassApprove extends AbstractMassAction
{
    private CustomLogger $logger;
    private ApproveFinance $approveFinance;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param ApproveFinance $approveFinance
     * @param CollectionFactory $collectionFactory
     * @param CustomLogger $logger
     */
    public function __construct(
        Context           $context,
        Filter            $filter,
        ApproveFinance    $approveFinance,
        CollectionFactory $collectionFactory,
        CustomLogger      $logger
    )
    {
        parent::__construct($context, $filter);
        $this->logger = $logger;
        $this->approveFinance = $approveFinance;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param AbstractCollection $collection
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    protected function massAction(AbstractCollection $collection)
    {
        $count = 0;
        /**
         * @var $detail \Webkul\MpRmaSystem\Model\Details
         */
        $valid = [
            RmaStatus::RETURN_FINANCIAL_REVIEW_PROCESSING
        ];
        foreach ($collection->getItems() as $detail) {
            try {
                if (!in_array($detail->getStatus(), $valid)) {
                    $isApproved = false;
                } else {
                    $isApproved = $this->approveFinance->execute($detail);
                }
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $isApproved = false;
            }
            if ($isApproved === false) {
                continue;
            }
            $count++;
        }
        $countNonCancelOrder = $collection->count() - $count;
        if ($countNonCancelOrder && $count) {
            $this->messageManager->addErrorMessage(__('%1 RMA(s) cannot be approved finance.', $countNonCancelOrder));
        } elseif ($countNonCancelOrder) {
            $this->messageManager->addErrorMessage(__('You cannot approve RMA(s).'));
        }

        if ($count) {
            $this->messageManager->addSuccessMessage(__('Approve finance for %1 RMA(s).', $count));
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }


    /**
     * Return component referrer url
     * TODO: Technical dept referrer url should be implement as a part of Action configuration in appropriate way
     *
     * @return null|string
     */
    protected function getComponentRefererUrl()
    {
        return $this->filter->getComponentRefererUrl() ?: 'mprmasystem/rma/index';
    }

}
