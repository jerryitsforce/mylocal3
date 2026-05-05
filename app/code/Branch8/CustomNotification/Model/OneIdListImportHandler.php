<?php

namespace Branch8\CustomNotification\Model;

use Branch8\CustomNotification\Exception\OneIdImportValidateException;
use Branch8\CustomNotification\Model\OneId\ReadCsv;
use Branch8\CustomNotification\Model\OneId\ReadExcel;
use Magenest\NotificationBox\Model\Notification;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\FileSystem;

class OneIdListImportHandler
{
    const XML_PATH_ONE_ID_GROUP_CONFIG = 'magenest_notification_box/notification_box/one_id_group';
    const MAX_ROW = 10000;
    /**
     * @var FileSystem
     */
    private FileSystem $fileSystem;

    private array $processors;
    private ResourceConnection $resourceConnection;

    /**
     * @param FileSystem $fileSystem
     * @param ResourceConnection $resourceConnection
     * @param array $processors
     */
    public function __construct(
        FileSystem         $fileSystem,
        ResourceConnection $resourceConnection,
        array              $processors = []
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->processors = $processors;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @param $filePath
     * @param $errSplitChar
     * @return array
     * @throws FileSystemException
     */
    public function read($filePath, $errSplitChar = ',')
    {
        $fullPath = $this->getAbsoluteUploadDir($filePath);
        if (!file_exists($fullPath)) {
            throw new FileSystemException(__('OneId File not found.'));
        }
        $info = (pathinfo($filePath));
        $extension = $info['extension'];
        $fileName = strtolower($info['filename']);
        switch ($extension) {
            case 'csv':
                $result = ObjectManager::getInstance()->get(ReadCsv::class)->read($fullPath);
                break;
            case 'xls':
            case 'xlsx':
                $result = ObjectManager::getInstance()->get(ReadExcel::class)->read($fullPath);
                break;
            default:
                throw new FileSystemException(__('Unknown OneId File type.'));
        }
        $result->setData('fileName',$fileName);
        $result->setData('extension',$extension);
        return $result;
    }

    /**
     * @param Notification $notification
     * @param $path
     * @return array
     * @throws FileSystemException
     */
    public function validateAndImport(Notification $notification, $path)
    {
        try {
            $result = $this->read($path, '<br/>');
        } catch (\Exception $e) {
            throw $e;
        }
        if (count($result->getList())) {
            $update = [
                'oneid_list_file' => $path,
                'oneid_list' => join(',', $result->getList()),
                'import_data' => $result->getData('importData'),
            ];
            $this->resourceConnection->getConnection()->update(
                'magenest_notification',
                $update,
                ['id = ?' => $notification->getId()]
            );
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function getBaseUploadDir()
    {
        $dir = $this->fileSystem->getDirectoryRead(DirectoryList::VAR_DIR);
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
