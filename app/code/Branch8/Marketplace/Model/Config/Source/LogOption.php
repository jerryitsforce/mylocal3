<?php

declare(strict_types=1);

namespace Branch8\Marketplace\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogOption implements OptionSourceInterface
{
    /**
     * Return loggable class options for Marketplace module.
     *
     * Labels are displayed with a category prefix, while values store only the class short name.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'FixMissingRecordMarketPlaceSaleList', 'label' => __('Command_FixMissingRecordMarketPlaceSaleList (var/log/Marketplace/FixMissingRecordMarketPlaceSaleList/Y_m_d.log)')],

            ['value' => 'CancelOrderAction', 'label' => __('Model_CancelOrderAction (var/log/Marketplace/CancelOrderAction/Y_m_d.log)')],
            ['value' => 'OrderFailedDeliveryHandle', 'label' => __('Model_OrderFailedDeliveryHandle (var/log/Marketplace/OrderFailedDeliveryHandle/Y_m_d.log)')],
            ['value' => 'BuildMarketPlaceSaleListRecord', 'label' => __('Model_BuildMarketPlaceSaleListRecord (var/log/Marketplace/BuildMarketPlaceSaleListRecord/Y_m_d.log)')],
            ['value' => 'GetTrackingNumberFromCsv', 'label' => __('Model_GetTrackingNumberFromCsv (var/log/Marketplace/GetTrackingNumberFromCsv/Y_m_d.log)')],

            ['value' => 'Email', 'label' => __('Helper_Email (var/log/Marketplace/Email/Y_m_d.log)')],
            ['value' => 'Shipment', 'label' => __('Helper_Shipment (var/log/Marketplace/Shipment/Y_m_d.log)')],

            ['value' => 'InvoicePay', 'label' => __('Observer_InvoicePay (var/log/Marketplace/InvoicePay/Y_m_d.log)')],

            ['value' => 'View', 'label' => __('Block_View (var/log/Marketplace/View/Y_m_d.log)')],
            ['value' => 'Items', 'label' => __('Block_Items (var/log/Marketplace/Items/Y_m_d.log)')],

            ['value' => 'PickupNumber', 'label' => __('Controller_PickupNumber (var/log/Marketplace/PickupNumber/Y_m_d.log)')],
            ['value' => 'SaveTrackingNumber', 'label' => __('Controller_SaveTrackingNumber (var/log/Marketplace/SaveTrackingNumber/Y_m_d.log)')],
            ['value' => 'CreateShipment', 'label' => __('Controller_CreateShipment (var/log/Marketplace/CreateShipment/Y_m_d.log)')],
            ['value' => 'Cancel', 'label' => __('Controller_Cancel (var/log/Marketplace/Cancel/Y_m_d.log)')],
            ['value' => 'FailedDelivery', 'label' => __('Controller_FailedDelivery (var/log/Marketplace/FailedDelivery/Y_m_d.log)')],
            ['value' => 'DownloadOrderNotification', 'label' => __('Controller_DownloadOrderNotification (var/log/Marketplace/DownloadOrderNotification/Y_m_d.log)')],
            ['value' => 'GetCarriers', 'label' => __('Controller_GetCarriers (var/log/Marketplace/GetCarriers/Y_m_d.log)')],
            ['value' => 'CheckWaybills', 'label' => __('Controller_CheckWaybills (var/log/Marketplace/CheckWaybills/Y_m_d.log)')],
            ['value' => 'Validate', 'label' => __('Controller_Validate (var/log/Marketplace/Validate/Y_m_d.log)')],
            ['value' => 'Execute', 'label' => __('Controller_Execute (var/log/Marketplace/Execute/Y_m_d.log)')],
            ['value' => 'Upload', 'label' => __('Controller_Upload (var/log/Marketplace/Upload/Y_m_d.log)')],

            ['value' => 'OrdersHistoryDataProvider', 'label' => __('Ui_OrdersHistoryDataProvider (var/log/Marketplace/OrdersHistoryDataProvider/Y_m_d.log)')],
            ['value' => 'Skus', 'label' => __('Ui_Skus (var/log/Marketplace/Skus/Y_m_d.log)')],

            ['value' => 'CustomerRegisterSuccessObserver', 'label' => __('Plugin_CustomerRegisterSuccessObserver (var/log/Marketplace/CustomerRegisterSuccessObserver/Y_m_d.log)')],
            ['value' => 'Dashboard', 'label' => __('Plugin_Dashboard (var/log/Marketplace/Dashboard/Y_m_d.log)')],
            ['value' => 'MarketplaceCustomNotification', 'label' => __('Plugin_MarketplaceCustomNotification (var/log/Marketplace/MarketplaceCustomNotification/Y_m_d.log)')],
            ['value' => 'Tunnel', 'label' => __('Plugin_Tunnel (var/log/Marketplace/Tunnel/Y_m_d.log)')],
            ['value' => 'Add', 'label' => __('Override_Add (var/log/Marketplace/Add/Y_m_d.log)')],
        ];
    }
}

