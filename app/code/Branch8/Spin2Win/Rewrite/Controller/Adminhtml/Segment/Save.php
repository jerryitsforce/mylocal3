<?php

namespace Branch8\Spin2Win\Rewrite\Controller\Adminhtml\Segment;

use Magento\Backend\App\Action\Context;

class Save extends \Webkul\SpinToWin\Controller\Adminhtml\Segment\Save
{
    protected $salesRuleCollectionFactory;

    protected $productRepo;

    protected $timezone;

    protected $spinDraftHelper;

    protected $_conn;

    public function __construct(
        Context $context,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\SalesRule\Model\RuleFactory $ruleFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory $salesRuleCollectionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepo,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Branch8\Spin2Win\Helper\Draft $spinDraftHelper,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct($context, $dateFilter, $segmentsFactory, $infoFactory, $logger,
            $groupFactory, $ruleFactory, $serializer, $helper);
        $this->salesRuleCollectionFactory  = $salesRuleCollectionFactory;
        $this->productRepo = $productRepo;
        $this->timezone = $timezone;
        $this->spinDraftHelper = $spinDraftHelper;
        $this->_conn = $resourceConnection->getConnection();
    }
    public function execute()
    {
        $responseData = [
            'success' => 1,
            'message' => __('Segment data successfully saved.')
        ];
        try {
            $data = $this->getRequest()->getPostValue();
            $segmentData = [];
            if ($data['segment_type'] == 1) {/** Coupon */
                $segmentData['rule_id'] = $data['rule_id'];

                if (strpos($data['coupon_image'], '.tmp') !== false) {
                    $data['coupon_image'] = rtrim($data['coupon_image'], ".tmp");
                    $newFile = $this->helper->saveFile($data['coupon_image']);
                    $segmentData['coupon_image'] = 'spintowin'.$newFile;
                    $responseData['data']['coupon_image'] = $segmentData['coupon_image'];
                }
                $segmentData['coupon_expired_date'] = $data['coupon_expired_date'];
            }else if($data['segment_type'] == 2){/** Virtual Item */
                $segmentData['pool_id'] = $data['pool_id'];
            }else if($data['segment_type'] == 3){/** Physical Item */
                $physicalExpireDate = $this->timezone->convertConfigTimeToUtc($data['physical_expire_date'].' 00:00:00');
                $segmentData['physical_expire_date'] = $physicalExpireDate;

                if (strpos($data['physical_image'], '.tmp') !== false) {
                    $data['physical_image'] = rtrim($data['physical_image'], ".tmp");
                    $newFile = $this->helper->saveFile($data['physical_image']);
                    $segmentData['physical_image'] = 'spintowin'.$newFile;
                    $responseData['data']['physical_image'] = $segmentData['physical_image'];
                }

            }else if($data['segment_type'] == 4){/** Reward points */
                $segmentData['reward_point'] = $data['reward_point'];
                $segmentData['point_expire_date'] = $data['point_expire_date'];
                $segmentData['reward_point_account'] = $data['reward_point_account'];
                if($data['reward_point_account'] == \Branch8\Spin2Win\Model\Config\Source\PointsAccount::TYPE_CUSTOMIZE){
                    $segmentData['point_bu_no'] = $data['point_bu_no'];
                    $segmentData['point_rs_no'] = $data['point_rs_no'];
                }
            }

            $segmentData['spin_id'] = $data['spin_id'];
            $segmentData['type'] = $data['segment_type'];
            $segmentData['heading'] = $data['heading'];
            $segmentData['label'] = $data['label'];
            $segmentData['description'] = $data['description'];
            $segmentData['limits'] = $data['limits'];
            $segmentData['threshold'] = isset($data['threshold']) ? $data['threshold'] : 0;;
            $segmentData['gravity'] = $data['gravity'];
            $segmentData['position'] = $data['position'];
            $segmentData['stock_reminder'] = $data['stock_reminder'];
            if($segmentData['stock_reminder']){
                $segmentData['threshold'] = (int)$data['threshold'];
            }
            $isSaveDraft = true;
            /** Existed */
            if ($data['segment_id']) {
                /** Get Availed */
                $segementSelect = $this->_conn->select()
                    ->from(['segment' => 'spintowin_segments'])
                    ->where('entity_id = ?', $data['segment_id']);
                $dbSegment = $this->_conn->fetchRow($segementSelect);
                if($data['limits'] != '' && (int)$data['limits'] < $dbSegment['availed']){
                    throw new \Exception(__('There have been %1 spins for this prize, please enter a number greater than the number drawn.', $dbSegment['availed']));
                }

                $segmentData['entity_id'] = $data['segment_id'];
            }else{/** New Object */
                $isSaveDraft = false;
                $segmentData['is_publish'] = 0;
            }

            if ($segmentData['limits']==='' || $segmentData['limits']===null) {
                $segmentData['limits'] = null;
            }
            /** Existed object => save draft and waiting for Apply */
            if($isSaveDraft){
                $spinId = $data['spin_id'];
                /** Get Segments from draft */
                $draftDb = $this->spinDraftHelper->getDraft($spinId);
                if(empty($draftDb)){
                    $segmentDraft = [$segmentData['entity_id'] => $segmentData];
                    $this->spinDraftHelper->saveDraft($spinId, 'segments', $segmentDraft);
                }else{
                    $draftDbData = json_decode($draftDb['data'], true);
                    if(isset($draftDbData['segments'])){
                        $segmentDraft = $draftDbData['segments'];
                    }else{
                        $segmentDraft = [];
                    }
                    $segmentDraft[$segmentData['entity_id']] = $segmentData;
                }
                $this->spinDraftHelper->saveDraft($spinId, 'segments', $segmentDraft);
            }else{/** If new object, save to segment and publish = 0 */
                $segment = $this->segmentsFactory->create();
                $segment->setData($segmentData);
                $segment->save();
            }
            $this->spinDraftHelper->reloadDraft(true);
            $checkPrize100Invalid = $this->spinDraftHelper->checkPrize100Invalid($data['spin_id']);
            
            if($checkPrize100Invalid['result']){
                $responseData['pageMessage'] = __('The total probability is incorrect. Current total: %1%. Please adjust to 100%.', $checkPrize100Invalid['total']);
            }else{
                $responseData['pageMessage'] = '';
            }

            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $this->getResponse()->setBody($this->_serializer
                ->serialize($responseData));
            return;
        } catch (\Exception $e) {
            $this->logger->info($e->getTraceAsString());
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