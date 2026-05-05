<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       18/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Plugin\Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Catalog\Model\ProductRepository;
use Magento\Customer\Api\CustomerRepositoryInterface;

class ThreadRepositoryPlugin
{
    private CustomerRepositoryInterface $customerRepository;

    private ProductRepository $productRepository;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        ProductRepository           $productRepository,
    )
    {
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * @param $subject
     * @param Thread $model
     * @return Thread[]
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function beforeSave($subject, Thread $model)
    {
        if ($model->getId()
            && $model->getOrigData('product_id') !== $model->getProductId()
            && $product = $this->getProduct($model->getProductId())
        ) {
            $model->setProductName($product->getName());
            $model->setSku($product->getSku());
        }
        return [$model];
    }

    /**
     * @param $id
     * @return \Magento\Catalog\Api\Data\ProductInterface|mixed|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getProduct($id)
    {
        try {
            return $this->productRepository->getById($id);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * @param $id
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    private function getCustomer($id)
    {
        try {
            return $this->customerRepository->getById($id);
        } catch (\Exception $exception) {
            return null;
        }

    }
}
