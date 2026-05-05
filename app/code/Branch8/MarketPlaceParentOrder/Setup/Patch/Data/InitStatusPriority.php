<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketPlaceParentOrder\Setup\Patch\Data;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

/**
 * Class ChangePriceAttributeDefaultScope
 * @package Magento\Catalog\Setup\Patch
 */
class InitStatusPriority implements DataPatchInterface, PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * ChangePriceAttributeDefaultScope constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection       $resourceConnection
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        /** @var CategorySetup $categorySetup */
        $table = $this->resourceConnection->getConnection()->getTableName('sales_parent_order_status_priority');
        $sales_parent_order_status_priority = array(
            array('status' => 'pending', 'priority' => '1'),
            array('status' => 'holded', 'priority' => '2'),
            array('status' => 'rejected', 'priority' => '3'),
            array('status' => 'pending_payment', 'priority' => '4'),
            array('status' => 'pending_paypal', 'priority' => '5'),
            array('status' => 'ecpay_pending_payment', 'priority' => '6'),
            array('status' => 'payment_review', 'priority' => '7'),
            array('status' => 'paypal_reversed', 'priority' => '8'),
            array('status' => 'paypal_canceled_reversal', 'priority' => '9'),
            array('status' => 'received', 'priority' => '10'),
            array('status' => 'fraud', 'priority' => '11'),
            array('status' => 'processing', 'priority' => '12'),
            array('status' => 'shipping', 'priority' => '13'),
            array('status' => 'arrived', 'priority' => '14'),
            array('status' => 'complete', 'priority' => '16'),
            array('status' => 'canceled', 'priority' => '17'),
            array('status' => 'closed', 'priority' => '18'),
            array('status' => 'closed_refund_success', 'priority' => '19')
        );
        $this->resourceConnection->getConnection()->insertOnDuplicate(
            $table,
            $sales_parent_order_status_priority,
            ['status', 'priority']
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '0.1.1';
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}
