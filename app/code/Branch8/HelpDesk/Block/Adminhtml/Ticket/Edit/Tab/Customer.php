<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab;

use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;
use Magento\Framework\UrlInterface;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Customer extends \Magento\Backend\Block\Widget\Form\Generic implements
    \Magento\Backend\Block\Widget\Tab\TabInterface
{

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    protected $order;

    protected $orderRepository;

    private $adminPermission;

    private $maskRuleComposite;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Branch8\MaskCustomerInformation\Model\AdminPermission $adminPermission
     * @param MaskRulesCompositeInterface $maskRulesComposite
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context                $context,
        \Magento\Framework\Registry                            $registry,
        UrlInterface                                           $urlBuilder,
        \Magento\Framework\Data\FormFactory                    $formFactory,
        \Branch8\MaskCustomerInformation\Model\AdminPermission $adminPermission,
        MaskRulesCompositeInterface                            $maskRulesComposite,
        array                                                  $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->adminPermission = $adminPermission;
        $this->maskRuleComposite = $maskRulesComposite;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form tab configuration
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setShowGlobalIcon(true);
    }

    /**
     * Initialise form fields
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        /*
         * Checking if user have permissions to save information
         */
        $isElementDisabled = !$this->_isAllowedAction('Branch8_HelpDesk::ticket_edit');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(['data' => ['html_id_prefix' => 'chat_']]);

        $model = $this->_coreRegistry->registry('helpdesk_ticket');
        $canViewSensitiveInformation = $this->adminPermission->canView();
        $emailMasked = $canViewSensitiveInformation ? $model->getCustomerEmail() : $this->maskRuleComposite->mask(
            'email',
            $model->getCustomerEmail()
        );
        $phoneMasked = $canViewSensitiveInformation ? $model->getPhone() : $this->maskRuleComposite->mask(
            'phone',
            $model->getPhone()
        );
        $fieldset = $form->addFieldset(
            'base_fieldset',
            ['legend' => __('Customer Information'), 'class' => 'fieldset-wide', 'disabled' => $isElementDisabled]
        );
        $fieldset->addField(
            'customer_name',
            'note',
            [
                'name' => 'customer_name',
                'label' => __('Customer Name'),
                'title' => __('Customer Name'),
                'text' => $model->getCustomerName() ?: __('Guest')
            ]
        );
        $fieldset->addField(
            'customer_email',
            'note',
            [
                'name' => 'customer_email',
                'label' => __('Customer Email'),
                'title' => __('Customer Email'),
                'text' => $model->getCustomerEmail() ? "<a href='" . $this->urlBuilder->getUrl('customer/index/edit',
                        ['id' => $model->getCustomerId()]) . "' target='blank' title='" . __('View Customer') . "'>" . $emailMasked . '</a>' : __('Guest')
            ]
        );
        $fieldset->addField(
            'phone',
            'note',
            [
                'name' => 'phone',
                'label' => __('Phone'),
                'title' => __('Phone'),
                'text' => $phoneMasked
            ]
        );

        $form->setValues($model->getData());

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
        return __('Customer Information');
    }

    /**
     * Prepare title for tab
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Customer Information');
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
