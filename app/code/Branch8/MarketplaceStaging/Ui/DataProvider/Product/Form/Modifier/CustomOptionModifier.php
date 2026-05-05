<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       23/03/2026
 */

namespace Branch8\MarketplaceStaging\Ui\DataProvider\Product\Form\Modifier;

use Branch8\MarketplaceStaging\Helper\Data as HelperData;
use Magento\Framework\Stdlib\ArrayManager;

class CustomOptionModifier extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier
{
    /**
     * @var Magento\Framework\Stdlib\ArrayManager
     */
    private $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager,
    ) {
        $this->arrayManager = $arrayManager;
    }
    /**
     * modifyData
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        $attribute='custom_options';
        if (isset($meta[$attribute]['arguments']['data']['config'])) {
            $meta[$attribute]['arguments']['data']['config']['component'] = 'Branch8_MarketplaceStaging/js/form/components/fieldset';
        }
        return $meta;
    }
}
