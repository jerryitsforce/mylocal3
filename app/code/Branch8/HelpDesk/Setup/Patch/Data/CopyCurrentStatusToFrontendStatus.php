<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Setup\Patch\Data;

use Branch8\HelpDesk\Model\Ticket\Status;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchVersionInterface;

class CopyCurrentStatusToFrontendStatus implements DataPatchInterface, PatchVersionInterface
{
    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    )
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $csrRepliedStatus = Status::CSR_REPLIED;
        $connection = $this->moduleDataSetup->getConnection();
        $query = "UPDATE `branch8_helpdesk_ticket` SET `frontend_status` = CASE
                WHEN status = 1 THEN 1
                WHEN status = 2 THEN $csrRepliedStatus
                WHEN status = 3 THEN $csrRepliedStatus
                END";
        $query = $connection->query($query);
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
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
        return '2.0.0';
    }
}
