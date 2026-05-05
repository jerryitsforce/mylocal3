<?php
namespace Branch8\Checkout\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\ObjectManager;

class AddWishlistMultiple extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    protected $_wishlistFactory;

    protected $_productRepository;

    protected $itemFactory;

    protected $customerSession;

    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
       \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
       StoreManagerInterface $storeManager,
       \Magento\Quote\Model\Quote\ItemFactory $itemFactory,
       \Magento\Customer\Model\Session $customerSession,
       ?RequestQuantityProcessor $quantityProcessor = null
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_wishlistFactory = $wishlistFactory;
        $this->_productRepository = $productRepository;
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager,
            $formKeyValidator, $cart, $productRepository, $quantityProcessor);
        $this->quantityProcessor = $quantityProcessor
            ?? ObjectManager::getInstance()->get(RequestQuantityProcessor::class);
        $this->itemFactory = $itemFactory;
        $this->customerSession = $customerSession;
    }
    /**
     * View page action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $ids = (string)$this->getRequest()->getParam('ids');
        if (!$ids) {
            $defaultUrl = $this->_objectManager->create(\Magento\Framework\UrlInterface::class)->getUrl('*/*');
            return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
        }

        $idArr = explode(',', $ids);
        $successItems = [];
        $errorrItems = [];
        $customerId = $this->customerSession->getCustomerId();
        
        $storeId = $this->_storeManager->getStore()->getId();
        try{
            $wishlist = $this->_wishlistFactory->create()->loadByCustomerId($customerId, true);
            foreach($idArr as $id){
                try{
                    $cartItem = $this->itemFactory->create()->load($id);
                    $pid = $cartItem->getProductId();
                    $product = $this->_productRepository->getById($pid, false, $storeId);

                    $wishlist->addNewItem($product);
                    $cartItem->delete();
                    $successItems[] = $id;
                }catch(\Exception $e){
                    $errorrItems[] = $id;
                }
                
            }
            $errorMessage = '';
        }catch(\Exception $e){
            $errorMessage = __('Error on adding cart items to wishlist.');
        }
        $result = $this->resultJsonFactory->create();
        $result->setData([
            'success_items' => $successItems,
            'error_items' => $errorrItems,
            'error_message' => $errorMessage
        ]);

        return $result;
    }
}
