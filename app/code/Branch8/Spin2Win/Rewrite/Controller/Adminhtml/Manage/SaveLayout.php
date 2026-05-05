<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;
use Magento\Backend\App\Action\Context;



class SaveLayout extends \Webkul\SpinToWin\Controller\Adminhtml\Manage\SaveLayout{

    protected $dbTransactionFactory;

    protected $_urlRewriteFactory;

    protected $helper;

    protected $spinDraftHelper;

    public function __construct(
        Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Model\LayoutFactory $layoutFactory,
        \Magento\Framework\DB\TransactionFactory $dbTransactionFactory,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    ) {
        parent::__construct($context, $serializer, $layoutFactory);
        $this->dbTransactionFactory = $dbTransactionFactory;
        $this->_urlRewriteFactory = $urlRewriteFactory;
        $this->helper = $helper;
        $this->spinDraftHelper = $spinDraftHelper;
    }

    public function execute()
    {
        try {
            $data = $this->getRequest()->getParams();
            if (!empty($data) && isset($data['entity_id'])) {
                $layoutData = $this->layoutFactory->create();
                $layoutData->load($data['entity_id']);
                $spinId = $layoutData->getSpinId();

                if (strpos($data['desktop_background_image'], '.tmp') !== false) {
                    $data['desktop_background_image'] = rtrim($data['desktop_background_image'], ".tmp");
                    $newFile = $this->helper->saveFile($data['desktop_background_image']);
                    $data['desktop_background_image'] = 'spintowin'.$newFile;
                }
                if (strpos($data['mobile_background_image'], '.tmp') !== false) {
                    $data['mobile_background_image'] = rtrim($data['mobile_background_image'], ".tmp");
                    $newFile = $this->helper->saveFile($data['mobile_background_image']);
                    $data['mobile_background_image'] = 'spintowin'.$newFile;
                }
                unset($data['key']);
                $this->spinDraftHelper->saveDraft($spinId, 'layout', $data);
                // $layoutData->setData($data);

                // $dbTransaction = $this->dbTransactionFactory->create();
                // $dbTransaction->addObject($layoutData);
                // $layoutData->save();

                // if($data['view'] == 'page'){
                //     $targetPath = "spintowin/campaign/view/id/".$spinId;
                //     $urlRewriteRecord = $this->_urlRewriteFactory->create()->getCollection()
                //         ->addFieldToFilter('target_path', $targetPath)
                //         ->getFirstItem();
                //     if($urlRewriteRecord->getId()){
                //         $urlRewriteRecord->setRequestPath($data['page_url']);
                //         $dbTransaction->addObject($urlRewriteRecord);
                //     }else{
                //         $urlRewriteModel = $this->_urlRewriteFactory->create();
                //         $urlRewriteModel->setStoreId(1);
                //         $urlRewriteModel->setEntityId(0);
                //         $urlRewriteModel->setEntityType('custom');
                //         $urlRewriteModel->setRedirectType(0);
                //         $urlRewriteModel->setTargetPath("spintowin/campaign/view/id/".$spinId);
                //         $urlRewriteModel->setRequestPath($data['page_url']);
                //         $dbTransaction->addObject($urlRewriteModel);
                //     }
                // }else{
                //     $targetPath = "spintowin/campaign/view/id/".$spinId;
                //     $urlRewriteModel = $this->_urlRewriteFactory->create()->getCollection()
                //         ->addFieldToFilter('target_path', $targetPath)
                //         ->getFirstItem();
                //     if($urlRewriteModel->getId()){
                //         $urlRewriteModel->isDeleted(true);
                //         $dbTransaction->addObject($urlRewriteModel);
                //     }
                // }
                // $dbTransaction->save();
                $this->getResponse()->setHeader('Content-type', 'application/javascript');
                $this->getResponse()->setBody($this->_serializer
                    ->serialize(
                        [
                            'success' => 1,
                            'message' => __('Layout data successfully saved.'),
                            'data' => [
                                'desktop_background_image' => $data['desktop_background_image'],
                                'mobile_background_image' => $data['mobile_background_image']
                            ]
                        ]
                    ));
                return;
            } else {
                $this->getResponse()->setHeader('Content-type', 'application/javascript');
                $this->getResponse()->setBody($this->_serializer
                    ->serialize(
                        [
                            'success' => 0,
                            'message' => __('Invalid data.')
                        ]
                    ));
                return;
            }
        } catch (\Exception $e) {
            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $this->getResponse()->setBody($this->_serializer
                    ->serialize(
                        [
                            'success' => 0,
                            'message' => $e->getMessage()
                        ]
                    ));
            return;
        }
    }

}