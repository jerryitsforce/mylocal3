<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Controller\Member;

use Branch8\MarketPlaceProductDiscussionCustomer\Model\ConfigData;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Controller\AccountInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Controller for the 'product_discussion/member/questioning' URL route.
 */
class Questioning implements HttpGetActionInterface, AccountInterface
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
     * @param ManagerInterface $manager
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param Registry $registry
     */
    public function __construct(
        RedirectFactory       $resultRedirectFactory,
        PageFactory           $resultPageFactory,
        ConfigData            $configData,
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
        $this->messageManager = $manager;
        $this->registry = $registry;
        $this->storeManger = $storeManager;
    }

    /**
     * Execute controller action.
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
            $page = $this->resultPageFactory->create();
            return $page;
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('*');
            return $resultRedirect;
        }
    }
}
