<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiAuth\Controller\HotaiGoTravel;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

#[AllowDynamicProperties] class Index implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * @var HotaiAuthService
     */
    protected HotaiAuthService $hotaiAuthService;

    public function __construct(
        HotaiAuthService $hotaiAuthService,
        RequestInterface $request,
        PageFactory $resultPageFactory
    )
    {
        $this->hotaiAuthService = $hotaiAuthService;
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {

        $redirectUrl = $this->request->getParam('redirect_url');
        $utmSource = $this->request->getParam('utm_source');
        $utmMedium = $this->request->getParam('utm_medium');
        $utmCampaign = $this->request->getParam('utm_campaign');
        $data = $this->hotaiAuthService->hotaiGoTravel();

        $page = $this->resultPageFactory->create();
        $block = $page->getLayout()->getBlock("hotai.auth.account.go.travel");
        $block->setData('post_data', $data);

        return $page;
    }
}
