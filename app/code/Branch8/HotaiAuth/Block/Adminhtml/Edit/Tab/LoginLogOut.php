<?php
namespace Branch8\HotaiAuth\Block\Adminhtml\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
class LoginLogOut  extends \Magento\Backend\Block\Template implements TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Branch8_HotaiAuth::tab/customer_login_logout.phtml';

    protected $_coreRegistry;

    /**
     * Resource instance.
     *
     * @var Resource
     */
    protected $resource;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ResourceConnection $resource,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->_coreRegistry = $registry;
        $this->resource = $resource;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getTabLabel()
    {
        return __('Customer Login/Logout');
    }

    public function getTabTitle()
    {
        return __('Customer Login/Logout');
    }

    public function getTabClass()
    {
       return '';
    }

    public function getCustomerId()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
    }

    public function getTabUrl()
    {
        return '';
    }

    public function isAjaxLoaded()
    {
        return false;
    }

    public function canShowTab()
    {
        if ($this->getCustomerId()) {
            return true;
        }
        return false;
    }

    public function isHidden()
    {
        if ($this->getCustomerId()) {
            return false;
        }
        return true;
    }

    public function getCustomerDataLoginOut(){
        if ($this->getCustomerId()) {
            $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
            $tableName = $connection->getTableName('branch_custom_customer_log');

            // Prepare the SQL query
            $select = $connection->select()
                ->from($tableName)
                ->where('customer_id = ?', $this->getCustomerId());

            // Execute the query and fetch the result as an array
            return $connection->fetchAll($select);
        }
        return [];
    }

    /**
     * @param $value
     * @return string
     */
    public function getTime($value){
        return $this->formatDate($value, \IntlDateFormatter::MEDIUM, true);
    }

}
