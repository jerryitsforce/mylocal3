<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Controller\Variation;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\StoreManagerInterface;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Branch8\OptionsWithStockAndImages\Helper\Salable;

class Getdatasync extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Json
     */
    protected $serializer;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Http
     */
    protected $http;
    
    /**
     * Summary of productRepository
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Summary of stockItemRepository
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    public $directoryList;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    public $coreFileStorageDatabase;

    /**
     * @var WriteInterface
     */
    public $mediaDirectory;

   /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helperData;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Http $http
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ProductRepositoryInterface $productRepositoryInterface,
        StockRegistryInterface $stockRegistry,
        Salable $salable,
        DirectoryList $directoryList,
        Database $coreFileStorageDatabase,
        Filesystem $filesystem,
        StoreManagerInterface $storeManagerInterface,
        \Webkul\Marketplace\Helper\Data $helperData,
        PageFactory $resultPageFactory,
        Json $json,
        LoggerInterface $logger,
        Http $http
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->productRepository = $productRepositoryInterface;
        $this->stockRegistry = $stockRegistry;
        $this->salable = $salable;
        $this->directoryList = $directoryList;
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->helperData = $helperData;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $this->storeManagerInterface = $storeManagerInterface;
        $this->serializer = $json;
        $this->logger = $logger;
        $this->http = $http;
        parent::__construct($context);
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        try {
            $sku = $this->getRequest()->getParam('sku');
            $product = $this->productRepository->get(trim($sku));
            if($product->getId() && $product->getTypeId() == 'simple'){
                $sellerId = (int)$this->helperData->getSellerIdByProductId($product->getId());
                if($sellerId != $this->helperData->getCustomerId()){
                    return $this->jsonResponse('This sku does not belong to you!');
                }
                $qty = $this->salable->getQtyBySku($sku);
                $images = $product->getMediaGalleryEntries();
                $dataImages = [];
                foreach($images as $image){
                    try{
                        $this->saveFile($image->getFile());
                        $dataImages[] = [
                            'file' => $image->getFile(),
                            'name' => $image->getLabel(),
                            'url' => $this->getMediaUrl($image->getFile())
                        ];
                    } catch(\Exception $e){
                        continue;
                    }
                }
                $data['sku'] = $product->getSku();
                $data['weight'] = $product->getWeight();
                $data['stock'] = $qty;
                $data['images'] = $dataImages;
                return $this->jsonResponse($data);
            } else {
                return $this->jsonResponse(__('Only simple products are allowed.'));
            }
        } catch (LocalizedException $e) {
            return $this->jsonResponse($e->getMessage());
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'exceptionlog')){
                $this->logger->critical($e);
            }
            return $this->jsonResponse($e->getMessage());
        }
    }

    /**
     * Create json response
     *
     * @return ResultInterface
     */
    public function jsonResponse($response = '')
    {
        $this->http->getHeaders()->clearHeaders();
        $this->http->setHeader('Content-Type', 'application/json');
        return $this->http->setBody(
            $this->serializer->serialize($response)
        );
    }

    /**
     * Move file from tmp to wkosi directory
     *
     * @param string $fileName
     * @return void
     */
    public function saveFile($fileName)
    {
        $dirList = $this->directoryList->getPath('media');
        $baseTmpImagePath = $this->getFilePath($dirList.'/catalog/product', $fileName);
        $baseImagePath = $this->getFilePath($dirList.'/wkosi/products', $fileName);
        if(!$this->coreFileStorageDatabase->fileExists($baseImagePath)) {
            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            $this->mediaDirectory->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        }
    }

    /**
     * Get Image Path
     *
     * @param string $path
     * @param string $imageName
     * @return string
     */
    public function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Get path of uploaded images
     *
     * @return string
     */
    public function getMediaUrl($filename)
    {
        return $this->storeManagerInterface->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'wkosi/products' . $filename;
    }
}

