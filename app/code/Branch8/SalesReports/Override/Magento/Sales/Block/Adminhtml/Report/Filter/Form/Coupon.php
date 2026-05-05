<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\SalesReports\Override\Magento\Sales\Block\Adminhtml\Report\Filter\Form;

/**
 * Sales Adminhtml report filter form for coupons report
 *
 * @api
 * @author     Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 * @since 100.0.2
 */
class Coupon extends \Magento\Sales\Block\Adminhtml\Report\Filter\Form\Coupon
{
    /**
     * Prepare form
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        /** @var \Magento\Framework\Data\Form\Element\Fieldset $fieldset */
        $fieldset = $this->getForm()->getElement('base_fieldset');

        if (is_object($fieldset) && $fieldset instanceof \Magento\Framework\Data\Form\Element\Fieldset) {
            // remove unused fields
            $fieldset->removeField('report_type');
            $fieldset->removeField('period_type');
            $fieldset->removeField('show_order_statuses');
            $fieldset->removeField('order_statuses');
            $fieldset->removeField('show_empty_rows');
            $fieldset->removeField('price_rule_type');
            $fieldset->removeField('rules_list');
            $fieldset->addField('coupon_name_list',
                \Branch8\SalesReports\Data\Form\Element\CouponName::class,
                [
                    'name' => 'coupon_name_list',
                    'label' => __('Coupon Name'),
                ]
            );
            $fieldset->addField('coupon_code_list',
                \Branch8\SalesReports\Data\Form\Element\CouponCode::class,
                [
                    'name' => 'coupon_code_list',
                    'label' => __('Coupon Code'),
                ]
            );
            $fieldset->addField(
                'active',
                'select',
                [
                    'name' => 'status',
                    'options' => ['' => __('Any'), '1' => __('Active'), '2' => __('Disabled')],
                    'label' => __('Status'),
                    'value' => ''
                ]
            );
            $this->_renderDependentElement = true;
        }

        return $this;
    }
}
