<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Status extends ParentOrder implements HttpGetActionInterface
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->_initAction();
        $resultPage->getConfig()->getTitle()->prepend(
            __('Status definition For Parent Order')
        );
        return $resultPage;
    }
}
