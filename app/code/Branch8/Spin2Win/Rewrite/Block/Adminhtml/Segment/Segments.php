<?php

namespace Branch8\Spin2Win\Rewrite\Block\Adminhtml\Segment;

class Segments extends \Webkul\SpinToWin\Block\Adminhtml\Segment\Segments
{
    protected $segmentType;

    protected $yesNoConfig;

    protected $spinDraftHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory,
        \Branch8\Spin2Win\Model\Config\Source\SegmentType $segmentType,
        \Magento\Config\Model\Config\Source\Yesno $yesNoConfig,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $segmentsFactory, $data);
        $this->segmentType = $segmentType;
        $this->yesNoConfig = $yesNoConfig;
        $this->spinDraftHelper = $spinDraftHelper;
    }

    protected function _prepareCollection()
    {
        $spinId = $this->getRequest()->getParam('spin_id');

        /** Get deleted segments */
        $deletetdSegments = $this->spinDraftHelper->getDeletedSegment($spinId);

        $collection = $this->segmentsFactory->create()
                                ->getCollection()
                                ->addFieldToFilter('spin_id', $spinId);
        if(!empty($deletetdSegments)){
            $collection->addFieldToFilter('entity_id', ['nin' =>  $deletetdSegments]);
        }
                          
        $collection->getSelect()->columns(
            new \Zend_Db_Expr('limits - availed as remain_qty')
        );
        $this->setCollection($collection);
        return $this;
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'label',
            [
                'header' => __('Prize Label'),
                'index' => 'label',
                'renderer' => \Branch8\Spin2Win\Ui\Component\Listing\Column\MergeDraft::class
            ]
        );

        $this->addColumn(
            'type',
            [
                'header' => __('Prize Type'),
                'index' => 'type',
                'type' => 'options',
                'options' => $this->segmentType->getOptions(),

            ]
        );

        $this->addColumn(
            'limits',
            [
                'header' => __('Prize Qty'),
                'index' => 'limits',
            ]
        );

        $this->addColumn(
            'remain_qty',
            [
                'header' => __('Prize Remain Qty'),
                'index' => 'remain_qty',
            ]
        );

        $this->addColumn(
            'gravity',
            [
                'header' => __('Prize Probability'),
                'index' => 'gravity',
                'renderer' => \Branch8\Spin2Win\Ui\Component\Listing\Column\Gravity::class
            ]
        );

        $this->addColumn(
            'award',
            [
                'header' => __('Detail Type'),
                'index' => 'award',
                'renderer' => \Branch8\Spin2Win\Ui\Component\Listing\Column\Award::class
            ]
        );

        $this->addColumn(
            'stock_reminder',
            [
                'header' => __('Prize Stock Reminder'),
                'index' => 'stock_reminder',
                'type' => 'options',
                'options' => $this->yesNoConfig->toArray(),
            ]
        );
        $this->addColumn(
            'threshold',
            [
                'header' => __('Prize Safety Stock'),
                'index' => 'threshold',
            ]
        );
        $this->addColumn(
            'position',
            [
                'header' => __('Position'),
                'index' => 'position',
            ]
        );
        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'width' => '100',
                'type' => 'action',
                'renderer' => \Webkul\SpinToWin\Block\Adminhtml\Segment\Renderer\Action::class,
                'filter' => false,
                'sortable' => false,
            ]
        );

        return $this;
    }

}