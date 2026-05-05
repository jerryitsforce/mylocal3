<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Controller\Mui\Render;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Staging\Controller\Result\JsonFactory;
use Magento\Ui\Component\Control\ActionPool;
use Magento\Ui\Component\Wrapper\UiComponent;
use Magento\Ui\Controller\Adminhtml\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Ui\Controller\UiActionInterface;
use Webkul\Marketplace\Model\Notification;

/**
 * Class Handle
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Handle extends \Magento\Customer\Controller\AbstractAccount implements UiActionInterface
{
    /**
     * @var UiComponentFactory
     */
    protected $factory;

    /**
     * @var ContextFactory
     */
    private $contextFactory;

    /**
     * @var \Webkul\Marketplace\Controller\Product\Builder
     */
    protected $productBuilder;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helperData;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @param Context $context
     * @param UiComponentFactory $factory
     * @param \Webkul\Marketplace\Controller\Product\Builder $productBuilder
     * @param \Webkul\Marketplace\Helper\Data $helperData
     * @param JsonFactory $jsonFactory
     * @param ContextFactory|null $contextFactory
     */
    public function __construct(
        Context $context,
        UiComponentFactory $factory,
        \Webkul\Marketplace\Controller\Product\Builder $productBuilder,
        \Webkul\Marketplace\Helper\Data $helperData,
        JsonFactory $jsonFactory,
        ContextFactory $contextFactory = null
    ) {
        parent::__construct($context);
        $this->factory = $factory;
        $this->productBuilder = $productBuilder;
        $this->helperData = $helperData;
        $this->jsonFactory = $jsonFactory;
        $this->contextFactory = $contextFactory
            ?: ObjectManager::getInstance()->get(ContextFactory::class);
    }

    /**
     * Render UI component by namespace in handle context
     *
     * @return ResponseInterface|Json|ResultInterface|void
     */
    public function execute()
    {
        $isPartner = $this->helperData->isSeller();
        if ($isPartner == 1) {
            $handle = $this->_request->getParam('handle');
            $blockStage = $this->_request->getParam('block');
            $buttons = $this->_request->getParam('buttons', false);
            $this->productBuilder->build(
                $this->getRequest()->getParams(),
                0
            );
            $this->_view->loadLayout(['default', $handle], true, true, false);
            $layout = $this->_view->getLayout();

            $uiComponent = $layout->getBlock($blockStage);
            $response = $uiComponent ? $uiComponent->toHtml() : '';

            if ($buttons) {
                $actionsToolbar = $layout->getBlock(ActionPool::ACTIONS_PAGE_TOOLBAR);
                $response .= $actionsToolbar instanceof Template ? $actionsToolbar->toHtml() : '';
            }

            $this->_response->appendBody($response);
        } else {
            return $this->jsonFactory->create(['ajaxExpired' => 1, 'ajaxRedirect' => $this->_url->getUrl('marketplace/account/becomeseller', ['_secure' => $this->getRequest()->isSecure()])]);
        }
    }

    /**
     * ExecuteAjaxRequest Action for AJAX request.
     */
    public function executeAjaxRequest()
    {
        $this->execute();
    }

    /**
     * Call marketplace ui coponent prepare method.
     *
     * @param UiComponentInterface $componentInterface
     */
    protected function prepareMarketplaceUiComponent(UiComponentInterface $componentInterface)
    {
        foreach ($componentInterface->getChildComponents() as $childComponent) {
            $this->prepareMarketplaceUiComponent($childComponent);
        }
        $componentInterface->prepare();
    }
}
