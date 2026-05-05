<?php
namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Manage;

class Save extends \Webkul\SpinToWin\Controller\Adminhtml\Manage\Save
{
    protected $timezone;

    protected $spinDraftHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Webkul\SpinToWin\Model\EditFormFactory $editFormFactory,
        \Webkul\SpinToWin\Model\ResultFormFactory $resultFormFactory,
        \Webkul\SpinToWin\Model\WheelFactory $wheelFactory,
        \Webkul\SpinToWin\Model\LayoutFactory $layoutFactory,
        \Webkul\SpinToWin\Model\VisibilityFactory $visibilityFactory,
        \Webkul\SpinToWin\Model\ButtonFactory $buttonFactory,
        \Webkul\SpinToWin\Model\CouponFactory $couponFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper
    ) {
        parent::__construct($context, $coreRegistry, $infoFactory, $editFormFactory, $resultFormFactory,
            $wheelFactory, $layoutFactory, $visibilityFactory, $buttonFactory, $couponFactory,
            $serializer, $logger);
        $this->timezone = $timezone;
        $this->spinDraftHelper = $spinDraftHelper;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $msg = "";
        if ($data && !empty($data)) {
            try {
                if ($data['scheduled']) {
                    if (strtotime($data['start_date'])<0 || strtotime($data['end_date'])<0) {
                        $msg = __("Date must be valid.");
                    }
                    if (strtotime($data['start_date']) >= strtotime($data['end_date'])) {
                        $msg = __("Start Date must be less than End Date.");
                    }
                    $data['start_date'] = $this->timezone->convertConfigTimeToUtc($data['start_date'], 'Y:m:d H:i:s');
                    $data['end_date'] = $this->timezone->convertConfigTimeToUtc($data['end_date'], 'Y:m:d H:i:s');
                } else {
                    $data['start_date'] = null;
                    $data['end_date'] = null;
                }
                if (!$msg) {
                    $newSave = false;
                    if (!$data['entity_id']) {
                        unset($data['entity_id']);
                        $newSave = true;
                    }
                    if (is_array($data['website_ids'])) {
                        $data['website_ids'] = implode(',', $data['website_ids']);
                    }
                    if (is_array($data['customergroup_ids'])) {
                        $data['customergroup_ids'] = implode(',', $data['customergroup_ids']);
                    }
                    
                    if ($newSave) {
                        $infoModel = $this->infoFactory->create();
                        $infoModel->setData($data);
                        $infoModel->save();
                        $id = $infoModel->getId();
                        $this->setDefaultData($id);
                    }else{
                        $id = $data['entity_id'];
                        /** Validate prize 100% */
                        $checkPrize100Invalid = $this->spinDraftHelper->checkPrize100Invalid($id);
                        if($checkPrize100Invalid['result']){
                            $this->messageManager->addErrorMessage(__("Can't save the Spin to win if total odds of prizes are different from 100."));
                            $this->_redirect('spintowin/*/edit', ['id' => $id]);
                            return;
                        }else{
                            $this->spinDraftHelper->saveDraft($id, 'information', $data);
                        }
                    }
                    if (isset($data['entity_id']) && $data['entity_id']) {
                        $this->_redirect('spintowin/*/edit', ['id' => $id]);
                        $this->messageManager->addSuccess(
                            __('Spin to Win info successfully saved.')
                        );
                        return;
                    } else {
                        $this->_redirect('spintowin/*/edit', ['id' => $id]);
                        $this->messageManager->addSuccess(
                            __('Spin Campaign data has been saved successfully.')
                        );
                        return;
                    }
                }
                if (isset($data['entity_id']) && $data['entity_id']) {
                    $this->getResponse()->setHeader('Content-type', 'application/javascript');
                    $this->getResponse()->setBody($this->serializer
                        ->serialize(
                            [
                                'success' => 0,
                                'message' => $msg,
                            ]
                        ));
                    return;
                } else {
                    $this->messageManager->addError($msg);
                    $this->_redirect('spintowin/*/edit', ['id' => $this->getRequest()->getParam('entity_id')]);
                    return;
                }

            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
                if (isset($data['entity_id']) && $data['entity_id']) {
                    $this->getResponse()->setHeader('Content-type', 'application/javascript');
                    $this->getResponse()->setBody($this->serializer
                        ->serialize(
                            [
                                'success' => 0,
                                'message' => __('Something went wrong while saving the campaign data.
                                Please review the error log.'),
                            ]
                        ));
                    return;
                } else {
                    $this->messageManager->addError(
                        __('Something went wrong while saving the campaign data.
                        Please review the error log.')
                    );
                    $this->_redirect(
                        'spintowin/*/edit',
                        ['id' => $this->getRequest()->getParam('entity_id')]
                    );
                    return;
                }
            }
            if (isset($data['entity_id']) && $data['entity_id']) {
                $this->getResponse()->setHeader('Content-type', 'application/javascript');
                $this->getResponse()->setBody($this->serializer
                    ->serialize(
                        [
                            'success' => 0,
                            'message' => __('Something went wrong while saving the campaign data.
                            Please review the error log.'),
                        ]
                    ));
                return;
            } else {
                $this->_redirect('spintowin/*/edit', ['id' => $this->getRequest()->getParam('entity_id')]);
                return ;
            }
        }
    }
}
