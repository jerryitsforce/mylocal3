<?php

namespace Ecpay\General\Helper\Foundation;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\FileSystemException;
use Zend_Log_Exception;

class GeneralHelper extends AbstractHelper
{
    public const CONFIG_PATH_ECPAY_MODULE_NAME  = 'ecpay';
    public const CONFIG_PATH_ECPAY_GENERAL  = 'ecpay/general';
    public const CONFIG_PATH_ECPAY_PAYMENT  = 'ecpay/payment';
    public const CONFIG_PATH_ECPAY_LOGISTIC = 'ecpay/logistic';
    public const CONFIG_PATH_ECPAY_INVOICE  = 'ecpay/invoice';

    /**
     * Deployment configuration
     *
     * @var DeploymentConfig
     */
    protected $_deploymentConfig ;

    /**
     * Deployment configuration
     *
     * @var HotaiCoreCommonHelper
     */
    protected HotaiCoreCommonHelper $_hotaiCoreCommonHelper ;

    /**
     * @param DeploymentConfig $deploymentConfig
     */
	public function __construct(
        DeploymentConfig $deploymentConfig,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
    ) {
		$this->_deploymentConfig = $deploymentConfig;
		$this->_hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
	}

    /**
     * 取 env.php ecpay/general 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayGeneralConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_GENERAL . $path);
    }

    /**
     * 取 env.php ecpay/payment 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayPaymentConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_PAYMENT . $path);
    }

    /**
     * 取 env.php ecpay/logistic 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayLogisticConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_LOGISTIC . $path);
    }

    /**
     * 取 env.php ecpay/invoice 資料
     *
     * @param  string $path
     * @return mixed
     */
    public function getEcpayInvoiceConfigData(string $path)
    {
        return $this->_deploymentConfig->get(self::CONFIG_PATH_ECPAY_INVOICE . $path);
    }

    /**
     * 從 env.php 中取出 KEY、IV
     *
     * @return array
     */
    public function getEncryKeyIV()
    {
        $info = [
            'key' => $this->getEcpayGeneralConfigData('/hash_key'),
            'iv'  => $this->getEcpayGeneralConfigData('/hash_iv'),
        ] ;

        return $info;
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     *
     * @param  int    $length
     * @return string
     */
    public function random($length = 16)
    {
        $string = '';

        while (($len = strlen($string)) < $length) {
            $size = $length - $len;

            $bytes = random_bytes($size);

            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }

        return $string;
    }

    /**
     * @throws Zend_Log_Exception
     * @throws FileSystemException
     */
    public function writeLog($message, string $folderName = "", string $fileName = ""): void
    {
        $folderPath = self::CONFIG_PATH_ECPAY_MODULE_NAME;

        if (!empty($folderName))
        {
            $folderPath .= '/'.$folderName;
        }

        $this->_hotaiCoreCommonHelper->writeLog($message, $folderPath, $fileName);
    }
}
