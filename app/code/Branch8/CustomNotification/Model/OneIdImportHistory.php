<?php

namespace Branch8\CustomNotification\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\TargetDirectory;
use Magento\Framework\Filesystem\DriverPool;

class OneIdImportHistory extends \Magento\Framework\Model\AbstractModel
{

    const CACHE_TAG = 'oneid_import_history';

    protected $_cacheTag = 'oneid_import_history';

    protected $_eventPrefix = 'oneid_import_history';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Branch8\CustomNotification\Model\ResourceModel\OneIdImportHistory::class);
    }

    /**
     * @param $notificationId
     * @return $this
     */
    public function setNotificationId($notificationId)
    {
        $this->setData('notification_id', $notificationId);
        return $this;
    }

    /**
     * @param $importFile
     * @return $this
     */
    public function setImportFile($importFile)
    {
        $this->setData('imported_file', $importFile);
        return $this;
    }

    /**
     * @param $summary
     * @return $this
     */
    public function setSummary($summary)
    {
        $this->setData('summary', $summary);
        return $this;
    }

    /**
     * @param $oneIdList
     * @return $this
     */
    public function setOneIdList($oneIdList)
    {
        $this->setData('oneid_list', $oneIdList);
        return $this;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->setData('user', $user);
        return $this;
    }
}
