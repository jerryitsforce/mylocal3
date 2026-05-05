<?php
namespace Branch8\CustomerGraphQl\Model\Resolver;

use Branch8\Customer\Helper\Ticket;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Branch8\HotaiCore\Model\Ticket\Status;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\View\Page\Config;

class Icon implements ResolverInterface
{

    public function __construct(
        private readonly \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        private readonly \Magento\Store\Model\StoreManagerInterface $storeManager,
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
        $mediaUrl = $this->storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        if ((string)$group->getExtensionAttributes()->getIcon() != ''){
            return $mediaUrl.$group->getExtensionAttributes()->getIcon();
        }

        return '';
    }


}
