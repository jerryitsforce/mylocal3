<?php
namespace Branch8\SellerPermission\Setup;


use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\SetFactory as AttributeSetFactory;

class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var AttributeSetFactory
     */
    private $attributeSetFactory;

    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        AttributeSetFactory $attributeSetFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->attributeSetFactory      = $attributeSetFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\LocalizedException
     */
	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
		$this->moduleDataSetup->getConnection()->startSetup();
        if(version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->moduleDataSetup->getConnection()->addColumn('marketplace_userdata', 'is_allow_large_item', [
                'type' => Table::TYPE_SMALLINT,
                'visible' => true,
                'required' => false,
                'COMMENT' => 'Allow large item'
            ]);
        }
        if(version_compare($context->getVersion(), '1.0.2', '<')) {
            $this->moduleDataSetup->getConnection()->addColumn('marketplace_userdata', 'shipping_methods', [
                'type' => Table::TYPE_TEXT,
                'visible' => true,
                'required' => false,
                'COMMENT' => 'Allowed Shipping Methods'
            ]);
        }

        $this->moduleDataSetup->getConnection()->endSetup();
	}
}