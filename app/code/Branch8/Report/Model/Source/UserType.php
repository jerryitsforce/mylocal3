<?php

declare(strict_types=1);

namespace Branch8\Report\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class UserType implements OptionSourceInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const TYPE_SYSTEM = 0;
    public const TYPE_ADMIN = 1;
    public const TYPE_SELLER = 2;
    /**#@-*/

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->toArray() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * Returns options value-label.
     *
     * @return array
     */
    public static function toArray(): array
    {
        return [
            self::TYPE_SYSTEM => __('System'),
            self::TYPE_ADMIN => __('Admin'),
            self::TYPE_SELLER => __('Seller')
        ];
    }
}
