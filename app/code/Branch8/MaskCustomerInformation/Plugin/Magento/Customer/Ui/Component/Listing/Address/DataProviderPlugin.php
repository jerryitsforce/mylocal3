<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Plugin\Magento\Customer\Ui\Component\Listing\Address;

use Branch8\MaskCustomerInformation\Model\Customer\Rule;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;

/**
 * Custom DataProvider for customer addresses listing
 */
class DataProviderPlugin
{
    private PermissionInterface $permission;
    private Rule $rule;

    /**
     * @param PermissionInterface $permission
     * @param Rule $rule
     */
    public function __construct(
        PermissionInterface $permission,
        Rule             $rule
    )
    {
        $this->rule = $rule;
        $this->permission = $permission;
    }

    /**
     * @param $subject
     * @param $result
     * @return array
     */
    public function afterGetData($subject, $result): array
    {
        if ($this->permission->canView()) {
            return $result;
        }
        if (isset($result['items'])) {
            foreach ($result['items'] as $key => $address) {
                $result['items'][$key] = $this->rule->apply($address);
            }
        }
        return $result;
    }
}
