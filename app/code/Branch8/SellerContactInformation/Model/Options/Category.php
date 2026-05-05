<?php
declare(strict_types=1);

namespace Branch8\SellerContactInformation\Model\Options;

use Magento\Framework\Option\ArrayInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class Category implements ArrayInterface
{
    const ALL_CATEGORIES = 0;
    const SYSTEM_CATEGORY_ID = 1;
    const ROOT_LEVEL = 1;

    /**
     * @var CategoryCollectionFactory
     */
    protected $collectionFactory;

    /**
     * @param CategoryCollectionFactory $collectionFactory
     */
    public function __construct(
        CategoryCollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        $optionArray = [];
        $arr = $this->toArray();
        foreach ($arr as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label
            ];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getChildren(self::SYSTEM_CATEGORY_ID, self::ROOT_LEVEL);
    }

    /**
     * @param $parentCategoryId
     * @param $level
     * @return array
     */
    private function getChildren($parentCategoryId, $level)
    {
        $options[self::ALL_CATEGORIES] = __('All Categories');
        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToFilter('level', $level);
        $collection->addAttributeToFilter('parent_id', $parentCategoryId);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->setOrder('position', 'asc');

        foreach ($collection as $category) {
            if ($category->getLevel() > self::ROOT_LEVEL) {
                $options[$category->getId()] =
                    str_repeat("-", max(0, ($category->getLevel() - 1) * 3)) . $category->getName();
            }
            if ($category->hasChildren()) {
                $options = array_replace($options, $this->getChildren($category->getId(), $category->getLevel() + 1));
            }
        }

        return $options;
    }
}
