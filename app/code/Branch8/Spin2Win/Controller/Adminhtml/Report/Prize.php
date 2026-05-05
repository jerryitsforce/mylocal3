<?php
namespace Branch8\Spin2Win\Controller\Adminhtml\Report;
use Magento\Backend\App\Action\Context;

class Prize extends \Magento\Backend\App\Action{

    protected $_conn;

    protected $_dir;

    protected $fileFactory;

    protected $segmentType;

    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Branch8\Spin2Win\Model\Config\Source\SegmentType $segmentType
    ){

        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->_dir = $dir;
        $this->fileFactory = $fileFactory;
        $this->segmentType = $segmentType;
    }

    public function execute(){
        $spinId = $this->getRequest()->getParam('sid');
        $sqlSpin = $this->_conn->select()
        ->from(['spin' => 'spintowin_info'])
        ->columns(new \Zend_Db_Expr('addtime(start_date, "08:00:00") as s_date'))
        ->columns(new \Zend_Db_Expr('addtime(end_date, "08:00:00") as e_date'))
        ->where('entity_id = ?', $spinId);
        $spinData = $this->_conn->fetchRow($sqlSpin);
        $spinDescription = strip_tags((string)$spinData['spin_description']);

        $customerGroupSelect = $this->_conn->select()
            ->from(['cg' => 'customer_group'], ['gr' => 'group_concat(customer_group_code)'])
            ->where('customer_group_id in('.$spinData['customergroup_ids'].')');
        $customerGroupStr = $this->_conn->fetchOne($customerGroupSelect);
        
        $sqlReportPrize = $this->_conn->select()
            ->from(['report' => 'spintowin_reports'])
            ->columns(new \Zend_Db_Expr('addtime(report.created_at, "08:00:00") as c_date'))
            ->joinLeft(['customer' => 'customer_grid_flat'], 'customer.entity_id = report.customer_id', ['group_id', 'phone_number', 'member_seq'])
            ->joinLeft(['address' => 'spintowin_award_address'], 'address.segment_report_id = report.entity_id and address.customer_id=report.customer_id', 
                ['address_name' => 'name', 'address_phone_number' => 'phone_number', 'street', 'region', 'city', 'country'])
                ->joinLeft(
                    ['consolation_report' => 'spintowin_consolation_reports'], 
                    'consolation_report.prize_report_id = report.entity_id', 
                    [
                        'consolation_report.consolation_title', 'consolation_reward_point' => 'reward_point', 'consolation_type',
                        'consolation_rule_id' => 'rule_id', 'consolation_pool_id' => 'pool_id', 'consolation_pool_name', 
                        'consolation_rule_name', 'consolation_coupon' => 'coupon', 'consolation_serial_number' =>'serial_number',
                        'consolation_point_account' => 'reward_point_account', 'consolation_bu_no' => 'point_bu_no', 
                        'consolation_rs_no' => 'point_rs_no', 'consolation_prize_trace_no' => 'prize_trace_no'
                    ])
            ->where('report.spin_id = ?', $spinId)
            ->order('report.entity_id asc');
        $queryReportPrize = $this->_conn->query($sqlReportPrize);
        $rows = [];
        $handle = fopen("php://output", "w");
        ob_start();
        $rowHeader = [
            '#',
            'event_name' => '活動名稱',
            'event_description' => '活動描述',
            'customer_group' => '客戶群組設定',
            'start_date' => '活動開始日',
            'end_date' => '活動結束日',
            'created_at' => '抽獎時間',
            'member_seq' => '抽獎會員OneID',
            'member_phone' => '抽獎會員電話 ',
            'result' => '是否得獎',
            'prize_won_of_gift' => '得獎商品',
            'win_points' => '得獎點數',
            'consolation_prize' => '保底獎品',
            'additional_prize' => '額外贈品',
            'consolation_type' => '獎品類型',
            'address_name' => '實體獎品收件人姓名',
            'address_phone' => '實體獎品收件人電話',
            'address' => '實體獎品收件人地址',
            'Coupon Rule ID' => 'Coupon Rule ID',
            'Coupon Rule Name' => 'Coupon Rule Name',
            'Coupon Serial Number' => 'Coupon 序號',
            'Pool ID' => 'Pool ID',
            'Pool Name' => __('Pool Name'),
            'Voucher Serial Number' => '票券序號',
            'point_bu_no' => 'buNo', 
            'point_rs_no' => 'rsNo', 
            'redeem_trace_no' => 'Redeem TraceNo',
            'prize_trace_no' => 'Prize Trace No'
        ];
        fputcsv($handle, $rowHeader);
        $cnt = 0;
        $allPrizeType = $this->segmentType->getOptions();
        while($row = $queryReportPrize->fetch()){
            $cnt ++;
            $winPoint = $row['result'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::REWARD_POINT_TYPE ? $row['reward_point'] : '';
            if($row['result'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::LOSE_TYPE && $row['is_apply_consolation']){
                $winPoint = $row['consolation_reward_point'];
            }
            $address = '';
            if(
                ($row['result'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE) 
                || (
                    $row['result'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::LOSE_TYPE 
                    && $row['is_apply_consolation'] 
                    && $row['consolation_type'] == \Branch8\Spin2Win\Model\Config\Source\SegmentType::PHYSICAL_TYPE
                    )
            ){
                $address = !empty($row['street']) ? $row['street'].','.$row['region'].','.$row['city'].','.$row['country'] : '';
            }
            $winType = '';
            $ruleId = '';
            $poolId = '';
            $ruleName = '';
            $poolName = '';
            $couponCode = '';
            $serialNumber = '';
            $buNo = '';
            $rsNo = '';
            $prizeTraceNo = '';
            if($row['result']){/** WIN */
                $winType = $allPrizeType[$row['result']];
                $ruleId = $row['rule_id'];
                $poolId = $row['pool_id'];
                $ruleName = $row['rule_name'];
                $poolName = $row['pool_name'];
                $couponCode = $row['coupon'];
                $serialNumber = $row['reward_serial_number'];
                $pointAccount = $row['reward_point_account'];
                if($pointAccount == \Branch8\Spin2Win\Model\Config\Source\PointsAccount::TYPE_CUSTOMIZE){
                    $buNo = $row['point_bu_no'];
                    $rsNo = $row['point_rs_no'];
                }
                $prizeTraceNo = (string)$row['prize_trace_no'];
            }else{/** LOSE */
                if(isset($allPrizeType[$row['consolation_type']])){ /** Win consolation */
                    $winType = $allPrizeType[$row['consolation_type']];
                    $ruleId = $row['consolation_rule_id'];
                    $poolId = $row['consolation_pool_id'];
                    $ruleName = $row['consolation_rule_name'];
                    $poolName = $row['consolation_pool_name'];
                    $couponCode = $row['coupon'];
                    $serialNumber = $row['consolation_serial_number'];
                    $pointAccount = $row['consolation_point_account'];
                    if($pointAccount == \Branch8\Spin2Win\Model\Config\Source\PointsAccount::TYPE_CUSTOMIZE){
                        $buNo = $row['consolation_bu_no'];
                        $rsNo = $row['consolation_rs_no'];
                    }
                    $prizeTraceNo = (string)$row['consolation_prize_trace_no'];
                }else{
                    /** Lose */
                    $winType = $allPrizeType[$row['result']];
                }
            }

            $rowData = [
                $cnt,
                'event_name' => $spinData['name'],
                'event_description' => $spinDescription,
                'customer_group' => $customerGroupStr,
                'start_date' => $spinData['s_date'],
                'end_date' => $spinData['e_date'],
                'created_at' => $row['c_date'],
                'member_seq' => $row['member_seq'],
                'member_phone' => $row['phone_number'],
                'result' => $row['result'] == 0 ? 'N':'Y',
                'prize_won_of_gift' => $row['segment_label'],
                'win_points' => $winPoint,
                'consolation_prize' => $row['consolation_title'],
                'additional_prize' => '',
                'consolation_type' => $winType,
                'address_name' => $row['address_name'],
                'address_phone' => $row['address_phone_number'],
                'address' => $address,
                'coupon_rule_id' => $ruleId,
                'coupon_rule_name' => $ruleName,
                'coupon_serial_number' => $couponCode,
                'pool_id' => $poolId,
                'pool_name' => $poolName,
                'voucher_serial_number' => $serialNumber,
                'point_bu_no' => $buNo,
                'point_rs_no' => $rsNo,
                'redeem_trace_no' => $row['redeem_trace_no'],
                'prize_trace_no' => $prizeTraceNo
            ];
            fputcsv($handle, $rowData);
        }
        $csvContent = ob_get_clean();
        $downloadedFileName = 'event_report_prize_'.$spinId.'.csv';

        $exportPath = $this->_dir->getPath('var');
        $exportFilePath = $exportPath.'/export/'.$downloadedFileName;
        file_put_contents($exportFilePath, $csvContent);
        
        $filepath = '/export/'.$downloadedFileName;

        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = 1;
        return $this->fileFactory->create($downloadedFileName, $content, \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
    }
}