<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiAuth\Controller\GroupApps;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Service\HotaiAuthService;
use JsonException;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{
    /**
     * @var HotaiAuthService
     */
    protected $hotaiAuthService;
    
    /**
     * @var PageFactory
     */
    protected $request;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        HotaiAuthService $hotaiAuthService,
        RequestInterface $request,
    )
    {
        $this->hotaiAuthService = $hotaiAuthService;
        $this->request = $request;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     * @throws JsonException
     */
    public function execute(): ResultInterface
    {
        $device = $this->request->getParam('device');
        $data = $this->hotaiAuthService->getGroupApps($device);

        $page = $this->resultPageFactory->create();
        $block = $page->getLayout()->getBlock("hotai.auth.account.groupapps");
        $block->setData('group_apps', $data);

        return $page;
    }
}
