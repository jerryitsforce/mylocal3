<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderConfirmation\NotifierInterface;
use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;
use Magento\Backend\App\Action;

/**
 *
 */
class Sendmail extends ParentOrder implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::email';
    /**
     * @var NotifierInterface
     */
    private NotifierInterface $notifier;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param HistoryFactory $historyFactory
     * @param NotifierInterface $notifier
     * @param RecordFactory $recordFactory
     * @param ParentOrderManagementInterface $parentOrderManagement
     */
    public function __construct(
        Action\Context                 $context,
        Registry                       $coreRegistry,
        PageFactory                    $resultPageFactory,
        LoggerInterface                $logger,
        ParentOrderRepositoryInterface $parentOrderRepository,
        NotifierInterface              $notifier,
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
        $this->notifier = $notifier;
    }


    /**
     * Cancel order
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->isValidPostRequest()) {
            $this->messageManager->addErrorMessage(__('You can not send mail for this item.'));
            return $resultRedirect->setPath('sales/*/');
        }
        $parentOrder = $this->_initParentOrder();
        if ($parentOrder) {
            try {
                $this->notifier->notify($parentOrder);
                $this->messageManager->addSuccessMessage(__('You sent the order email.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t send the email order right now.'));
                $this->_objectManager->get(\Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger::class)->critical($e);
            }
            return $resultRedirect->setPath('sales/parent_order/view', ['id' => $parentOrder->getId()]);
        }
        return $resultRedirect->setPath('sales/*/');
    }
}
