<?php

namespace Branch8\Marketplace\Override\Controller\Account\Dashboard;

use Laminas\Http\Request;
use Magento\Framework\Encryption\Helper\Security;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result;
use Webkul\Marketplace\Helper\Dashboard\Data as MpDashboardHelper;
use Webkul\Marketplace\Helper\Data as MpHelper;

class Tunnel extends \Webkul\Marketplace\Controller\Account\Dashboard\Tunnel{

    /**
     * @var MarketplaceLogger
     */
    private MarketplaceLogger $marketplaceLogger;

    /**
     * Construct.
     *
     * Keep the same dependencies as `Webkul\Marketplace\Controller\Account\Dashboard\Tunnel`,
     * and add `MarketplaceLogger` for configurable Marketplace logging.
     *
     * @param Context $context
     * @param Result\RawFactory $resultRawFactory
     * @param MpDashboardHelper $mpDashboardHelper
     * @param \Magento\Framework\HTTP\LaminasClient $httpZendClient
     * @param MpHelper $mpHelper
     * @param \Magento\Framework\Url\DecoderInterface $decoderInterface
     * @param MarketplaceLogger $marketplaceLogger
     */
    public function __construct(
        Context $context,
        Result\RawFactory $resultRawFactory,
        MpDashboardHelper $mpDashboardHelper,
        \Magento\Framework\HTTP\LaminasClient $httpZendClient,
        MpHelper $mpHelper,
        \Magento\Framework\Url\DecoderInterface $decoderInterface,
        MarketplaceLogger $marketplaceLogger
    ) {
        parent::__construct(
            $context,
            $resultRawFactory,
            $mpDashboardHelper,
            $httpZendClient,
            $mpHelper,
            $decoderInterface
        );
        $this->marketplaceLogger = $marketplaceLogger;
    }

    public function execute()
    {

        $errorMessage = __('invalid request');
        $httpCode = 400;
        $getEncodedParamData = $this->_request->getParam('param_data');
        $getEncryptedHashData = $this->_request->getParam('encrypted_data');
        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->_resultRawFactory->create();
        if ($getEncodedParamData && $getEncryptedHashData) {
            /** @var $helper \Webkul\Marketplace\Helper\Dashboard\Data */
            $helper = $this->mpDashboardHelper;
            $newEncryptedHashData = $helper->getChartEncryptedHashData($getEncodedParamData);
            if (Security::compareStrings($newEncryptedHashData, $getEncryptedHashData)) {
                $params = null;
                $paramsJson = $this->decoderInterface->decode(urldecode($getEncodedParamData));

                if ($paramsJson) {
                    $params = json_decode($paramsJson, true);
                }
                if ($params) {
                    try {
                        /** @var $httpZendClient \Magento\Framework\HTTP\ZendClient */
                        $httpZendClient = $this->httpZendClient;

                        $httpZendClient->setUri(
                            \Magento\Backend\Block\Dashboard\Graph::API_URL
                        )->setParameterGet(
                            $params
                        )
                            ->setMethod(
                                Request::METHOD_GET
                            );

                        $response = $httpZendClient->send();
                        $responseHeaders = $response->getHeaders()->toArray();

                        $resultRaw->setHeader('Content-type', $responseHeaders['Content-Type'])
                            ->setContents($response->getBody());

                        return $resultRaw;
                    } catch (\Exception $e) {
                        $this->marketplaceLogger->logException('Tunnel', $e);
                        $errorMessage = __('see error log for details');
                        $httpCode = 503;
                    }
                }
            }
        }
        $resultRaw->setHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->setHttpResponseCode($httpCode)
            ->setContents(__('Service unavailable: %1', $errorMessage));

        return $resultRaw;
    }

}