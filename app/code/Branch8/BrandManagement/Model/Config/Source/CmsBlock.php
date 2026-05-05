<?php

namespace Branch8\BrandManagement\Model\Config\Source;

use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;
use Psr\Log\LoggerInterface;

class CmsBlock implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $blockCollectionFactory;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param CollectionFactory $blockCollectionFactory CMS block collection factory.
     * @param LoggerInterface $logger Logger for recording exceptions during option building.
     */
    public function __construct(
        CollectionFactory $blockCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->logger = $logger;
    }

    /**
     * Return all options as used by Magento form fields.
     *
     * @return array
     */
    public function getAllOptions()
    {
        return $this->toOptionArray();
    }
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $options[] = ['value' => '', 'label' => __('-- Please Select --')];
        
        try {
            $collection = $this->blockCollectionFactory->create();
            $collection->addFieldToFilter('is_active', 1);
            $collection->setOrder('title', 'ASC');
            
            foreach ($collection as $block) {
                $options[] = [
                    'value' => $block->getId(),
                    'label' => $block->getTitle()
                ];
            }
            
            
        } catch (\Exception $e) {
            $this->logger->error($e);
        }
        
        return $options;
    }
}
