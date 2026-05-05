<?php

namespace Branch8\HotaiCore\Model\Ticket;

class AttributeCodes
{
    const ALL_CODES = [
        'exchange_url',
        'exchange_hint',
        'is_offline_operation',
        'display_serial_number',
        'display_barcode',
        'barcode_type',
        'return_ticket_value',

        'edenred_order_number',
        'edenred_product_code',
        'edenred_merchant_code',

        'qware_guid',
        'qware_sale_start_date',
        'qware_sale_end_date',

        'openhub_product_id'
    ];

    const SKIP_CODES_YOXI = [
        'exchange_hint',
        'is_offline_operation',
        'barcode_type',

        'edenred_order_number',
        'edenred_product_code',
        'edenred_merchant_code',

        'qware_guid',
        'qware_sale_start_date',
        'qware_sale_end_date',

        'openhub_product_id'
    ];

    const SKIP_CODES_EDENRED = [
        'exchange_hint',
        'is_offline_operation',
        'qware_guid',
        'qware_sale_start_date',
        'qware_sale_end_date',

        'openhub_product_id'
    ];

    const SKIP_CODES_FAMILY_BONUS_PIN = [
        'exchange_hint',
        'is_offline_operation',

        'edenred_order_number',
        'edenred_product_code',
        'edenred_merchant_code',

        'qware_guid',
        'qware_sale_start_date',
        'qware_sale_end_date',

        'openhub_product_id'
    ];

    const SKIP_CODES_GENERAL_NOTIFY_TICKET = [
        'is_offline_operation',

        'edenred_order_number',
        'edenred_product_code',
        'edenred_merchant_code',

        'qware_guid',
        'qware_sale_start_date',
        'qware_sale_end_date',

        'openhub_product_id'
    ];

    const SKIP_CODES_GENERAL_NON_NOTIFY_TICKET = [
        'return_ticket_value',

        'edenred_order_number',
        'edenred_product_code',
        'edenred_merchant_code',

        'qware_guid',
        'qware_sale_start_date',
        'qware_sale_end_date',

        'openhub_product_id'
    ];

    const SKIP_CODES_QWARE = [
        'exchange_hint',
        'is_offline_operation',

        'edenred_order_number',
        'edenred_product_code',
        'edenred_merchant_code',

        'openhub_product_id'
    ];

    const SKIP_CODES_OPENHUB = [
        'exchange_hint',
        'is_offline_operation',

        'edenred_order_number',
        'edenred_product_code',
        'edenred_merchant_code',

        'qware_guid',
        'qware_sale_start_date',
        'qware_sale_end_date'
    ];
}
