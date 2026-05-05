<?php

namespace Branch8\EventTicket\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;

class CustomerSerial extends \Magento\ImportExport\Model\Source\Import\AbstractBehavior
{
    /**
     * @inheritdoc
     */
    public function toArray()
    {
        return [
            Import::BEHAVIOR_APPEND => __('Add/Update')
        ];
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return 'mapping_one_id_serial';
    }

    /**
     * @inheritdoc
     */
    public function getNotes($entityCode)
    {
//        $messages = ['catalog_product' => [
//            Import::BEHAVIOR_APPEND => __(
//                "New product data is added to the existing product data for the existing entries in the database. "
//                . "All fields except sku can be updated."
//            ),
//            Import::BEHAVIOR_REPLACE => __(
//                "The existing product data is replaced with new data. <b>Exercise caution when replacing data "
//                . "because the existing product data will be completely cleared and all references "
//                . "in the system will be lost.</b>"
//            ),
//            Import::BEHAVIOR_DELETE => __(
//                "Any entities in the import data that already exist in the database are deleted from the database."
//            ),
//        ]];
        return isset($messages[$entityCode]) ? $messages[$entityCode] : [];
    }
}