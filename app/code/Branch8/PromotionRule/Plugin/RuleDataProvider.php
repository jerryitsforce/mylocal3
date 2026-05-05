<?php

declare(strict_types=1);

namespace Branch8\PromotionRule\Plugin;

use Magento\Ui\DataProvider\AbstractDataProvider;

class RuleDataProvider
{
    /**
     * Convert data
     *
     * @param AbstractDataProvider $subject
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterGetData($subject, $result)
    {
        if (is_array($result)) {
            foreach ($result as &$item) {
                if (isset($item['seller_ids']) && !is_array($item['seller_ids'])) {
                    $item['seller_ids'] = explode(',', (string)$item['seller_ids']);
                }
            }
        }

        return $result;
    }
}
