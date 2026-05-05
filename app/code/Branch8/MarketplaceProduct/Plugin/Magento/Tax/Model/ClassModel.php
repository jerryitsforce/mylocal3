<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketplaceProduct\Plugin\Magento\Tax\Model;

/**
 * Product tax class source model.
 */
class ClassModel
{
    /**
     * {@inheritdoc}
     */
    public function afterGetClassName($subject, $result)
    {
        return __($result);
    }
}
