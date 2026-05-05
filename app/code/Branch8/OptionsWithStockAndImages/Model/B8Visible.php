<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Model;

use Branch8\OptionsWithStockAndImages\Api\Data\B8VisibleInterface;
use Magento\Framework\Api\AbstractExtensibleObject;

class B8Visible extends AbstractExtensibleObject implements B8VisibleInterface
{
    public function __construct(
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $attributeValueFactory,
        $data = []
    ) {
        $this->extensionFactory = $extensionFactory;
        $this->attributeValueFactory = $attributeValueFactory;
        parent::__construct($extensionFactory,$attributeValueFactory);
    }
    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->_get(self::TITLE);   
    }

    /**
     * @inheritDoc
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * @inheritDoc
     */
    public function getIsVisible()
    {
        return $this->_get(self::IS_VISIBLE);   
    }

    /**
     * @inheritDoc
     */
    public function setIsVisible($isVisible)
    {
        return $this->setData(self::IS_VISIBLE, $isVisible);
    }

    public function getProductItemId()
    {
        return $this->_get(self::PRODUCT_ITEM_ID);
    }

    public function setProductItemId($productItemId)
    {
        return $this->setData(self::PRODUCT_ITEM_ID, $productItemId);
    }
}
