<?php

namespace Branch8\Marketplace\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface{

    public function upgrade( SchemaSetupInterface $setup, ModuleContextInterface $context ) {
        $installer = $setup;

        $installer->startSetup();

        if(version_compare($context->getVersion(), '1.0.1', '<')) {
            $sql = "CREATE TABLE `marketplace_custom_notification` (`entity_id` INT NOT NULL AUTO_INCREMENT , `seller_id` INT NOT NULL, `description` VARCHAR(255) NOT NULL , `url` VARCHAR(255) NULL DEFAULT NULL , `seller_pending_notification` SMALLINT(5) NOT NULL DEFAULT '0' , PRIMARY KEY (`entity_id`)) ENGINE = InnoDB;";
            $installer->getConnection()->query($sql);
        }
        if(version_compare($context->getVersion(), '1.0.2', '<')) {
            $sql = 'CREATE TABLE branch8_shipping_method_update_queue (`entity_id` INT NOT NULL AUTO_INCREMENT , `carrier` VARCHAR(255) NOT NULL, `store_id` SMALLINT NOT NULL , `created_at` DATETIME NOT NULL , `status` SMALLINT NOT NULL , `updated_at` DATETIME NULL DEFAULT NULL, PRIMARY KEY (`entity_id`)) ENGINE = InnoDB;';
            $installer->getConnection()->query($sql);
        }



        $installer->endSetup();
    }

}