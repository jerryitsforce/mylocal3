<?php
declare(strict_types=1);

namespace Branch8\CustomNotification\Controller\Adminhtml\Ajax;

use Branch8\CustomNotification\Exception\OneIdImportValidateException;
use Branch8\CustomNotification\Model\OneIdFileUpLoader;
use Branch8\CustomNotification\Model\OneIdImportHistory;
use Branch8\CustomNotification\Model\OneIdImportHistoryFactory;
use Branch8\CustomNotification\Model\OneIdListImportHandler;
use Magenest\NotificationBox\Model\NotificationFactory;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;

/**
 * Controller for the 'notibox/ajax/uploadoneid' URL route.
 */
class UploadOneIdList extends Action implements HttpPostActionInterface
{
    /**
     * @var string[]
     */
    private $allowedMimeTypes = [
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xls' => 'application/vnd.ms-excel',
        'csv' => 'text/csv'
    ];
    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var UploaderFactory
     */
    private UploaderFactory $fileUploader;
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    private \Magento\Framework\Controller\Result\RawFactory $resultRawFactory;
    private NotificationFactory $notificationFactory;
    private OneIdListImportHandler $handler;
    private \Magento\Framework\Serialize\Serializer\Json $jsonSerializer;
    private OneIdImportHistoryFactory $oneIdImportHistoryFactory;

    /**
     * @param Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param Filesystem $filesystem
     * @param NotificationFactory $notificationFactory
     * @param UploaderFactory $fileUploader
     * @param \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
     * @param OneIdListImportHandler $handler
     * @param OneIdImportHistoryFactory $oneIdImportHistoryFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context             $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        Filesystem                                      $filesystem,
        NotificationFactory                             $notificationFactory,
        UploaderFactory                                 $fileUploader,
        \Magento\Framework\Serialize\Serializer\Json    $jsonSerializer,
        OneIdListImportHandler                          $handler,
        OneIdImportHistoryFactory                       $oneIdImportHistoryFactory
    )
    {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->filesystem = $filesystem;
        $this->notificationFactory = $notificationFactory;
        $this->fileUploader = $fileUploader;
        $this->handler = $handler;
        $this->jsonSerializer = $jsonSerializer;
        $this->oneIdImportHistoryFactory = $oneIdImportHistoryFactory;
    }

    /**
     * Execute controller action.
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        try {

            /**
             * @var $uploader OneIdFileUpLoader
             */
            $uploader = $this->_objectManager->create(
                OneIdFileUpLoader::class,
                ['fileId' => 'oneid_uploader']
            );
            $id = $this->getRequest()->getParam('notificationId');
            /**
             * @var OneIdImportHistory $oneIdImportHistory
             */
            $oneIdImportHistory = $this->oneIdImportHistoryFactory->create();
            if ($id) {
                $oneIdImportHistory->setNotificationId($id);
            }
            $uploader->setAllowedExtensions($this->getAllowedExtensions());
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);
            $dir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            $result = $uploader->save(
                $dir->getAbsolutePath('OneIdList')
            );
            $oneIdImportHistory->setImportFile($result['file']);
            $oneIdImportHistory->setUser($this->_auth->getUser()->getName());
            $validResult = $this->handler->read($result['file']);
            $summary = [
                'success' => count($validResult->getList()),
                'failed' => $validResult->getData('failed'),
                'errors' => [],
                'fileName' => $validResult->getData('fileName'),
                'extension' => $validResult->getData('extension'),
            ];
            $oneIdImportHistory->setSummary($this->jsonSerializer->serialize($summary))
                ->setOneIdList($validResult->getList() ? join(',', $validResult->getList()) : '')
                ->setData('import_data', $this->jsonSerializer->serialize($validResult->getData('importData')));
            $oneIdImportHistory->save();
            $result['importId'] = $oneIdImportHistory->getId();
            $result['summary'] = $summary;
            $result['validResultLink'] = $this->_url->getUrl('notibox/ajax/DownloadResultImportOneId',
                ['id' => $oneIdImportHistory->getId()]
            );
            if (is_array($result)) {
                unset($result['tmp_name']);
                unset($result['path']);
            } else {
                $result = ['error' => 'Something went wrong while saving the file(s).'];
            }
        } catch (OneIdImportValidateException $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        } catch (LocalizedException $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        } catch (\Throwable $e) {
            $result = ['error' => 'Currently,csv,excel supported for upload. Please re-select the file.', 'errorcode' => 0];
        }
        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }

    /**
     * Get the set of allowed file extensions.
     *
     * @return array
     */
    private function getAllowedExtensions()
    {
        return array_keys($this->allowedMimeTypes);
    }
}
