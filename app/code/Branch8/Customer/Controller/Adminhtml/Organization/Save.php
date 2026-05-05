<?php

namespace Branch8\Customer\Controller\Adminhtml\Organization;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Save extends Action{
    /**
     * @var \Branch8\Customer\Model\OrganizationFactory
     */
    protected $organizationFactory;

    protected $_timezone;

    protected $_organizationHelper;

    /**
     * @param Context $context
     * @param \Branch8\Customer\Model\OrganizationFactory $organizationFactory
     */
    public function __construct(
        Context $context,
        \Branch8\Customer\Model\OrganizationFactory $organizationFactory,
        \Branch8\Customer\Helper\Organization $organizationHelper,
        TimezoneInterface $_timezone,
    ) {
        $this->organizationFactory = $organizationFactory;
        $this->_timezone = $_timezone;
        parent::__construct($context);
        $this->_organizationHelper = $organizationHelper;
    }
    /**
     * Provides content
     *
     * @return Magento\Framework\Controller\Result\Redirect
     */
    public function execute(){

        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParam('information');
        try {
            if (isset($data['entity_id']) && $data['entity_id']) {
                $model = $this->organizationFactory->create()->load($data['entity_id']);
                $model->setName($data['name'])
                    ->setData('hotai1_name',$data['hotai1_name'])
                    ->save();
                $this->messageManager->addSuccessMessage(__('You have updated the Organization successfully.'));
            } else {
                $model = $this->organizationFactory->create();
                $model->setName($data['name'])
                    ->setData('hotai1_name',$data['hotai1_name'])
                    ->setCreatedAt($this->_timezone->convertConfigTimeToUtc($this->_timezone->date(), 'Y-m-d H:i:s'))
                    ->save();

                $this->messageManager->addSuccessMessage(__('You have successfully created the Organization.'));
            }
            //update fullname of group
            $this->_organizationHelper->updateGroupFullname($model->getId(), $model->getName());
        }catch(\Exception $e) {
            $this->messageManager->addErrorMessage(__('Something went wrong while trying to save the Organization. Please try again.'));
        }
        return $resultRedirect->setPath('*/*/');
    }
    /**
     * Check Autherization
     *
     * @return boolean
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_Customer::organization');
    }
}