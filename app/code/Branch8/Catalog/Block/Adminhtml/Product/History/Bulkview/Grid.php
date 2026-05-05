<?php

declare(strict_types=1);

namespace Branch8\Catalog\Block\Adminhtml\Product\History\Bulkview;

use Branch8\MarketplaceProduct\Block\Adminhtml\Details\Renderer\Diff;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;

/**
 * @method $this setId(string $id)
 */
class Grid extends Extended
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $dataCollectionFactory;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $dataCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context             $context,
        Data                $backendHelper,
        CollectionFactory   $dataCollectionFactory,
        array               $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->dataCollectionFactory = $dataCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setId('productHistoryBulkviewGrid');
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
            'original_data',
            [
                'header' => __('Items Selected'),
                'sortable' => false,
                'renderer' => Diff::class,
                'index' => 'original_data'
            ]
        );

        $this->addColumn(
            'result_data',
            [
                'header' => __('Final Items After Update'),
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
        $collection = $this->dataCollectionFactory->create();
        $bulkData = $this->getData('bulk_data');

        if ($bulkData) {
            $data = [
                'original_data' => $bulkData['selected'] ?? 'N/A',
                'result_data' => $bulkData['updated'] ?? 'N/A'
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
