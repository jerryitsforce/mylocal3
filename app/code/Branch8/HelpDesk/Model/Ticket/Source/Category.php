<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket\Source;
/**
 * Category Source
 */
class Category implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var null
     */
    private $options = null;

    /**
     * @param \Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @return null
     */
    public function toOptionArray()
    {
        $options = [];
        if ($this->options === null) {
            foreach ($this->collectionFactory->create() as $item) {
                if (!$item->getIsActive()) {
                    continue;
                }
                $this->options[] = ['value' => $item->getId(), 'label' => $item->getTitle()];
            }
            array_unshift($this->options, [
                'value' => '',
                'label' => __('Please select category')
            ]);
        }
        return $this->options;
    }

}
