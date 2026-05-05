<?php
namespace Branch8\CustomerGraphQl\Model\Resolver;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Magento\Customer\Model\Data\Customer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class HotaiPoint implements ResolverInterface
{
    protected ApiHelper $apiHelper;

    public function __construct(
        ApiHelper $apiHelper
    ) {
        $this->apiHelper = $apiHelper;
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
        return $this->getCustomerPoint($customer);
    }

    public function getCustomerPoint($customer) {
        try {
            $result = $this->apiHelper->requestApiGetTransInfo((int) $customer->getId());

            if ($this->apiHelper->getReturnCodeFromResponse($result) == ApiHelper::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
                return 0;
            }

            return (int) $this->apiHelper->getPointFromResponse($result);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
