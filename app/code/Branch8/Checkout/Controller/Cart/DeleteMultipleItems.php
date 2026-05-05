<?php
namespace Branch8\Checkout\Controller\Cart;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\ObjectManager;

class DeleteMultipleItems extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

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
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
       \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
       ?RequestQuantityProcessor $quantityProcessor = null
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager,
            $formKeyValidator, $cart, $productRepository, $quantityProcessor);
        $this->quantityProcessor = $quantityProcessor
            ?? ObjectManager::getInstance()->get(RequestQuantityProcessor::class);
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
        $deletedItems = [];
        $errorrItems = [];
        $errorMessage = '';
        try{
            foreach($idArr as $id){
                try{
                    $this->cart->removeItem($id);
                    $deletedItems[] = $id;
                }catch(\Exception $e){
                    $errorrItems[] = $id;
                }
            }
            // We should set Totals to be recollected once more because of Cart model as usually is loading
            // before action executing and in case when triggerRecollect setted as true recollecting will
            // executed and the flag will be true already.
            $this->cart->getQuote()->setTotalsCollectedFlag(false);
            $this->cart->save();
        } catch (\Exception $e) {
            $errorMessage = __('Error on deleting cart items.');
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Checkout', 'exceptionlog')){
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $result = $this->resultJsonFactory->create();
        $result->setData([
            'success_items' => $deletedItems,
            'error_items' => $errorrItems,
            'error_message' => $errorMessage
        ]);

        return $result;
    }
}
