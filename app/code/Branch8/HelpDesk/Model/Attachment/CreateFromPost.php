<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Attachment;

use Branch8\HelpDesk\Api\Data\AttachmentInterfaceFactory;
use Branch8\HelpDesk\Model\Ticket\AttachmentUploader;
use Branch8\HelpDesk\Model\Ticket\AttachmentUploaderConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Image\AdapterFactory;
use Branch8\HelpDesk\Helper\Logger as LoggerInterface;

/**
 * Create new Attachment from post data
 */
class CreateFromPost
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8\HelpDesk\Model\Attachment\CreateFromPost';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var AttachmentUploader
     */
    private AttachmentUploader $uploader;

    /**
     * @var AdapterFactory
     */
    private AdapterFactory $imageFactory;

    /**
     * @var AttachmentInterfaceFactory
     */
    private AttachmentInterfaceFactory $attachmentFactory;


    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param AttachmentUploader $uploader
     * @param AdapterFactory $imageFactory
     * @param AttachmentInterfaceFactory $attachmentFactory
     */
    public function __construct(
        LoggerInterface            $logger,
        AttachmentUploader         $uploader,
        AdapterFactory             $imageFactory,
        AttachmentInterfaceFactory $attachmentFactory
    )
    {
        $this->logger = $logger;
        $this->uploader = $uploader;
        $this->imageFactory = $imageFactory;
        $this->attachmentFactory = $attachmentFactory;
    }

    /**
     * Create new Attachment from post data.
     *
     * @param array $data
     * @param int $messageId
     *
     * @return void
     */
    public function create(array $data, int $messageId): void
    {
        $baseTmpPath = $this->uploader->getBaseTmpPath();
        $mediaDirectory = $this->uploader->getMediaAbsolutePath();

        $filesToDelete = [];
        foreach ($data as $image) {
            $imagePath = $mediaDirectory . $baseTmpPath . '/' . $image['file'];
            if (file_exists($imagePath)) {
                try {
                    $fileName = 'attachment_' . basename($imagePath);
                    $newImagePath = $mediaDirectory . $baseTmpPath . '/' . $fileName;
                    $fileName = $this->resizeAndConverToJPEG($imagePath, $newImagePath);
                    $name = $this->uploader->moveFileFromTmp($fileName);

                    $attachment = $this->attachmentFactory->create();
                    $attachment->setName($name)
                        ->setPath($name)
                        ->setMessageId($messageId)
                        ->setType(AttachmentUploaderConfig::getAttachmentType($image['type'] ?? ''))
                        ->setMeta($data)
                        ->save();

                    $filesToDelete[] = $newImagePath;
                    $filesToDelete[] = $imagePath;

                } catch (\Exception $e) {
                    $this->logger->error(self::LOG_PREFIX, ['exception' => $e]);
                }
            }
        }

        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Change the image size and convert to WebP.
     *
     * @param string $sourcePath
     * @param string $destPath
     *
     * @return string
     *
     * @throws \Exception
     */
    private function resizeAndConverToJPEG(string $sourcePath, string $destPath): string
    {
        try {
            $image = $this->imageFactory->create();
            $image->open($sourcePath);

            $image->keepAspectRatio(true);
            $image->constrainOnly(true);
            $image->keepTransparency(true);
            $image->keepFrame(false);
            $image->resize(800, 800);
            $image->save($destPath);
            $webpPath = preg_replace('/\.(jpg|webp|jpeg|png)$/i', '.jpeg', $destPath);

            try {
                $success = $this->convertTojpeg($destPath, $webpPath);
                if ($success) {
                    unlink($destPath);
                }
                return basename($webpPath);
            } catch (\Exception $e) {
                $this->logger->error('Convert to WebP failed: ' . $e->getMessage());
            }
            return basename($destPath);
        } catch (\Exception $e) {
            throw new LocalizedException(__('Resize image failed: ' . $e->getMessage()));
        }
    }

    /**
     * @param string $sourcePath
     * @param string $webpPath
     * @return bool
     * @throws \Exception
     */
    private function convertTojpeg(string $sourcePath, string &$webpPath): bool
    {
        $webpPath = $sourcePath;
        $info = getimagesize($sourcePath);
        $canConvert = true;
        if ($info['mime'] == 'image/webp' && function_exists('imagecreatefromwebp')) {
            $image = imagecreatefromwebp($sourcePath);
        } elseif ($info['mime'] == 'image/jpg') {
            $image = imagecreatefromjpeg($sourcePath);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($sourcePath);
            imagepalettetotruecolor($image);
        } else {
            throw new \Exception('Unsupported image type: ' . $info['mime']);
        }
        if (function_exists('imagewebp')) {
            if (!imagewebp($image, $webpPath)) {
                $canConvert = false;
            }
        }
        if (function_exists('imagejpeg')) {
            if (!imagejpeg($image, $webpPath)) {
                $canConvert = false;
            }
        } else {
            $canConvert = false;
        }
        imagedestroy($image);
        return $canConvert;
    }
}
