<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Customer Group Catalog for Magento 2
 */

namespace Branch8\RestrictedProduct\Model\Rule;

class ForbiddenActionOptionsProvider implements \Magento\Framework\Data\OptionSourceInterface
{
    public const REDIRECT_TO_404 = 0;
    public const REDIRECT_TO_PAGE = 1;
    public const REDIRECT_TO_SPECIFIED_URL = 1000;
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::REDIRECT_TO_404, 'label' => __('Show 404 page')],
            ['value' => self::REDIRECT_TO_PAGE, 'label' => __('Redirect to CMS page')],
            ['value' => self::REDIRECT_TO_SPECIFIED_URL, 'label' => __('Redirect to Specified URL')],
        ];
    }
}
