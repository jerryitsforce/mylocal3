<?php

namespace Branch8\MarketPlaceSeller\Cron;

use Magento\Store\Model\ScopeInterface;


class ReturnDailyNotification extends OrderDailyNotification
{
    const ENABLE = 'email_notification/return_report_settings/enabled';
    const EMAIL_TO = 'email_notification/return_report_settings/emails';
    const EMAIL_TEMPLATE = 'email_notification/return_report_settings/template';
    const EMAILS = 'email_notification/return_report_settings/emails';

    const COPY_TO = 'email_notification/return_report_settings/copy_to';
    const FILE_NAME_PREFIX = 'email_notification/return_report_settings/file_prefix';
    const ORDER_PREFIX = 'hotai_order';

    const HK_HEADER = [
        "項次",
        "訂單成立日期",
        "訂單編號",
        "訂單狀態",
        "商品編號",
        "廠商貨號",
        "規格標題 | 規格名稱",
        "商品名稱",
        "數量",
        "售價",
        "付款狀態",
        "訂貨人姓名",
        "訂貨人電話",
        "收件人姓名",
        "收件人電話",
        "配送方式",
        "超商店號",
        "超商門市名稱",
        "配送地址",
        "訂單備註說明",
        "退換貨狀態",
        "退貨申請日",
        "退貨單號",
        "1.0 訂單編號",
    ];

    protected $pendingPaymentStatus = [
        'pending_payment',
        'ecpay_pending_payment',
        'pending',
        'pending_paypal'
    ];

    public function execute()
    {
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
            $this->logger->info('-----------------------------Cron Job Return/Exchange daily notification to seller: Start ----------------------------------');
        }
        try {
            // Check if the feature is enabled
            $isEnabled = $this->scopeConfig->getValue(
                self::ENABLE,
                ScopeInterface::SCOPE_STORE
            );

            if (!$isEnabled) {
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                    $this->logger->info('-----------------------------Cron Job Return/Exchange daily notification to seller: END due to this function is disable ----------------------------------');
                }
                return;
            }

