<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Hopes\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Admin config field for SIN API: file name to seller code mapping.
 * Each row = file name (output key) + seller code. User can add more as needed.
 */
class SinSellerGroups extends AbstractFieldArray
{
    /**
     * @inheritdoc
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('file_name', [
            'label' => __('File Name'),
            'class' => 'required-entry',
            'style' => 'width: 80px;'
        ]);
        $this->addColumn('seller_code', [
            'label' => __('Seller Code'),
            'class' => 'required-entry',
            'style' => 'width: 120px;'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
