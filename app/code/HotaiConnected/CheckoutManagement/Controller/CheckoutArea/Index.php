<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HotaiConnected\CheckoutManagement\Controller\CheckoutArea;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Customer\Model\Url as CustomerUrl;

class Index extends \Webkul\Marketplace\Controller\Account\Editprofile
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var State
     */
    protected $appState;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param MarketplaceHelper $marketplaceHelper
     * @param CustomerUrl $customerUrl
     * @param State $appState
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        MarketplaceHelper $marketplaceHelper = null,
        CustomerUrl $customerUrl = null,
        State $appState = null
    ) {
        $this->_customerSession = $customerSession;
        $this->_resultPageFactory = $resultPageFactory;
        $this->context = $context;
        $this->marketplaceHelper = $marketplaceHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MarketplaceHelper::class);
        $this->customerUrl = $customerUrl ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CustomerUrl::class);
        $this->appState = $appState ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(State::class);
        parent::__construct($context, $resultPageFactory, $customerSession, $marketplaceHelper, $customerUrl);
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        // 檢查是否在後台環境，如果是則不執行此控制器
        try {
            $areaCode = $this->appState->getAreaCode();
            if ($areaCode === Area::AREA_ADMINHTML) {
                // 如果在後台環境，直接返回，讓後台路由處理
                $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
                return $this->getResponse();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            // 如果無法獲取 area code，繼續執行（可能是未初始化）
        }

        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Checkout Area page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        // 檢查Marketplace功能是否啟用
        $helper = $this->marketplaceHelper;
        if (!$helper->getSellerProfileDisplayFlag()) {
            $this->getRequest()->initForward();
            $this->getRequest()->setActionName('noroute');
            $this->getRequest()->setDispatched(false);
            return false;
        }

        // 所有檢查通過，顯示頁面
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_resultPageFactory->create();

        // 載入我們的 layout handle
        $resultPage->addHandle('checkout_management_checkoutarea_index');
          
        // 設定頁面標題
        $resultPage->getConfig()->getTitle()->set(__('Checkout Area'));

        // 取得 filtersData、batchActions 和 actionsData 並傳遞給 Block
        $filtersData = $this->getFiltersData();
        $batchActions = $this->getBatchActions();
        $actionsData = $this->getActionsData();
        $block = $resultPage->getLayout()->getBlock('hotai_connected_checkout_management_checkoutarea_content');
        if ($block) {
            $block->setData('filters_data', $filtersData);
            $block->setData('batch_actions', $batchActions);
            $block->setData('actions_data', $actionsData);
        }
        
        return $resultPage;
    }

    /**
     * Get batch actions configuration
     *
     * @return array
     */
    protected function getBatchActions()
    {
        // 下拉選單
        $data = [
            // 批量操作將在此處添加
        ];

        return $data;
    }

    /**
     * Get filters data configuration
     *
     * @return array
     */
    protected function getFiltersData()
    {
        $data = [
            [
                'note' => '訂單結帳序號異動日(起/訖)',
                'type' => 'date-range',
                'label' => __('Order Checkout Number Modification Date (From-To)'),
                'name' => 'checkout_date',
                'date_from' => [
                    'name' => 'from_date',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
                'date_to' => [
                    'name' => 'to_date',
                    'placeholder' => 'YYYY-MM-DD',
                    'validate' => false,
                    'required' => false,
                    'msg_required' => 'This is a required field.',
                ],
            ]
        ];

        return $data;
    }

    /**
     * Get actions data configuration
     *
     * @return array
     */
    protected function getActionsData()
    {
        $data = [];

        return $data;
    }
}

