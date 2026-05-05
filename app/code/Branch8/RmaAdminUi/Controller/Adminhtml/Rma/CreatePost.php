<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Branch8\Rma\Helper\Config\StatusLabel;
use Branch8\Rma\Helper\RmaRecord;
use Branch8\Rma\Helper\Status as RmaStatus;
use Branch8\Rma\Model\SenderType;
use Branch8\RmaAdminUi\Model\AdminRma\PostDataToRmaObject;
use Branch8\Sales\Helper\Order\UpdateOrderStatus as ItemStatus;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Branch8\Rma\Helper\Email as EmailHelper;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;
use Magento\Framework\App\ResourceConnection;

class CreatePost extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Webkul_MpRmaSystem::rma';
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    private \Magento\Framework\View\Result\PageFactory $resultPageFactory;
    /**
     * @var PostDataToRmaObject
     */
    private $postDataToRmaObject;
    /**
     * @var ValidateHandler
     */
    private ValidateHandler $dataProcessor;

    /** @var \Branch8\Rma\Helper\Data $helper */

    protected $helper;
    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;
    private \Webkul\MpRmaSystem\Model\ItemsFactory $itemFactory;

    /** @var \Branch8\Rma\Helper\Config\StatusLabel $statusLabel */
    protected $statusLabel;

    /**
     * @var RmaStatus
     */
    private $status;

    /**
     * @var ItemStatus
     */
    private $itemStatus;

    private EmailHelper $emailHelper;

    protected RmaRecord $rmaRecord;
    protected $rmaId;
    private CustomLogger $logger;
    private ResourceConnection $resourceConnection;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param PostDataToRmaObject $postDataToRmaObject
     * @param ValidateHandler $postDataProcessor
     * @param \Branch8\Rma\Helper\Data $helper
     * @param DataPersistorInterface $dataPersistor
     * @param \Webkul\MpRmaSystem\Model\ItemsFactory $itemsFactory
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param StatusLabel $statusLabel
     * @param RmaStatus $status
     * @param ItemStatus $itemStatus
     * @param EmailHelper $emailHelper
     * @param RmaRecord $rmaRecord
     * @param CustomLogger $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Backend\App\Action\Context        $context,
        \Magento\Framework\Registry                $coreRegistry,
        PostDataToRmaObject                        $postDataToRmaObject,
        ValidateHandler                            $postDataProcessor,
        \Branch8\Rma\Helper\Data                   $helper,
        DataPersistorInterface                     $dataPersistor,
        \Webkul\MpRmaSystem\Model\ItemsFactory     $itemsFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        StatusLabel $statusLabel,
        RmaStatus $status,
        ItemStatus $itemStatus,
        EmailHelper $emailHelper,
        RmaRecord $rmaRecord,
        CustomLogger $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->postDataToRmaObject = $postDataToRmaObject;
        $this->dataProcessor = $postDataProcessor;
        $this->helper = $helper;
        $this->dataPersistor = $dataPersistor;
        $this->itemFactory = $itemsFactory;
        $this->statusLabel = $statusLabel;
        $this->status = $status;
        $this->itemStatus = $itemStatus;
        $this->emailHelper = $emailHelper;
        $this->rmaRecord = $rmaRecord;
        $this->logger=$logger;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    public function execute()
    {
        $postData = $this->getRequest()->getParams();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($postData) {
            /**
             * @var $model \Webkul\MpRmaSystem\Model\Details
             */
            try {
                $connection = $this->resourceConnection->getConnection();
                $connection->beginTransaction();

                try {
                    $model = $this->postDataToRmaObject->build($postData);
                    $requestItemInformation = $model->getRequestItemInformation();
                    if (!$requestItemInformation) {
                        throw new LocalizedException(__('Need Product To Create RMA , contact Administrator to check it.'));
                    }

                    // Check for existing active RMA items with locking
                    foreach ($requestItemInformation as $orderId => $groups) {
                        foreach ($groups as $request) {
                            $itemId = $request['item_id'];
                            
                            // Lock rows
                            $select = $connection->select()
                                ->from(['items' => $connection->getTableName('marketplace_rma_items')], ['rma_id'])
                                ->where('item_id = ?', $itemId)
                                ->forUpdate(true);
                        
                            $existingRmaIds = $connection->fetchCol($select);
                            
                            if (!empty($existingRmaIds)) {
                                $selectDetails = $connection->select()
                                    ->from(['details' => $connection->getTableName('marketplace_rma_details')], ['id'])
                                    ->where('id IN (?)', $existingRmaIds)
                                    ->where('status NOT IN (?)', [
                                        \Branch8\Rma\Model\Rma\Status::RMA_CANCELED,
                                        \Branch8\Rma\Model\Rma\Status::RETURN_APPLY_CANCEL,
                                        \Branch8\Rma\Model\Rma\Status::REPLACE_APPLY_CANCEL
                                    ]);
                                
                                $activeRmaIds = $connection->fetchCol($selectDetails);
                                
                                if (!empty($activeRmaIds)) {
                                    throw new LocalizedException(__('The RMA has already been created for item ID %1.', $itemId));
                                }
                            }
                        }
                    }

                    $model->save();
                    $id = $model->getId();
                    if (empty($id)) {
                        throw new LocalizedException(__('Rma saving error'));
                    }
                    $requestItemInformation = $model->getRequestItemInformation();
                    $itemIds = [];
                    foreach ($requestItemInformation as $orderId => $groups) {
                        foreach ($groups as $request) {
                            $item = $this->itemFactory->create()->setData(
                                array_merge(['rma_id' => $id], $request)
                            );
                            $itemIds[] = $request['item_id'];
                            try {
                                $item->save();
                            } catch (\Exception $exception) {
                                $model->delete();
                                throw $exception;
                            }
                        }
                    }

                    $this->helper->saveRmaHistory(
                        $id,
                        $this->helper->getConfigData('new_rma_message'),
                        SenderType::TYPE_ADMIN
                    );


                    // 新增歷史紀錄 -- add history record
                    $status = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($model->getStatus());
                    $this->status->createStatusRecord($status, $model); //model = detail model
                    foreach ($requestItemInformation as $orderId => $groups) {
                        foreach ($groups as $request) {
                            $this->itemStatus->updateItemStatusById($request['item_id'], $status, $orderId, true, $id);
                        }
                    }

                    $connection->commit();

                    $this->dataPersistor->clear('rma_detail');
                    $this->messageManager->addSuccessMessage(__('Save successfully.'));
                    // After successful save
                    $this->emailHelper->sendNewRmaNotifyEmailToSeller($requestItemInformation, $postData, $id);
                    $this->setRmaId($id);
                    $rmaData =  $model->getData();
                    $rmaData['order_items'] = $itemIds;
                    $this->sendRmaEmail($rmaData);
                    return $resultRedirect->setPath('mprmasystem/rma/edit', ['id' => $id]);
                
                } catch (\Exception $e) {
                    $connection->rollBack();
                    throw $e;
                }

            } catch (LocalizedException $e) {
                $this->dataPersistor->set('rma_detail', $postData);
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
                return $resultRedirect->setPath('mprmasystem/rma/new');
            } catch (\Throwable $e) {
                $this->logger->critical($e->getMessage());
                $this->dataPersistor->set('rma_detail', $postData);
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the page.'));
                return $resultRedirect->setPath('mprmasystem/rma/new');
            }
        }
        return $resultRedirect->setPath('mprmasystem/rma/index');
    }

    /**
     * setRmaId
     *
     * @param  int $rmaId
     * @return void
     */
    private function setRmaId($rmaId)
    {
        $this->rmaId = $rmaId;
    }

    /**
     * sendRmaEmail
     *
     * @param  array $rmaData
     * @return void
     */
    private function sendRmaEmail($rmaData)
    {
        $rmaData['order_data'] = $this->rmaRecord->getRmaOrderData($rmaData);
        $rmaInfo = $rmaData;
        $rmaInfo['rma_id'] = $this->rmaId;
        $rmaInfo['additional_info'] = "";
        $details = [
            'type' => 0,
            'name' => $rmaData['customer_name'],
            'order_id' => $rmaData['order_id'],
            'rma' => $rmaInfo,
            'order_data' => $rmaData['order_data'],
        ];
        try {
            $this->helper->sendNewRmaEmail($details);
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
    }
}
