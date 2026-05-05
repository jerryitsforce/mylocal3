<?php
namespace Branch8\EventTicket\Helper;

class MappingLog extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $timezone;

    protected $_conn;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        parent::__construct($context);
        $this->timezone = $timezone;
        $this->_conn = $resourceConnection->getConnectionByName('indexer');
    }

    public function writeLog($data){
        $val = '';
        $dateObj = $this->timezone->date();
            $time = $dateObj->format('Y-m-d H:i:s').'.'.$dateObj->format('u');
            $val .= '(NULL, '.$data['pool_id'].', "'.$time.': '.$data['content'].'");';
        $sql = 'insert into ticket_import_mapping_log values'.$val;
        $sql = substr($sql, 0, -1);
        $this->_conn->query($sql);
    }
}
