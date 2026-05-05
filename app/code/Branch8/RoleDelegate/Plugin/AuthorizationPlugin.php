<?php
namespace Branch8\RoleDelegate\Plugin;

use Magento\Framework\AuthorizationInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Branch8\RoleDelegate\Api\DelegateRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ResourceConnection;

class AuthorizationPlugin
{
    /**
     * @var AuthSession
     */
    protected $authSession;

    /**
     * @var DelegateRepositoryInterface
     */
    protected $delegateRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    protected $cacheDelegation = [];

    /**
     * Constructor
     *
     * @param AuthSession $authSession
     * @param DelegateRepositoryInterface $delegateRepository
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        AuthSession $authSession,
        DelegateRepositoryInterface $delegateRepository,
        LoggerInterface $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->authSession = $authSession;
        $this->delegateRepository = $delegateRepository;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Around plugin for isAllowed method
     *
     * @param AuthorizationInterface $subject
     * @param callable $proceed
     * @param string $resource
     * @return bool
     */
    public function aroundIsAllowed(AuthorizationInterface $subject, callable $proceed, $resource)
    {
        try {
            if ($proceed($resource)) {
                return true;
            }

            $user = $this->authSession->getUser();
            if (!$user || !$user->getId()) {
                return false;
            }
            $userId = (int)$user->getId();

            if (isset($this->cacheDelegation[$userId][$resource]) || isset($this->cacheDelegation[$userId]['Magento_Backend::all'])) {
                return true;
            } elseif (isset($this->cacheDelegation[$userId])) {
                return false;
            }
            $delegation = $this->delegateRepository->getActiveForUser($userId);
            if (!$delegation->getId()) {
                $this->cacheDelegation[$userId] = [];
                return false;
            }

            $originalUserId = (int)$delegation->getUserId();
            if ($this->originalUserHasResource($originalUserId, $userId, $resource)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('AuthorizationPlugin error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Check if the original user has access to the resource
     *
     * @param int $originalUserId
     * @param int $userId
     * @param string $resource
     * @return bool
     */
    private function originalUserHasResource(int $originalUserId, int $userId, string $resource): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $roleIds = $connection->fetchCol(
            'SELECT parent_id FROM ' . $connection->getTableName('admin_user') . ' au ' .
            'JOIN ' . $connection->getTableName('authorization_role') . ' ar ON au.user_id = ar.user_id ' .
            'WHERE au.user_id = ? AND ar.role_type = ? AND ar.user_type = ?',
            [$originalUserId, 'U', '2']
        );

        if (empty($roleIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $roleIds[] = 'allow';
        $select = 'SELECT resource_id FROM ' . $connection->getTableName('authorization_rule') .
            ' WHERE role_id IN (' . $placeholders . ') AND permission = ?';
        $resources = $connection->fetchCol($select, $roleIds);

        if (empty($resources)) {
            $this->cacheDelegation[$userId] = [];
            return false;
        }

        foreach ($resources as $r) {
            $this->cacheDelegation[$userId][$r] = true;
        }

        return (isset($this->cacheDelegation[$userId][$resource]) || isset($this->cacheDelegation[$userId]['Magento_Backend::all']));
    }
}
