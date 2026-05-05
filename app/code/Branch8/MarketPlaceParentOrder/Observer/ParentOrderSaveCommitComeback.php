<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Observer;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\Services\ProcessParentOrdersWhenCheckoutWithFullPoint;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webkul\Mpsplitorder\Model\Mpsplitorder;

class ParentOrderSaveCommitComeback implements ObserverInterface
{
    /**
     * @var ParentOrderRepositoryInterface
     */
    private ParentOrderRepositoryInterface $parentOrderRepository;
    /**
     * @var LoggerInterface
     */
    private Log $log;
    private ProcessParentOrdersWhenCheckoutWithFullPoint $processParentOrderWithFullPoint;

    /**
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ProcessParentOrdersWhenCheckoutWithFullPoint $processParentOrdersWhenCheckoutWithFullPoint
     * @param Log $log
     */
    public function __construct(
        ParentOrderRepositoryInterface               $parentOrderRepository,
        ProcessParentOrdersWhenCheckoutWithFullPoint $processParentOrdersWhenCheckoutWithFullPoint,
        Log                                          $log
    )
    {
        $this->processParentOrderWithFullPoint = $processParentOrdersWhenCheckoutWithFullPoint;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->log = $log;
    }

    /**
     * Get information from a sub order then copy them to parent order
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /**
         * @var $dataObject Mpsplitorder
         * Todo:
         * We can move this action into cron process to reduce the checkout time
         */
        $dataObject = $observer->getData('data_object');
        try {
            if ($dataObject && $dataObject->isObjectNew() && $dataObject->getId()) {
                $this->processParentOrderWithFullPoint->execute($this->parentOrderRepository->get((int)$dataObject->getId()));
            }
        } catch (\Throwable $exception) {
            $this->log->logException('ParentOrderSaveCommitComeback', $exception);
        }
    }
}
