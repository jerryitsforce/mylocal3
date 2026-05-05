<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Controller\Thread;

use Branch8\MarketPlaceProductDiscussionCustomer\Model\ConfigData;
use Magento\Catalog\Controller\Product\View\ViewInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controller for the 'product_discussion/thread/list' URL route.
 */
class ListAction implements HttpGetActionInterface
{
    private RedirectFactory $resultRedirectFactory;
    private PageFactory $resultPageFactory;
    private $configData;
    private ProductRepository $productRepository;
    private RequestInterface $request;
    private ManagerInterface $messageManager;
    private $registry;

    private StoreManagerInterface $storeManger;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param PageFactory $resultPageFactory
     * @param ConfigData $configData
     * @param ProductRepository $productRepository
     * @param ManagerInterface $manager
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     */
    public function __construct(
        RedirectFactory       $resultRedirectFactory,
        PageFactory           $resultPageFactory,
        ConfigData            $configData,
        ProductRepository     $productRepository,
        ManagerInterface      $manager,
        RequestInterface      $request,
        StoreManagerInterface $storeManager,
        Registry              $registry
    )
    {
        $this->request = $request;
        $this->configData = $configData;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->productRepository = $productRepository;
        $this->messageManager = $manager;
        $this->registry = $registry;
        $this->storeManger = $storeManager;
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
        try {
            $product = $this->productRepository->get($this->request->getParam('sku'));
            $this->registry->register('current_product', $product);
            $this->registry->register('product', $product);
            $page = $this->resultPageFactory->create();
            return $page;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('*');
            return $resultRedirect;
        }
    }
}
