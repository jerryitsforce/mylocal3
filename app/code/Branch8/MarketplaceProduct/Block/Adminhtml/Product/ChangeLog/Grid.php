<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Adminhtml\Product\ChangeLog;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Block\Adminhtml\Details\Renderer\Diff;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Logging\Block\Adminhtml\Details\Renderer\Sourcename;

/**
 * @method $this setId(string $id)
 */
class Grid extends Extended
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $dataCollectionFactory;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param SerializerInterface $serializer
     * @param CollectionFactory $dataCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context             $context,
        Data                $backendHelper,
        SerializerInterface $serializer,
        CollectionFactory   $dataCollectionFactory,
        array               $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->serializer = $serializer;
        $this->dataCollectionFactory = $dataCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setId('productChangelogGrid');
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    protected function _prepareColumns(): self
    {
        $this->addColumn(
            'source_name',
            [
                'header' => __('Source Data'),
                'sortable' => false,
                'renderer' => Sourcename::class,
                'index' => 'source_name',
                'width' => 1
            ]
        );

        $this->addColumn(
            'original_data',
            [
                'header' => __('Value Before Change'),
                'sortable' => false,
                'renderer' => Diff::class,
                'index' => 'original_data'
            ]
        );

        $this->addColumn(
            'result_data',
            [
                'header' => __('Value After Change'),
                'sortable' => false,
                'renderer' => Diff::class,
                'index' => 'result_data'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    protected function _prepareCollection(): self
    {
        /** @var DataObject $logEntry */
        $logEntry = $this->getData('log_detail');
        $additionalInfo = (string)$logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION];
        $additionalInfo = $this->serializer->unserialize($additionalInfo);

        $collection = $this->dataCollectionFactory->create();

        foreach ($additionalInfo as $key => $value) {
            if ($value['before'] == 'no change') continue;
            $beforeValue = $value['before'] ?? null;
            $afterValue = $value['after'] ?? null;
            if ($key === 'image_gallery' && is_array($afterValue)) {
                $afterValue = array_filter($afterValue, fn($imgValue) => empty($imgValue['removed']) || $imgValue['removed'] != '1');
            }
            if ($key === 'quantity_and_stock_status') {
                foreach (['beforeValue', 'afterValue'] as $var) {
                    if (isset(${$var}['is_in_stock']) && !is_bool(${$var}['is_in_stock'])) {
                        ${$var}['is_in_stock'] = (bool)${$var}['is_in_stock'];
                    }
                }
            }
            $data = [
                'source_name' => $key,
                'original_data' => $beforeValue ?? 'N/A',
                'result_data' => $afterValue ?? 'N/A',
            ];
            $collection->addItem(new DataObject($data));
        }

        $this->setCollection($collection);
        parent::_prepareCollection();

        if ($this->_isExport) {
            $collection->setPageSize(null);
        }
        return $this;
    }
}
