<?php
namespace Branch8\CustomerGraphQl\Model\Resolver;

use Branch8\Customer\Helper\Ticket;
use Branch8\HotaiCore\Model\Ticket\Status;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class Label implements ResolverInterface
{
    protected Ticket $generalHelperTicket;

    public function __construct(
        private readonly \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
    ) {
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
        $customerGroupId = $customer->getGroupId();
        $group = $this->groupRepository->getById($customerGroupId);
        return $group->getExtensionAttributes()->getLabel();
    }

}
