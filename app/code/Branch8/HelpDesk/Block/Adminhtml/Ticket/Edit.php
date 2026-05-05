<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket;

use Branch8\HelpDesk\Model\Ticket\AclRole;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $authSession;

    protected $_storeManager;

    /**
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry           $registry,
        \Magento\Backend\Model\Auth\Session   $authSession,
        array                                 $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_storeManager = $context->getStoreManager();
        $this->authSession = $authSession;
        $this->_coreRegistry = $registry;

    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'ticket_id';
        $this->_blockGroup = 'Branch8_HelpDesk';
        $this->_controller = 'adminhtml_ticket';
        parent::_construct();
        if ($this->_isAllowedAction(AclRole::EDIT_TICKET)) {
            $this->buttonList->update('save', 'label', __('Save Ticket'));
            $this->buttonList->add(
                'saveandcontinue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => ['event' => 'saveAndContinueEdit', 'target' => '#edit_form'],
                        ],
                    ]
                ],
                -100
            );

        } else {
            $this->buttonList->remove('save');
        }
        if ($this->_isAllowedAction(AclRole::NEW_TICKET)) {
            $this->buttonList->add('new', [
                'label' => __('New Ticket'),
                'class' => 'save',
                'onclick' => 'setLocation(\'' . $this->getNewTicketUrl() . '\')',
            ], -1000);
        } else {
            $this->buttonList->remove('new');
        }
        if ($this->_isAllowedAction(AclRole::REMOVE_TICKET)) {
            $this->buttonList->update('delete', 'label', __('Delete Ticket'));
        } else {
            $this->buttonList->remove('delete');
        }
        $this->buttonList->remove('reset');
    }

    /**
     * Retrieve text for header element depending on loaded page
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        $ticket = $this->_coreRegistry->registry('helpdesk_ticket');
        if ($ticket->getId()) {
            $ticketCode = $ticket->getCode() ? $ticket->getCode() : $ticket->getId();
            return __("View Ticket #%1 - '%2'", $this->escapeHtml($ticketCode), $this->escapeHtml($ticket->getSubject()));
        } else {
            return __('New Ticket');
        }
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
     * Prepare layout
     *
     * @return \Magento\Framework\View\Element\AbstractBlock
     */
    protected function _prepareLayout()
    {
        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('page_content') == null) {
                    tinyMCE.execCommand('mceAddControl', false, 'page_content');
                } else {
                    tinyMCE.execCommand('mceRemoveControl', false, 'page_content');
                }
            };
        ";
        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    private function getNewTicketUrl(){
        return $this->getUrl('helpdesk/ticket_create/index');
    }
}
