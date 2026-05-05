<?php

namespace Branch8\Checkout\Observer;

class TrackingCategory implements \Magento\Framework\Event\ObserverInterface{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;
    /**
     * @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory
     */
    protected $urlRewrite;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory $urlRewrite
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory $urlRewrite,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ){
        $this->request = $request;
        $this->categoryFactory = $categoryFactory;
        $this->urlRewrite = $urlRewrite;
        $this->_storeManager = $storeManager;
    }

    /**
     * Using referer url if user add to cart on listing page
     * if user add to cart on Product detail page, add save the referer url to hidden field, push it to add to cart url
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer){
        $quoteItem = $observer->getEvent()->getData('quote_item');
        $refererUrl = $this->request->getParam('referer_url', false);
        $product = $quoteItem->getProduct();
        if ($product->getData('individual_product')) {
            $quoteItem->setAvailableToCheckout(1);
        }

        if(!$refererUrl && isset($_SERVER['HTTP_REFERER'])){
            $origRefererUrl = $_SERVER['HTTP_REFERER'];
            $explRefererUrl = explode('?', $origRefererUrl);
            $refererUrl = $explRefererUrl[0];
        }

        $requestPath = str_replace($this->_storeManager->getStore()->getBaseUrl(), '', $refererUrl);
        /**
         * Load rewrite obj from request path
         */

        $rewriteCol = $this->urlRewrite->create()->addFieldToFilter('request_path', $requestPath);
        $rewriteObj = $rewriteCol->getFirstItem();
        $isExistedSource = false;
        if($rewriteObj->getEntityType() == 'category'){
            $isExistedSource = true;
            $targetPath = $rewriteObj->getTargetPath();
            $explTargetPath = explode('/', $targetPath);
            $categoryId = end($explTargetPath);
            /**
             * Load category
             */
            $cate = $this->categoryFactory->create()->load($categoryId);
            $cName = $cate->getName();

            $quoteItem->setCategoryName($cName);
            $quoteItem->setCategoryId($cate->getId());
        }
        /**
         * If not referer url found, get Main category data
         */
        if(!$isExistedSource){
            $cate = $this->categoryFactory->create()->load($quoteItem->getProduct()->getMainCategory());
            if($cate->getId()){
                $quoteItem->setCategoryName($cate->getName());
                $quoteItem->setCategoryId($cate->getId());
            }else{
                /**
                 * do nothings
                 * no category, no main category
                 */
            }
        }


    }

}
