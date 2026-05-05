<?php

namespace Branch8\Affiliate\Block\Adminhtml\Customer\Tab;

use Amasty\Affiliate\Api\Data\AccountInterface;
use Magento\Backend\Block\Template;

class Form extends \Amasty\Affiliate\Block\Adminhtml\Customer\Tab\Form
{
    /**
     * @var \Magento\Config\Model\Config\Source\YesnoFactory
     */
    private $yesnoFactory;

    /**
     * @var \Amasty\Affiliate\Api\AccountRepositoryInterface
     */
    private $accountRepository;

    /**
     * Form constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory
     * @param \Amasty\Affiliate\Api\AccountRepositoryInterface $accountRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory,
        \Amasty\Affiliate\Api\AccountRepositoryInterface $accountRepository,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $yesnoFactory, $accountRepository, $data);
        $this->yesnoFactory = $yesnoFactory;
        $this->accountRepository = $accountRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();
        $account = $this->accountRepository->getByCustomerId($this->getCustomerId());

        $fieldset = $form->getElement('amasty_affiliate_account_fieldset');
        $fieldset->removeField(AccountInterface::REFERRING_CODE);
        $fieldset->addField(
            AccountInterface::REFERRING_CODE,
            'text',
            [
                'name' => 'affiliate[referring_code]',
                'label' => __('Custom Affiliate Code'),
                'title' => __('Custom Affiliate Code'),
                'data-form-part' => 'customer_form',
                'note' => __('Please update the default affiliate code with the custom value.'),
                'required' => (!$account->getIsCustomReferringCode()) ? false : true,
                'class' => (!$account->getIsCustomReferringCode()) ? 'validate-alphanum': 'required-entry validate-alphanum',
                'disabled' => !$account->getIsCustomReferringCode()
            ]
        );

        return $this;
    }
}