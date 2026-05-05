<?php

namespace Branch8\RoleDelegate\Plugin;

use Branch8\RoleDelegate\Api\DelegateRepositoryInterface;
use Branch8\RoleDelegate\Helper\Config;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\User\Block\User\Edit\Tabs;

class AddTabToAdminUserEdit
{
    /**
     * @var Session
     */
    protected $_adminSession;

    /**
     * @var DelegateRepositoryInterface
     */
    protected $delegateRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Config
     */
    protected $config;

    public function __construct(
        Session $adminSession,
        DelegateRepositoryInterface $delegateRepository,
        ResourceConnection $resourceConnection,
        Registry $registry,
        Config $config
    ){
        $this->_adminSession = $adminSession;
        $this->delegateRepository = $delegateRepository;
        $this->resourceConnection = $resourceConnection;
        $this->_coreRegistry = $registry;
        $this->config = $config;
    }

    /**
     * Check if tab should be displayed
     *
     * @param Tabs $subject
     * @throws LocalizedException
     */
    public function beforeToHtml(Tabs $subject)
    {
        $roleId = $this->_adminSession->getUser()->getRole()->getId();
        if (!in_array($roleId, $this->config->getUserRoles())) {
            $delegation = $this->delegateRepository->getActiveForUser((int)$this->_adminSession->getUser()->getId());
            if ($delegation->getId()) {
                $roleIdDelegated = $this->getRoleUser((int)$delegation->getUserId());
                if (!in_array($roleIdDelegated, $this->config->getUserRoles())) {
                    return;
                }
            } else {
                return;
            }
        }
        /** @var $model \Magento\User\Model\User */
        $model = $this->_coreRegistry->registry('permissions_user');
        if (!$model->getUserId()) {
            return;
        }
        $form = $subject->getLayout()->createBlock(\Branch8\RoleDelegate\Block\Adminhtml\User\Delegate::class)->toHtml();

        $subject->addTabAfter(
            'role_delegate_settings',
            [
                'label' => __('Role Delegate Settings'),
                'title' => __('Role Delegate Settings'),
                'content' => $form,
                'active' => false
            ],
            'roles_section'
        );
    }

    public function afterAddTab(Tabs $subject, $result, $tabId, $tab)
    {
        $roleId = $this->_adminSession->getUser()->getRole()->getId();
        if (!in_array($roleId, $this->config->getUserRoles())) {
            return $result;
        }
        if ($subject->getRequest()->getParam('delegate_id')) {
            $subject->setActiveTab("role_delegate_settings");
        }

        return $result;
    }

    /**
     * Check if the original user has access to the resource
     *
     * @param int $originalUserId
     * @return string
     */
    private function getRoleUser(int $originalUserId)
    {
        $connection = $this->resourceConnection->getConnection();
        return $connection->fetchOne(
            'SELECT parent_id FROM ' . $connection->getTableName('admin_user') . ' au ' .
            'JOIN ' . $connection->getTableName('authorization_role') . ' ar ON au.user_id = ar.user_id ' .
            'WHERE au.user_id = ? AND ar.role_type = ? AND ar.user_type = ?',
            [$originalUserId, 'U', '2']
        );
    }
}
