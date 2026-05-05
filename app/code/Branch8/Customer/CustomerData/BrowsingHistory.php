<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\CustomerData;

use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Catalog\Helper\Image as ImageHelper;

class BrowsingHistory implements \Magento\Customer\CustomerData\SectionSourceInterface
{

    protected $logger;

    private HttpContext $httpContext;

    /**
     * Product Index Collection
     *
     * @var \Magento\Reports\Model\ResourceModel\Product\Index\Collection\AbstractCollection
     */
    protected $_collection;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_productVisibility;

    /**
     * @var \Magento\Reports\Model\Product\Index\Factory
     */
    protected $_indexFactory;

    /** @var ImageHelper */
    private $imageHelper;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ImageHelper $imageHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Reports\Model\Product\Index\Factory $indexFactory,
    )
    {
        $this->logger = $logger;
        $this->imageHelper = $imageHelper;
        $this->httpContext = $httpContext;
        $this->_productVisibility = $productVisibility;
        $this->_indexFactory = $indexFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $items = [];
        if($this->getCustomerId()){
            $collection = $this->getItemsCollection();
            foreach($collection as $product){
                $items[] = [
                    'id' => $product->getId(),
                    'sku' => $product->getSku(),
                    'name' => $product->getName(),
                    'canonical_url' => $product->getUrlModel()->getUrl($product),
                    'image' => $this->imageHelper->init($product,'product_thumbnail_image')->getUrl()
                ];
            }
        }
        return [
            'items' => $items,
            'count' => count($items)
        ];
    }

    /**
     * Public method for retrieve Product Index model
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return \Magento\Reports\Model\Product\Index\AbstractIndex
     */
    public function getModel()
    {
        try {
            $model = $this->_indexFactory->get(\Magento\Reports\Model\Product\Index\Factory::TYPE_VIEWED);
        } catch (\InvalidArgumentException $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Index type is not valid'));
        }

        return $model;
    }

    protected function getCustomerId(){
        return $this->httpContext->getValue('customer_id');
    }

    /**
     * Retrieve Index Product Collection
     *
     * @return \Magento\Reports\Model\ResourceModel\Product\Index\Collection\AbstractCollection
     */
    public function getItemsCollection()
    {
        if ($this->_collection === null) {
            $this->_collection = $this->getModel()->getCollection()->addAttributeToSelect('*');

            if ($this->getCustomerId()) {
                $this->_collection->setCustomerId($this->getCustomerId());
            }

            $this->_collection->excludeProductIds(
                $this->getModel()->getExcludeProductIds()
            )->addUrlRewrite()->setPageSize(
                4
            )->setCurPage(
                1
            );

            /* Price data is added to consider item stock status using price index */
            $this->_collection->addPriceData();
            $this->_collection->addIndexFilter();
            $this->_collection->setAddedAtOrder()->setVisibility($this->_productVisibility->getVisibleInSiteIds());
        }

        return $this->_collection;
    }
}