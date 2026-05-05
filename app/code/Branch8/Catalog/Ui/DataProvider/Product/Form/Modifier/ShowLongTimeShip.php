<?php

declare(strict_types=1);

namespace Branch8\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

class ShowLongTimeShip extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private ArrayManager $arrayManager;

    /**
     * ShowLongTimeShip constructor.
     *
     * @param ArrayManager $arrayManager
     */
    public function __construct(ArrayManager $arrayManager)
    {
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta): array
    {
        $path = $this->arrayManager->findPath('show_long_time_ship', $meta, null, 'children');

        if (empty($path)) {
            return $meta;
        }

        return $this->arrayManager->merge(
            $path . static::META_CONFIG_PATH,
            $meta,
            [
                'component' => 'Branch8_Catalog/js/components/show-long-time-ship',
                'prefer' => 'toggle'
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function modifyData(array $data): array
    {
        return $data;
    }
}
