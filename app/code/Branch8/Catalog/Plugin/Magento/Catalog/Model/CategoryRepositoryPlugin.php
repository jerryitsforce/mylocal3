<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Plugin\Magento\Catalog\Model;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\CategoryRepository;

class CategoryRepositoryPlugin
{
    private const ATTRIBUTES_TO_PROCESS = [
        'url_key',
        'url_path'
    ];
    private \Magento\Framework\Filter\Translit $translit;

    /**
     * @param \Magento\Framework\Filter\Translit $translit
     */
    public function __construct(
        \Magento\Framework\Filter\Translit $translit
    )
    {
        $this->translit = $translit;
    }


    public function beforeSave(
        CategoryRepository $subject, CategoryInterface $category
    ): array
    {
        foreach (self::ATTRIBUTES_TO_PROCESS as $attributeKey) {
            $attribute = $category->getCustomAttribute($attributeKey);
            if ($attribute !== null) {
                $value = $category->getData($attributeKey);
                $formattedValue = $attributeKey === 'url_path' ? $this->formatUrlPath($value) : $category->formatUrlKey($value);
                $attribute->setValue($formattedValue);
            }
        }

        return [$category];
    }

    /**
     * Prevent url_path convert '/' to '-' by allow '/'  (eg:regular-promotion/promotion-brand-sale)
     * @param $string
     * @return string
     */
    private function formatUrlPath($string)
    {
        $string = preg_replace('#[^0-9a-z/]+#i', '-', $this->translit->filter($string));
        $string = strtolower($string);
        return trim($string, '-');
    }
}
