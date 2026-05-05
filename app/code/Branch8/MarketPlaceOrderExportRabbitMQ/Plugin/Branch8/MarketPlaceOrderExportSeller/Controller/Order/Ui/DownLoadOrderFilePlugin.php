<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Plugin\Branch8\MarketPlaceOrderExportSeller\Controller\Order\Ui;

use Branch8\MarketPlaceOrderExport\Model\Writer;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Config;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue\Publish;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile as ProfileResource;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Message\Manager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Branch8\MarketPlaceOrderExportAdminUi\Model\Filter;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MpOrdersCollection;

class DownLoadOrderFilePlugin
{
    private Config $config;
    private ProfileFactory $profileFactory;
    private ProfileResource $profileResource;
    private Publish $publish;

    private Manager $manager;
    private \Magento\Backend\Model\Auth\Session $authSession;
    private \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory;
    private CollectionFactory $orderCollectionFactory;
    private Filter $filter;
    private HelperData $helper;
    private Session $customerSession;
    private CustomerFactory $customerFactory;

    private Manager $messageManager;
    private RequestInterface $request;

    private HttpContext $httpContext;

    /**
     * @param Filter $filter
     * @param Session $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param HelperData $helper
     * @param Config $config
     * @param Publish $publish
     * @param ProfileResource $profileResource
     * @param CustomerFactory $customerFactory
     * @param ProfileFactory $profileFactory
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param Manager $messageManager
     * @param RequestInterface $request
     * @param HttpContext $httpContext
     */
    public function __construct(
        Filter                                               $filter,
        Session                                              $customerSession,
        CollectionFactory                                    $orderCollectionFactory,
        HelperData                                           $helper,
        Config                                               $config,
        Publish                                              $publish,
        ProfileResource                                      $profileResource,
        CustomerFactory                                      $customerFactory,
        ProfileFactory                                       $profileFactory,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        Manager                                              $messageManager,
        RequestInterface                                     $request,
        HttpContext                                          $httpContext
    )
    {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->profileResource = $profileResource;
        $this->profileFactory = $profileFactory;
        $this->config = $config;
        $this->publish = $publish;
        $this->filter = $filter;
        $this->redirectFactory = $resultRedirectFactory;
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->httpContext = $httpContext;
    }

    /**
     * @param $subject
     * @param $proceed
     * @return mixed|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundExecute($subject, $proceed)
    {
        if (!$this->config->enable()) {
            return $proceed();
        }
        $threshold = $this->config->getThreshold();
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            $collection = $this->filter->getCollection(
                $this->orderCollectionFactory->create()
            );
            $ids = $collection->getAllIds();
            if (count($ids) <= $threshold) {
                return $proceed();
            }
            $sellerId = $this->helper->getCustomerId();
            //$sellerId = $this->customerSession->getCustomer()->getId();
            $customer = $this->customerFactory->create()->load($sellerId);
            $sellerName = $customer->getName();

            if ($ids) {
                $profile = $this->profileFactory->create();
                $profile->setOrderIds($ids)->setProfileType(
                    Profile::TYPE_SELLER
                )->setUserId((int)$customer->getId())
                    ->setReceiverName(($this->customerSession->getCustomer()->getName()))
                    ->setReceiverEmail($this->customerSession->getCustomer()->getEmail());
                $this->profileResource->save($profile);
                $this->publish->execute($profile);
                $this->messageManager->addSuccessMessage(__('Your profile id [%1],your export will be emailed to: [%2],you can also check status process at Orders -> Download Order Files',
                    $profile->getProfileId(),
                    $this->customerSession->getCustomer()->getEmail())
                );
            } else {
                $this->messageManager->addNotice(
                    __('There are no download files related to selected order(s).')
                );
            }
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('marketplace/order/history');
        } else {
            return $this->redirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->request->isSecure()]
            );
        }
    }
}
