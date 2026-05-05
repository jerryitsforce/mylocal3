<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderCancel;
use Magento\Sales\Model\Order\Email\Container;

class Identity extends Container\Container
{
    /**
     * Configuration paths
     */
    const XML_PATH_EMAIL_COPY_METHOD = 'sales_email/parent_order_cancel/copy_method';
    const XML_PATH_EMAIL_COPY_TO = 'sales_email/parent_order_cancel/copy_to';
    const XML_PATH_EMAIL_GUEST_TEMPLATE = 'sales_email/parent_order_cancel/guest_template';
    const XML_PATH_EMAIL_TEMPLATE = 'sales_email/parent_order_cancel/template';
    const XML_PATH_EMAIL_IDENTITY = 'sales_email/parent_order_cancel/identity';
    const XML_PATH_EMAIL_ENABLED = 'sales_email/parent_order_cancel/enabled';

    /**
     * Is email enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_EMAIL_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $this->getStore()->getStoreId()
        );
    }

    /**
     * Return email copy_to list
     *
     * @return array|bool
     */
    public function getEmailCopyTo()
    {
        $data = $this->getConfigValue(self::XML_PATH_EMAIL_COPY_TO, $this->getStore()->getStoreId());
        if (!empty($data)) {
            return array_map('trim', explode(',', $data));
        }
        return false;
    }

    /**
     * Return email copy method
     *
     * @return mixed
     */
    public function getCopyMethod()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_COPY_METHOD, $this->getStore()->getStoreId());
    }

    /**
     * Return guest template id
     *
     * @return mixed
     */
    public function getGuestTemplateId()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_GUEST_TEMPLATE, $this->getStore()->getStoreId());
    }

    /**
     * Return template id
     *
     * @return mixed
     */
    public function getTemplateId()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_TEMPLATE, $this->getStore()->getStoreId());
    }

    /**
     * Return email identity
     *
     * @return mixed
     */
    public function getEmailIdentity()
    {
        return $this->getConfigValue(self::XML_PATH_EMAIL_IDENTITY, $this->getStore()->getStoreId());
    }
}
