<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model;

class MessageType
{
    const TEXT = 'text';
    const IMAGE = 'image';
    const FILE = 'file';
    const HTML = 'html';
    const ORDER = 'order';
    const VIDEO = 'video';

    /**
     * getValidTypes
     * @return string[]
     */
    public static function getValidTypes()
    {
        return [
            self::FILE,
            self::IMAGE,
            self::HTML,
            self::TEXT,
            self::VIDEO,
            self::ORDER
        ];
    }
}
