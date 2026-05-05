<?php

namespace Branch8\HotaiCore\Helper;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as SetCollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Catalog\Api\ProductAttributeManagementInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory;

use Magento\Eav\Setup\EavSetup;

class CreateTicketAttributeSet
{
    /** @var EavConfig */
    protected $eavConfig;

    /** @var ModuleDataSetupInterface */
    protected $moduleDataSetup;

    /** @var EavSetupFactory */
    protected $eavSetupFactory;

    /** @var SetCollectionFactory */
    protected $setCollectionFactory;

    /** @var GroupCollectionFactory */
    protected $groupCollectionFactory;

    /** @var AttributeCollectionFactory */
    protected $attributeCollectionFactory;

    /** @var ProductAttributeManagementInterface */
    protected $productAttributeManagement;

    /** @var SetFactory */
    protected $setFactory;

    protected $eavSetup;
    protected $entityTypeCode;
    protected $entityTypeId;

    public function __construct(
        EavConfig $eavConfig,
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        SetCollectionFactory $setCollectionFactory,
        GroupCollectionFactory $groupCollectionFactory,
        AttributeCollectionFactory $attributeCollectionFactory,
        ProductAttributeManagementInterface $productAttributeManagement,
        SetFactory $setFactory
    ) {
        $this->eavConfig                  = $eavConfig;
        $this->moduleDataSetup            = $moduleDataSetup;
        $this->eavSetupFactory            = $eavSetupFactory;
        $this->setCollectionFactory       = $setCollectionFactory;
        $this->groupCollectionFactory     = $groupCollectionFactory;
        $this->attributeCollectionFactory = $attributeCollectionFactory;
        $this->productAttributeManagement = $productAttributeManagement;
        $this->setFactory                 = $setFactory;

        $this->entityTypeCode           = \Magento\Catalog\Model\Product::ENTITY;
        $this->entityTypeId             = $this->eavConfig->getEntityType($this->entityTypeCode)->getId();
    }

    public function execute(
        string $newAttributeSetName,
        array $skipTicketAttributeCodes,
        string $baseAttributeSetName = "Default"
    ): void {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $this->eavSetup */
        $this->eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $baseSetId = $this->eavSetup->getAttributeSetId($this->entityTypeCode, $baseAttributeSetName);

        // 創建attribute set
        if (!$this->isAttributeSetExist(trim($newAttributeSetName))) {
            $model = $this->setFactory->create()->setEntityTypeId($this->entityTypeId);
            $model->setAttributeSetName(trim($newAttributeSetName));
            $model->save();
            $model->initFromSkeleton($baseSetId);
            $model->save();
        }

        $newSetId = $model->getId();

        $this->addTicketAttributes($newSetId, \Branch8\HotaiCore\Model\Ticket\AttributeCodes::ALL_CODES);

        $this->removeTicketAttributeFromSet($newSetId, $skipTicketAttributeCodes);
    }

    protected function isAttributeSetExist(string $setName): bool
    {
        $productEntityTypeId = $this->eavConfig->getEntityType('catalog_product')->getId();

        $attributeSetCollection = $this->setCollectionFactory->create();
        $attributeSetCollection->addFieldToFilter('entity_type_id', $productEntityTypeId);
        $attributeSetCollection->addFieldToFilter('attribute_set_name', $setName);
        $attributeSet = $attributeSetCollection->getFirstItem();

        return !is_null($attributeSet->getId());
    }

    protected function getGroupIdBySetIdAndGroupCode(int $setId, string $groupCode): null|string
    {
        $attributeGroupCollection = $this->groupCollectionFactory->create();
        $attributeGroupCollection->addFieldToFilter('attribute_set_id', $setId);
        $attributeGroupCollection->addFieldToFilter('attribute_group_code', $groupCode);

        return $attributeGroupCollection->getFirstItem()->getAttributeGroupId();
    }

    protected function getGroupCodeBySetIdAndGroupId(int $setId, int $groupId): string
    {
        $groupData = $this->eavSetup->getAttributeGroup(
            $this->entityTypeCode,
            $setId,
            $groupId
        );

        return $groupData["attribute_group_code"];
    }

    protected function addTicketAttributes(int $newSetId, array $ticketAttributeCodes): void
    {
        $groupId = $this->eavSetup->getDefaultAttributeGroupId($this->entityTypeCode, $newSetId);

        foreach ($ticketAttributeCodes as $code) {
            $this->eavSetup->addAttributeToSet(
                $this->entityTypeCode,
                $newSetId,
                $groupId,
                $code
            );
        }
    }

    protected function removeTicketAttributeFromSet(int $newSetId, array $ticketAttributeCodes)
    {
        $productEntityTypeId = $this->eavConfig->getEntityType($this->entityTypeCode)->getId();
        $connection          = $this->moduleDataSetup->getConnection();

        foreach ($ticketAttributeCodes as $code) {
            $tableName = $this->moduleDataSetup->getTable('eav_entity_attribute');
            $connection->delete($tableName, [
                'attribute_set_id = ?' => $newSetId,
                'attribute_id = ?'     => $this->getAttributeIdFromCode($code),
                'entity_type_id = ?'   => $productEntityTypeId,
            ]);
        }
    }

    protected function getAttributeIdFromCode(string $attributeCode): null|int
    {
        $productEntityTypeId = $this->eavConfig->getEntityType($this->entityTypeCode)->getId();

        return $this->eavSetup->getAttributeId($productEntityTypeId, $attributeCode);
    }
}
