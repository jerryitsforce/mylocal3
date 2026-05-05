<?php
namespace Branch8\OptionsWithStockAndImages\Setup;


use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;

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
     * @var SchemaSetupInterface
     */
    protected $setup;

    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        SchemaSetupInterface $setup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->setup = $setup;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\LocalizedException
     */
	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
		$this->moduleDataSetup->getConnection()->startSetup();
        if(version_compare($context->getVersion(), '1.0.0', '<')) {
            //$this->moduleDataSetup->getConnection()->truncateTable('wk_osi_variations');
            //$this->moduleDataSetup->getConnection()->truncateTable('wk_osi_swatch');

            // $this->moduleDataSetup->getConnection()->addIndex(
            //     $this->setup->getIdxName(
            //         'wk_osi_variations',
            //         ['product_item_id', 'sku'],
            //         \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
            //     ),
            //     ['product_item_id', 'sku'],
            //     ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            // );
        }

        if(version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->moduleDataSetup->getConnection()->dropIndex(
                'wk_osi_variations',
                'WK_OSI_VARIATIONS_PRODUCT_ITEM_ID_SKU'
            );
        }

        $this->moduleDataSetup->getConnection()->endSetup();
	}
}