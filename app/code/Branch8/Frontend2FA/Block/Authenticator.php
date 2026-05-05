<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 13:36.
 */

namespace Branch8\Frontend2FA\Block;

use Branch8\Frontend2FA\Model\GoogleAuthenticatorService;
use Branch8\Frontend2FA\Observer\TfaFrontendCheck;
use Magento\Catalog\Model\Session as CatalogSession;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

class Authenticator extends \Magento\Framework\View\Element\Template
{
    const SESSION_KEY = 'google_authentication';


    /**
     * Google Secret
     *
     * @var string $_googleSecret
     */
    protected $_googleSecret = null;

    /**
     * Catalog Session
     *
     * @var \Magento\Catalog\Model\Session $session
     */
    protected $_session = null;
    /**
     * @var TfaFrontendCheck
     */
    public $observer;
    /**
     * @var Session
     */
    public $customerSession;
    /**
     * @var StoreManagerInterface
     */
    public $storeManager;

    /**
     * @var GoogleAuthenticatorService
     */
    public $googleAuthenticatorService;



    public function __construct(
        GoogleAuthenticatorService $googleAuthenticatorService,
        CatalogSession $session,
        TfaFrontendCheck $observer,
        Session $customerSession,
        StoreManagerInterface $storeManager,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->googleAuthenticatorService = $googleAuthenticatorService;
        $this->observer = $observer;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->_session = $session;

        if ($secret = $this->getSessionData(self::SESSION_KEY)) {
            $this->_googleSecret = $secret;
        } else {
            $this->_googleSecret = $this->googleAuthenticatorService->createSecret();
            $this->setSessionData(self::SESSION_KEY, $this->_googleSecret);
        }
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     *
     * @return string
     */
    public function getQrCodeBase64Image()
    {
        $domain = $this->storeManager->getStore()->getBaseUrl();
        $domain = str_replace('https://', '', $domain);
        $domain = str_replace('/', '', $domain);
        // Replace non-alphanumeric characters with dashes; Google Authenticator does not like spaces in the title
        $title = preg_replace('/[^a-z0-9]+/i', '-', $domain.' Seller 2FA');
        $imageData = base64_encode(
            $this->googleAuthenticatorService
                ->getQrCodeEndroid($title, $this->_googleSecret)
                ->getString()
        );

        return 'data:image/png;base64,'.$imageData;
    }

    /**
     * Returns action url for authentication form.
     *
     * @return string
     */
    public function getSetupFormAction()
    {
        return $this->getUrl('frontend2fa/account/setup', ['_secure' => true]);
    }

    /**
     * Returns action url for authentication form.
     *
     * @return string
     */
    public function getAuthenticateFormAction()
    {
        return $this->getUrl('frontend2fa/account/authenticate', ['_secure' => true]);
    }

    /**
     * @param null $customer
     *
     * @return bool
     */
    public function is2faConfiguredForCustomer($customer = null)
    {
        if ($customer === null) {
            $customer = $this->customerSession->getCustomer();
        }

        return $this->observer->is2faConfiguredForCustomer($customer);
    }

    /**
     * Returns QR secret code
     *
     * @return string
     */
    public function getSecretCode()
    {
        return $this->_googleSecret;
    }

    /**
     * Sets session for secret key
     *
     * @return string
     */
    public function setSessionData($key, $value)
    {
        return $this->_session->setData($key, $value);
    }

    /**
     * Gets session for secret key
     *
     * @param $key
     * @param bool $remove
     * @return string
     */
    public function getSessionData($key, $remove = false)
    {
        return $this->_session->getData($key, $remove);
    }

    /**
     * Returns action url for authentication form
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->getUrl('authenticator/index/post', ['_secure' => true]);
    }

    /**
     * Returns QR code url
     *
     * @return string
     */
    public function getQRCodeUrl()
    {
        return $this->googleAuthenticatorService->getQRCodeGoogleUrl('Authenticator', $this->_googleSecret);
    }

    /**
     * Authenticates QR code
     *
     * @param $secret
     * @param $code
     * @return string
     */
    public function authenticateQRCode($secret, $code)
    {
        if (!$secret || !$code) {
            return false;
        }

        return $this->googleAuthenticatorService->verifyCode($secret, $code);
    }
}
