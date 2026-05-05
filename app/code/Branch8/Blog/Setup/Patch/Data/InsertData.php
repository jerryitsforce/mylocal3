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
use Magento\Framework\Stdlib\DateTime\DateTime;
use Branch8\Blog\Model\AuthorFactory;

/**
 * Class InsertData
 *
 * @package Branch8\Blog\Setup\Patch\Data
 */
class InsertData implements
    DataPatchInterface,
    PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * Date model
     *
     * @var DateTime
     */
    protected $date;

    /**
     * @var AuthorFactory
     */
    protected $authorFactory;

    /**
     * InsertData constructor.
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param AuthorFactory $authorFactory
     * @param DateTime $date
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        AuthorFactory $authorFactory,
        DateTime $date
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->authorFactory   = $authorFactory;
        $this->date            = $date;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $defaultData = [
            'name'       => 'Admin',
            'type'       => 0,
            'status'     => 1,
            'created_at' => $this->date->date()
        ];

        if (!$this->authorFactory->create()->getCollection()->getSize()) {
            $this->moduleDataSetup->getConnection()->insertOnDuplicate(
                $this->moduleDataSetup->getTable('branch8_blog_author'),
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
