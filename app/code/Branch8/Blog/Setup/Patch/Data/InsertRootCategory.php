<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */
declare(strict_types=1);

namespace Branch8\Blog\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Branch8\Blog\Model\CategoryFactory;

/**
 * Class InsertRootCategory
 *
 * @package Branch8\Blog\Setup\Patch\Data
 */
class InsertRootCategory implements
    DataPatchInterface,
    PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * InsertRootCategory constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategoryFactory $categoryFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategoryFactory $categoryFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $defaultData = [
            'store_ids'      => 0,
            'parent_id'      => null,
            'path'           => '1',
            'position'       => 0,
            'children_count' => 0,
            'level'          => 0,
            'name'           => 'ROOT',
            'url_key'        => 'root'
        ];
        if (!$this->categoryFactory->create()->getCollection()->getSize()) {
            $this->moduleDataSetup->getConnection()->insertOnDuplicate(
                $this->moduleDataSetup->getTable('branch8_blog_category'),
                $defaultData
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }
}
