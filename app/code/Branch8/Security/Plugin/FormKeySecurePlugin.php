<?php
declare(strict_types=1);

namespace Branch8\Security\Plugin;

use Magento\Framework\App\PageCache\FormKey;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

/**
 * Plugin to enforce HttpOnly flag on form_key cookie.
 */
class FormKeySecurePlugin
{
    /**
     * Enforce HttpOnly on form_key cookie metadata before it is set.
     *
     * @param FormKey $subject
     * @param string $value
     * @param PublicCookieMetadata $metadata
     * @return array
     */
    public function beforeSet(FormKey $subject, $value, PublicCookieMetadata $metadata): array
    {
        $metadata->setHttpOnly(true);
        return [$value, $metadata];
    }
}
