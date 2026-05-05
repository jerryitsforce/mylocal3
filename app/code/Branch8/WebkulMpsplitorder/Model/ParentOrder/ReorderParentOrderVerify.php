<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model\ParentOrder;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\WebkulMpsplitorder\Model\VerifyQuote\VerifyQuoteActionInterface;
use Branch8\WebkulMpsplitorder\Model\VerifyQuote\VerifyResult;

class ReorderParentOrderVerify implements VerifyQuoteActionInterface
{
    private $parentOrderRepository;

    private $parentOrderManagement;

    /**
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     */
    public function __construct(
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement
    )
    {
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderRepository = $parentOrderRepository;
    }

    /**
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Magento\Sales\Model\AdminOrder\Create $adminOrderModel
     * @return VerifyResult
     */
    public function verify(\Magento\Backend\Model\Session\Quote $quoteSession, \Magento\Sales\Model\AdminOrder\Create $adminOrderModel)
    {
        $verify = new VerifyResult(
            [
                'result' => true,
                'error' => ''
            ]
        );
        if (($id = $quoteSession->getReOrderParentOrderFrom())
            && $parentOrder = $this->getParentOrder($id)
        ) {
            $verify = $this->__verify($parentOrder);
        }
        return $verify;
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return VerifyResult
     */
    private function __verify(ParentOrderInterface $parentOrder)
    {
        $result = $this->parentOrderManagement->canReorder($parentOrder);
        $error = $result === false ? __('Can not re-order parent')->render() : '';
        return new VerifyResult(
            [
                'result' => $result,
                'error' => $error
            ]
        );
    }

    /**
     * @param $id
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface|string
     */
    private function getParentOrder($id)
    {
        try {
            return $this->parentOrderRepository->get((int)$id);
        } catch (\Exception $exception) {
            return '';
        }
    }
}
