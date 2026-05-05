<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab;

use Branch8\HelpDesk\Model\Ticket\AclRole;
class Messages extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    /**
     * @var \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface
     */
    protected $pageLayoutBuilder;

    protected $authSession;

    protected $userFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context                          $context,
        \Magento\Framework\Registry                                      $registry,
        \Magento\Framework\Data\FormFactory                              $formFactory,
        \Magento\Store\Model\System\Store                                $systemStore,
        \Magento\Framework\View\Model\PageLayout\Config\BuilderInterface $pageLayoutBuilder,
        array                                                            $data = []
    )
    {
        $this->pageLayoutBuilder = $pageLayoutBuilder;
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
         * @var $ticket \Branch8\HelpDesk\Model\Ticket\
         */
        $ticket = $this->_coreRegistry->registry('helpdesk_ticket');
        $havePermission = $this->_isAllowedAction(AclRole::ANSWER_TICKET);
        /* @var $layoutBlock \Magento\Widget\Block\Adminhtml\Widget\Instance\Edit\Tab\Main\Layout */
        $layoutBlock = $this->getLayout()->getBlock(
            'helpdesk.ticket.messages'
        );
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('ticket_');

        $fieldset = $form->addFieldset('base_fieldset',
            ['legend' => __('Message Information'), 'class' => 'fieldset-wide']
        );
        $fieldset->addField('messages_component', 'note', []);
        $form->getElement('messages_component')
            ->setRenderer($layoutBlock);
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
        return __('Messages');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Messages');
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

}
