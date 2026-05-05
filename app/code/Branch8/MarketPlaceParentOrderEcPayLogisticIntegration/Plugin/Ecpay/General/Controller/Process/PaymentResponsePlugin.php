<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Plugin\Ecpay\General\Controller\Process;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Ecpay\General\Helper\Services\Common\EncryptionsService;
use Ecpay\General\Helper\Services\Common\OrderService;
use Ecpay\General\Helper\Services\Config\MainService;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use Ecpay\General\Helper\Services\Config\LogisticService;
use Ecpay\General\Helper\Services\Config\PaymentService;
use Ecpay\General\Helper\Foundation\EncryptionsHelper;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Psr\Log\LoggerInterface;

class PaymentResponsePlugin
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
        ?LoggerInterface $logger = null
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
        // log debug data from ECPAY
        $paymentInfo = $this->request->getPostValue();
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('branch8_marketplaceparentorderecpaylogisticintegration', 'paymentresponselog')) {
            $this->logger->info('PaymentInfoResponse paymentInfo:' . print_r($paymentInfo, true));
        }
        echo '1|OK';

        exit();
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $requestInterface
    ): ?InvalidRequestException
    {

        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $requestInterface): ?bool
    {
        return true;
    }
}
