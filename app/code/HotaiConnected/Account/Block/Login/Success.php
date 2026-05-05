<?php
namespace HotaiConnected\Account\Block\Login;

use Magento\Framework\View\Element\Template;
use Magento\Customer\Model\Context as CustomerContext;
use Branch8\HotaiPoint\Helper\Api as HotaiPointApi;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\Session as CustomerSession;

class Success extends Template
{
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * @var HotaiPointApi
     */
    protected $hotaiPointApi;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    private $customer_id = 0;

    /**
     * @param Context         $context
     * @param HotaiPointApi   $hotaiPointApi
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param array           $data
     */
    public function __construct(
        Context $context,
        HotaiPointApi $hotaiPointApi,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->_request = $context->getRequest();
        parent::__construct($context, $data);
        $this->hotaiPointApi = $hotaiPointApi;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->customer_id = $this->customerSession->getCustomer()->getId();
    }

    /**
     * Get customer points
     *
     * @return int
     */
    public function getHotaiPoint(): int
    {
        try {
            if ($this->customer_id === 0) {
                return 0;
            }

            $result = $this->hotaiPointApi->requestApiGetPointByOneid($this->customer_id);
            $points = (int)$this->hotaiPointApi->getPointFromResponse($result);
       
            return $points;
        } catch (\Exception $e) {
            $this->logger->info("[login_success] Error getting hotai points: " . $e->getMessage());
            return -1;
        }
    }

    /**
     * Get nearest expire point formatted text
     *
     * @return string
     */
    public function getNearestExpirePoint(): string
    {
        try {
            if ($this->customer_id === 0) {
                return '';
            }

            $result = $this->hotaiPointApi->getRecentMonthsDuePointsByCustomerId($this->customer_id, SORT_ASC);
           
            if (!empty($result) && isset($result[0]) && is_array($result[0])) {
                $expirePointData = $result[0];
               
                if (isset($expirePointData['date']) && isset($expirePointData['point'])) {
                    $expireDate = $expirePointData['date'];
                    $expireDateFormatted = sprintf(
                        '%s/%s/%s',
                        substr($expireDate, 0, 4),
                        substr($expireDate, 4, 2),
                        substr($expireDate, 6, 2)
                    );
                   
                    $expirePoint = $expirePointData['point'];
                    $pointFormatted = number_format((float)$expirePoint, 0, '.', ',');
                   
                    return $this->escapeHtml(
                        __("將有%1點於 %2 到期", $pointFormatted, $expireDateFormatted)
                    );
                }
            }

            return '';
            
        } catch (\Exception $e) {
            $this->logger->info("[login_success] Error getting nearest expire points : " . $e->getMessage());
            return '到期點數因系統維護中暫無法顯示';
        }
    }

    /**
     * @return string|null
     */
    public function getReceivedPointsTime()
    {
        return $this->_request->getParam('receivedPointsTime');
    }

    /**
     * @return string|null
     */
    public function getReceivedPoints()
    {
        return $this->_request->getParam('receivedPoints');
    }
}
