<?php

declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Model;

use Branch8\MarketplaceStaging\Api\Data\ProductVersionDataInterface;
use Magento\Framework\Model\AbstractModel;

class ProductVersionData extends AbstractModel implements ProductVersionDataInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->_init(ResourceModel\ProductVersionData::class);
    }

    /**
     * @inheritdoc
     */
    public function getId(): ?int
    {
        $id = $this->getData(self::ID);
        return $id ? (int)$id : null;
    }

    /**
     * @inheritdoc
     */
    public function getParentId(): ?int
    {
        $parentId = $this->getData(self::PARENT_ID);
        return $parentId ? (int)$parentId : null;
    }

    /**
     * @inheritdoc
     */
    public function setParentId(int $parentId): self
    {
        return $this->setData(self::PARENT_ID, $parentId);
    }

    /**
     * @inheritdoc
     */
    public function getInformation(): string
    {
        return (string)$this->getData(self::INFORMATION);
    }

    /**
     * @inheritdoc
     */
    public function setInformation(string $information): self
    {
        return $this->setData(self::INFORMATION, $information);
    }
}
