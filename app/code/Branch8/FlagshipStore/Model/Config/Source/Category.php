<?php

namespace Branch8\FlagshipStore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\ScopeInterface;

class Category extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;
    /**
     * @var \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore\CollectionFactory
     */
    protected $flagshipStoreCollection;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore\CollectionFactory $flagshipStoreCollection
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore\CollectionFactory $flagshipStoreCollection,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->scopeConfig = $scopeConfig;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->flagshipStoreCollection = $flagshipStoreCollection;
        $this->request = $request;
    }

    public function toOptionArray()
    {
        $categories = $this->getCategories();
        $optionArr = [
            ['value' => '', 'label' => __('Please select')]
        ];

        $optionArr = array_merge($optionArr, $categories);

        return $optionArr;
    }

    public function getCategories()
    {
        /**
         * Get category in other flagship store
         */
        $currentFlagshipId = (int)$this->request->getParam('id');
        $flagshipCate = $this->flagshipStoreCollection->create()
            ->addFieldToSelect('category_id')
            ->addFieldToFilter('entity_id',['neq' => $currentFlagshipId]);
        $excludeCategoryIds = $flagshipCate->getColumnValues('category_id');

        $flagshipCategoryConfig = $this->scopeConfig->getValue(
            \Branch8\FlagshipStore\Helper\Data::FLAGSHIP_CATEGORY_CONFIG,
            ScopeInterface::SCOPE_STORE);
        $flagshipCategoryConfigArr = explode(',', (string)$flagshipCategoryConfig);

        $availableCategory = array_diff($flagshipCategoryConfigArr, $excludeCategoryIds);
        $categories = $this->categoryCollectionFactory->create()
            ->addFieldToSelect('entity_id')
            ->addFieldToSelect('name')
            ->addAttributeToFilter('entity_id', ['in' => $availableCategory])
            ->addAttributeToFilter('level', 3);
        $configArr = [];
        foreach($categories as $_cate){
            $configArr[] = [
                'value' => $_cate->getId(),
                'label' => $_cate->getName()
            ];
        }

        return $configArr;
    }
}