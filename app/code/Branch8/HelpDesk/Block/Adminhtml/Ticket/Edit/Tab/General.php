<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab;

use Branch8\HelpDesk\Model\ResourceModel\Attachment;
use Branch8\HelpDesk\Model\ResourceModel\Category;
use Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory;
use Branch8\HelpDesk\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Branch8\HelpDesk\Model\Services\GetAttachmentsAsHtml;
use Branch8\HelpDesk\Model\Ticket;
use Branch8\HelpDesk\Model\Ticket\AclRole;
use Branch8\HelpDesk\Model\Ticket\Priority;
use Branch8\HelpDesk\Model\Ticket\Status;

class General extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;
    /**
     * @var
     */
    protected $authSession;
    /**
     * @var
     */
    protected $userFactory;
    private $categoryCollectionfctory;

    private $orderCollectionFactory;
    private $priorities = null;
    private $categories = null;
    private $statues = null;
    private $priority;
    private $status;

    private $attachmentAsHtml;

    private $attachmentCollectionFactory;

    private $wysiwygConfig;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Store\Model\System\Store $systemStore
     * @param CollectionFactory $categoryCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param Priority $priority
     * @param Status $status
     * @param GetAttachmentsAsHtml $attachmentsAsHtml
     * @param Attachment\CollectionFactory $attachmentCollectionFactory
     * @param \Magento\Cms\Model\Wysiwyg\Config $wysiwygConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry             $registry,
        \Magento\Framework\Data\FormFactory     $formFactory,
        \Magento\Store\Model\System\Store       $systemStore,
        Category\CollectionFactory              $categoryCollectionFactory,
        OrderCollectionFactory                  $orderCollectionFactory,
        Priority                                $priority,
        Status                                  $status,
        GetAttachmentsAsHtml                    $attachmentsAsHtml,
        Attachment\CollectionFactory            $attachmentCollectionFactory,
        \Magento\Cms\Model\Wysiwyg\Config       $wysiwygConfig,
        array                                   $data = []
    )
    {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->status = $status;
        $this->priority = $priority;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->_systemStore = $systemStore;
        $this->attachmentAsHtml = $attachmentsAsHtml;
        $this->categoryCollectionfctory = $categoryCollectionFactory;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->attachmentCollectionFactory = $attachmentCollectionFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * Prepare form
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
        $isElementDisabled = $this->_isAllowedAction(AclRole::EDIT_TICKET);
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('ticket_');
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Ticket Information'), 'class' => 'fieldset-wide']
        );
        $categories = $this->getCategoryOptions();
        $priorities = $this->getPriorities();
        $statues = $this->getStatues();
        $fieldset->addField('code', 'label', [
            'label' => __('Ticket Code'),
            'name' => 'code',
            'value' => $ticket->getCode()
        ]);

        $widgetFilters = ['is_email_compatible' => 0];
        $wysiwygConfig = $this->wysiwygConfig->getConfig([
                'readonly' => true,
                'add_variables' => false,
                'add_widgets' => false,
                'add_images' => false,
                'add_directives' => false,
                'toggle_button' => false
            ]
        );
        $settings = $wysiwygConfig->getData('settings');
        $settings['readonly'] = true;

        $wysiwygConfig->setData('settings', $settings);
        if ($ticket->getTicketId()) {
            $fieldset->addField(
                'ticket_id', 'hidden',
                [
                    'name' => 'ticket_id',
                    'value' => $ticket->getTicketId()
                ]
            );
        }
        $fieldset->addField(
            'category_id',
            'select',
            [
                'name' => 'category_id',
                'label' => __('Category'),
                'title' => __('Category'),
                'required' => true,
                'values' => $categories,
                'disabled' => $isElementDisabled == false,
                'value' => $ticket->getCategoryId()
            ]
        );
        $fieldset->addField(
            'priority',
            'select',
            [
                'name' => 'priority',
                'label' => __('Priority'),
                'title' => __('Priority'),
                'required' => true,
                'values' => $priorities,
                'disabled' => $isElementDisabled == false,
                'value' => $ticket->getPriority()
            ]
        );
        $this->getOrderField($ticket, $fieldset, $isElementDisabled);
        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'values' => $statues,
                'disabled' => $isElementDisabled == false,
                'value' => $ticket->getStatus()
            ]
        );

        $fieldset->addField('content',
            'editor', [
                'label' => __('Problem Description'),
                'state' => 'html',
                'required' => false,
                'value' => $ticket->getContent(),
                'style' => 'height: 600px;',
                'config' => $wysiwygConfig,
                'class' => 'text-underline'
            ]);
        if ($ticket->getData('attachment_ids')) {
            $attachmentIds = explode(',',
                (string)$ticket->getData('attachment_ids')
            );
            if ($attachmentIds) {
                $fieldset->addField(
                    'attachments',
                    'note', [
                    'label' => __('Attachments'),
                    'name' => 'attachments',
                    'text' => $this->attachmentAsHtml->get(
                        $this->attachmentCollectionFactory->create()->addFieldTofilter(
                            'attachment_id',
                            ['in', $attachmentIds]
                        )
                    )
                ]);
            }

        }

        $form->setValues($ticket->getData());
        $this->setForm($form);
        return parent::_prepareForm();
    }

    /**
     * @param Ticket $ticket
     * @param \Magento\Framework\Data\Form\Element\FieldSet $fieldset
     * @param $isElementDisabled
     * @return void
     */
    public function getOrderField(
        Ticket                                        $ticket,
        \Magento\Framework\Data\Form\Element\FieldSet $fieldset,
                                                      $isElementDisabled
    )
    {
        $orderIds = explode(',', (string)$ticket->getOrder());
        $order = '';
        if ($orderIds) {
            $urls = [];
            $collection = $this->orderCollectionFactory->create()->addFieldToFilter('entity_id', ['in' => $orderIds]);
            foreach ($collection as $item) {
                $html = "<a href='" . $this->getUrl('sales/order/view',
                        ['order_id' => $item->getId()]) . "' target='blank' title='" . $item->getIncrementId() . "'>";
                $html .= '#' . $item->getIncrementId() . '</a>';
                $urls[] = $html;

            }
            $order = join(' ', $urls);
        }

        $fieldset->addField(
            'order',
            'note',
            [
                'name' => 'last_order',
                'label' => __('Order'),
                'title' => __('Order'),
                'required' => false,
                'disabled' => $isElementDisabled == false,
                'text' => $order
            ]
        );
    }

    /**
     * Prepare label for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Ticket Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Ticket Information');
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
    private function getCategoryOptions()
    {
        if ($this->categories === null) {
            $this->categories = $this->categoryCollectionfctory->create()
                ->addFieldToFilter('is_active', 1)
                ->toOptionArray();
            array_unshift($this->categories, [
                'label' => __('--Please Select Category--'),
                'value' => ''
            ]);
        }
        return $this->categories;
    }

    /**
     * @return array[]
     */
    private function getPriorities()
    {
        if ($this->priorities === null) {
            $this->priorities = $this->priority->toOptionArray();
            array_unshift($this->priorities, [
                'label' => __('--Please Select Priority--'),
                'value' => ''
            ]);
        }
        return $this->priorities;
    }

    /**
     * @return array[]
     */
    private function getStatues()
    {
        /**
         *
         */
        if ($this->statues === null) {
            $this->statues = $this->status->toOptionArray();
            array_unshift($this->statues, [
                'label' => __('--Please Select Status--'),
                'value' => ''
            ]);
        }
        return $this->statues;
    }
}
