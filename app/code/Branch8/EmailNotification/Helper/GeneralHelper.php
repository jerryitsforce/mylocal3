<?php

namespace Branch8\EmailNotification\Helper;

use Branch8\HotaiCore\Helper\Logger as HotaiCoreCommonHelper;
use Magento\Framework\Exception\FileSystemException;
use Zend_Log_Exception;

class GeneralHelper
{
    public const CONFIG_PATH_HOTAI_AUTH_MODULE_NAME  = 'hotai_auth';

    /**
     * Deployment configuration
     *
     * @var HotaiCoreCommonHelper
     */
    protected HotaiCoreCommonHelper $_hotaiCoreCommonHelper ;

    /**
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     */
	public function __construct(
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
    ) {
		$this->_hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
	}

    /**
     * @throws Zend_Log_Exception
     * @throws FileSystemException
     */
    public function writeLog($message, string $folderName = "", string $fileName = ""): void
    {
        $folderPath = self::CONFIG_PATH_HOTAI_AUTH_MODULE_NAME;

        if (!empty($folderName))
        {
            $folderPath .= '/'.$folderName;
        }

        $this->_hotaiCoreCommonHelper->writeLog($message, $folderPath, $fileName);
    }
}
