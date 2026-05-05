<?php
namespace Branch8\RoleDelegate\Controller\Adminhtml\Manage;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Branch8\RoleDelegate\Repository\DelegateRepository;
use Magento\Framework\Controller\Result\RedirectFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;

class Cancel extends Action
{
    const ADMIN_RESOURCE = 'Branch8_RoleDelegate::manage';

    /**
     * @var DelegateRepository
     */
    protected $delegateRepository;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        Context $context,
        DelegateRepository $delegateRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->delegateRepository = $delegateRepository;
        $this->logger = $logger;
    }

    /**
     * Execute cancel action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = (int)$this->getRequest()->getParam('delegate_id');
        if (!$id) {
            $id = (int)$this->getRequest()->getParam('id');
            if (!$id) {
                $this->messageManager->addErrorMessage(__('Missing delegation ID.'));
                return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
            }
        }

        try {
            $model = $this->delegateRepository->getById($id);

            if (!$model->getId()) {
                throw new LocalizedException(__('Delegation not found.'));
            }

            if ($model->getStatus() === 'cancelled') {
                throw new LocalizedException(__('This delegation is already cancelled.'));
            }

            // Update status
            $model->setData('status', 'cancelled');
            $model->setData('updated_by', $this->_auth->getUser()->getId());
            $this->delegateRepository->save($model);

            $this->messageManager->addSuccessMessage(__('Delegation has been successfully cancelled.'));
        } catch (LocalizedException $e) {
            $this->logger->error('Error cancelling delegation: ' . $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->error('Error cancelling delegation: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('An error occurred while cancelling delegation.'));
        }

        return $resultRedirect->setUrl($this->_redirect->getRefererUrl());
    }
}
