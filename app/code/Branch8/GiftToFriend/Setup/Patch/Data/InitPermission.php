<?php
namespace Branch8\GiftToFriend\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use PDO;

class InitPermission implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;
    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply()
    {
        $conn = $this->moduleDataSetup->getConnection();
        $conn->startSetup();
        
        $sqlNeedToUpdateItems = 'select distinct role_id from authorization_rule where resource_id <> "Magento_Backend::all" or permission <> "allow";';
        $items = $conn->fetchCol($sqlNeedToUpdateItems);
        foreach($items as $_item){
            $sqlInsert = 'insert into authorization_rule  values(NULL, '.$_item.', "Branch8_GiftToFriend::gift_orders", NULL, "deny");';
            $conn->query($sqlInsert);
        }
        $conn->endSetup();
    }

    public function revert()
    {

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
    public static function getDependencies()
    {
        return [

        ];
    }
}
