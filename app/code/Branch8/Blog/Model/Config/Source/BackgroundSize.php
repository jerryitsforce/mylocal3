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
class BackgroundSize implements ArrayInterface
{
    const COVER = 'cover';
    const CONTAIN = 'contain';
    const AUTO = 'auto';

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
        return [self::COVER => __('Cover'), self::CONTAIN => __('Contain'), self::AUTO => __('Auto')];
    }
}
