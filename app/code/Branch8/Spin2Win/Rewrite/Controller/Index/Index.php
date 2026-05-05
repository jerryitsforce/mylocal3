<?php

namespace Branch8\Spin2Win\Rewrite\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;

class Index extends Action
{
    /**
     * @var \Magento\Framework\Serialize\SerializerInterface
     */
    protected $serializer;
    /**
     * @var  \Webkul\SpinToWin\Helper\Data
     */
    protected $helper;
    /**
     * @var \Webkul\SpinToWin\Model\InfoFactory
     */
    protected $infoFactory;
    /**
     * @var \Webkul\SpinToWin\Logger\Logger
     */
    protected $logger;
    /**
     * @var CookieMetadataFactory
     */
    protected $cookieMetadata;
    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;
    /**
     * @var \Webkul\SpinToWin\Model\ReportsFactory
     */
    public $reportsFactory;
    /**
     * @var \Webkul\SpinToWin\Model\SegmentsFactory
     */
    public $segmentsFactory;
    /**
     * @var Magento\Framework\Session\SessionManagerInterface
     */
    public $sessionManager;
    /**
     * @var \Branch8\Spin2Win\Helper\Data
     */
    protected $b8SpinHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    public $_conn;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Framework\Serialize\SerializerInterface $serializer
     * @param \Webkul\SpinToWin\Model\InfoFactory $infoFactory
     * @param \Webkul\SpinToWin\Helper\Data $helper
     * @param \Branch8\Spin2Win\Helper\Data $b8SpinHelper
     * @param \Magento\Framework\Registry $registry
     * @param CookieMetadataFactory $cookieMetadata
     * @param CookieManagerInterface $cookieManager
     * @param \Webkul\SpinToWin\Logger\Logger $logger
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory
     * @param \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory
     * @param SessionManagerInterface $sessionManager
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \Webkul\SpinToWin\Model\InfoFactory $infoFactory,
        \Webkul\SpinToWin\Helper\Data $helper,
        \Branch8\Spin2Win\Helper\Data $b8SpinHelper,
        \Magento\Framework\Registry $registry,
        CookieMetadataFactory $cookieMetadata,
        CookieManagerInterface $cookieManager,
        \Webkul\SpinToWin\Logger\Logger $logger,
        \Magento\Customer\Model\Session $customerSession,
        \Webkul\SpinToWin\Model\ReportsFactory $reportsFactory,
        \Webkul\SpinToWin\Model\SegmentsFactory $segmentsFactory,
        SessionManagerInterface $sessionManager,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->serializer = $serializer;
        $this->helper = $helper;
        $this->infoFactory = $infoFactory;
        $this->logger = $logger;
        $this->cookieMetadata = $cookieMetadata;
        $this->cookieManager = $cookieManager;
        $this->_customerSession = $customerSession;
        $this->reportsFactory = $reportsFactory;
        $this->segmentsFactory = $segmentsFactory;
        $this->sessionManager = $sessionManager;
        $this->_conn = $resourceConnection->getConnection();
        parent::__construct($context);
        $this->b8SpinHelper = $b8SpinHelper;
        $this->registry = $registry;
    }

    /**
     * Execute
     *
     * @return \Magento\Framework\App\ResponseInterface|void
     */
    public function execute()
    {
        $storeCode = $this->_customerSession->getStoreCode();
        $data = $this->getRequest()->getParams();
        if(isset($data['spinId'])){
            $spinId = $data['spinId'];
        }else{
            $spinId = null;
        }

        try {
            $success = 1;
            $result = [];
            if($spinId){
                $spin = $this->b8SpinHelper->getSpin($spinId);
                if (!$this->registry->registry('current_spinid')) {
                    $this->registry->register('current_spinid', $spinId);
                }
            } else {
               $spin = $this->b8SpinHelper->getSpin();
            }
            $data = $this->getRequest()->getParams();
            if ($spin->getId()) {
                $result['info'] = [
                    'spin_type' => $spin->getData('spin_type'),
                    'x_times' => $spin->getData('x_times'),
                    'allow_redeem_point' =>$spin->getData('allow_redeem_point'),
                    'point_to_drawn' => $spin->getData('point_to_drawn'),
                    'redeem_point_to_drawn_limit_per_day' => $spin->getData('redeem_point_to_drawn_limit_per_day'),
                    'description' => $spin->getData('spin_description')
                ];
                /**
                 * Get Consolation
                 */
                $consolationCol = $this->_conn->select()
                    ->from('spintowin_consolation')
                    ->where('spin_id = ?', $spin->getId());
                $consolation = $this->_conn->fetchRow($consolationCol);
                if(!empty($consolation)){
                    $result['consolation'] = [
                        'is_active' => $consolation['is_active'],
                        'fail_times' => $consolation['consolation_fail_times'],
                        'type' => $consolation['consolation_type'],
                        'title' => $consolation['consolation_title']
                    ];
                }
                $result['wheel'] = $this->helper->getWheelData($spin->getId())->getData();
                $result['welcome'] = $spin->getEditForm()->getData();
                $result['result'] = $spin->getResultForm()->getData();
                $result['layout'] = $spin->getLayout()->getData();
                $result['visibility'] = $spin->getVisibility()->getData();
                $result['button'] = $spin->getButton()->getData();
                $result['coupon'] = $spin->getCoupon()->getData();
                $result['mediaUrl'] = $this->helper->getMediaDirectory();
                $customerGroupId = 0;
                $success = 0;
                if ($this->_customerSession->isLoggedIn()) {
                    $customerId = $this->_customerSession->getCustomer()->getId();
                    $customerGroupId = $this->b8SpinHelper->getCustomerGroupId($customerId);
                }
                $spinGroupIds = explode(',', $spin->getCustomergroupIds());
                foreach ($spinGroupIds as $spinGroupId) {
                    if ($customerGroupId == $spinGroupId) {
                        $success = 1;
                        break;
                    }
                }
            }
            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $this->getResponse()->setBody($this->serializer
                ->serialize(
                    [
                        'success' => $success,
                        'isempty' => empty($result),
                        'data' => $result
                    ]
                ));
        } catch (\Exception $e) {
            $this->logger->info("Index.php: ".$e->getMessage());
            $this->getResponse()->setHeader('Content-type', 'application/javascript');
            $this->getResponse()->setBody($this->serializer
                ->serialize(
                    [
                        'success' => 0,
                        // 'message' => __('Something went wrong in getting spin wheel.'. $e->getMessage()),
                        'message' => __('發生錯誤，請重新嘗試。'),
                    ]
                ));
        }
    }

    public function getCurrentSpinId()
    {
        return $this->registry->registry('current_spinid');
    }
}
