<?php
namespace Branch8\Customer\Plugin\Block\Address;

use Branch8\OneStepCheckout\Model\Source\HotaiAddressType;

class Edit
{
    /**
     * @param Edit $subject
     * @param string $result
     * @return string
     */
    public function afterGetTitle(\Magento\Customer\Block\Address\Edit $subject, string $result): string
    {
        $hotaiAddressType = $subject->getRequest()->getParam('type');
        $id = $subject->getRequest()->getParam('id');

        if($id && $hotaiAddressType == HotaiAddressType::ADDRESS_TYPE_CONVENIENCE_STORE) {
            return __('修改超商取貨地址');
        } else if($id && $hotaiAddressType != HotaiAddressType::ADDRESS_TYPE_CONVENIENCE_STORE){
            return __('修改宅配地址');
        } else if($hotaiAddressType == HotaiAddressType::ADDRESS_TYPE_CONVENIENCE_STORE){
            return __('新增超商取貨地址');
        } else {
            return __('新增宅配地址');
        }
    }

}
