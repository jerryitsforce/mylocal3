<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Plugin\Magento\Customer\Block\Adminhtml\Edit\Tab\View;

use Branch8\MaskCustomerInformation\Model\Customer\Rule;

use Branch8\MaskCustomerInformation\Model\GlobalPermission;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Address\Mapper;
use Magento\Framework\Exception\NoSuchEntityException;

class PersonalInfoPlugin
{
    /**
     * @var GlobalPermission
     */
    private PermissionInterface $permission;
    /**
     * @var AccountManagementInterface
     */
    private AccountManagementInterface $accountManagement;
    private \Magento\Customer\Helper\Address $addressHelper;
    private Mapper $addressMapper;
    private Rule $rule;

    /**
     * @param PermissionInterface $permission
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param Rule $rule
     * @param Mapper $addressMapper
     */
    public function __construct(
        PermissionInterface                 $permission,
        AccountManagementInterface       $accountManagement,
        \Magento\Customer\Helper\Address $addressHelper,
        Rule                             $rule,
        Mapper                           $addressMapper
    )
    {
        $this->permission = $permission;
        $this->accountManagement = $accountManagement;
        $this->addressHelper = $addressHelper;
        $this->addressMapper = $addressMapper;
        $this->rule = $rule;
    }

    /**
     * Mask information
     * @param \Magento\Customer\Block\Adminhtml\Edit\Tab\View\PersonalInfo $subject
     * @param callable $proceed
     * @param ...$arguments
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetBillingAddressHtml(
        \Magento\Customer\Block\Adminhtml\Edit\Tab\View\PersonalInfo $subject,
        callable                                                     $proceed,
                                                                     ...$arguments
    )
    {
        if ($this->permission->canView()) {
            return $proceed(...$arguments);
        }
        try {
            $address = $this->accountManagement->getDefaultBillingAddress($subject->getCustomer()->getId());
        } catch (NoSuchEntityException $e) {
            return $subject->escapeHtml(__('The customer does not have default billing address.'));
        }

        if ($address === null) {
            return $subject->escapeHtml(__('The customer does not have default billing address.'));
        }
        $flatData = $this->rule->apply($this->addressMapper->toFlatArray($address));
        return $this->addressHelper->getFormatTypeRenderer(
            'html'
        )->renderArray($flatData);
    }
}
