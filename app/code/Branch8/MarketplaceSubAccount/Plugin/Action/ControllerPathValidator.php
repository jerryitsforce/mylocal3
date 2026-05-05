<?php

namespace Branch8\MarketplaceSubAccount\Plugin\Action;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\ControllersRepository;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory;
use Webkul\Marketplace\Model\SellerFactory;
use Webkul\SellerSubAccount\Api\SubAccountRepositoryInterface;
use Webkul\SellerSubAccount\Helper\Data;
use Webkul\SellerSubAccount\Helper\Data as MarketplaceHelperSeller;
use Webkul\SellerSubAccount\Helper\Data as SubAccountHelper;

class ControllerPathValidator {
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $_objectManager;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $_url;

    /**
     * @var \Magento\Framework\App\ResponseInterface
     */
    public $_response;

    /**
     * @var CustomerSession
     */
    public $_customerSession;

    /**
     * @var subAccountRepository
     */
    public $_subAccountRepository;

    /**
     * @var ControllersRepository
     */
    public $_controllersRepository;

    /**
     * @var MarketplaceHelper
     */
    public $_marketplaceHelper;

    /**
     * @var SubAccountHelper
     */
    public $_subAccountHelper;

    /**
     * @var CollectionFactory
     */
    public $_sellerCollection;

    /**
     * @var CollectionFactory
     */
    public $_marketplaceHelperSeller;

    /**
     * @var Data
     */
    public $helperData;

