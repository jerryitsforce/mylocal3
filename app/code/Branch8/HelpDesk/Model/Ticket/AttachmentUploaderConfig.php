<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Branch8\HelpDesk\Model\Attachment;
use Magento\Framework\App\Config\ScopeConfigInterface;

class AttachmentUploaderConfig
{
    const XML_PATH_ALLOW_EXTENSIONS = 'helpdesk/general_settings/upload_allow_extensions';
    const XML_PATH_MAX_FILE_SIZE = 'helpdesk/general_settings/maxFileSize';
    /**
     * @var string[]
     */
    static $images = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'png' => 'image/png'
    ];
    /**
     * @var string[]
     */
    static $docs = [
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'pdf' => 'application/pdf'
    ];
    static $audios = [
        'mp4' => 'video/mp4',
        'webp' => 'image/webp',
        'weba' => 'audio/webm',
        'webm' => 'video/webm'
    ];
    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * GetUploadAttachmentAllowedExtensions
     * @return string[]
     */
    public static function getUploadAttachmentAllowedExtensions()
    {
        return array_merge(
            array_keys(self::$images),
            array_keys(self::$docs),
            array_keys(self::$audios)
        );
    }

    /**
     * GetUploadAttachmentAllowedMimeType
     * @return string[]
     */
    public static function getUploadAttachmentAllowedMimeType($filter = [])
    {
        $all = array_merge(
            array_values(self::$images),
            array_values(self::$docs),
            array_values(self::$audios)
        );
        if ($filter) {
            return array_filter($all, function ($item, $key) use ($filter) {
                return in_array($key, $filter);
            },ARRAY_FILTER_USE_BOTH);
        }
        return $all;
    }
    /**
     * GetUploadAttachmentAllowedMimeType
     * @return string[]
     */
    public static function getAllowUploadAttachmentAllowedMimeType($filter = [])
    {
        $all = array_merge(
            self::$images,
            self::$docs,
            self::$audios
        );
        if ($filter) {
            return array_filter($all, function ($item, $key) use ($filter) {
                return in_array($key, $filter);
            },ARRAY_FILTER_USE_BOTH);
        }
        return $all;
    }
    /**
     * GetAttachmentType
     * @param $type
     * @return string
     */
    public static function getAttachmentType($type)
    {
        if (in_array($type, self::$images)) {
            return Attachment::TYPE_IMAGE;
        }
        if (in_array($type, self::$audios)) {
            return Attachment::TYPE_VIDEO;
        }
        if (in_array($type, self::$docs)) {
            return Attachment::TYPE_DOC;
        }
        return '';
    }

    /**
     * @return string|string[]
     */
    public function getAllowExtensionFiles()
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_ALLOW_EXTENSIONS);
        if ($value) {
            return explode(',', $value);
        }
        return  ['png', 'jpg', 'jpeg'];
    }

    /**
     * @return int
     */
    public function getMaxFileSize()
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_MAX_FILE_SIZE);
    }
}
