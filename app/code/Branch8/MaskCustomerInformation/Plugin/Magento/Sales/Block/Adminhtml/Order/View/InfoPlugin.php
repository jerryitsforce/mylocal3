<?php

namespace Branch8\MaskCustomerInformation\Plugin\Magento\Sales\Block\Adminhtml\Order\View;

use Branch8\MaskCustomerInformation\Model\GlobalPermission;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\ViewModel\MaskPersonal;
use Magento\Framework\App\ObjectManager;

/**
 * @api
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class InfoPlugin
{
    private PermissionInterface $permission;

    /**
     * @param PermissionInterface $permission
     */
    public function __construct(
        PermissionInterface $permission

    )
    {
        $this->permission = $permission;
    }

    /**
     * Retrieve required options from parent
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetTemplate($subject, $result)
    {
        if ($this->permission->canView()) {
            return $result;
        }
        if ($result !== 'Magento_Sales::order/view/info.phtml') {
            return $result;
        }
        $subject->setData('mask_view_model', ObjectManager::getInstance()->get(MaskPersonal::class));
        return "Branch8_MaskCustomerInformation::order/view/info.phtml";
    }
}
