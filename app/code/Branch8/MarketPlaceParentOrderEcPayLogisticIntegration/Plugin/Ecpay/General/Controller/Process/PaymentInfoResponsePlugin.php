<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Ecpay\General\Controller\Process;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Model\Order;
use Ecpay\General\Helper\Services\Common\MailService;
use Psr\Log\LoggerInterface;

class PaymentInfoResponsePlugin
{
    const XML_PATH_ENABLE_SPLIT_ORDER = 'marketplace/mpsplitorder/mpsplitorder_enable';

    private RequestInterface $request;
    private LoggerInterface $logger;
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param RequestInterface $request
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RequestInterface     $request,
        ?LoggerInterface      $logger=null
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
        $this->logger = $logger?:ObjectManager::getInstance()->get('ecPayPaymentWithParentResponseOrderDebugResponseLogger');
    }

    /**
     * @param $subject
     * @param callable $process
     * @param ...$args
     * @return void
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Log_Exception
     */
    public function aroundExecute($subject, callable $process, ...$args)
    {
        $isEnable = (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLE_SPLIT_ORDER);
        if (!$isEnable) {
            return $process($args);
        }
        // 接收金流資訊
        $paymentInfo = $this->request->getPostValue();
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentresponselog')) {
            $this->logger->info('PaymentInfoResponse paymentInfo:' . print_r($paymentInfo, true));
        }
    }
}
