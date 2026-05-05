<?php

namespace Branch8\RoleDelegate\Block\Adminhtml\User;

use Branch8\RoleDelegate\Block\Adminhtml\User\Grid\DelegateUsers;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\Authorization\Model\RoleFactory;
use Branch8\RoleDelegate\Api\DelegateRepositoryInterface;

class Delegate extends Generic
{
    public const COMM_TEMPLATE = 'Branch8_RoleDelegate::user/delegate.phtml';

    /**
     * @var Session
     */
    protected $authSession;

    /**
     * @var UserCollectionFactory
     */
    protected $userCollectionFactory;

    /**
     * @var RoleFactory
     */
    protected $roleFactory;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var DelegateRepositoryInterface
     */
    protected $delegateRepository;

    protected $blockGrid;


    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Session $authSession
     * @param UserCollectionFactory $userCollectionFactory
     * @param RoleFactory $roleFactory
     * @param TimezoneInterface $localeDate
     * @param DelegateRepositoryInterface $delegateRepository
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Session $authSession,
        UserCollectionFactory $userCollectionFactory,
        RoleFactory $roleFactory,
        TimezoneInterface $localeDate,
        DelegateRepositoryInterface $delegateRepository,
        array $data = []
    ) {
        $this->authSession = $authSession;
        $this->userCollectionFactory = $userCollectionFactory;
        $this->roleFactory = $roleFactory;
        $this->localeDate = $localeDate;
        $this->delegateRepository = $delegateRepository;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Set template to itself.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->setTemplate(static::COMM_TEMPLATE);

        return $this;
    }

    public function getUsersOptions()
    {
        $collection = $this->userCollectionFactory->create()
            ->addFieldToSelect(['user_id', 'username', 'firstname', 'lastname'])
            ->setOrder('firstname', 'ASC');

        $user = $this->_coreRegistry->registry('permissions_user');
        $loggedInUser = $this->authSession->getUser();

        $options = [];
        $options[] = ['value' => '', 'label' => __('-- Please Select --')];
        foreach ($collection as $u) {
            if ($u->getId() == $loggedInUser->getId() || ($user && $u->getId() == $user->getId())) {
                continue;
            }
            $options[] = [
                'value' => $u->getId(),
                'label' => $u->getUsername()
            ];
        }
        return $options;
    }

    /**
     * Prepare form fields
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return \Magento\Backend\Block\Widget\Form
     */
    protected function _prepareForm()
    {
        $this->setUseContainer(false);
        $model = $this->_coreRegistry->registry('permissions_user');
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('user_');
        $baseFieldset = $form->addFieldset('base_fieldset', ['legend' => __('Role Delegate')]);
        if ($model->getUserId()) {
            $baseFieldset->addField('user_id', 'hidden', ['name' => 'user_id']);
        }
        if ($this->getRequest()->getParam('delegate_id')) {
            $delegate = $this->delegateRepository->getById($this->getRequest()->getParam('delegate_id'));
            if ($delegate->getId() && $delegate->getUserId() == $model->getUserId() && $delegate->getStatus() == 'pending') {
                $startAt = $this->localeDate->date(
                    new \DateTime($delegate->getStartAt())
                );
                $endAt = $this->localeDate->date(
                    new \DateTime($delegate->getEndAt())
                );
                $model->setData('delegate_id', $delegate->getId());
                $model->setData('delegate_user_id', $delegate->getDelegateUserId());
                $model->setData('delegate_start_date', $startAt);
                $model->setData('delegate_end_date', $endAt);
            }
            $baseFieldset->addField(
                'delegate_id',
                'hidden',
                ['name' => 'role_delegate[delegate_id]']
            );
        }

        $dateFormat = 'Y/M/d';
        $timeFormat = 'HH:mm';
        $currentDate = $this->localeDate->date()->format('Y/m/d');
        $baseFieldset->addField(
            'delegate_user_id',
            'select',
            [
                'name' => 'role_delegate[delegate_user_id]',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Delegate'),
                'title' => __('Delegate'),
                'value' => '',
                'values' => $this->getUsersOptions(),
            ]
        );
        $baseFieldset->addField(
            'delegate_start_date',
            'date',
            [
                'name' => 'role_delegate[start_date]',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('Start Date'),
                'title' => __('Start Date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'min_date' => $currentDate,
                'class' => 'validate-date',
            ]
        );
        $baseFieldset->addField(
            'delegate_end_date',
            'date',
            [
                'name' => 'role_delegate[end_date]',
                'data-form-part' => $this->getData('target_form'),
                'label' => __('End Date'),
                'title' => __('End Date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'min_date' => $currentDate,
                'class' => 'validate-date',
            ]
        );
        $data = $model->getData();
        $form->setValues($data);

        $this->setForm($form);

        return parent::_prepareForm();
    }

    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                DelegateUsers::class,
                'user.delegate.grid'
            );
        }
        return $this->blockGrid;
    }

    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }
}
