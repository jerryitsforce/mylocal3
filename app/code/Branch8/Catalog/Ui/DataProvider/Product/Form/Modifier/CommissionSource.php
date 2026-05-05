<?php

namespace Branch8\Catalog\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\Field;
use Magento\Ui\Component\Form\Element\Hidden;

class CommissionSource extends AbstractModifier{

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
    const ATTR = 'commission_source';
    const FIELD_ORDER = 10;

    public function modifyMeta(array $meta){
        if ($name = $this->getGeneralPanelName($meta)) {
            $meta[$name]['children'][self::ATTR]['arguments']['data']['config']  = [
                'disableLabel' => true,
                'filterOptions' => true,
                'formElement' => Hidden::NAME,
                'componentType' => Field::NAME,
                'visible' => 1,
                'label' => __('Commission Source'),
                'source' => $name,
                'sortOrder' => $this->getNextAttributeSortOrder(
                    $meta,
                    ['commission_percent'],
                    self::FIELD_ORDER
                ),
                'multiple' => false,
                'disabled' => true,
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
                        self::ATTR => $this->locator->getProduct()->getCommissionSource()
                    ],
                ]
            ]
        );
    }


}
