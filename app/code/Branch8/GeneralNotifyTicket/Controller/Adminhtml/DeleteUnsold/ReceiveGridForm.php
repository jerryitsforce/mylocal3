<?php

namespace Branch8\GeneralNotifyTicket\Controller\Adminhtml\DeleteUnsold;

use Magento\Framework\Controller\Result\Redirect;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSettingFactory;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSettingRepository;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecordFactory;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketRecordRepository;
use Magento\Backend\App\Action;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketRecord\Collection;
use Branch8\HotaiCore\Helper\VirtualProductQuantity as VirtualProductQuantityHelper;
use Branch8\GeneralNotifyTicket\Model\Ticket\Synchronizer as TicketSynchronizer;
use Magento\Catalog\Model\Product\Action as ProductAction;

class ReceiveGridForm extends Action
{
    const ADMIN_RESOURCE                               = 'Branch8_GeneralNotifyTicket::delete_unsold';
    const DATA_INDEX_GeneralNotifyTicket_SERIAL_NUMBER = 0;

    /** @var MessageManagerInterface */
    protected $messageManager;

    /** @var ResultFactory */
    protected $resultFactory;

    /** @var EavConfig */
    protected $eavConfig;

    /** @var ProductFactory */
    protected $productFactory;

    /** @var ProductRepository */
    protected $productRepository;

    /** @var GeneralNotifyTicketRecordFactory */
    protected $ticketRecordFactory;

    /** @var GeneralNotifyTicketRecordRepository */
    protected $ticketRecordRepository;

    /** @var GeneralNotifyTicketBatchSettingFactory */
    protected $batchSettingFactory;

    /** @var GeneralNotifyTicketBatchSettingRepository */
    protected $batchSettingRepository;

    /** @var Transaction */
    protected $transaction;

    /** @var VirtualProductQuantityHelper */
    protected $virtualProductQuantityHelper;

    /** @var TicketSynchronizer */
    protected $ticketSynchronizer;

    /** @var ProductAction */
    protected $productAction;

    public function __construct(
        MessageManagerInterface $messageManager,
        ResultFactory $resultFactory,
        EavConfig $eavConfig,
        ProductFactory $productFactory,
        ProductRepository $productRepository,
        GeneralNotifyTicketRecordFactory $ticketRecordFactory,
        GeneralNotifyTicketRecordRepository $ticketRecordRepository,
        GeneralNotifyTicketBatchSettingFactory $batchSettingFactory,
        GeneralNotifyTicketBatchSettingRepository $batchSettingRepository,
        Transaction $transaction,
        VirtualProductQuantityHelper $virtualProductQuantityHelper,
        TicketSynchronizer $ticketSynchronizer,
        ProductAction $productAction,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->messageManager               = $messageManager;
        $this->resultFactory                = $resultFactory;
        $this->eavConfig                    = $eavConfig;
        $this->productFactory               = $productFactory;
        $this->productRepository            = $productRepository;
        $this->ticketRecordFactory          = $ticketRecordFactory;
        $this->ticketRecordRepository       = $ticketRecordRepository;
        $this->batchSettingFactory          = $batchSettingFactory;
        $this->batchSettingRepository       = $batchSettingRepository;
        $this->transaction                  = $transaction;
        $this->virtualProductQuantityHelper = $virtualProductQuantityHelper;
        $this->ticketSynchronizer     = $ticketSynchronizer;
        $this->productAction                = $productAction;

        return parent::__construct($context);
    }

    public function execute()
    {
        try {
            $productId      = (int) $this->getRequest()->getParam("product_id");
            $batchSettingId = (int) $this->getRequest()->getParam("batch_setting_id");

            $batchSettingSearchResult = $this->batchSettingRepository->getById($batchSettingId);

            if (!$batchSettingSearchResult) {
                throw new \Exception("Can't find GeneralNotifyTicket batch setting by giving batch setting id: {$batchSettingId}");
            }

            $unsoldCollection = $this->ticketRecordRepository->getUnsoldRecordsByProductIdAndBatchCode($productId, $batchSettingId);

            if (count($unsoldCollection->getItems()) == 0) {
                $this->messageManager->addSuccessMessage(__("No unsold ticket records need to be delete."));

                return $this->redirectToPreviousPage();
            }

            $this->delete($unsoldCollection);

            $availableCollection = $this->ticketRecordRepository->getAvailableForSaleRecordsByProductId($productId);

            $this->virtualProductQuantityHelper->updateQuantity($productId, $availableCollection->getSize());

            // Sync variations after deleting records
            $product = $this->productRepository->getById($productId);
            // Explicitly persist admin user updated identity before sync
            if ($this->_auth->getUser()) {
                $this->productAction->updateAttributes([$productId], ['admin_user_updated' => $this->_auth->getUser()->getUserName()], 0);
            }
            // Sync variations after deleting records using specific synchronizer
            $this->ticketSynchronizer->sync($product);

            $this->messageManager->addSuccessMessage(__("Unsold ticket records delete success."));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->redirectToPreviousPage();
    }

    /**
     * 導回先前頁面
     *
     * @return Redirect
     */
    private function redirectToPreviousPage(): Redirect
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }

    /**
     * 以transaction方式刪除傳入collection底下的對應資料庫紀錄
     *
     * @param Collection $collection
     * @return void
     */
    private function delete(Collection $collection)
    {
        $recordsArray = $collection->getItems();

        foreach ($recordsArray as $record) {
            $this->transaction->addObject($record);
        }

        $this->transaction->delete();
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
