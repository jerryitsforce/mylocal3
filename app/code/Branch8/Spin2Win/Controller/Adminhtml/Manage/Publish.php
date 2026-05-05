<?php
namespace Branch8\Spin2Win\Controller\Adminhtml\Manage;

class Publish extends \Magento\Backend\App\Action
{
    protected $timezone;

    protected $spinDraftHelper;

    protected $_conn;

    protected $segmentsFactory;

    protected $infoFactory;

    protected $_urlRewriteFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
    ) {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->spinDraftHelper = $spinDraftHelper;
        $this->_conn = $resourceConnection->getConnection();
        $this->segmentsFactory = $segmentsFactory;
        $this->infoFactory = $infoFactory;
        $this->_urlRewriteFactory = $urlRewriteFactory;
    }

    public function execute()
    {
        $spinId = $this->getRequest()->getParam('id');
        $checkPrize100Invalid = $this->spinDraftHelper->checkPrize100Invalid($spinId);
        if($checkPrize100Invalid['result']){
            $this->messageManager->addErrorMessage(__('Publishing failed, please try again.'));
            $this->messageManager->addErrorMessage(__('The total probability is incorrect. Current total: %1%. Please adjust to 100%.', $checkPrize100Invalid['total']));
            $this->_redirect('spintowin/*/edit', ['id' => $spinId]);
            return ;
        }
        try{
            $infoModel = $this->infoFactory->create()->load($spinId);
            
            if(!$infoModel->getId()){
                $this->messageManager->addErrorMessage(__("The event doesn't exist."));
                $this->_redirect('spintowin/manage/index');
                return ;
            }
            
            $draftData = $this->spinDraftHelper->getDraft($spinId);
            if(empty($draftData)){
                $this->_redirect($this->_redirect->getRefererUrl());
                return;
            }
            $this->_conn->beginTransaction();

            /** Information */
            $draftInfor = $this->spinDraftHelper->getDraftByType($spinId, 'information');
            if($draftInfor){
                $newInfor = array_merge($infoModel->getData(), $draftInfor);
                $infoModel->setData($newInfor);
                $infoModel->save();
            }
            /** Consolation */
            $draftConsolation = $this->spinDraftHelper->getDraftByType($spinId, 'consolation');
            if($draftConsolation){
                $consolationSelect = $this->_conn->select()
                    ->from(['consolation' => 'spintowin_consolation'])
                    ->where('spin_id = ?', $spinId);
                $consolation = $this->_conn->fetchRow($consolationSelect);
                if(!empty($consolation)){
                    $consolationId = $consolation['consolation_id'];
                    unset($consolation['consolation_id']);
                    $newConsolation = array_merge($consolation, $draftConsolation);
                    $this->_conn->update('spintowin_consolation', $newConsolation, 'consolation_id = ' . $consolationId);
                }else{
                    $this->_conn->insert('spintowin_consolation', $draftConsolation);
                }
                
            }
            /** Layout */
            $layoutModel = $infoModel->getLayout();
            $draftLayout = $this->spinDraftHelper->getDraftByType($spinId, 'layout');
            if($draftLayout){
                $newLayout = array_merge($layoutModel->getData(), $draftLayout);
                $layoutModel->setData($newLayout);
                $layoutModel->save();
                if($layoutModel->getView() == 'page'){
                    $targetPath = "spintowin/campaign/view/id/".$spinId;
                    $urlRewriteModel = $this->_urlRewriteFactory->create()->getCollection()
                        ->addFieldToFilter('target_path', $targetPath)
                        ->getFirstItem();
                    if($urlRewriteModel->getId()){
                        $urlRewriteModel->setRequestPath($layoutModel->getPageUrl());
                    }else{
                        $urlRewriteModel = $this->_urlRewriteFactory->create();
                        $urlRewriteModel->setStoreId(1);
                        $urlRewriteModel->setEntityId(0);
                        $urlRewriteModel->setEntityType('custom');
                        $urlRewriteModel->setRedirectType(0);
                        $urlRewriteModel->setTargetPath("spintowin/campaign/view/id/".$spinId);
                        $urlRewriteModel->setRequestPath($layoutModel->getPageUrl());
                    }
                }else{
                    $targetPath = "spintowin/campaign/view/id/".$spinId;
                    $urlRewriteModel = $this->_urlRewriteFactory->create()->getCollection()
                        ->addFieldToFilter('target_path', $targetPath)
                        ->getFirstItem();
                    if($urlRewriteModel->getId()){
                        $urlRewriteModel->isDeleted(true);
                    }
                }
                $urlRewriteModel->save();
            }
            /** Wheel */
            $wheelModel = $infoModel->getWheel();
            $draftWheel = $this->spinDraftHelper->getDraftByType($spinId, 'wheel');
            if($draftWheel){
                $newWheel = array_merge($wheelModel->getData(), $draftWheel);
                $wheelModel->setData($newWheel);
                $wheelModel->save();
            }
            /** Button */
            $buttonModel = $infoModel->getButton();
            $draftButton = $this->spinDraftHelper->getDraftByType($spinId, 'button');
            if($draftButton){
                $newButton = array_merge($buttonModel->getData(), $draftButton);
                $buttonModel->setData($newButton);
                $buttonModel->save();
            }
            /** Segments */
            $draftSegments = $this->spinDraftHelper->getDraftByType($spinId, 'segments');
            if($draftSegments){
                foreach($draftSegments as $sId => $_draftSegment){
                    $segment = $this->segmentsFactory->create()->load($sId);
                    $newSegmentData = array_merge($segment->getData(), $_draftSegment);
                    $segment->setData($newSegmentData);
                    $segment->save();
                }
            }
            /** Publish new segment */
            $this->_conn->update('spintowin_segments', ['is_publish' => '1'], 'is_publish=0');
            
            $deletedSegments = $this->spinDraftHelper->getDeletedSegment($spinId);
            if(!empty($deletedSegments)){
                $this->_conn->delete('spintowin_segments', 'entity_id in ('.implode(',', $deletedSegments).')');
            }

            $this->_conn->delete('spintowin_draft', 'spin_id='.$spinId);

            $this->_conn->commit();
            $this->messageManager->addSuccessMessage(__('Published successfully.'));
    
        }catch(\Exception $e){
            $this->_conn->rollBack();
            $this->messageManager->addErrorMessage(__('Publishing failed, please try again.').' '.$e->getMessage());
        }

        $this->_redirect('spintowin/*/edit', ['id' => $spinId]);
        return;


            // if (isset($data['entity_id']) && $data['entity_id']) {
            //     $this->getResponse()->setHeader('Content-type', 'application/javascript');
            //     $this->getResponse()->setBody($this->serializer
            //         ->serialize(
            //             [
            //                 'success' => 0,
            //                 'message' => __('Something went wrong while saving the campaign data.
            //                 Please review the error log.'),
            //             ]
            //         ));
            //     return;
            // } else {
            //     $this->_redirect('spintowin/*/edit', ['id' => $this->getRequest()->getParam('entity_id')]);
            //     return ;
            // }
        
    }
}
