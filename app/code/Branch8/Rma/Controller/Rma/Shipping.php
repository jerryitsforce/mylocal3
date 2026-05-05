<?php
namespace Branch8\Rma\Controller\Rma;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Model\OrdersFactory as MpOrdersModel;
use Branch8\Rma\Helper\Config\StatusLabel;
use Branch8\Rma\Helper\Status as RmaStatusHelper;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;

class Shipping extends \Webkul\MpRmaSystem\Controller\Rma\Change
{
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

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

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
        UpdateOrderStatus                                    $updateOrderStatus
    ) {
        $this->statusLabel = $statusLabel;
        $this->rmaActions = $rmaActions;
        $this->rmaStatusHelper = $rmaStatusHelper;
        $this->updateOrderStatus = $updateOrderStatus;
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
     * @inheritDoc
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->resultRedirectFactory->create()
                ->setPath('mprmasystem/seller/allrma');
        }

        $data = $this->getRequest()->getParams();

        $rmaId = $this->mpRmaHelper->decrypt($data['rma_id']);

        if (!is_numeric($rmaId)) {
            $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
            return $this->resultRedirectFactory->create()
                ->setPath('mprmasystem/seller/rma');
        }

        $IsCustomer = $this->mpRmaHelper->getCustmerByRmaId($rmaId);

        if (!$IsCustomer) {
            $this->messageManager->addErrorMessage(__('Customer not exists'));
            return $this->resultRedirectFactory->create()
                ->setPath(
                    'mprmasystem/seller/rma',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }

        $status = $this->rmaActions->updateReplaceShippingNumberBySellerOrAdmin(
            $rmaId,
            $data['shipping_number'],
            $this->getShippingCarrier($data),
        );

        $item = $this->rmaActions->getRmaItemCollection($rmaId);

        foreach ($item as $singleItem) {
            $this->updateOrderStatus->addItemStatusRecord(
                $singleItem->getOrderId(), $singleItem, $status);

            $singleItem->setFlowStatus($status);
            $singleItem->save();
        }

        return $this->resultRedirectFactory->create()
            ->setPath(
                'mprmasystem/seller/rma',
                ['id' => $rmaId, 'back' => null, '_current' => true]
            );
    }

    /**
     * Returns shipping carrier from post data.
     *
     * @param array $data
     *
     * @return string
     */
    private function getShippingCarrier(array $data): string
    {
        $carrier = $data['shipping_carrier'] ?? false;
        if (!$carrier) {
            return '';
        }

        if ($carrier === '其他：自行填寫名稱') {
            $carrier = $data['logistics_provider_name'] ?? '';
        }

        return (string)$carrier;
    }
}
