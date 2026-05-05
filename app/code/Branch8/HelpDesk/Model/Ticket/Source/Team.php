<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket\Source;

class Team implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Branch8\HelpDesk\Model\ResourceModel\Category\CollectionFactory|\Branch8\HelpDesk\Model\ResourceModel\Team\CollectionFactory
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
        \Branch8\HelpDesk\Model\ResourceModel\Team\CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * To Option Array
     * @return null|array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = [];
            foreach ($this->collectionFactory->create() as $item) {
                if (!$item->getIsActive()) {
                    continue;
                }
                $this->options[] = ['value' => $item->getId(), 'label' => $item->getName()];
            }
            array_unshift($this->options, [
                'value' => '',
                'label' => __('Please select Team')
            ]);
        }
        return $this->options;
    }

}
