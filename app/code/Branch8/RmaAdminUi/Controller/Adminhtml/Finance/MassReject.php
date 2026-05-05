<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Finance;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\Marketplace\Model\Actions\CancelOrderAction;
use Branch8\Marketplace\Model\Actions\GetSellerByOrder;
use Branch8\Rma\Model\Actions\RejectFinance;
use Branch8\Rma\Model\Rma\Status as RmaStatus;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class MassReject extends AbstractMassAction
{
    private CustomLogger $logger;
    private RejectFinance $rejectFinance;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param RejectFinance $rejectFinance
     * @param CollectionFactory $collectionFactory
     * @param CustomLogger $logger
     */
    public function __construct(
        Context           $context,
        Filter            $filter,
        RejectFinance     $rejectFinance,
        CollectionFactory $collectionFactory,
        CustomLogger      $logger
    )
    {
        parent::__construct($context, $filter);
        $this->logger = $logger;
        $this->rejectFinance = $rejectFinance;
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
                    $isRejected = false;
                } else {
                    $isRejected = $this->rejectFinance->execute($detail);
                }
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $isRejected = false;
            }
            if ($isRejected === false) {
                continue;
            }
            $count++;
        }
        $countNonce = $collection->count() - $count;
        if ($countNonce && $count) {
            $this->messageManager->addErrorMessage(__('%1 RMA(s) cannot be rejected finance.', $countNonce));
        } elseif ($countNonce) {
            $this->messageManager->addErrorMessage(__('You cannot reject finance for RMA(s).'));
        }

        if ($count) {
            $this->messageManager->addSuccessMessage(__('We rejected finance for %1 RMA(s).', $count));
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
