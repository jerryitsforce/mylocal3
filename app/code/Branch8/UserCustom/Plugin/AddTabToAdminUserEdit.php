<?php

namespace Branch8\UserCustom\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\User\Block\User\Edit\Tabs;

class AddTabToAdminUserEdit
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;
    protected $config;
    public function __construct(
        \Magento\Backend\Model\Auth\Session $adminSession,
        \Branch8\UserCustom\Helper\Config $config,
    )
    {
        $this->_adminSession = $adminSession;
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
        if(!in_array($roleId, $this->config->getUserRoles())){
            return;
        }
        $tfaForm = $subject->getLayout()->createBlock(\Branch8\UserCustom\Block\User\Edit\Tab\Main::class)->toHtml();

        $subject->addTabAfter(
            'cmspagepermission',
            [
                'label' => __('CMS PAGE Permission'),
                'title' => __('CMS PAGE Permission'),
                'content' => $tfaForm,
                'active' => true
            ],
            'roles_section'
        );
    }
}
