<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class provides methods to retrieve configs.
 */
class Config
{
    /**#@+
     * Constants for config path.
     */
    private const XML_PATH_EXCLUDE_ATTRIBUTES = 'marketplace/branch8_product_approval/exclude_attributes';
    private const XML_PATH_ADMIN_ROLES_APPROVAL = 'marketplace/branch8_product_approval/role_approval';
    private const XML_PATH_ATTRIBUTE_SELECTOR = 'marketplace/branch8_product_approval/attribute_selector';
    private const XML_PATH_VERSION_LIFETIME = 'marketplace/branch8_product_approval/version_lifetime';
    /**#@-*/

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * Config constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface  $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->serializer = $serializer;
    }

    /**
     * Retrieve config for exclude attributes.
     *
     * @return array
     */
    public function getExcludeAttributes(): array
    {
        $items = [];
        $configs = (string)$this->scopeConfig->getValue(self::XML_PATH_EXCLUDE_ATTRIBUTES);
        if (!empty($configs) && $configs !== '[]') {
            foreach ($this->serializer->unserialize($configs) as $item) {
                if (isset($items[$item['value']])) {
                    continue;
                }
                $items[] = $item['value'];
            }
        }
        return $items;
    }

    /**
     * Retrieve config for exclude attributes.
     *
     * @return array
     */
    public function getAdminApprovalRoles(): array
    {
        $items = [];
        $configs = (string)$this->scopeConfig->getValue(self::XML_PATH_ADMIN_ROLES_APPROVAL);
        if (!empty($configs) && $configs !== '') {
            $items = explode(',', $configs);
        }
        return $items;
    }

    /**
     * Retrieve config for attributes selector.
     *
     * @return array
     */
    /*public function getAttributeSelector(): array
    {
        $items = [];
        $configs = (string)$this->scopeConfig->getValue(self::XML_PATH_ATTRIBUTE_SELECTOR);
        if (!empty($configs) && $configs !== '') {
            $items = explode(',', $configs);
        }
        return $items;
    }*/


    /**
     * Retrieve config for version lifetime.
     *
     * @return int
     */
    public function getVersionLifetime(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_VERSION_LIFETIME) ?? 180;
    }
}
