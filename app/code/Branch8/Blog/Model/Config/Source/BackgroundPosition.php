<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Display
 * @package Branch8\Blog\Model\Config\Source\Blogview
 */
class BackgroundPosition implements ArrayInterface
{
    const TL = 'Top Left';
    const TC = 'Top Center';
    const TR = 'Top Right';
    const CL = 'Center Left';
    const CC = 'Center';
    const CR = 'Center Right';
    const BL = 'Bottom Left';
    const BC = 'Bottom Center';
    const BR = 'Bottom Right';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->toArray() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::TL => __('Top Left'),
            self::TC => __('Top Center'),
            self::TR => __('Top Right'),
            self::CL => __('Center Left'),
            self::CC => __('Center'),
            self::CR => __('Center Right'),
            self::BL => __('Bottom Left'),
            self::BC => __('Bottom Center'),
            self::BR => __('Bottom Right')
        ];
    }
}
