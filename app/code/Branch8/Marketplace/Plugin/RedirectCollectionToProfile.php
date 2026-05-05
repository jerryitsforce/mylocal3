<?php

namespace Branch8\Marketplace\Plugin;

use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Webkul\Marketplace\Helper\Data as HelperData;

class RedirectCollectionToProfile
{
    protected $actionFlag;

    protected $response;

    protected $helper;

    protected $urlBuilder;

    public function __construct(
        ActionFlag $actionFlag,
        ResponseInterface $response,
        HelperData $helper,
        \Magento\Framework\UrlInterface $urlBuilder
    )
    {
        $this->actionFlag = $actionFlag;
        $this->response = $response;
        $this->helper = $helper;
        $this->urlBuilder = $urlBuilder;
    }


    public function aroundExecute($subject, $process){
        /** @see \Magento\Framework\App\FrontController::getActionResponse */
        $this->actionFlag->set('', ActionInterface::FLAG_NO_DISPATCH, true);

        $helper = $this->helper;
        if (!$helper->getSellerProfileDisplayFlag()) {
            $this->getRequest()->initForward();
            $this->getRequest()->setActionName('noroute');
            $this->getRequest()->setDispatched(false);

            return false;
        }
        $shopUrl = $this->helper->getCollectionUrl();
        if (!$shopUrl) {
            $shopUrl = $subject->getRequest()->getParam('shop');
        }
        $url = $this->urlBuilder->getUrl('marketplace/seller/profile', ['shop' => $shopUrl]);

        $this->response
            ->setRedirect($url)
            ->sendResponse();
    }

}