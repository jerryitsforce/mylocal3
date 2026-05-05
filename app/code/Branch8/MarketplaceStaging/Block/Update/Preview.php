<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Block\Update;

use Magento\Framework\App\ObjectManager;
use Magento\Staging\Model\Preview\RequestSigner;

/**
 * Staging preview block.
 *
 * @api
 * @since 100.1.0
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Preview extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\Url
     * @since 100.1.0
     */
    protected $frontendUrl;

    /**
     * @var \Magento\Framework\Session\SidResolverInterface
     * @since 100.1.0
     */
    protected $sidResolver;

    /**
     * @var RequestSigner
     */
    private $requestSigner;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Url $frontendUrl
     * @param array $data
     * @param RequestSigner|null $requestSigner
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url $frontendUrl,
        array $data = [],
        RequestSigner $requestSigner = null
    ) {
        $this->frontendUrl = $frontendUrl;
        $this->sidResolver = $context->getSidResolver();
        $this->requestSigner = $requestSigner ?: ObjectManager::getInstance()->get(RequestSigner::class);
        parent::__construct($context, $data);
    }

    /**
     * Get preview frontend url.
     *
     * @return string
     * @since 100.1.0
     */
    public function getPreviewFrontendUrl()
    {
        $previewUrl = $this->getRequest()->getParam($this->getPreviewUrlParamName()) ? urldecode(
            $this->getRequest()->getParam($this->getPreviewUrlParamName())
        ) : '';

        $versionParamName = $this->getVersionParamName();
        $versionParamValue = $this->getPreviewVersion();

        $storeParamName = $this->getStoreParamName();
        $storeParamValue = $this->getPreviewStoreCode();

        if ($previewUrl) {
            $ampersand = strpos($previewUrl, '?') === false ? '?' : '&';

            if (strpos($previewUrl, $versionParamName) === false) {
                $params[] = $versionParamName . '=' . $versionParamValue;
            }

            if (!$this->isStoreCodeUsedInUrl()) {
                $params[] = $storeParamName . '=' . $storeParamValue;
            }

            if (!empty($params)) {
                $previewUrl .= $ampersand . implode('&', $params);
            }
        } else {
            $previewUrl = $this->frontendUrl->getUrl(
                null,
                [
                    '_query' => [
                        $versionParamName => $versionParamValue
                    ]
                ]
            );
        }

        return $this->requestSigner->signUrl($this->modifyHost($previewUrl));
    }

    /**
     * Gets code of current store or returns default.
     *
     * @return string
     * @since 100.1.0
     */
    public function getPreviewStoreCode()
    {
        $code = $this->getRequest()->getParam($this->getPreviewStoreParamName());

        if (!$code) {
            $code = $this->_storeManager->getDefaultStoreView()->getCode();
        }

        return $code;
    }

    /**
     * Gets version of the preview.
     *
     * @return string
     * @since 100.1.0
     */
    public function getPreviewVersion()
    {
        return $this->getRequest()->getParam($this->getPreviewVersionParamName());
    }

    /**
     * Get version parameter name
     *
     * @return string
     * @since 100.1.0
     */
    public function getVersionParamName()
    {
        return \Magento\Staging\Model\VersionManager::PARAM_NAME;
    }

    /**
     * Get store param name
     *
     * @return string
     * @since 100.1.0
     */
    public function getStoreParamName()
    {
        return \Magento\Store\Model\StoreManagerInterface::PARAM_NAME;
    }

    /**
     * Get preview url parameter name
     *
     * @return string
     * @since 100.1.0
     */
    public function getPreviewUrlParamName()
    {
        return \Magento\Staging\Model\Preview\UrlBuilder::PARAM_PREVIEW_URL;
    }

    /**
     * Get preview store parameter name
     *
     * @return string
     * @since 100.1.0
     */
    public function getPreviewStoreParamName()
    {
        return \Magento\Staging\Model\Preview\UrlBuilder::PARAM_PREVIEW_STORE;
    }

    /**
     * Get preview version parameter name
     *
     * @return string
     * @since 100.1.0
     */
    public function getPreviewVersionParamName()
    {
        return \Magento\Staging\Model\Preview\UrlBuilder::PARAM_PREVIEW_VERSION;
    }

    /**
     * Get date time format
     *
     * @return string
     * @since 100.1.0
     */
    public function getDateTimeFormat()
    {
        return $this->getDateFormat() . ' ' . $this->getTimeFormat();
    }

    /**
     * Is store code used in url
     *
     * @return bool
     */
    private function isStoreCodeUsedInUrl()
    {
        return $this->_scopeConfig->getValue(
            \Magento\Store\Model\Store::XML_PATH_STORE_IN_URL
        );
    }

    /**
     * Modify host
     *
     * @param string $url
     *
     * @return string
     */
    private function modifyHost($url)
    {
        // phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
        $host = parse_url($url, PHP_URL_HOST);
        // phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
        $port = parse_url($url, PHP_URL_PORT);
        if ($port) {
            $host .= ':' . $port;
        }
        return $url = str_replace(
            $host,
            $this->_request->getServer('HTTP_HOST'),
            $url
        );
    }

    /**
     * Get date format
     *
     * @return string
     */
    private function getDateFormat()
    {
        return $this->_localeDate->getDateFormat(
            \IntlDateFormatter::MEDIUM
        );
    }

    /**
     * Get time format
     *
     * @return string
     */
    private function getTimeFormat()
    {
        return $this->_localeDate->getTimeFormat(
            \IntlDateFormatter::SHORT
        );
    }
}
