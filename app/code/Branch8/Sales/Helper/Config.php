<?php

declare(strict_types=1);

namespace Branch8\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Class provides methods to retrieve configs
 */
class Config extends AbstractHelper
{
    /**#@+
     * Constants for config path.
     */
    private const XML_PATH_SHIPPING_LOGISTICS_COMPANY = 'b8sales/shipping/logistics_company';
    private const XML_PATH_ORDER_STATUSES_ALLOW_SPECIFIC = 'b8sales/order_status/allow_specific';
    private const XML_PATH_ORDER_STATUSES_SPECIFIC_APPLY = 'b8sales/order_status/statuses_filter_apply';
    /**#@-*/

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    protected $logisticInfos;

    /**
     * Config constructor.
     *
     * @param Context $context
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Context             $context,
        SerializerInterface $serializer
    ) {
        parent::__construct($context);
        $this->serializer = $serializer;
    }

    /**
     * Retrieve config for logistics company.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getLogisticsCompanies(int $storeId = null): array
    {
        if (!isset($this->logisticInfos)) {
            $items = [];
            $configs = (string)$this->scopeConfig->getValue(
                self::XML_PATH_SHIPPING_LOGISTICS_COMPANY,
                ScopeInterface::SCOPE_STORE,
                $storeId
            );
            if (!empty($configs) && $configs !== '[]') {
                foreach ($this->serializer->unserialize($configs) as $item) {
                    if (isset($items[$item['name']])) {
                        continue;
                    }
                    $items[$item['name']] = $item['url'];
                }
            }
            $this->logisticInfos = $items;
        }
        return $this->logisticInfos;
    }

    /**
     *  Check if filtering by specific order statuses is enabled.
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isFilterBySpecificStatusesEnabled(int $storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ORDER_STATUSES_ALLOW_SPECIFIC,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Retrieve config for specific order statuses for filtering.
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getSpecificStatusesForFilter(int $storeId = null): array
    {
        $statuses = $this->scopeConfig->getValue(
            self::XML_PATH_ORDER_STATUSES_SPECIFIC_APPLY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $this->convertToArray((string)$statuses);
    }

    /**
     * Convert string value to array.
     *
     * @param string $value
     *
     * @return array
     */
    private function convertToArray(string $value): array
    {
        return strlen($value) ? explode(',', $value) : [];
    }
}
