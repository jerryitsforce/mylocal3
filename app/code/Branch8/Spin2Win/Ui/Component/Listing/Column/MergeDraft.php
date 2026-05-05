<?php
declare(strict_types=1);

namespace Branch8\Spin2Win\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\Spin2Win\Model\Config\Source\SegmentType;

/**
 * Class Edit
 */
class MergeDraft extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{

    protected $spinDraftHelper;

    public function __construct(
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    )
    {
        $this->spinDraftHelper = $spinDraftHelper;
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return mixed
     */
    public function render(\Magento\Framework\DataObject $row){
        $spinId = $row->getSpinId();
        $segmentDraft = $this->spinDraftHelper->getSegmentDraft($spinId, $row->getId());
        $newData = $row->getData();
        if($segmentDraft){
            $newData = array_merge($newData, $segmentDraft);   
        }
        $newData['label'] = str_replace('\n', '', $newData['label']);
        if($newData['limits'] === null){
            $newData['remain_qty'] = '';
        }else{
            $newData['remain_qty'] = (int)$newData['limits'] - (int)$newData['availed'];
        }
        
        $row->setData($newData);
        return $row->getLabel();
    }
}
