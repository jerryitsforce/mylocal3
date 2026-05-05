<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;


abstract class ParentOrder extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::parent_orders';

    /**
     * Array of actions which can be processed without secret key validation
     *
     * @var string[]
     */
    protected $_publicActions = ['view', 'index'];
    /**
     * @var Registry
     */
    protected Registry $_coreRegistry;
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;
    /**
     * @var ParentOrderRepositoryInterface
     */
    protected $parentOrderRepository;
    /**
     * @var ParentOrderManagementInterface
     */
    protected ParentOrderManagementInterface $parentOrderManagement;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     */
    public function __construct(
        Action\Context                 $context,
        Registry                       $coreRegistry,
        PageFactory                    $resultPageFactory,
        LoggerInterface                $logger,
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement
    )
    {
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->_coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    protected function _initAction()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Sales::sales_order');
        $resultPage->addBreadcrumb(__('Sales'), __('Sales'));
        $resultPage->addBreadcrumb(__('Orders'), __('Orders'));
        return $resultPage;
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Model\ParentOrder|false
     */
    protected function _initParentOrder()
    {
        $id = $this->getRequest()->getParam('id');
        try {
            /**
             * @var $parentOrder \Branch8\MarketPlaceParentOrder\Model\ParentOrder
             */
            $parentOrder = $this->parentOrderRepository->get((int)$id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This parent order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return false;
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage(__('This parent order no longer exists.'));
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
            return false;
        }
        $this->_coreRegistry->register('parent_order', $parentOrder);
        $this->_coreRegistry->register('current_parent_order', $parentOrder);
        return $parentOrder;
    }

    /**
     * @return bool
     */
    protected function isValidPostRequest()
    {
        $formKeyIsValid = $this->_formKeyValidator->validate($this->getRequest());
        $isPost = $this->getRequest()->isPost();
        return ($formKeyIsValid && $isPost);
    }
}
