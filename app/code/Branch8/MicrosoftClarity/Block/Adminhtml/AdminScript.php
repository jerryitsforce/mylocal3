<?php
namespace Branch8\MicrosoftClarity\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Config\ScopeConfigInterface;

class AdminScript extends Template
{
    /**
     * @var AdminSession
     */
    protected $adminSession;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        Template\Context $context,
        AdminSession $adminSession,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->adminSession = $adminSession;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * Get current admin user ID
     */
    public function getAdminUserId()
    {
        $user = $this->adminSession->getUser();
        return $user ? $user->getId() : null;
    }

    /**
     * Get current admin user email
     */
    public function getAdminUserEmail()
    {
        $user = $this->adminSession->getUser();
        return $user ? $user->getEmail() : null;
    }

    /**
     * Get current admin user name
     */
    public function getAdminUserName()
    {
        $user = $this->adminSession->getUser();
        return $user ? $user->getName() : null;
    }
    /**
     * Get Microsoft Clarity project ID for admin
     */
    public function getClarityCode()
    {
        return $this->scopeConfig->getValue('branch8_microsoftclarity/general/clarity_project_id_admin');
    }
}