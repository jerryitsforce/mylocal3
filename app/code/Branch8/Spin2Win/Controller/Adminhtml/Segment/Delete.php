<?php

namespace Branch8\Spin2Win\Controller\Adminhtml\Segment;
use Webkul\SpinToWin\Model\SegmentsFactory;


class Delete extends \Magento\Backend\App\Action{

    protected $segmentFactory;

    protected $_serializer;

    protected $spinDraftHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        SegmentsFactory $segmentFactory,
        \Magento\Framework\Serialize\SerializerInterface $_serializer,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper      
        )
    {
        parent::__construct($context);
        $this->segmentFactory = $segmentFactory;
        $this->_serializer = $_serializer;
        $this->spinDraftHelper = $spinDraftHelper;
    }
    public function execute(){
        
        $this->getResponse()->setHeader('Content-type', 'application/javascript');
        try{
            $sid = $this->getRequest()->getParam('id');
            $segment = $this->segmentFactory->create()->load($sid);
            if($segment->getId()){
                $this->spinDraftHelper->addDeleteSegment($segment->getSpinId(), $segment->getId());
            }
            /** ReCalculate total % */
            $checkPrize100Invalid = $this->spinDraftHelper->checkPrize100Invalid($segment->getSpinId());
            if($checkPrize100Invalid['result']){
                $pageMessage = __('The total probability is incorrect. Current total: %1%. Please adjust to 100%.', $checkPrize100Invalid['total']);
            }else{
                $pageMessage = '';
            }
            
        }catch(\Exception $e){
            $this->getResponse()->setBody($this->_serializer
            ->serialize(
                [
                    'success' => 0,
                    'message' => __('Segment deleted failure.')
                ]
            ));
            return;
        }
        
        $this->getResponse()->setBody($this->_serializer
            ->serialize(
                [
                    'success' => 1,
                    'message' => __('Segment successfully deleted.'),
                    'pageMessage' => $pageMessage
                ]
            ));
        return;
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_SpinToWin::manage');
    }
}



