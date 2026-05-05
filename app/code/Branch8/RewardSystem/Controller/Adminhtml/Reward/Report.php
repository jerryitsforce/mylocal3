<?php
namespace Branch8\RewardSystem\Controller\Adminhtml\Reward;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class Report extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Branch8_RewardSystem::report';

    protected $_dir;
    
    protected $_conn;

    protected $fileFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\Filesystem\DirectoryList $dir,
       \Magento\Framework\App\ResourceConnection $resourceConnection,
       \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
       
    )
    {
        $this->_conn = $resourceConnection->getConnection();
        parent::__construct($context);
        $this->_dir = $dir;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $eventId = $this->getRequest()->getParam('id');
        $reportSql = 'select rp.status, rp.created_at, rp.reward_type, rp.reward_coupon, rp.reward_pool_serial, rp.fail_reason, cus.member_seq 
            from branch8_rewardsystem_report as rp left join customer_entity as cus on rp.customer_id=cus.entity_id where event_id='.$eventId;
        $queryEvent = $this->_conn->query($reportSql);


        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', '時間');
        $sheet->setCellValue('B1', '用戶ID');
        $sheet->setCellValue('C1', '狀態');
        $sheet->setCellValue('D1', '獎勵內容 / 失敗原因');
        // $headerCsv = [
        //     '時間',/**Time */
        //     '用戶ID',/** User ID */
        //     '狀態',/** Status */
        //     '獎勵內容 / 失敗原因'/** Reward content / Failure reason */
        // ];
        // $handle = fopen("php://output", "w");
        // ob_start();
        // fputcsv($handle, $headerCsv);
        $line = 2;
        while($row = $queryEvent->fetch()){
            if($row['status'] == 1){
                $statusTxt = '成功';
                if($row['reward_type']== \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_POOL){
                    $rewardContent = $row['reward_pool_serial'];
                }
                if($row['reward_type']== \Branch8\RewardSystem\Model\Config\Source\RewardType::TYPE_COUPON){
                    $rewardContent = $row['reward_coupon'];
                }
            }else{
                $statusTxt = '失敗';
                $rewardContent = $row['fail_reason'];
            }
            
            $sheet->setCellValue('A'.$line, $row['created_at']);
            $sheet->setCellValue('B'.$line, $row['member_seq']);
            $sheet->setCellValue('C'.$line, $statusTxt);
            $sheet->setCellValue('D'.$line, $rewardContent);
            $line ++;
        }
        $writer = new Xls($spreadsheet);
        $downloadedFileName = 'automated_reward_'.$eventId.'.xls';
        
        $exportPath = $this->_dir->getPath('var');
        $exportFilePath = $exportPath.'/export/'.$downloadedFileName;
        $writer->save($exportFilePath);
        
        $filepath = '/export/'.$downloadedFileName;

        $content['type'] = 'filename';
        $content['value'] = $filepath;
        $content['rm'] = 1;
        return $this->fileFactory->create($downloadedFileName, $content, \Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
    }

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
