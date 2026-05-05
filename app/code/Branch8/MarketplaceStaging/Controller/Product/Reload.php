<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Controller\Product;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Controller\ResultFactory;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Helper\Notification as NotificationHelper;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;

class Reload extends Action
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Array of actions which can be processed without secret key validation.
     *
     * @var array
     */
    protected $_publicActions = ['edit'];

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var CustomerUrl
     */
    protected $customerUrl;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var NotificationHelper
     */
    protected $notificationHelper;

    /**
     * @var CollectionFactory
     */
    protected $productCollection;

    /**
     * @var \Magento\Downloadable\Model\SampleFactory
     */
    protected $sample;

    /**
     * @var \Magento\Downloadable\Helper\File
     */
    protected $fileHelper;

    /**
     * @var \Magento\Downloadable\Helper\Download
     */
    protected $downloadHelper;

    /**
     * @var \Magento\Downloadable\Model\LinkFactory
     */
    protected $linkModel;
    /**
     * @var \Webkul\Marketplace\Controller\Product\Builder
     */
    protected $productBuilder;

    /**
     * @param \Magento\Framework\App\Action\Context         $context
     * @param \Webkul\Marketplace\Controller\Product\Builder $productBuilder
     * @param \Magento\Framework\View\Result\PageFactory    $resultPageFactory
     * @param \Magento\Customer\Model\Session               $customerSession
     * @param CustomerUrl                                   $customerUrl
     * @param HelperData                                    $helper
     * @param NotificationHelper                            $notificationHelper
     * @param CollectionFactory                             $productCollection
     * @param \Magento\Downloadable\Model\SampleFactory     $sample
     * @param \Magento\Downloadable\Helper\File             $fileHelper
     * @param \Magento\Downloadable\Helper\Download         $downloadHelper
     * @param \Magento\Downloadable\Model\LinkFactory       $linkModel
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Webkul\Marketplace\Controller\Product\Builder $productBuilder,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $customerSession,
        CustomerUrl $customerUrl = null,
        HelperData $helper = null,
        NotificationHelper $notificationHelper = null,
        CollectionFactory $productCollection = null,
        \Magento\Downloadable\Model\SampleFactory $sample = null,
        \Magento\Downloadable\Helper\File $fileHelper = null,
        \Magento\Downloadable\Helper\Download $downloadHelper = null,
        \Magento\Downloadable\Model\LinkFactory $linkModel = null
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct(
            $context
        );
        $this->productBuilder = $productBuilder;
        $this->_resultPageFactory = $resultPageFactory;
        $this->customerUrl = $customerUrl ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CustomerUrl::class);
        $this->helper = $helper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(HelperData::class);
        $this->notificationHelper = $notificationHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(NotificationHelper::class);
        $this->productCollection = $productCollection ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CollectionFactory::class);
        $this->sample = $sample ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Downloadable\Model\SampleFactory::class);
        $this->fileHelper = $fileHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Downloadable\Helper\File::class);
        $this->downloadHelper = $downloadHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Downloadable\Helper\Download::class);
        $this->linkModel = $linkModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Downloadable\Model\LinkFactory::class);
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
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * Seller Product Edit Action.
     *
     * @return mixed
     */
    public function execute()
    {
        if (!$this->getRequest()->getParam('set')) {
            return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
        }
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            $this->productBuilder->build(
                $this->getRequest()->getParams(),
                0
            );

            /** @var \Magento\Framework\View\Result\Layout $resultLayout */
            $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
            $resultLayout->getLayout()->getUpdate()->addHandle(['marketplacestaging_product_reload']);
            $resultLayout->getLayout()->getUpdate()->removeHandle('default');
            return $resultLayout;

        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
