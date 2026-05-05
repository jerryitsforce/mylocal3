<?php
namespace Ecpay\General\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // logistic
        if (!$installer->tableExists('ecpay_logistic')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('ecpay_logistic')
            )
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Entity Id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    0,
                    [],
                    'Order Id'
                )
                ->addColumn(
                    'merchant_trade_no',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'MerchantTradeNo'
                )
                ->addColumn(
                    'rtn_code',
                    Table::TYPE_INTEGER,
                    0,
                    [],
                    'RtnCode'
                )
                ->addColumn(
                    'rtn_msg',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'RtnMsg'
                )
                ->addColumn(
                    'all_pay_logistics_id',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'AllPayLogisticsID'
                )
                ->addColumn(
                    'logistics_type',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'LogisticsType'
                )
                ->addColumn(
                    'logistics_sub_type',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'LogisticsSubType'
                )
                ->addColumn(
                    'booking_note',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'BookingNote'
                )
                ->addColumn(
                    'cvs_payment_no',
                    Table::TYPE_TEXT,
                    20,
                    [],
                    'CVSPaymentNo'
                )
                ->addColumn(
                    'cvs_validation_no',
                    Table::TYPE_TEXT,
                    20,
                    [],
                    'CVSValidationNo'
                )
                ->setComment('Ecpay Logistic');

            $installer->getConnection()->createTable($table);
        }

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_logistic_cvs_store_id',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 10,
                'comment' => 'CVSStoreID',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_logistic_cvs_store_name',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 10,
                'comment' => 'CVSStoreName',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_logistic_cvs_store_address',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 60,
                'comment' => 'CVSAddress',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_logistic_cvs_store_telephone',
            [
                'type' => 'text',
                'nullable' => true,
                'length'    => 20,
                'comment' => 'CVSTelephone',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_logistic_auto_tag',
            [
                'type' => 'integer',
                'nullable' => false,
                'default' => 0,
                'comment' => 'Logistic Auto Tag',
            ]
        );

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_shipping_tag',
            [
                'type' => 'integer',
                'nullable' => false,
                'default' => 0,
                'comment' => 'Shipping Tag',
            ]
        );

        // payment

        $installer->getConnection()->addColumn(
            $installer->getTable('sales_order'),
            'ecpay_payment_complete_tag',
            [
                'type' => 'integer',
                'nullable' => false,
                'default' => 0,
                'comment' => 'Payment Complete Tag',
            ]
        );

        if (!$installer->tableExists('ecpay_payment_info')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('ecpay_payment_info')
            )
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary'  => true,
                        'unsigned' => true,
                    ],
                    'Entity Id'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    0,
                    [],
                    'Order Id'
                )
                ->addColumn(
                    'merchant_id',
                    Table::TYPE_TEXT,
                    10,
                    [],
                    'MerchantID '
                )
                ->addColumn(
                    'merchant_trade_no',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'MerchantTradeNo'
                )
                ->addColumn(
                    'store_id',
                    Table::TYPE_TEXT,
                    10,
                    [],
                    'StoreID '
                )
                ->addColumn(
                    'rtn_code',
                    Table::TYPE_INTEGER,
                    0,
                    [],
                    'RtnCode'
                )
                ->addColumn(
                    'rtn_msg',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'RtnMsg'
                )
                ->addColumn(
                    'trade_no',
                    Table::TYPE_TEXT,
                    255,
                    [],
                    'TradeNo'
                )
                ->addColumn(
                    'trade_amt',
                    Table::TYPE_INTEGER,
                    0,
                    [],
                    'TradeAmt'
                )
                ->addColumn(
                    'payment_type',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'PaymentType'
                )
                ->addColumn(
                    'trade_date',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'TradeDate'
                )
                ->addColumn(
                    'custom_field1',
                    Table::TYPE_TEXT,
                    100,
                    [],
                    'CustomField1'
                )
                ->addColumn(
                    'custom_field2',
                    Table::TYPE_TEXT,
                    100,
                    [],
                    'CustomField2'
                )
                ->addColumn(
                    'custom_field3',
                    Table::TYPE_TEXT,
                    100,
                    [],
                    'CustomField3'
                )
                ->addColumn(
                    'custom_field4',
                    Table::TYPE_TEXT,
                    100,
                    [],
                    'CustomField4'
                )
                ->addColumn(
                    'bank_code',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'BankCode'
                )
                ->addColumn(
                    'vaccount',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'vAccount'
                )
                ->addColumn(
                    'expire_date',
                    Table::TYPE_TEXT,
                    100,
                    [],
                    'ExpireDate'
                )
                ->addColumn(
                    'payment_no',
                    Table::TYPE_TEXT,
                    20,
                    [],
                    'PaymentNo'
                )
                ->addColumn(
                    'barcode1',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Barcode1'
                )
                ->addColumn(
                    'barcode2',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Barcode2'
                )
                ->addColumn(
                    'barcode3',
                    Table::TYPE_TEXT,
                    50,
                    [],
                    'Barcode3'
                )
                ->setComment('Ecpay Payment Info');

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
