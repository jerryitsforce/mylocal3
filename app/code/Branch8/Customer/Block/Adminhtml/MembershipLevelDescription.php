<?php

namespace Branch8\Customer\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;

class MembershipLevelDescription extends AbstractFieldArray
{
    protected $customerGroupRenderer;
    protected $organizationRenderer;

    protected function _prepareToRender()
    {
        $this->addColumn(
            'sort_order',
            [
                'label' => __('Sort Order'),
                'class' => 'validate-digits required-entry',
                'default' => 0
            ]
        );
        $this->addColumn(
            'organization',
            [
                'label' => __('Organization'),
                'class' => 'required-entry',
                'renderer' => $this->_getOrganizationRenderer()
            ]

        );

        $this->addColumn(
            'customer_group',
            [
                'label' => __('Group'),
                'class' => 'required-entry',
                'renderer' => $this->_getCustomerGroupRenderer()
            ]
        );
        $this->addColumn('benefit', ['label' => __('Benefit')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }


    protected function _getOrganizationRenderer()
    {
        if (!$this->organizationRenderer) {
            $this->organizationRenderer = $this->getLayout()->createBlock(
                \Branch8\Customer\Block\Adminhtml\System\Config\Form\Field\OrganizationRenderer::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->organizationRenderer;
    }

    protected function _getCustomerGroupRenderer()
    {
        if (!$this->customerGroupRenderer) {
            $this->customerGroupRenderer = $this->getLayout()->createBlock(
                \Branch8\Customer\Block\Adminhtml\System\Config\Form\Field\CustomerGroupRenderer::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->customerGroupRenderer;
    }


    protected function _prepareArrayRow(DataObject $row)
    {
        $options = [];

        $customerGroup = $row->getCustomerGroup();
        if ($customerGroup) {
            $options['option_' . $this->_getCustomerGroupRenderer()->calcOptionHash($customerGroup)] = 'selected="selected"';
        }


        $organization = $row->getOrganization();
        if ($organization) {
            $options['option_' . $this->_getCustomerGroupRenderer()->calcOptionHash($organization)] = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
