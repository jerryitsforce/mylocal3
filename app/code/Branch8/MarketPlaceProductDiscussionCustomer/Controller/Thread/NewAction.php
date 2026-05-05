<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Controller\Thread;

use Branch8\MarketPlaceProductDiscussionCustomer\Model\ConfigData;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controller for the 'product_discussion/thread/new' URL route.
 */
class NewAction implements HttpGetActionInterface, ViewInterface
{
    private \Magento\Customer\Model\Session $customerSession;
    private RedirectFactory $resultRedirectFactory;
    private PageFactory $resultPageFactory;
    private $configData;
    private ProductRepository $productRepository;
    private RequestInterface $request;
    private ManagerInterface $messageManager;
    private $registry;
    private StoreManagerInterface $storeManger;

    private $url;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param PageFactory $resultPageFactory
     * @param ConfigData $configData
     * @param ProductRepository $productRepository
     * @param ManagerInterface $manager
     * @param RequestInterface $request
     * @param Registry $registry
     * @param StoreManagerInterface $storeManger
     * @param UrlInterface $url
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        RedirectFactory                 $resultRedirectFactory,
        PageFactory                     $resultPageFactory,
        ConfigData                      $configData,
        ProductRepository               $productRepository,
        ManagerInterface                $manager,
        RequestInterface                $request,
        Registry                        $registry,
        StoreManagerInterface           $storeManger,
        UrlInterface                    $url,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->url = $url;
        $this->request = $request;
        $this->configData = $configData;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->messageManager = $manager;
        $this->registry = $registry;
        $this->storeManger = $storeManger;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $enable = (bool)$this->configData->getValue('enabled');
        if (!$enable) {
            $resultRedirect->setPath('404');
            return $resultRedirect;
        }
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect->setPath('customer/account/');
        }
        try {
            $product = $this->productRepository->get($this->request->getParam('sku'));
            $this->registry->register('current_product', $product);
            $this->registry->register('product', $product);
            $page = $this->resultPageFactory->create();
            // $pageMainTitle = $page->getLayout()->getBlock('page.main.title');
            // if ($pageMainTitle) {
            //     $pageMainTitle->setPageTitle($product->getName());
            // }
            $this->resolveBreadCrumbs($page, $product);
            return $page;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('*');
            return $resultRedirect;
        }
    }

    /**
     * @param \Magento\Framework\View\Result\Page $page
     * @param Product $product
     * @return \Magento\Framework\View\Result\Page
     * @throws NoSuchEntityException
     */
    private function resolveBreadCrumbs(\Magento\Framework\View\Result\Page $page, Product $product)
    {
        $breadcrumbs = $page->getLayout()->getBlock('breadcrumbs');
        $referer = $this->request->getParam('referer', false);
        $params = [
            'home' => [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link' => $this->storeManger->getStore()->getBaseUrl()
            ]
        ];
        switch ($referer) {
            case 'list':
                $params['list'] = [
                    'label' => __('回到全部問答'),
                    'title' => __('回到全部問答'),
                    'link' => $this->url->getUrl(
                        'product_discussion/thread/list', ['sku' => $product->getSku(), 'referer' => 'list']
                    )
                ];
                break;
            default:
                $params['product'] = [
                    'label' => __('回到商品頁'),
                    'title' => __('回到商品頁'),
                    'link' => $this->url->getUrl(
                        $product->getProductUrl(), ['sku' => $product->getSku(), 'referer' => 'list']
                    )
                ];
                break;
        }
        $params['current'] = [
            'label' => __('我要提問'),
            'title' => __('我要提問'),
        ];
        foreach ($params as $name => $param) {
            $breadcrumbs->addCrumb($name, $param);
        }
        return $page;
    }
}
