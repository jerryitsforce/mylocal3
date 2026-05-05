<?php
namespace Branch8\GiftToFriend\Setup\Patch\Data;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class HelpDeskGiftCategory implements DataPatchInterface, PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    protected $hdCategory;

    protected $timezone;
    /**
     * Constructor
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Branch8\HelpDesk\Model\CategoryFactory $hdCategory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->hdCategory = $hdCategory;
        $this->timezone = $timezone;
    }

    public function apply()
    {
        $conn = $this->moduleDataSetup->getConnection();
        $conn->startSetup();
        $currentTime = $this->timezone->convertConfigTimeToUtc($this->timezone->date());
        $cate = $this->hdCategory->create()
            ->setData([
                'category_id' => NULL,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
                'position' => 10, 
                'is_active' => 1,
                'title' => '禮物訂單問題',
                'description' => ''
            ]);
        $cate->save();
        $cateId = $cate->getId();
        $cateStoreSql = 'Insert into branch8_helpdesk_category_store values(NULL, '.$cateId.', 0);';
        $conn->query($cateStoreSql);
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
