<?php

declare(strict_types=1);

namespace Branch8\HotaiCore\Observer;

use Branch8\HotaiCore\Model\Config\Source\LogOption;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;

class CheckImportTypeTicketQuantity implements ObserverInterface
{
    const LOG_FOLDER = 'HotaiCore/Observer/CheckImportTypeTicketQuantity';

    private const DEBUG_LOG_OPTION = LogOption::LOG_CHECK_IMPORT_TYPE_TICKET_QUANTITY;

    /**
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     * @param VirtualProductHelper $virtualProductHelper
     */
    public function __construct(
        protected HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        protected VirtualProductHelper $virtualProductHelper
    ) {}

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $masterQuote = $observer->getData('quote');

        if (!$masterQuote) {
            return;
        }

        foreach ($masterQuote->getAllVisibleItems() as $quoteItem) {
            if ($quoteItem->getAvailableToCheckout() == 0) {
                continue;
            }

            $productId = (int) $quoteItem->getProductId();

            if (!$this->hotaiCoreCommonHelper->isBatchImportTicketProduct($productId)) {
                continue;
            }

            try {
                $batchCode = $this->virtualProductHelper->getBatchSettingCustomOptionValueForQuoteItemFlow($quoteItem)->getTitle();

                if (empty($batchCode)) {
                    throw new LocalizedException(__('Empty batch code from custom option.'));
                }

                $requestQuantity = (int) $quoteItem->getQty();

                $checkResultArray = $this->virtualProductHelper->checkIfQuantityEnoughByCustomOptionAndRequestQuantity(
                    $productId,
                    $batchCode,
                    $requestQuantity
                );

                $isQuantityEnough = $checkResultArray['result'] ?? false;

                if (!$isQuantityEnough) {
                    $logData = [
                        'title'            => 'CheckImportTypeTicketQuantity failed.',
                        'customer_id'      => $masterQuote->getCustomerId(),
                        'quote_id'         => $masterQuote->getId(),
                        'quote_item_id'    => $quoteItem->getId(),
                        'product_id'       => $productId,
                        'product_name'     => $quoteItem->getName(),
                        'batch_code'       => $batchCode,
                        'request_quantity' => $requestQuantity,
                        'check_result'     => json_encode($checkResultArray ?? []),
                    ];

                    $this->hotaiCoreCommonHelper->writeLogIfEnabled(
                        $logData,
                        self::LOG_FOLDER,
                        self::DEBUG_LOG_OPTION
                    );

                    throw new LocalizedException(
                        __('Ticket batch setting sale end time check fail.')
                    );
                }
            } catch (LocalizedException $exception) {
                throw $exception;
            } catch (\Throwable $throwable) {
                $exceptionTitle = 'Check import type ticket quantity failed.';

                $logData = [
                    'title'         => $exceptionTitle,
                    'quote_id'      => $masterQuote->getId(),
                    'quote_item_id' => $quoteItem->getId(),
                    'product_id'    => $productId,
                    'exception'     => $throwable->getMessage(),
                ];

                $this->hotaiCoreCommonHelper->writeLogIfEnabled(
                    $logData,
                    self::LOG_FOLDER,
                    self::DEBUG_LOG_OPTION
                );

                throw new LocalizedException(
                    __($exceptionTitle)
                );
            }
        }
    }
}
