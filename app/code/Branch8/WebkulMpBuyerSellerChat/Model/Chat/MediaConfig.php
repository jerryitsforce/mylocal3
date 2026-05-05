<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Chat;

use Branch8\HelpDesk\Model\Attachment;

class MediaConfig
{
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


    /**
     * GetUploadAttachmentAllowedExtensions
     * @return string[]
     */
    public static function getUploadAttachmentAllowedExtensions()
    {
        return array_merge(
            array_keys(self::$images),
            array_keys(self::$audios)
        );
    }

    /**
     * GetUploadAttachmentAllowedMimeType
     * @return string[]
     */
    public static function getUploadAttachmentAllowedMimeType()
    {
        return array_merge(
            array_values(self::$images),
            array_values(self::$audios)
        );
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
}
