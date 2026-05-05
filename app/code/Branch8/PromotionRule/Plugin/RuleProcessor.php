<?php

declare(strict_types=1);

namespace Branch8\PromotionRule\Plugin;

use Magento\Rule\Model\AbstractModel;

class RuleProcessor
{
    /**
     * Convert data after loading post data.
     *
     * @param AbstractModel $subject
     * @param AbstractModel $rule
     *
     * @return AbstractModel
     */
    public function afterLoadPost($subject, $rule, $data)
    {
        $sellerIds = $data['seller_ids'] ?? null;
        if (empty($sellerIds)) {
            $sellerIds = null;
        } elseif (is_array($sellerIds)) {
            $sellerIds = implode(',', $sellerIds);
        }

        $rule->setData('seller_ids', $sellerIds);
        return $rule;
    }
}
