<?php

namespace Branch8\Yoxi\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Branch8\Yoxi\Setup\Patch\Data\AddTicketAttributeSet;
use Magento\Catalog\Model\Product;
use Branch8\HotaiCore\Model\Ticket\AttributeCodes as TicketAttributeCodes;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ReApplyTicketAttributeSet implements DataPatchInterface
{
    /** @var ModuleDataSetupInterface */
    protected $moduleDataSetup;

    /** @var EavConfig */
    protected $eavConfig;

    /** @var EavSetupFactory */
    protected $eavSetupFactory;

    protected $eavSetup;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavConfig $eavConfig,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavConfig       = $eavConfig;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * The patch app/code/Branch8/Yoxi/Setup/Patch/Data/AddTicketAttributeSet.php
     * set the wrong TicketAttributeCodes::SKIP_CODES at previous commit,
     * so we need this patch to re-apply attributes to "ticket_yoxi" attribute set for other environment.
     * The typo of AddTicketAttributeSet was also fixed as well.
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $this->eavSetup */
        $this->eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attributeSetId = $this->eavSetup->getAttributeSetId(Product::ENTITY, AddTicketAttributeSet::NEW_ATTRIBUTE_SET_NAME);

        $this->addTicketAttributes($attributeSetId, TicketAttributeCodes::ALL_CODES);

        $this->removeTicketAttributeFromSet($attributeSetId, TicketAttributeCodes::SKIP_CODES_YOXI);
    }

    public function addTicketAttributes(int $newSetId, array $ticketAttributeCodes): void
    {
        $groupId = $this->eavSetup->getDefaultAttributeGroupId(Product::ENTITY, $newSetId);

        foreach ($ticketAttributeCodes as $code) {
            $this->eavSetup->addAttributeToSet(
                Product::ENTITY,
                $newSetId,
                $groupId,
                $code
            );
        }
    }

    public function removeTicketAttributeFromSet(int $newSetId, array $ticketAttributeCodes)
    {
        $productEntityTypeId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();
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
        $productEntityTypeId = $this->eavConfig->getEntityType(Product::ENTITY)->getId();

        return $this->eavSetup->getAttributeId($productEntityTypeId, $attributeCode);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [
            AddTicketAttributeSet::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '1.0.0';
    }
}