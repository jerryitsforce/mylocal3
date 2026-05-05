<?php

namespace Branch8\Rma\Controller\Rma;

use Branch8\Rma\Helper\Config\StatusLabel;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Helper\Status as RmaStatusHelper;
use Branch8\Rma\Model\Rma\Status;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Model\OrdersFactory as MpOrdersModel;

class Change extends \Webkul\MpRmaSystem\Controller\Rma\Change

{
    /**
     * Log option value for this controller.
     */
    private const LOG_OPTION = 'Change';
    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $url;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Branch8\Rma\Helper\Data
     */
    protected $mpRmaHelper;

    /**
     * @var \Webkul\MpRmaSystem\Model\DetailsFactory
     */
    protected $details;

    /**
     * @var \Webkul\MpRmaSystem\Model\ItemsFactory
     */
    protected $items;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var MpOrdersModel
     */
    protected $mpOrdersModel;

    /** @var \Branch8\Rma\Helper\Config\StatusLabel $statusLabel */
    protected $statusLabel;

    /** @var \Branch8\Rma\Helper\Config\Flow $flow */
    protected $flow;

    /** @var \Magento\Sales\Api\OrderItemRepositoryInterface $itemCollectionFactory */
    protected $rmaStatusHelper;

    protected $rmaActions;
    protected $emailHelper;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;
    private LoggerInterface $logger;

    /**
     * @param Context $context
     * @param \Magento\Customer\Model\Url $url
     * @param \Magento\Customer\Model\Session $session
     * @param \Branch8\Rma\Helper\Data $mpRmaHelper
     * @param \Webkul\MpRmaSystem\Model\DetailsFactory $details
     * @param \Webkul\MpRmaSystem\Model\ItemsFactory $items
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param MpOrdersModel $mpOrdersModel
     * @param StatusLabel $statusLabel
     * @param RmaStatusHelper $rmaStatusHelper
     * @param RmaActions $rmaActions
     * @param UpdateOrderStatus $updateOrderStatus
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context                                              $context,
        \Magento\Customer\Model\Url                          $url,
        \Magento\Customer\Model\Session                      $session,
        \Branch8\Rma\Helper\Data                             $mpRmaHelper,
        \Webkul\MpRmaSystem\Model\DetailsFactory             $details,
        \Webkul\MpRmaSystem\Model\ItemsFactory               $items,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Sales\Model\OrderFactory                    $orderFactory,
        \Magento\Framework\Message\ManagerInterface          $messageManager,
        MpOrdersModel                                        $mpOrdersModel,
        StatusLabel                                          $statusLabel,
        RmaStatusHelper                                      $rmaStatusHelper,
        RmaActions                                           $rmaActions,
        UpdateOrderStatus                                    $updateOrderStatus,
        LoggerInterface                                      $logger,
        \Branch8\Rma\Helper\Email                            $emailHelper
    )
    {
        $this->logger = $logger;
        $this->statusLabel = $statusLabel;
        $this->rmaActions = $rmaActions;
        $this->rmaStatusHelper = $rmaStatusHelper;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->emailHelper = $emailHelper;
        parent::__construct($context, $url, $session, $mpRmaHelper, $details, $items, $stockRegistry, $orderFactory, $messageManager, $mpOrdersModel);
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->url->getLoginUrl();
        if (!$this->session->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * Change Rma Action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->resultRedirectFactory
                ->create()
                ->setPath('mprmasystem/seller/allrma');
        }
        $data = $this->getRequest()->getParams();
        $rmaId = $this->mpRmaHelper->decrypt($data['rma_id']);

        if (!is_numeric($rmaId)) {
            $this->messageManager->addError(__("Invalid rma Id."));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma'
                );
        }

        $IsCustomer = $this->mpRmaHelper->getCustmerByRmaId($rmaId);

        if (!$IsCustomer) {
            $this->messageManager->addError(__("Customer not exists"));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }

        try {
            $this->validatePost($data);
            $changedStatus = (int)$data['changed_status'];
            $extraParams = [];
            if (in_array($changedStatus, Status::declinedStatus())) {
                $extraParams = [
                    'decline_reason_id' => $data['decline_reason_id'],
                    'decline_reason_detail' => $data['decline_reason_detail'],
                ];
            }

            if (in_array($changedStatus, Status::canCancelTicket())) {
                $this->rmaActions->cancelTicket($rmaId);
            }

            if ($changedStatus == Status::REPLACE_TO_RETURN) {
                $this->rmaActions->changeResolutionType($rmaId, $this->mpRmaHelper::RESOLUTION_REFUND);
            }

            if ($changedStatus == Status::RETURN_TO_REPLACE) {
                $this->rmaActions->changeResolutionType($rmaId, $this->mpRmaHelper::RESOLUTION_REPLACE);
            }

            if ($changedStatus == Status::REPLACE_AGAIN) {
                $extraParams['is_replace_again'] = true;
            }
            // If reject replace again action, just document this action but not change status
            if ($changedStatus == Status::REJECT_REPLACE_AGAIN) {
                $this->rmaActions->saveActionRecord($changedStatus, $rmaId);
                return $this->resultRedirectFactory
                    ->create()
                    ->setPath(
                        'mprmasystem/seller/rma',
                        ['id' => $rmaId, 'back' => null, '_current' => true]
                    );
            }

            $status = $this->rmaActions->changeStatusBySeller(
                $rmaId,
                $changedStatus,
                $extraParams
            );
            $items = $this->rmaActions->getRmaItemCollection($rmaId);

            foreach ($items as $singleItem) {
                $this->updateOrderStatus->updateItemStatusById(
                    $singleItem->getItemId(),
                    $status,
                    $singleItem->getOrderId(),
                    false,
                    null,
                    false
                );
            }

            foreach ($items as $singleItem) {
                $this->updateOrderStatus->addItemStatusRecord(
                    $singleItem->getOrderId(),
                    $singleItem,
                    $status
                );
            }

            $this->_eventManager->dispatch('branch8_rma_status_change_after', ['rma_id' => $rmaId, 'new_status' => $changedStatus, 'extra_params' => $extraParams]);

        } catch (LocalizedException $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId]);
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $exception) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($exception, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId]);
            $this->messageManager->addErrorMessage(__('Unknown Error'));
        }

        $this->_eventManager->dispatch(
            'return_exchange_change',
            [
                'rma_id' => $rmaId
            ]
        );
        return $this->resultRedirectFactory
            ->create()
            ->setPath(
                'mprmasystem/seller/rma',
                ['id' => $rmaId, 'back' => null, '_current' => true]
            );
    }

    /**
     * @param $params
     * @return void
     * @throws InputException
     */
    private function validatePost($params)
    {
        $declineStatus = Status::declinedStatus();
        $changedStatus = (int)$params['changed_status'];
        if (in_array($changedStatus, $declineStatus)
            && (
                empty($params['decline_reason_id'])
                || empty($params['decline_reason_detail'])
            )
        ) {
            throw new InputException(__('Invalid declined reasons fields '));
        }
    }
}
