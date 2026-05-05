<?php
namespace Branch8\Spin2Win\Block;

use Magento\Customer\Helper\Address;
use Magento\Framework\App\ObjectManager;

class TriggerAction extends \Magento\Framework\View\Element\Template {
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Webkul\SpinToWin\Model\ReportsFactory
     */
    protected $reportsFactory;

    /**
     * @var \Branch8\Spin2Win\Helper\Data
     */
    protected $b8SpinHelper;

    /**
     * @var  \Webkul\SpinToWin\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_conn;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Branch8\Spin2Win\Helper\Data $b8SpinHelper
     * @param \Webkul\SpinToWin\Helper\Data $helper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\Spin2Win\Helper\Data $b8SpinHelper,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );
        $this->reportsFactory = $reportsFactory;
        $this->customerSession = $customerSession;
        $this->b8SpinHelper = $b8SpinHelper;
        $this->helper = $helper;
        $this->timezone = $timezone;
        $this->registry = $registry;
        $this->_conn = $resourceConnection->getConnection();
    }

    public function getCurrentSpinId()
    {
        return $this->registry->registry('current_spinid');
    }
}