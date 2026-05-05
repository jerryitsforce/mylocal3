<?php
namespace Branch8\CustomerGraphQl\Model\Resolver;

use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Fullname implements ResolverInterface
{
    protected \Magento\Customer\Helper\View $customerViewHelper;
    protected \Branch8\Customer\Model\GetCustomerNickname $getCustomerNickname;

    public function __construct(
        \Magento\Customer\Helper\View $customerViewHelper,
        \Branch8\Customer\Model\GetCustomerNickname $getCustomerNickname
    ) {
        $this->customerViewHelper = $customerViewHelper;
        $this->getCustomerNickname = $getCustomerNickname;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
              $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var Customer $customer */
        $customer = $value['model'];
        return $this->getCustomerNickname->getCustomerNickname($customer, true);
    }

}
