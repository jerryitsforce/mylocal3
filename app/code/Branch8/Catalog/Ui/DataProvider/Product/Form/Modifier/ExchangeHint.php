<?php

namespace Branch8\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;

class ExchangeHint extends AbstractModifier
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    public function modifyMeta(array $meta) {


        $exchangeHintPath = $this->arrayManager->findPath(
            \Branch8\HotaiCore\Model\Product\ExchangeHint::ATTRIBUTE_CODE,
            $meta,
            null,
            'children'
        );

        if (!$exchangeHintPath) {
            return $meta;
        }

        $meta = $this->arrayManager->set(
            "{$exchangeHintPath}/arguments/data/config/elementTmpl",
            $meta,
            'Branch8_Catalog/form/element/exchange-hint'
        );

        return $meta;
    }

    public function modifyData(array $data)
    {
        return $data;
    }
}
