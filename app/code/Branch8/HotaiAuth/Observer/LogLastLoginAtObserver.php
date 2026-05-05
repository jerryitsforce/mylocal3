<?php

namespace Branch8\HotaiAuth\Observer;

use Branch8\HotaiAuth\Helper\GeneralHelper;
use Branch8\HotaiAuth\Model\CustomLogger;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use DateTime;
use JsonException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Zend_Log_Exception;

class LogLastLoginAtObserver implements ObserverInterface
{
    public function __construct(
        protected CustomLogger $customLogger,
        protected RequestInterface $request,
        protected GeneralHelper $_loggerInterface,
        protected MobileDetect $mobileDetect
    ) {}

    /**
     * @param Observer $observer
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     * @throws JsonException
     */
    public function execute(Observer $observer)
    {
        $customerId = $observer->getEvent()->getCustomer()->getId();

//        $this->writeLog('CustomerId:' . $customerId . ', Hotai login observe(GET): ' . json_encode($this->request->getParams(), JSON_THROW_ON_ERROR));
//        $this->writeLog('CustomerId:' . $customerId . ', Hotai login observe(POST): ' . json_encode($this->request->getPost(), JSON_THROW_ON_ERROR));

        $data = [
            'last_login_at' => (new DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
            'device' => $this->mobileDetect->getDeviceType()
        ];

        $utmArray = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_id', 'utm_term', 'utm_content'];
        $utmArr = [];
        foreach ($utmArray as $utm) {
            $utmData = $this->request->getParam($utm);
            if (!empty($utmData)) {
                $utmArr[$utm] = $utmData;
            }
        }

        if (count($utmArr) > 0) {
            try {
                $data['utm'] = json_encode($utmArr, JSON_THROW_ON_ERROR);
            }catch (JsonException $e) {
                $data['utm'] = 'Error encoding UTM parameters: ' . $e->getMessage();
            }
        }

        $this->customLogger->log($customerId, $data);
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeLog($message): void
    {
        $this->_loggerInterface->writeLog($message, 'hotai_auth_observe');
    }
}
