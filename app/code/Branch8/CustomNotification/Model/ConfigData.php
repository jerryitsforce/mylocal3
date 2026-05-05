<?php

namespace Branch8\CustomNotification\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

class ConfigData
{

    const ONEIDUPLOAD_NA_OPTIONS = [
        'abandoned_cart_reminds',
        'order_status_update',
        'return_exchange',
        'spin_to_win',
        'automated_reward'
    ];

    const XML_PATH_ONE_ID_GROUP_CONFIG = 'magenest_notification_box/notification_box/one_id_group';

    const ONE_ID_GROUP = 'ONEIDGROUP';


    private ScopeConfigInterface $scopeConfig;

    private Filesystem $filesystem;
    /**
     * @var PersonalNotificationTypes
     */
    private PersonalNotificationTypes $personalNotificationTypes;

    /**
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param PersonalNotificationTypes $personalNotificationTypes
     */
    public function __construct(
        FileSystem                $filesystem,
        ScopeConfigInterface      $scopeConfig,
        PersonalNotificationTypes $personalNotificationTypes
    )
    {
        $this->personalNotificationTypes = $personalNotificationTypes;
        $this->filesystem = $filesystem;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return array|string[]
     */
    public function getNotAvailableNotifications()
    {
        return array_merge($this->personalNotificationTypes->getList(), self::ONEIDUPLOAD_NA_OPTIONS);
    }

    /**
     * @return mixed
     */
    public function getOneIdGroup()
    {
        return self::ONE_ID_GROUP;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return [
            'one_id_group' => $this->getOneIdGroup(),
        ];
    }

    /**
     * @return mixed
     */
    public function getBaseUploadDir()
    {
        $dir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        return $dir->getAbsolutePath('OneIdList');
    }

    /**
     * @param $path
     * @return string
     */
    public function getAbsoluteUploadDir($path)
    {
        return $this->getBaseUploadDir() . $path;
    }

}
