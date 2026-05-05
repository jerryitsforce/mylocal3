<?php

declare(strict_types=1);

namespace Branch8\Catalog\Rewrite\Controller\Adminhtml\Product;

use Branch8\Catalog\Model\ResourceModel\ProductCopyTracking;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Builder as ProductBuilder;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as InitializationHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Copier as ProductCopier;
use Magento\Catalog\Model\Product\TypeTransitionManager;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Product save controller
 */
class Save extends \Magento\Catalog\Controller\Adminhtml\Product\Save
{
    /**
     * @var Escaper
     */
    private Escaper $escaper;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ProductCopyTracking
     */
    private ProductCopyTracking $productCopyTracking;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param ProductBuilder $productBuilder
     * @param InitializationHelper $initializationHelper
     * @param ProductCopier $productCopier
     * @param TypeTransitionManager $productTypeManager
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCopyTracking $productCopyTracking
     * @param Escaper|null $escaper
     * @param LoggerInterface|null $logger
     * @param CategoryLinkManagementInterface|null $categoryLinkManagement
     * @param StoreManagerInterface|null $storeManager
     */
    public function __construct(
        Action\Context                  $context,
        ProductBuilder                  $productBuilder,
        InitializationHelper            $initializationHelper,
        ProductCopier                   $productCopier,
        TypeTransitionManager           $productTypeManager,
        ProductRepositoryInterface      $productRepository,
        ProductCopyTracking             $productCopyTracking,
        Escaper                         $escaper = null,
        LoggerInterface                 $logger = null,
        CategoryLinkManagementInterface $categoryLinkManagement = null,
        StoreManagerInterface           $storeManager = null
    ) {
        parent::__construct(
            $context,
            $productBuilder,
            $initializationHelper,
            $productCopier,
            $productTypeManager,
            $productRepository,
            $escaper,
            $logger,
            $categoryLinkManagement,
            $storeManager
        );
        $this->escaper = $escaper ?: ObjectManager::getInstance()
            ->get(Escaper::class);
        $this->logger = $logger ?: ObjectManager::getInstance()
            ->get(LoggerInterface::class);
        $this->categoryLinkManagement = $categoryLinkManagement ?: ObjectManager::getInstance()
            ->get(CategoryLinkManagementInterface::class);
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()
            ->get(StoreManagerInterface::class);
        $this->productCopyTracking = $productCopyTracking;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $storeId = $this->getRequest()->getParam('store', Store::DEFAULT_STORE_ID);
        $store = $this->storeManager->getStore($storeId);
        $this->storeManager->setCurrentStore($store->getCode());
        $redirectBack = $this->getRequest()->getParam('back', false);
        $productId = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        $productAttributeSetId = $this->getRequest()->getParam('set');
        $productTypeId = $this->getRequest()->getParam('type');
        if ($data) {
            try {
                $product = $this->initializationHelper->initialize(
                    $this->productBuilder->build($this->getRequest())
                );
                $this->productTypeManager->processProduct($product);
                if (isset($data['product'][$product->getIdFieldName()])) {
                    throw new LocalizedException(
                        __('The product was unable to be saved. Please try again.')
                    );
                }

                $originalSku = $product->getSku();
                $canSaveCustomOptions = $product->getCanSaveCustomOptions();
                $product->save();
                $this->handleImageRemoveError($data, $product->getId());
                $productId = $product->getEntityId();
                $productAttributeSetId = $product->getAttributeSetId();
                $productTypeId = $product->getTypeId();
                $extendedData = $data;
                $extendedData['can_save_custom_options'] = $canSaveCustomOptions;
                $this->copyToStores($extendedData, $productId);
                $this->messageManager->addSuccessMessage(__('You saved the product.'));
                $this->getDataPersistor()->clear('catalog_product');
                if ($product->getSku() != $originalSku) {
                    $this->messageManager->addNoticeMessage(
                        __(
                            'SKU for product %1 has been changed to %2.',
                            $this->escaper->escapeHtml($product->getName()),
                            $this->escaper->escapeHtml($product->getSku())
                        )
                    );
                }
                $this->_eventManager->dispatch(
                    'controller_action_catalog_product_save_entity_after',
                    ['controller' => $this, 'product' => $product]
                );

                // custom here: remove if the product no longer needs tracking
                $this->productCopyTracking->delete((int)$product->getId());
                if ($redirectBack === 'duplicate') {
                    $product->unsetData('quantity_and_stock_status');
                    $newProduct = $this->productCopier->copy($product);

                    // Reset seller-specific attributes that are invalid for the duplicated product.
                    // flagstore_category belongs to the original seller's flagship store and will be
                    // wrong if the new product is assigned to a different seller.
                    // index_seller_id will be synced correctly by the SyncSellerIdToindexSellerId observer.
                    $this->resetFlagstoreCategoryForDuplicate($newProduct, $product);

                    $this->checkUniqueAttributes($product);
                    $this->messageManager->addSuccessMessage(__('You duplicated the product.'));
                }
            } catch (LocalizedException $e) {
                $this->logger->critical($e);
                $this->messageManager->addExceptionMessage($e);
                $data = isset($product) ? $this->persistMediaData($product, $data) : $data;
                $this->getDataPersistor()->set('catalog_product', $data);
                $redirectBack = $productId ? true : 'new';
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage($e->getMessage());
                $data = isset($product) ? $this->persistMediaData($product, $data) : $data;
                $this->getDataPersistor()->set('catalog_product', $data);
                $redirectBack = $productId ? true : 'new';
            }
        } else {
            $resultRedirect->setPath('catalog/*/', ['store' => $storeId]);
            $this->messageManager->addErrorMessage('No data to save');
            return $resultRedirect;
        }

        if ($redirectBack === 'new') {
            $resultRedirect->setPath(
                'catalog/*/new',
                ['set' => $productAttributeSetId, 'type' => $productTypeId]
            );
        } elseif ($redirectBack === 'duplicate' && isset($newProduct)) {
            // custom here: add if the product needs tracking after being copied/duplicated
            $this->productCopyTracking->insert((int)$newProduct->getId());
            $resultRedirect->setPath(
                'catalog/*/edit',
                ['id' => $newProduct->getEntityId(), 'back' => null, '_current' => true]
            );
        } elseif ($redirectBack) {
            $resultRedirect->setPath(
                'catalog/*/edit',
                ['id' => $productId, '_current' => true, 'set' => $productAttributeSetId]
            );
        } else {
            $resultRedirect->setPath('catalog/*/', ['store' => $storeId]);
        }
        return $resultRedirect;
    }

