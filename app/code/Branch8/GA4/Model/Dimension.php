<?php

namespace Branch8\GA4\Model;


class Dimension extends \Branch8\GA4\Model\Storage
{
    const DIMENSION_TYPE = 'dimension';
    const METRIC_TYPE = 'metric';
    const DIMENSION_STOCK_STATUS = 'item_stock_status';
    const DIMENSION_SALE_PRODUCT = 'item_sale_product';
    const DIMENSION_REVIEWS_COUNT = 'item_reviews_count';
    const DIMENSION_REVIEWS_SCORE = 'item_reviews_score';

    /**
     * @var \Magento\Review\Model\Review\SummaryFactory
     */
    protected $reviewSummaryFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Review\Model\Review\SummaryFactory $reviewSummaryFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\Model\Context            $context,
        \Magento\Framework\Registry                 $registry,
        \Magento\Review\Model\Review\SummaryFactory $reviewSummaryFactory,
        \Magento\Store\Model\StoreManagerInterface  $storeManager
    )
    {
        parent::__construct($context, $registry);
        $this->reviewSummaryFactory = $reviewSummaryFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $product
     * @return \Magento\Review\Model\Review\Summary
     */
    public function getReviewSummary($product)
    {
        $storeId = $this->storeManager->getStore()->getId();
        $reviewSummary = $this->reviewSummaryFactory->create();
        $reviewSummary->setData('store_id', $storeId);
        $summaryModel = $reviewSummary->load($product->getId());
        return $summaryModel;
    }

    /**
     * @param $product
     * @return array
     */
    public function getProductDimensions($product)
    {
        $dimensions = [];
        $productStockStatus = ($product->isAvailable()) ? 'In stock' : 'Out of stock';
        $dimensions[self::DIMENSION_STOCK_STATUS] = $productStockStatus;
        $saleProduct = ($product->getSale()) ? 'Yes' : 'No';
        $dimensions[self::DIMENSION_SALE_PRODUCT] = $saleProduct;
        $summaryModel = $this->getReviewSummary($product);
        $reviewCount = ($summaryModel->getReviewsCount()) ? $summaryModel->getReviewsCount() : 0;
        $dimensions[self::DIMENSION_REVIEWS_COUNT] = strval($reviewCount);
        $ratingSummary = $summaryModel->getRatingSummary();
        $dimensions[self::DIMENSION_REVIEWS_SCORE] = strval($ratingSummary / 20);
        return $dimensions;
    }
}
