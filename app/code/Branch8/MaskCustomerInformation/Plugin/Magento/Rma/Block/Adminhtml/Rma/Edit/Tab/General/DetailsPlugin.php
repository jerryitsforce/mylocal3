<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Plugin\Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\ViewModel\MaskPersonal;
use Magento\Framework\App\ObjectManager;

/**
 * @api
 * @since 100.0.2
 */
class DetailsPlugin
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
        if ($result !== 'Magento_Rma::edit/general/details.phtml') {
            return $result;
        }
        $subject->setData('mask_view_model', ObjectManager::getInstance()->get(MaskPersonal::class));
        return "Branch8_MaskCustomerInformation::rma/edit/general/details.phtml";
    }
}
