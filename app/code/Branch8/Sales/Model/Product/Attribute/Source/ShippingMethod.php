<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Sales\Model\Product\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Shipping\Model\Config\Source\Allmethods;
use Magento\Framework\App\RequestInterface;

class ShippingMethod extends AbstractSource
{
    /**
     * @var Allmethods
     */
    protected $_shippingAllMethods;

    /**
     * @var RequestInterface
     */
    private RequestInterface $_request;

    const DISALLOWED_ACTIONS = [
        'catalog_product_edit',
        'catalog_product_new',
        'marketplace_product_add',
        'marketplace_product_edit',
        'marketplacestaging_mui_render_handle',
        'marketplacestaging_update',
        'catalogstaging_update',
        'mui_index_render_handle',
    ];

    public function __construct(
        Allmethods $shippingAllMethods,
        RequestInterface $request
    ) {
        $this->_shippingAllMethods = $shippingAllMethods;
        $this->_request = $request;
    }

    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = $this->_shippingAllMethods->toOptionArray(true);
        if(!in_array($this->_request->getFullActionName(), self::DISALLOWED_ACTIONS)) {
            $this->_options = array_merge([['value' => 'electronic', 'label' => __('Electronic tickets')]], $this->_options);
        }
        return $this->_options;
    }

    /**
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'nullable' => true,
                'comment' => $attributeCode . ' column',
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFlatIndexes()
    {
        $indexes = [];

        $index = 'IDX_' . strtoupper($this->getAttribute()->getAttributeCode());
        $indexes[$index] = ['type' => 'index', 'fields' => [$this->getAttribute()->getAttributeCode()]];

        return $indexes;
    }

    /**
     * @param int $store
     * @return \Magento\Framework\DB\Select|null
     */
    public function getFlatUpdateSelect($store)
    {
        return $this->eavAttrEntity->create()->getFlatUpdateSelect($this->getAttribute(), $store);
    }
}
