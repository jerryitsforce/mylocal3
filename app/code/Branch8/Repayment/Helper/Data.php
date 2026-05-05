<?php

namespace Branch8\Repayment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Branch8\Repayment\Helper\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Session\SessionManager;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    public $scopeConfig;
    public $config;
    protected $session;
    protected $_storeManager;
    protected $_connection;
    protected $_resource;
    /**
     * Initialize helper dependencies.
     *
     * @param ScopeConfigInterface $scopeConfig Scope config reader.
     * @param Config $config Repayment config constants.
     * @param SessionManager $session Session manager.
     * @param Context $context Helper context.
     * @param StoreManagerInterface $storeManager Store manager.
     * @param ResourceConnection $resource Database resource.
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Config $config,
        SessionManager $session,
        Context $context,
        StoreManagerInterface $storeManager,
        ResourceConnection $resource
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->session = $session;
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->_resource = $resource;
        $this->_connection = $this->_resource->getConnection();
    }
  
    
    /**
     * Get available repayment methods from store config.
     *
     * @return mixed
     */
    public function getAvaiableRepaymentMethod()
    {
        return $this->scopeConfig->getValue($this->config::REPAYMENT_METHOD, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
}
