<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Plugin\Magento\Reports\Model\ResourceModel\Product\Index;

class AbstractIndex
{

    public function beforeSave(
        \Magento\Reports\Model\ResourceModel\Product\Index\AbstractIndex $subject,
        $object
    ): array {
        if(empty($object->getAddedAt())){
            $object->setAddedAt((new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        }
        return [$object];
    }
}