<?php
namespace Branch8\Spin2Win\Controller\Adminhtml\Report;
use Magento\Backend\App\Action\Context;

class Redemption extends \Magento\Backend\App\Action{

    protected $_conn;

    protected $_dir;

    protected $fileFactory;

    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ){

        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
        $this->_dir = $dir;
        $this->fileFactory = $fileFactory;
    
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

        $sqlRedemption = $this->_conn->select()
            ->from(['redemption' => 'spintowin_redemption'])
            ->columns(new \Zend_Db_Expr('addtime(redemption.created_at, "08:00:00") as redemption_at'))
            ->joinLeft(['customer' => 'customer_grid_flat'], 'customer.entity_id = redemption.customer_id', ['group_id', 'phone_number', 'member_seq'])
            ->where('spin_id = ?', $spinId);
        $queryRedemption = $this->_conn->query($sqlRedemption);
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
            'redemption_time' => '兌點時間',
            'task_completion' => '完成任務時間',
            'member_seq' => '會員OneID',
            'member_phone' => '會員電話',
            'point' => '兌點數量',
            'chance' => '取得抽獎次數'
        ];
        fputcsv($handle, $rowHeader);
        $cnt = 0;
        while($row = $queryRedemption->fetch()){
            $cnt ++;
            $rowData = [
                $cnt,
                'event_name' => $spinData['name'],
                'event_description' => $spinDescription,
                'customer_group' => $customerGroupStr,
                'start_date' => $spinData['s_date'],
                'end_date' => $spinData['e_date'],
                'redemption_time' => $row['redemption_at'],
                'task_completion' => '',
                'member_seq' => $row['member_seq'],
                'member_phone' => $row['phone_number'],
                'point' => $row['point'],
                'chance' => $row['chance_qty']
            ];
            fputcsv($handle, $rowData);
        }
        $csvContent = ob_get_clean();
        $downloadedFileName = 'event_report_redemption_'.$spinId.'.csv';

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