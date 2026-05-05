<?php

namespace Branch8\Catalog\Plugin\Magento\ImportExport\Model;

use Magento\ImportExport\Model\Import;

class BehaviorBasic
{
    /**
     * @inheritdoc
     */
    public function afterToArray($subject, $result)
    {
        if (isset($result[Import::BEHAVIOR_REPLACE])) {
            $result[Import::BEHAVIOR_REPLACE] = __('Import Replace');
        }
        return $result;
    }

}
