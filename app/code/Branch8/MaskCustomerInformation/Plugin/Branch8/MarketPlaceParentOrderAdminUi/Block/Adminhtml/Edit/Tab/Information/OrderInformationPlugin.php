<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Plugin\Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Tab\Information;

use Branch8\MaskCustomerInformation\Model\GlobalPermission;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\ViewModel\MaskPersonal;
use Magento\Framework\App\ObjectManager;

class OrderInformationPlugin
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
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetTemplate($subject, $result)
    {
        if ($this->permission->canView()) {
            return $result;
        }
        if ($result !== 'Branch8_MarketPlaceParentOrderAdminUi::tab/view/information.phtml') {
            return $result;
        }
        $subject->setData('mask_view_model', ObjectManager::getInstance()->get(MaskPersonal::class));
        return "Branch8_MaskCustomerInformation::parent_order/tab/view/information.phtml";
    }
}
