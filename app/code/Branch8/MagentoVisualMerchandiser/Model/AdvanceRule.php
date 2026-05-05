<?php

namespace Branch8\MagentoVisualMerchandiser\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Rule\Model\Condition\Sql\Builder as SqlBuilder;
use Magento\VisualMerchandiser\Model\Position\Cache;

class AdvanceRule extends \Magento\Rule\Model\AbstractModel
{
    /**
     * @var AdvanceRule\Condition\CombineFactory
     */
    protected $conditionsFactory;
    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param AdvanceRule\Condition\CombineFactory $combineFactory
     * @param CollectionFactory $productCollectionFactory
     * @param SqlBuilder $sqlBuilder
     * @param Cache $cache
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param ExtensionAttributesFactory|null $extensionFactory
     * @param AttributeValueFactory|null $customAttributeFactory
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     */
    public function __construct(
        \Magento\Framework\Model\Context                                              $context,
        \Magento\Framework\Registry                                                   $registry,
        \Magento\Framework\Data\FormFactory                                           $formFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface                          $localeDate,
        \Branch8\MagentoVisualMerchandiser\Model\AdvanceRule\Condition\CombineFactory $combineFactory,
        CollectionFactory                                                             $productCollectionFactory,
        SqlBuilder                                                                    $sqlBuilder,
        Cache                                                                         $cache,
        \Magento\Framework\Model\ResourceModel\AbstractResource                       $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb                                 $resourceCollection = null,
        array                                                                         $data = [],
        ExtensionAttributesFactory                                                    $extensionFactory = null,
        AttributeValueFactory                                                         $customAttributeFactory = null,
        \Magento\Framework\Serialize\Serializer\Json                                  $serializer = null
    )
    {
        $this->conditionsFactory = $combineFactory;
        $this->sqlBuilder = $sqlBuilder;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->cache = $cache;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $localeDate,
            $resource,
            $resourceCollection,
            $data,
            $extensionFactory,
            $customAttributeFactory,
            $serializer
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConditionsInstance()
    {
        return $this->conditionsFactory->create();
    }

    /**
     * {@inheritdoc}
     */
    public function getActionsInstance()
    {
        return null;
    }

    /**
     * @return bool|string
     */
    public function serializedConditions()
    {
        return $this->serializer->serialize($this->getConditions()->asArray());
    }

    /**
     * @param Category $category
     * @param Collection $collection
     * @return Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function applyAllRules(Category $category, Collection $collection)
    {
        $collection = $collection->addStoreFilter()
            ->addAttributeToSort('entity_id', 'desc');
        $conditions = $this->getConditions();
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        /**
         * Do not use distinct because it make sql slow
         */
        $ids = $collection->getAllIds();
        $positions = $this->getProductsPositions($collection, array_unique($ids));
        $category->setPostedProducts($positions);
        // Clear any data that collection cached so far
        if ($collection->isLoaded()) {
            $collection->clear();
        }
        return $collection;
    }

    /**
     * @param int $productId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkProductCanMatchWith(int $productId)
    {
        /**
         * @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection
         */
        $collection = $this->productCollectionFactory->create()->addStoreFilter()
            ->addAttributeToSort('entity_id', 'desc');
        $conditions = $this->getConditions();
        $conditions->collectValidatedAttributes($collection);
        $this->sqlBuilder->attachConditionToCollection($collection, $conditions);
        $collection->addFieldToFilter('entity_id', ['eq' => $productId]);
        $result = $collection->getSize();
        gc_collect_cycles();
        return $result > 0;
    }

    /**
     * @param Collection $collection
     * @param array $ids
     * @return array
     */
    private function getProductsPositions(Collection $collection, array $ids): array
    {
        $positions = $this->cache->getPositions(Cache::POSITION_CACHE_KEY);
        if (!$positions || count($ids) != count($positions) || array_diff($ids, array_keys($positions))) {
            $positions = [];
            foreach ($collection as $key => $item) {
                /* @var $item ProductInterface */
                $positions[$item->getId()] = $key;
            }
        }

        return $positions;
    }

}
