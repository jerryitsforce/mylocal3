<?php
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

class Acl extends AbstractHelper
{
    const ROLE_ADMIN = 1;
    const ROLE_ACCOUNTING = 232;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param Context $context
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Check if the given admin user ID has full access (Admin or Finance).
     *
     * @param int|string|null $adminUserId
     * @return bool
     */
    public function isFullAccess($adminUserId): bool
    {
        if (empty($adminUserId)) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('authorization_role');

        $roleIds = [self::ROLE_ADMIN, self::ROLE_ACCOUNTING];

        $select = $connection->select()
            ->from($tableName, ['count' => new \Zend_Db_Expr('COUNT(*)')])
            ->where('user_id = ?', (int)$adminUserId)
            ->where('parent_id IN (?)', $roleIds);

        $count = (int)$connection->fetchOne($select);

        return $count > 0;
    }
}