            if (!$this->getConfigValue(self::EMAILS)) {
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                    $this->logger->info('-----------------------------Cron Job Return/Exchange daily notification to seller: END due to there is no receiver is configured ----------------------------------');
                }
                return;
            }

            // Fetch return/exchange requests for the previous day
            $yesterday = $this->timezone->date()->modify('-1 day');

            // Convert to UTC (since DB stores in UTC)
            $from = $this->timezone->convertConfigTimeToUtc($yesterday->format('Y-m-d 00:00:00'));
            $to = $this->timezone->convertConfigTimeToUtc($yesterday->format('Y-m-d 23:59:59'));

            $orderCollection = $this->orderFactory->create()->getCollection()->addFieldToSelect('increment_id');
            $orderCollection->getSelect()->join(['rma_detail' => 'marketplace_rma_details'],
                'main_table.entity_id = rma_detail.order_id');
            $orderCollection->getSelect()->join(['rma_item' => 'marketplace_rma_items'],
                'rma_detail.id = rma_item.rma_id',
                ['rma_item.qty']
            );
            $orderCollection->getSelect()->join(['item' => 'sales_order_item'],
                'rma_item.item_id = item.item_id',
                ['rma_item.item_id','item.sku','item.name','item.price_incl_tax', 'item.origin_sku']
            );
            $orderCollection->addFieldToFilter('main_table.increment_id',['like' => '%'.self::ORDER_PREFIX.'%'])
                ->addFieldToFilter('rma_detail.created_date',['gteq' => $from])
                ->addFieldToFilter('rma_detail.created_date',['lteq' => $to])
                ->addFieldToFilter('rma_detail.seller_id', ['notnull' => false])
                ->distinct('main_table.entity_id');

            if (empty($orderCollection)) {
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                    $this->logger->info('-----------------------------Cron Job Return/Exchange daily notification to seller: END due to there is no data for today ----------------------------------');
                }
                return; // No data, no email needed
            }
            $this->appState->emulateAreaCode(
                \Magento\Framework\App\Area::AREA_FRONTEND,
                [$this, 'sendNotificationToAdmin'],
                [$orderCollection]
            );
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->info("Daily report email sent successfully.");
            }
        } catch (\Exception $e) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->error("Error sending daily report email: " . $e->getMessage());
            }
        }
    }

    /**
     * @param $itemCollection
     * @return $this
     */
    public function sendNotificationToAdmin($itemCollection)
    {
        $orderData = [];
        $index = 0;
        try {
            foreach ($itemCollection as $item) {
                $index += 1;
                $orderId = $item->getData('order_id');
                try {
                    $orderModel = $this->orderFactory->create()->load($orderId);
                } catch (\Exception $e) {
                    if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                        $this->logger->error("The seller " . $item->getData('seller_id') . " linked to an order which does not exist. " . $item->getData('order_id'));
                        $this->logger->critical($e);
                    }
                    continue;
                }
                $oldOrderNumber = str_contains($orderModel->getIncrementId(), 'hotai_order') !== false ? $orderModel->getIncrementId() : null;
                if ($oldOrderNumber) {
                    $oldOrderNumber = str_replace('hotai_order_', "", $oldOrderNumber);
                }
                $billingAddress = $orderModel->getBillingAddress();
                $shippingAddress = $orderModel->getShippingAddress();
                $paymentStatus = !in_array($orderModel->getStatus(),$this->pendingPaymentStatus) ? "已付款": null;
                list($rmaApplicationDate, $rmaOrderId, $rmaStatus) = $this->getRmaData($orderId, $item->getProductId());
                $productId = $item->getData('product_id');
                $productModel = $this->productFactory->create()->load($productId);
                if (!$productModel->getId()) {
                    if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                        $this->logger->info('Order ' . $item->getData('increment_id') . ' with product ID #' . $productId . ' is not exist');
                    }
                    continue;
                }
                if ($item->getData('product_id')) {
                    $specialNameStr = $this->getSpecificationName($item);
                    $finalShippingAddress = null;
                    if ($shippingAddress) {
                        if ($orderModel->getShippingMethod() == 'hotai_711_hotai_711') {
                            $finalShippingAddress = $shippingAddress->getStreet()[0];
                        } else {
                            $finalShippingAddress = $shippingAddress->getRegion().$shippingAddress->getCity().$shippingAddress->getStreet()[0];
                        }
                    }
                    $originSku = $this->processColumnData($item->getData());
                    $ccShopNumber =  $billingAddress->getData('cvs_store_code');
                    $ccShopName =  $billingAddress->getData('cvs_store_name');
                    if ($orderModel->getShippingMethod() == 'hotai_delivery_hotai_delivery') {
                        $ccShopName = null;
                        $ccShopNumber = null;
                    }
                    $orderData[] = [
                        'index' => $index,
                        'purchased_data' => $this->timezone->date($orderModel->getCreatedAt())->format('Y-m-d H:i:s'),
                        'order_number' => $orderModel->getIncrementId(),
                        'order_status' => $orderModel->getStatusLabel() ?? $orderModel->getStatus(),
                        'sku' => $productModel->getSku(),
                        'original_sku' => $originSku,
                        'specification_name' => $specialNameStr,
                        'item_name' => $productModel->getName(),
                        'qty' => $item->getData('qty'),
                        'selling_price' => $item->getData('price_incl_tax'),
                        'payment_status' => $paymentStatus,
                        'billing_name' => $billingAddress->getFirstname(),
                        'billing_phone_number' => $billingAddress->getTelephone(),
                        'shipping_name' => $shippingAddress?->getFirstname(),
                        'shipping_phone_number' => $shippingAddress?->getTelephone(),
                        'shipping_method' => $orderModel->getShippingDescription(),
                        'c&c_shop_number' => $ccShopNumber,
                        'c&c_shop_name' => $ccShopName,
                        'shipping_address' => $finalShippingAddress,
                        'Note' => $orderModel->getData('order_note'),
                        'rma_status' => __($rmaStatus),
                        'return_date' => $rmaApplicationDate,
                        'return_order_number' => $rmaOrderId,
                        'old_order_number' => $oldOrderNumber,
                    ];
                }
            }
            if (!empty($orderData)) {
                $this->sendEmailNotification($orderData);
            } else {
                if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                    $this->logger->error("There is no data for today report");
                }
            }
        } catch (\Exception $e) {
            if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceseller', 'marketplaceemaillog')) {
                $this->logger->critical($e);
            }
        }
        return $this;

    }

    /**
     * @param $data
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendEmailNotification($data)
    {
        $prefix = $this->getConfigValue(self::FILE_NAME_PREFIX);
        $datetime = $this->timezone->date()->format('Ymd_His');
        $fileName = $prefix . "_" . $datetime;
        $fileDirectoryPath = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR)
            . "/return_notification_to_seller/";
        $attachment = $this->setExcelContent($data, $fileDirectoryPath, $fileName);
        $emailTemplateVariables = [];
        $emailTemplateVariables['base_url'] = $this->url->getBaseUrl().'marketplace/account/dashboard';
        if ($this->getConfigValue(self::EMAILS)) {
            $emails = $this->getConfigValue(self::EMAILS);
            $receivers = $emails ? explode(';', trim($emails)) : [];
        }

        // Set the sender information (can use default Magento senders or custom)
        $sender = [
            'name' => $this->scopeConfig->getValue('trans_email/ident_sales/name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'email' => $this->scopeConfig->getValue('trans_email/ident_sales/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];
        $result = $this->emailBuilder->sendEmail(self::EMAIL_TEMPLATE, $emailTemplateVariables, $sender, $receivers, $attachment);
    }

    /**
     * @param $data
     * @param $fileDirectoryPath
     * @param $fileName
     * @return array
     */
    public function setExcelContent($data, $fileDirectoryPath, $fileName)
    {
        $content[] = self::HK_HEADER;
        $content = array_merge($content, $data);
        if (!is_dir($fileDirectoryPath)) {
            mkdir($fileDirectoryPath, 0777, true);
        }
        $excelFileName = $fileName . ".xlsx";
        $filePath = $fileDirectoryPath . $excelFileName;
        if($this->spreadsheet->getSheetCount() > 0){
            $this->spreadsheet->removeSheetByIndex(0);
        }
        $sheet = $this->spreadsheet->createSheet(0);
        $sheet->fromArray($content, NULL, 'A1');
        $this->xlsx->create([$sheet])->save($filePath);
        return ["path" => $filePath, "file_name" => $fileName];
    }
}
