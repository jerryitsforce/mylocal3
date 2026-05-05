<?php
declare(strict_types=1);


namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\HistoryFactory;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderComment\Sender;
use Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Tab\Information\History\RecordFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;
use Magento\Backend\App\Action;

/**
 * Ajax Submit Comment
 */
class PostComment extends \Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder implements HttpPostActionInterface
{

    private $resultJsonFactory;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    private $resultRawFactory;
    /**
     * @var HistoryFactory
     */
    private $historyFactory;
    /**
     * @var ParentOrderCommentSender
     */
    private $parentOrderCommentSender;
    /**
     * @var
     */
    private $recordFactory;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param HistoryFactory $historyFactory
     * @param Sender $commentSender
     * @param RecordFactory $recordFactory
     * @param ParentOrderManagementInterface $parentOrderManagement
     */
    public function __construct(
        Action\Context                                   $context,
        Registry                                         $coreRegistry,
        PageFactory                                      $resultPageFactory,
        LoggerInterface                                  $logger,
        ParentOrderRepositoryInterface                   $parentOrderRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory,
        HistoryFactory                                   $historyFactory,
        Sender                                           $commentSender,
        RecordFactory                                    $recordFactory,
        ParentOrderManagementInterface                   $parentOrderManagement
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
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->historyFactory = $historyFactory;
        $this->parentOrderCommentSender = $commentSender;
        $this->recordFactory = $recordFactory;
    }

    /**
     * Execute
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Raw|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $httpBadRequestCode = 400;
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultRawFactory->create();
        $parentOrder = $this->_initParentOrder();
        if (!$this->getRequest()->isXmlHttpRequest()
            || $this->getRequest()->getMethod() !== 'POST'
            || !$parentOrder
            || !$parentOrder->getId()
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        $response = [
            'errors' => false,
            'messages' => [__('Comment was successfully save')]
        ];
        try {
            /**
             * @var $history History
             */
            $history = $this->historyFactory->create();
            $history->setParentId($parentOrder->getIndexId())
                ->setIsCustomerNotified(
                    filter_var($this->getRequest()->getParam('is_customer_notified'),
                        FILTER_VALIDATE_BOOL)
                )->setIsVisibleOnFront(
                    filter_var($this->getRequest()->getParam('history_visible'),
                        FILTER_VALIDATE_BOOL)
                )->setStatus(
                    $this->getRequest()->getParam('status')
                )->setComment($this->getRequest()->getParam('message'))->save();
            $history->setParentOrder($parentOrder);
            if (\filter_var($this->getRequest()->getParam('is_customer_notified'), FILTER_VALIDATE_BOOL)) {
                $this->parentOrderCommentSender->send(
                    $parentOrder,
                    $this->getRequest()->getParam('message')
                );
            }
            $response['new'] = [
                $this->recordFactory->create()->setNameInLayout('history_record_' . $history->getId())->setItem($history)->toHtml()
            ];
            $this->parentOrderManagement->saveStatusHistory($parentOrder, $this->getRequest()->getParam('status'));
        } catch (LocalizedException $e) {
            $response = [
                'errors' => true,
                'messages' => [$e->getMessage()],
            ];
        } catch (\Exception $e) {
            $response = [
                'errors' => true,
                'messages' => [__('Error when post comment')],
            ];
        }
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
