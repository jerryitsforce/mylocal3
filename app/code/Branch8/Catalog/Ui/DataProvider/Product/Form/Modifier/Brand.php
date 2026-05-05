<?php

namespace Branch8\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\Field;

class Brand extends AbstractModifier{

    protected $urlBuilder;

    protected $locator;

    protected $_attributeRepository;

    public function __construct(
        LocatorInterface $locator,
        UrlInterface $urlBuilder,
        \Magento\Catalog\Model\Product\Attribute\Repository $attributeRepository
    ) {
        $this->locator = $locator;
        $this->urlBuilder = $urlBuilder;
        $this->_attributeRepository = $attributeRepository;
    }
    const ATTR = 'brand';
    const FIELD_ORDER = 10;

    public function modifyMeta(array $meta){
        if ($name = $this->getGeneralPanelName($meta)) {
            $meta[$name]['children'][self::ATTR]['arguments']['data']['config']  = [
                'component' => 'Magento_Ui/js/form/element/ui-select',
                'disableLabel' => true,
                'filterOptions' => true,
                'elementTmpl' => 'ui/grid/filters/elements/ui-select',
                'formElement' => 'select',
                'componentType' => Field::NAME,
                'options' => $this->getOptions(),
                'visible' => 1,
                'required' => 1,
                'label' => __('Brand'),
                'source' => $name,
                'sortOrder' => $this->getNextAttributeSortOrder(
                    $meta,
                    [ProductAttributeInterface::CODE_WEIGHT],
                    self::FIELD_ORDER
                ),
                'multiple' => false,
                'disabled' => $this->locator->getProduct()->isLockedAttribute('brand'),
            ];
        }

        return $meta;
    }

    /**
     * @inheritdoc
     * @since 101.0.0
     */
    public function modifyData(array $data){
        return array_replace_recursive(
            $data,
            [
                $this->locator->getProduct()->getId() => [
                    self::DATA_SOURCE_DEFAULT => [
                        self::ATTR => $this->locator->getProduct()->getBrand()
                    ],
                ]
            ]
        );
    }

    public function getOptions(){
        $brandOptions = $this->_attributeRepository->get(self::ATTR)->getOptions();
        $options = [null => ''];
        foreach ($brandOptions as $_bOption){
            if ($_bOption->getValue() != '') {
                $options[] = ['value' => $_bOption->getValue(), 'label' => $_bOption->getLabel()];
            }
        }
        return $options;
    }

}
