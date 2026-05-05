<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Controller\Adminhtml\Chat;

use \Exception;
use Laminas\Json\Encoder;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Area;
use Magento\Store\Model\Store;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;

class Ping extends Action
{
    private CustomLogger $logger;
    private \Magento\Framework\HTTP\Client\Curl $curl;

    /**
     * @param Context $context
     * @param CustomLogger $logger
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        Context                             $context,
        CustomLogger                        $logger,
        \Magento\Framework\HTTP\Client\Curl $curl
    )
    {
        $this->logger = $logger;
        $this->curl = $curl;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = ['status' => false, 'error' => '', 'code' => ''];
        $host = $this->getRequest()->getParam('host');
        $port = $this->getRequest()->getParam('port');
        try {
            $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
            $this->curl->setOption(CURLOPT_PORT, $port);
            $this->curl->post($host, []);
            $status = $this->curl->getStatus();
            $result['code'] = $status;
            if ($status == 200) {
                $result['status'] = true;
            }
        } catch (\Exception $exception) {
            $result['error'] = $exception->getMessage();
        }
        return $this->getResponse()->representJson(Encoder::encode($result));
    }
}
