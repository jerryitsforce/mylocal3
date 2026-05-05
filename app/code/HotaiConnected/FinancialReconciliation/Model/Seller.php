<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Model;

use HotaiConnected\FinancialReconciliation\Api\SellerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\ResourceConnection;
use Magento\Backend\Model\Auth\Session as AuthSession;

class Seller implements \HotaiConnected\FinancialReconciliation\Api\SellerInterface
{
    protected ScopeConfigInterface $scopeConfig;
    protected Json $jsonSerializer;
    protected ResourceConnection $resourceConnection;
    private $response;
    private $authSession;
    private $aclHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $jsonSerializer,
        Response $response,
        ResourceConnection $resourceConnection,
        AuthSession $authSession,
        \HotaiConnected\FinancialReconciliation\Helper\Acl $aclHelper
    ) {
        $this->response = $response;
        $this->scopeConfig = $scopeConfig;
        $this->jsonSerializer = $jsonSerializer;
        $this->resourceConnection = $resourceConnection;
        $this->authSession = $authSession;
        $this->aclHelper = $aclHelper;
    }

    public function getSellers()
    {
        $connection = $this->resourceConnection->getConnection();
        $adminUser = $this->authSession->getUser();
        $adminUserId = $adminUser ? $adminUser->getId() : null;

        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');

        $select = $connection->select()
            ->from(
                $marketplaceUserdataTableName,
                ['seller_id', 'seller_code', 'shop_title']
            );

        // 判斷是否具備全覽權限 (1: Admin, 232: 財務)
        $isFullAccess = $this->aclHelper->isFullAccess($adminUserId);
        if (!$isFullAccess && $adminUserId) {
            $authorizationRoleTableName = $this->resourceConnection->getTableName('authorization_role');
            $roleSelect = $connection->select()
                ->from(['ar' => $authorizationRoleTableName], ['parent_id'])
                ->where('ar.user_id = ?', (int)$adminUserId);
            $roleIds = $connection->fetchCol($roleSelect);
            if (!empty($roleIds)) {
                $select->where('salesperson IN (?)', $roleIds);
            } else {
                $select->where('1=0');
            }
        }

        $sellers = $connection->fetchAll($select);

        $items = [];
        foreach ($sellers as $seller) {
            $items[] = [
                'seller_id' => (string)$seller['seller_id'],
                'shop_title' => $seller['shop_title'],
                'seller_code' => $seller['seller_code']
            ];
        }

        return  $this->response->setHeader('Content-Type', 'application/json', true)
                ->setBody(json_encode(['items' => $items]))
                ->sendResponse();
    }
}