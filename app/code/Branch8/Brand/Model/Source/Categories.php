<?php
namespace Branch8\Brand\Model\Source;

/**
 * Class:Categories
 * Algolia\AlgoliaSearch\Model\Source
 *
 * @author      Branch8
 * @package     Branch8\Algolia
 * @copyright   Copyright (c) 2015, Branch8. All rights reserved
 */
class Categories implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * Store categories cache
     *
     * @var array
     */
    protected $_storeCategories = [];
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    private $_categoryFactory;

    /**
     * Categories constructor.
     *
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     */
    public function __construct(\Magento\Catalog\Model\CategoryFactory $categoryFactory)
    {
        $this->_categoryFactory = $categoryFactory;
    }

    /**
     * Return array of options as value-label pairs
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        $cacheKey = sprintf('%d-%d-%d-%d', 1, false, false, true);
        if (isset($this->_storeCategories[$cacheKey])) {
            return $this->_storeCategories[$cacheKey];
        }

        /**
         * Check if parent node of the store still exists
         */
        $category = $this->_categoryFactory->create();
        $storeCategories = $category->getCategories(2, 0, false, false, true);

        $this->_storeCategories[$cacheKey] = $storeCategories;

        $resultArray = [];
        foreach ($storeCategories as $category) {
            $resultArray[$category->getId()] = $category->getName();
        }

        return $resultArray;
    }
}
