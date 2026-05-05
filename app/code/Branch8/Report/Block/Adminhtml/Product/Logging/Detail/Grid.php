<?php

declare(strict_types=1);

namespace Branch8\Report\Block\Adminhtml\Product\Logging\Detail;

use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Block\Adminhtml\Details\Renderer\Diff;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;

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
     * @var ProductChangeLogRepositoryInterface
     */
    private ProductChangeLogRepositoryInterface $productChangeLogRepository;

    /**
     * Grid constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param SerializerInterface $serializer
     * @param CollectionFactory $dataCollectionFactory
     * @param ProductChangeLogRepositoryInterface $productChangeLogRepository
     * @param array $data
     */
    public function __construct(
        Context                             $context,
        Data                                $backendHelper,
        SerializerInterface                 $serializer,
        CollectionFactory                   $dataCollectionFactory,
        ProductChangeLogRepositoryInterface $productChangeLogRepository,
        array                               $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
        $this->serializer = $serializer;
        $this->dataCollectionFactory = $dataCollectionFactory;
        $this->productChangeLogRepository = $productChangeLogRepository;
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setId('productChangeLogGrid');
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
            'post_data',
            [
                'header' => __('Posted Data'),
                'sortable' => false,
                'renderer' => Diff::class,
                'index' => 'post_data'
            ]
        );

        $this->addColumn(
            'original_data',
            [
                'header' => __('Product Data Before'),
                'sortable' => false,
                'renderer' => Diff::class,
                'index' => 'original_data'
            ]
        );

        $this->addColumn(
            'result_data',
            [
                'header' => __('Product Data After'),
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

        try {
            $id = (int)$this->getRequest()->getParam('id');
            $history = $this->productChangeLogRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            $history = null;
        }

        if ($history) {
            $postData = $history->getPostData();
            $beforeValues = $history->getBeforeValues();
            $afterValues = $history->getAfterValues();
            $data = [
                'post_data' => !empty($postData) ? $this->serializer->unserialize($postData) : 'N/A',
                'original_data' => !empty($beforeValues) ? $this->serializer->unserialize($beforeValues) : 'N/A',
                'result_data' => !empty($afterValues) ? $this->serializer->unserialize($afterValues) : 'N/A'
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
