<?php

namespace Branch8\HotaiPoint\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public const LOG_COMMON = 'hotai_point_common';
    public const LOG_API = 'hotai_point_api';
    public const LOG_API_INTEGRATION = 'hotai_point_api_integration';
    public const LOG_API_GET_POINT_DATA = 'hotai_point_api_get_point_data';
    public const LOG_SYNC_API_RECORD = 'hotai_point_sync_api_record';
    public const LOG_API_COMMIT_FOR_PAID_ORDER = 'hotai_point_api_commit_for_paid_order';
    public const LOG_RE_DEDUCTION_POINT_FOR_PAYMENT_RETRY = 'hotai_point_re_deduction_point_for_payment_retry';
    public const LOG_SALES_ORDER_PLACE_AFTER_OBSERVER = 'hotai_point_sales_order_place_after_observer';
    public const LOG_DEDUCTION_RETRY = 'hotai_point_deduction_retry';
    public const LOG_DEDUCTION_POINT_CANCEL_FOR_PENDING_PAYMENT_ORDERS = 'hotai_point_deduction_point_cancel_for_pending_payment_orders';
    public const LOG_API_RECORD_WRITER_OBSERVER = 'hotai_point_api_record_writer_observer';
    public const LOG_AJAX = 'hotai_point_ajax';
    public const LOG_CONSOLE_SYNC_API_RECORD = 'hotai_point_console_sync_api_record';
    public const LOG_ADD_POINT_TO_COMPLETE_SALES_ORDER = 'hotai_point_add_point_to_complete_sales_order';
    public const LOG_DEDUCTION_CANCEL_RETRY = 'hotai_point_deduction_cancel_retry';
    public const LOG_REMOVE_SYNC_BACKUP_FILE = 'hotai_point_remove_sync_backup_file';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::LOG_COMMON, 'label' => __('Common helper logs(var/log/HotaiPoint/{subfolder}/{Y_m_d}.log)')],
            ['value' => self::LOG_API, 'label' => __('API error logs(var/log/HotaiPoint/Api/ApiRequestError/{Y_m_d}.log)')],
            ['value' => self::LOG_API_INTEGRATION, 'label' => __('API integration logs(var/log/HotaiPoint/Api/Integration/{Y_m_d}.log)')],
            ['value' => self::LOG_API_GET_POINT_DATA, 'label' => __('API get-point-data logs(var/log/HotaiPoint/Api/GetPoint/{Y_m_d}.log)')],
            ['value' => self::LOG_SYNC_API_RECORD, 'label' => __('Cron SyncApiRecord(var/log/HotaiPoint/Cron/SyncApiRecord/{Y_m_d}.log)')],
            ['value' => self::LOG_API_COMMIT_FOR_PAID_ORDER, 'label' => __('Observer ApiCommitForPaidOrder(var/log/HotaiPoint/Observer/ApiCommitForPaidOrder/{Y_m_d}.log)')],
            ['value' => self::LOG_RE_DEDUCTION_POINT_FOR_PAYMENT_RETRY, 'label' => __('Observer ReDeductionPointForPaymentRetry(var/log/HotaiPoint/Observer/ReDeductionPointForPaymentRetryObserver/{Y_m_d}.log)')],
            ['value' => self::LOG_SALES_ORDER_PLACE_AFTER_OBSERVER, 'label' => __('Observer SalesOrderPlaceAfterObserver(var/log/HotaiPoint/Observer/SalesOrderPlaceAfterObserver/{Y_m_d}.log)')],
            ['value' => self::LOG_DEDUCTION_RETRY, 'label' => __('Cron DeductionRetry(var/log/HotaiPoint/Cron/DeductionRetry/{Y_m_d}.log)')],
            ['value' => self::LOG_DEDUCTION_POINT_CANCEL_FOR_PENDING_PAYMENT_ORDERS, 'label' => __('Cron DeductionPointCancelForPendingPaymentOrders(var/log/HotaiPoint/Cron/DeductionPointCancelForPendingPaymentOrders/{Y_m_d}.log)')],
            ['value' => self::LOG_API_RECORD_WRITER_OBSERVER, 'label' => __('Observer ApiRecordWriterObserver(var/log/HotaiPoint/Observer/ApiRecordWriterObserver/{Y_m_d}.log)')],
            ['value' => self::LOG_AJAX, 'label' => __('Model Ajax(var/log/HotaiPoint/Actions/{actionName}/Ajax/{Y_m_d}.log)')],
            ['value' => self::LOG_CONSOLE_SYNC_API_RECORD, 'label' => __('Console SyncApiRecord(var/log/HotaiPoint/Cron/SyncApiRecord/{Y_m_d}.log)')],
            ['value' => self::LOG_ADD_POINT_TO_COMPLETE_SALES_ORDER, 'label' => __('Cron AddPointToCompleteSalesOrder(var/log/HotaiPoint/Cron/AddPointToCompleteSalesOrder/{Y_m_d}.log)')],
            ['value' => self::LOG_DEDUCTION_CANCEL_RETRY, 'label' => __('Cron DeductionCancelRetry(var/log/HotaiPoint/Cron/DeductionCancelRetry/{Y_m_d}.log)')],
            ['value' => self::LOG_REMOVE_SYNC_BACKUP_FILE, 'label' => __('Cron RemoveSyncBackupFile(var/log/HotaiPoint/Cron/RemoveSyncBackupFile/{Y_m_d}.log)')],
        ];
    }
}