    /**
     * @var SellerFactory
     */
    public $sellerModel;
    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param SubAccountRepositoryInterface $subAccountRepository
     * @param ControllersRepository $controllersRepository
     * @param MarketplaceHelper $marketplaceHelper
     * @param MarketplaceHelperSeller $marketplaceHelperSeller
     * @param SubAccountHelper $subAccountHelper
     * @param CollectionFactory $sellerCollection
     * @param Data $helperData
     * @param SellerFactory $sellerModel
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        SubAccountRepositoryInterface $subAccountRepository,
        ControllersRepository $controllersRepository,
        MarketplaceHelper $marketplaceHelper,
        MarketplaceHelperSeller $marketplaceHelperSeller,
        SubAccountHelper $subAccountHelper,
        CollectionFactory $sellerCollection,
        Data $helperData,
        SellerFactory $sellerModel
    ) {
        $this->_objectManager = $context->getObjectManager();
        $this->_url = $context->getUrl();
        $this->_response = $context->getResponse();
        $this->_customerSession = $customerSession;
        $this->_subAccountRepository = $subAccountRepository;
        $this->_controllersRepository = $controllersRepository;
        $this->_marketplaceHelper = $marketplaceHelper;
        $this->_marketplaceHelperSeller = $marketplaceHelperSeller;
        $this->_subAccountHelper = $subAccountHelper;
        $this->_sellerCollection = $sellerCollection;
        $this->helperData = $helperData;
        $this->sellerModel = $sellerModel;
    }

    /**
     * Redirect to customer page if action is not allowed in the current group
     *
     * @param \Magento\Framework\App\Action\Action $dispatch
     * @param RequestInterface $request
     * @return void
     */
    public function beforeDispatch(\Magento\Framework\App\Action\Action $dispatch, $request)
    {
        if ($this->_customerSession->isLoggedIn()) {
            $subAccount = $this->_subAccountHelper->getCurrentSubAccount();
            if ($subAccount->getId()) {
                // Start calculationg correct current controller path
                $controllerPath = $request->getFullActionName();
                $controllerPathArr = explode("_", $controllerPath);
                $controllerPathcount = count($controllerPathArr);
                $controllerPath1 = $controllerPathArr[0];
                $controllerPath3 = $controllerPathArr[$controllerPathcount - 1];
                unset($controllerPathArr[0]);
                unset($controllerPathArr[$controllerPathcount - 1]);
                $controllerPath2 = implode('_', $controllerPathArr);
                $controllerPathArrFinal = [$controllerPath1, $controllerPath2, $controllerPath3];
                $controllerPath = implode('/', $controllerPathArrFinal);
                // End calculationg correct current controller path
                $getAllPermissionTypes = $this->_subAccountHelper->getAllPermissionTypes();
                $getAllAllowedActions = $this->_subAccountHelper->getAllAllowedActions();
                /*For Permission to manage subaccount */
                if (array_key_exists('sellersubaccount/account/manage', $getAllAllowedActions)) {
                    $getAllAllowedActions['sellersubaccount/account/new'] = __('Add New Account');
                    $getAllAllowedActions['sellersubaccount/account/edit'] = __('Edit Sub Account');
                    $getAllAllowedActions['sellersubaccount/account/massdelete'] = __('Delete Sub Account');
                    $getAllAllowedActions['sellersubaccount/account/save'] = __('Save Sub Account');
                }
                if ($controllerPath!='marketplace/account/becomeseller' /*&&
                    (strpos($controllerPath, 'sellersubaccount/') === false)*/) {
                    /*For Product Attribute Controllers */
                    if (strpos($controllerPath, 'marketplace/product_attribute') !== false) {
                        $controllerPath = 'marketplace/product_attribute/new';
                    }
                    /*For Product's Controllers */
                    if ($controllerPath !== 'marketplace/product/productlist') {
                        if (strpos($controllerPath, 'marketplace/product/') !== false) {
                            $controllerPath = 'marketplace/product/add';
                        }
                    }
                    /*For Transaction's Controllers */
                    if (strpos($controllerPath, 'marketplace/transaction') !== false) {
                        $controllerPath = 'marketplace/transaction/history';
                    }
                    /*For Order's Controllers */
                    if ($controllerPath == 'marketplace/order/shipping') {
                        $controllerPath = 'marketplace/order/shipping';
                    } elseif ($controllerPath == 'marketplace/order/printpdfinfo') {
                        $controllerPath = 'marketplace/order/shipping';
                    } elseif (strpos($controllerPath, 'marketplace/order') !== false) {
                        $controllerPath = 'marketplace/order/history';
                    }else if($this->isCustomOrderAction($controllerPath)){
                        $controllerPath = 'marketplace/order/history';
                    }else if(in_array($controllerPath, $getAllAllowedActions)){
                        $controllerPath = 'sellersubaccount/account/manage';
                    }
                    $mappedPathArr = $this->_marketplaceHelper->getControllerMappedPermissions();

                    if (!empty($mappedPathArr[$controllerPath])) {
                        $controllerPath = $mappedPathArr[$controllerPath];
                    }
                    $url = $this->_url->getUrl('marketplace/account/becomeseller');
                    if (array_key_exists($controllerPath, $getAllPermissionTypes)):
                        $flag=false;
                        if (!$flag && !array_key_exists($controllerPath, $getAllAllowedActions)):
                            $url=$this->getControllerFirstAction($getAllAllowedActions, $controllerPath);
                            $flag=($url)?true:false;
                            $this->_response->setRedirect($url);
                            $this->_response->sendResponse();
                            return [$request];
                        endif;
                    endif;
                } elseif (!array_key_exists($controllerPath, $getAllAllowedActions)) {
                    $url = $this->_url->getUrl('marketplace/account/becomeseller');
                    $this->_response->setRedirect($url);
                    $this->_response->sendResponse();
                    return [$request];
                }
            }
        }
    }

    /**
     * Get Controller First Action
     *
     * @param array $getAllAllowedActions
     * @param int $controllerPath
     *
     * @return string
     */
    public function getControllerFirstAction($getAllAllowedActions, $controllerPath)
    {
        $url = $this->_url->getUrl('marketplace/');
        if (count($getAllAllowedActions)>=1):
            $controllerPath=array_key_first($getAllAllowedActions);
            $url = $this->_url->getUrl($controllerPath);
        endif;
        return $url;
    }

    public function isCustomOrderAction($controllerPath){
        $customControllerPath = [
            'b8marketplace/import/validate',
            'b8marketplace/import/importTrackingTemplate',
            'b8marketplace/import/execute',
            'b8marketplace/order/createShipment',
            'b8marketplace/order/saveTrackingNumber',
        ];
        return in_array($controllerPath, $customControllerPath);
    }
}
