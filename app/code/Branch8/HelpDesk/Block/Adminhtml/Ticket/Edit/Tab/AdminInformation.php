<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab;

use Branch8\HelpDesk\Model\ResourceModel\Team\CollectionFactory;
use Branch8\HelpDesk\Model\Ticket\AclRole;
use Branch8\HelpDesk\Model\Ticket\Priority;
use Branch8\HelpDesk\Model\Ticket\Status;

/**
 *
 */
class AdminInformation extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

    protected $authSession;

    protected $userFactory;
    private $teamCollectionFactory;
    private $priorities = null;
    private $teams = null;
    private $userLists = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param CollectionFactory $teamCollectionFactory
     * @param \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context                  $context,
        \Magento\Framework\Registry                              $registry,
        \Magento\Framework\Data\FormFactory                      $formFactory,
        \Magento\Store\Model\System\Store                        $systemStore,
        CollectionFactory                                        $teamCollectionFactory,
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory,
        array                                                    $data = []
    )
    {
        $this->userCollectionFactory = $userCollectionFactory;
        $this->teamCollectionFactory = $teamCollectionFactory;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * Prepare form
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {

        /**
         * @var $ticket \Branch8\HelpDesk\Model\Ticket
         */
        $ticket = $this->_coreRegistry->registry('helpdesk_ticket');
        /** @var \Magento\Framework\Data\Form $form */
        $canAssignTicket = $this->_isAllowedAction(AclRole::ASSIGN_TICKET);
        $canEditTicket = $this->_isAllowedAction(AclRole::EDIT_TICKET);
        $canChangeTeam = $this->_isAllowedAction(AclRole::CHANGE_TEAM_TICKET);
        $teamOptions = $this->getTeamOptions();
        $userList = $this->getUserLists();
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('ticket_');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Team Information'), 'class' => 'fieldset-wide']
        );
        $fieldset->addField(
            'team_id',
            'select',
            [
                'name' => 'team_id',
                'label' => __('Team'),
                'title' => __('Team'),
                'required' => true,
                'values' => $teamOptions,
                'disabled' => $canChangeTeam == false,
                'value' => $ticket->getTeamId()
            ]
        );
        $fieldset->addField(
            'user_id',
            'select',
            [
                'name' => 'user_id',
                'label' => __('Responsibility By'),
                'title' => __('Responsibility By'),
                'required' => true,
                'values' => $userList,
                'disabled' => $canAssignTicket == false,
                'value' => $ticket->getUserId()
            ]
        );
        if ($ticket->getUserName()) {
            $fieldset->addField(
                'user_name',
                'label',
                [
                    'name' => 'user_name',
                    'label' => __('User Name'),
                    'title' => __('User Name'),
                    'required' => false,
                    'disabled' => $canEditTicket == false,
                    'value' => $ticket->getUserName()
                ]
            );
        }
        if ($ticket->getUserEmail()) {
            $fieldset->addField(
                'user_email',
                'label',
                [
                    'name' => 'user_id',
                    'label' => __('User Email'),
                    'title' => __('User Email'),
                    'required' => false,
                    'disabled' => $canEditTicket == false,
                    'value' => $ticket->getUserEmail()
                ]
            );
        }
        $form->setValues($ticket->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('General');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('General');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Check permission for passed action
     *
     * @param string $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * @return array
     */
    private function getTeamOptions()
    {
        if ($this->teams === null) {
            $this->teams = $this->teamCollectionFactory->create()
                ->addFieldToFilter('is_active', 1)
                ->toOptionArray();
            array_unshift($this->teams, [
                'label' => __('--Please Select Team--'),
                'value' => ''
            ]);
        }
        return $this->teams;
    }

    /**
     * @return array[]
     */
    private function getUserLists()
    {
        if ($this->userLists === null) {
            foreach ($this->userCollectionFactory->create() as $user) {
                $this->userLists[] = [
                    'value' => $user->getId(),
                    'label' => $user->getName()
                ];
            };
            array_unshift($this->userLists, [
                'label' => __('--Please Select User--'),
                'value' => ''
            ]);
        }
        return $this->userLists;
    }
}
