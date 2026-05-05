<?php

namespace Branch8\Yoxi\Helper;

use Branch8\Yoxi\Model\YoxiBatchSettingRepository as BatchSettingRepository;
use Branch8\Yoxi\Model\YoxiTicketRecordRepository as RecordRepository;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Framework\App\RequestInterface;
use Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId as SyncQuantityServices;
use Branch8\HotaiCore\Helper\TicketImport as TicketImportHelper;

class Import
{
    const DATA_INDEX                      = 0;
    const NOTIFY_LIMIT_TYPE_SALE_END_TIME = 1;
    const NOTIFY_LIMIT_TYPE_DUE_DAYS      = 2;

    /** @var MessageManagerInterface */
    protected $messageManager;

    /** @var ResultFactory */
    protected $resultFactory;

    /** @var RecordRepository */
    protected $recordRepository;

    /** @var BatchSettingRepository */
    protected $batchSettingRepository;

    /** @var MarketplaceHelper */
    protected $marketplaceHelper;

    /** @var RequestInterface */
    protected $request;

    /** @var SyncQuantityServices */
    protected $syncQuantityServices;

    /** @var TicketImportHelper */
    protected TicketImportHelper $ticketImportHelper;

    protected $productId;
    protected $sellerId;

    public function __construct(
        MessageManagerInterface $messageManager,
        ResultFactory $resultFactory,
        RecordRepository $recordRepository,
        BatchSettingRepository $batchSettingRepository,
        MarketplaceHelper $marketplaceHelper,
        RequestInterface $request,
        SyncQuantityServices $syncQuantityServices,
        TicketImportHelper $ticketImportHelper
    ) {
        $this->messageManager           = $messageManager;
        $this->resultFactory            = $resultFactory;
        $this->recordRepository         = $recordRepository;
        $this->batchSettingRepository   = $batchSettingRepository;
        $this->marketplaceHelper        = $marketplaceHelper;
        $this->request                  = $request;
        $this->syncQuantityServices     = $syncQuantityServices;
        $this->ticketImportHelper       = $ticketImportHelper;
    }

    public function handle()
    {
        try {
            $importMode      = $this->request->getParam('import_mode', TicketImportHelper::IMPORT_MODE_MANUAL);
            $csvDataArray    = $this->ticketImportHelper->extractSerialsFromCsvUpload(
                $this->request,
                'file',
                self::DATA_INDEX,
                2
            );
            $batchCode       = $this->request->getParam("batch_code");
            $this->productId = $this->request->getParam("entity_id");
            $this->sellerId  = $this->marketplaceHelper->getSellerIdByProductId($this->productId);
            if (!$this->sellerId) {
                throw new \Exception("Can't find seller ID for this product.");
            }

            $batchSetting = $this->ticketImportHelper->resolveOrCreateAndPopulateFromRequest(
                $this->batchSettingRepository,
                (string) $batchCode,
                (int) $this->productId,
                $this->request,
                (int) $this->sellerId
            );

            $autoImportSummary = null;
            if ($importMode === TicketImportHelper::IMPORT_MODE_AUTO) {
                $prefix     = (string) $this->request->getParam('auto_prefix', '');
                $length     = (int) $this->request->getParam('auto_serial_length', 0);
                $charType   = (string) $this->request->getParam('auto_char_type', 'alnum');
                $groupCount = (int) $this->request->getParam('auto_group_count', 0);

                $autoImportSummary = $this->ticketImportHelper->handleAutoImport(
                    $prefix,
                    $length,
                    $charType,
                    $groupCount,
                    (int) $this->productId,
                    (int) $batchSetting->getId(),
                    (int) $this->sellerId,
                    \Branch8\Yoxi\Model\YoxiTicketRecord::TABLE_NAME
                );
            } else {
                $importResult = $this->ticketImportHelper->import(
                    \Branch8\Yoxi\Model\YoxiTicketRecord::TABLE_NAME,
                    (int) $this->productId,
                    (int) $batchSetting->getId(),
                    $csvDataArray,
                    (int) $this->sellerId
                );
                if (!$importResult['is_success']) {
                    throw new \RuntimeException(
                        $importResult['exception_message'] ?? __('Failed to import ticket records.')->render()
                    );
                }
            }

            $this->ticketImportHelper->createBatchSettingCustomOption(
                (int) $this->productId,
                (string) $batchSetting->getBatchCode(),
                (int) $batchSetting->getId()
            );

            $availableQuantity = $this->ticketImportHelper->getAvailableQuantity(
                $this->recordRepository,
                (int) $this->productId
            );

            $this->ticketImportHelper->updateProductQuantity(
                (int) $this->productId,
                (int) $availableQuantity
            );

            $this->syncQuantityServices->syncLivesearchInstockIds([$this->productId]);
            $this->syncQuantityServices->syncNeedToRefillIds([$this->productId]);

            $this->messageManager->addSuccessMessage("Available quantity for sale: {$availableQuantity}");

            if ($autoImportSummary !== null) {
                $this->messageManager->addSuccessMessage(
                    __(
                        "Auto import summary - requested: %1, duplicated: %2, inserted: %3",
                        $autoImportSummary['requested_count'],
                        $autoImportSummary['duplicate_count'],
                        $autoImportSummary['inserted_count']
                    )
                );
            }

            $this->messageManager->addSuccessMessage(__("Import ticket records success."));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
