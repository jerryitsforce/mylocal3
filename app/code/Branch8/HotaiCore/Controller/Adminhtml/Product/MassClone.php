<?php

namespace Branch8\HotaiCore\Controller\Adminhtml\Product;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Copier;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Controller\ResultFactory;
use Branch8\Catalog\Model\ResourceModel\ProductCopyTracking;

class MassClone extends Action
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Copier
     */
    protected $productCopier;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var ProductCopyTracking
     */
    protected $productCopyTracking;
    
    /**
     * MassClone constructor.
     *
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param Copier $productCopier
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     */
    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        Copier $productCopier,
        CollectionFactory $collectionFactory,
        ProductCopyTracking $productCopyTracking,
        Filter $filter
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->productCopier = $productCopier;
        $this->collectionFactory = $collectionFactory;
        $this->productCopyTracking = $productCopyTracking;
        $this->filter = $filter;
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();

        foreach ($collection as $product) {
            try {
                $duplicateProduct = $this->productCopier->copy($product);
                if($duplicateProduct->getId()){
                    $this->productCopyTracking->insert($duplicateProduct->getId());
                    $this->resetFlagstoreCategory($duplicateProduct, $product);
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error cloning product %1: %2', $product->getSku(), $e->getMessage()));
            }
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 product(s) have been cloned.', $collectionSize));

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('catalog/product/index');
    }

    /**
     * Reset flagstore_category for a duplicated product.
     *
     * @param \Magento\Catalog\Model\Product $newProduct
     * @param \Magento\Catalog\Model\Product $originalProduct
     * @return void
     */
    private function resetFlagstoreCategory($newProduct, $originalProduct): void
    {
        try {
            $oldFlagstoreCategory = $originalProduct->getData('flagstore_category');
            if (empty($oldFlagstoreCategory)) {
                return;
            }
            $newProduct->setData('flagstore_category', null);
            $resource = $newProduct->getResource();
            $resource->saveAttribute($newProduct, 'flagstore_category');
            $categoryIds = $newProduct->getCategoryIds();
            if (is_array($categoryIds) && in_array($oldFlagstoreCategory, $categoryIds)) {
                $categoryIds = array_values(array_diff($categoryIds, [$oldFlagstoreCategory]));
                $newProduct->setCategoryIds($categoryIds);
                $resource->saveAttribute($newProduct, 'category_ids');
            }
        } catch (\Exception $e) {
            // Log but don't break the clone flow
        }
    }
}
