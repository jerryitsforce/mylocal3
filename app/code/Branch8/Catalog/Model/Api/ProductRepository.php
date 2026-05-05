<?php

namespace Branch8\Catalog\Model\Api;

use Branch8\Catalog\Api\ViewedResponseInterface;
use Branch8\Catalog\Api\WishlistResponseInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Reports\Model\Event;
use Magento\Reports\Observer\EventSaver;

class ProductRepository implements \Branch8\Catalog\Api\ProductInterface
{
    protected $_wishlistFactory;

    protected $_wishlistResource;

    protected $_productRepository;

    protected $_customerCollectionFactory;

    protected $viewedResponse;

    protected $wishlistResponse;

    protected $_productIndxFactory;

    protected $reportStatus;

    protected $eventSaver;

    protected $_logger;

    public function __construct(
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Wishlist\Model\ResourceModel\Wishlist $wishlistResource,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        ProductRepositoryInterface $productRepository,
        \Branch8\Catalog\Model\Api\ViewedResponse  $viewedResponse,
        \Branch8\Catalog\Model\Api\WishlistResponse $wishlistResponse,
        \Magento\Reports\Model\Product\Index\ViewedFactory $productIndxFactory,
        \Magento\Reports\Model\ReportStatus $reportStatus,
        EventSaver $eventSaver,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_wishlistFactory = $wishlistFactory;
        $this->_wishlistResource = $wishlistResource;
        $this->_productRepository = $productRepository;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->viewedResponse = $viewedResponse;
        $this->wishlistResponse = $wishlistResponse;
        $this->_productIndxFactory = $productIndxFactory;
        $this->reportStatus = $reportStatus;
        $this->eventSaver = $eventSaver;
        $this->_logger = $logger;
    }

    public function addViewedProduct($member_seq, $sku, $storeId = 1): ViewedResponseInterface
    {
        $response = $this->viewedResponse;

        if (!$this->reportStatus->isReportEnabled(Event::EVENT_PRODUCT_VIEW)) {
            $response->setError(true);
            $response->setMessage('Report event is disabled');
            return $response;
        }

        if(trim((string)$member_seq) == ''){
            $response->setError(true);
            $response->setMessage('Invalid member seq');
            return $response;
        }

        if(empty($sku)){
            $response->setError(true);
            $response->setMessage('Invalid SKUs');
            return $response;
        }

        $customer = $this->_customerCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('member_seq', $member_seq)
            ->getFirstItem();
        if(!$customer->getId()){
            $response->setError(true);
            $response->setMessage('Invalid member seq');
            return $response;
        }

        $errorSku = [];
        foreach($sku as $_sku){
            try{
                $product = $this->_productRepository->get($_sku, false, $storeId);
            }catch (\Exception $e){
                $errorSku[] = $_sku;
                continue;
            }
            $productId = $product->getId();

            $viewData['product_id'] = $productId;
            $viewData['store_id']   = $storeId;
            $viewData['customer_id'] = $customer->getId();
            try {
                $this->_productIndxFactory->create()->setData($viewData)->save()->calculate();
                $this->eventSaver->save(Event::EVENT_PRODUCT_VIEW, $productId, $customer->getId());
            }catch (\Exception $e){
                $errorSku[] = $_sku;
                continue;
            }
        }

        if(count($errorSku) == 0){
            $response->setError(false);
            $response->setMessage('Successfully');
        }elseif(count($errorSku) > 0 && count($errorSku) < count($sku)){
            $response->setError(false);
            $response->setMessage('Successfully but fail some SKUs: '.implode(',', $errorSku));
            
            $this->_logger->error('API viewed product error');
            $this->_logger->error(print_r([$member_seq, $errorSku, $storeId], true));
        }else{
            $response->setError(true);
            $response->setMessage('Failure');

            $this->_logger->error('API viewed product error');
            $this->_logger->error(print_r([$member_seq, $errorSku, $storeId], true));
        }

        return $response;
    }

    public function addWishlist($member_seq, $sku, $storeId = 1): WishlistResponseInterface
    {
        $response = $this->wishlistResponse;

        if(trim((string)$member_seq) == ''){
            $response->setError(true);
            $response->setMessage('Invalid member seq');
            return $response;
        }

        if(empty($sku)){
            $response->setError(true);
            $response->setMessage('Invalid SKUs');
            return $response;
        }

        $customer = $this->_customerCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToFilter('member_seq', $member_seq)
            ->getFirstItem();
        if(!$customer->getId()){
            $response->setError(true);
            $response->setMessage('Invalid member seq');
            return $response;
        }

        $errorSku = [];

        $customerId = $customer->getId();
        $wishlist = $this->_wishlistFactory->create()->loadByCustomerId($customerId, true);
        
        foreach($sku as $_sku) {
            try{
                $product = $this->_productRepository->get($_sku, false, $storeId);
                $wishlist->addNewItem($product);
            }catch(\Exception $e){
                $errorSku[] = $_sku;
            }
        }

        if(count($errorSku) == 0){
            $response->setError(false);
            $response->setMessage('Successfully');
        }elseif(count($errorSku) > 0 && count($errorSku) < count($sku)){
            $response->setError(false);
            $response->setMessage('Successfully but fail some SKUs: '.implode(',', $errorSku));

            $this->_logger->error('API wishlist product error');
            $this->_logger->error(print_r([$member_seq, $errorSku, $storeId], true));
        }else{
            $response->setError(true);
            $response->setMessage('Failure');

            $this->_logger->error('API wishlist product error');
            $this->_logger->error(print_r([$member_seq, $errorSku, $storeId], true));

        }

        return $response;
    }
}