    /**
     * Notify customer when image was not deleted in specific case.
     *
     *
     * @param array $postData
     * @param int $productId
     *
     * @return void
     *
     * @throws NoSuchEntityException
     */
    private function handleImageRemoveError($postData, $productId)
    {
        if (!isset($postData['product']['media_gallery']['images'])) {
            return;
        }
        $removedImagesAmount = 0;
        foreach ($postData['product']['media_gallery']['images'] as $image) {
            if (!empty($image['removed'])) {
                $removedImagesAmount++;
            }
        }
        if ($removedImagesAmount) {
            $expectedImagesAmount = count($postData['product']['media_gallery']['images']) - $removedImagesAmount;
            $product = $this->productRepository->getById($productId, false, null, true);
            $images = $product->getMediaGallery('images');
            if (is_array($images) && $expectedImagesAmount != count($images)) {
                $this->messageManager->addNoticeMessage(
                    __('The image cannot be removed as it has been assigned to the other image role')
                );
            }
        }
    }

    /**
     * Retrieve data persistor
     *
     * @return DataPersistorInterface|mixed
     */
    protected function getDataPersistor()
    {
        if (null === $this->dataPersistor) {
            $this->dataPersistor = $this->_objectManager->get(DataPersistorInterface::class);
        }

        return $this->dataPersistor;
    }

    /**
     * Check unique attributes and add error to message manager
     *
     * @param Product $product
     */
    private function checkUniqueAttributes(Product $product)
    {
        $uniqueLabels = [];
        foreach ($product->getAttributes() as $attribute) {
            if ($attribute->getIsUnique() && $attribute->getIsUserDefined()
                && $product->getData($attribute->getAttributeCode()) !== null
            ) {
                $uniqueLabels[] = $attribute->getDefaultFrontendLabel();
            }
        }
        if ($uniqueLabels) {
            $uniqueLabels = implode('", "', $uniqueLabels);
            $this->messageManager->addErrorMessage(__('The value of attribute(s) "%1" must be unique', $uniqueLabels));
        }
    }

    /**
     * Reset flagstore_category for a duplicated product.
     *
     * When a product is duplicated, the Copier copies all EAV attributes including
     * flagstore_category. This value belongs to the original seller's flagship store
     * and is invalid for the new product if the seller differs.
     * We always clear it so the admin can set the correct value.
     *
     * @param Product $newProduct
     * @param Product $originalProduct
     * @return void
     */
    private function resetFlagstoreCategoryForDuplicate(Product $newProduct, Product $originalProduct): void
    {
        try {
            $oldFlagstoreCategory = $originalProduct->getData('flagstore_category');
            if (empty($oldFlagstoreCategory)) {
                return;
            }

            // Clear flagstore_category on the new product
            $newProduct->setData('flagstore_category', null);
            $resource = $newProduct->getResource();
            $resource->saveAttribute($newProduct, 'flagstore_category');

            // Remove the old flagstore category from category_ids
            $categoryIds = $newProduct->getCategoryIds();
            if (is_array($categoryIds) && in_array($oldFlagstoreCategory, $categoryIds)) {
                $categoryIds = array_diff($categoryIds, [$oldFlagstoreCategory]);
                $this->categoryLinkManagement->assignProductToCategories(
                    $newProduct->getSku(),
                    $categoryIds
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to reset flagstore_category for duplicated product '
                . $newProduct->getSku() . ': ' . $e->getMessage()
            );
        }
    }

    /**
     * Persist media gallery on error, in order to show already saved images on next run.
     *
     * @param ProductInterface $product
     * @param array $data
     *
     * @return array
     */
    private function persistMediaData(ProductInterface $product, array $data)
    {
        $mediaGallery = $product->getData('media_gallery');
        if (!empty($mediaGallery['images'])) {
            foreach ($mediaGallery['images'] as $key => $image) {
                if (!isset($image['new_file'])) {
                    //Remove duplicates.
                    unset($mediaGallery['images'][$key]);
                }
            }
            $data['product']['media_gallery'] = $mediaGallery;
            $fields = [
                'image',
                'small_image',
                'thumbnail',
                'swatch_image',
            ];
            foreach ($fields as $field) {
                $data['product'][$field] = $product->getData($field);
            }
        }

        return $data;
    }
}
