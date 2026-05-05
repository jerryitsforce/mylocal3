<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderChildLink\Model\Source\Import\Behavior;

use Magento\ImportExport\Model\Import;

/**
 * Import behavior source model used for defining the behaviour during the import.
 *
 * @api
 * @since 100.0.2
 */
class Basic extends \Magento\ImportExport\Model\Source\Import\AbstractBehavior
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
        return 'parent_order_sub_order_link';
    }

    /**
     * @inheritdoc
     */
    public function getNotes($entityCode)
    {
        $messages = ['parent_order_sub_order_link' => [
            Import::BEHAVIOR_APPEND => __(
                "Link parent order and sub order , create new when \"ParentOrderNumber\" is empty "
            ),
        ]];
        return $messages[$entityCode] ?? [];
    }
}
