<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SellerDocument\Model;

use Branch8\SellerDocument\Api\Data\FileInterface;
use Magento\Framework\Model\AbstractModel;

class File extends AbstractModel implements FileInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Branch8\SellerDocument\Model\ResourceModel\File::class);
    }

    /**
     * @inheritDoc
     */
    public function getFileId()
    {
        return $this->getData(self::FILE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setFileId($fileId)
    {
        return $this->setData(self::FILE_ID, $fileId);
    }

    /**
     * @inheritDoc
     */
    public function getFileName()
    {
        return $this->getData(self::FILE_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setFileName($fileName)
    {
        return $this->setData(self::FILE_NAME, $fileName);
    }

    /**
     * @inheritDoc
     */
    public function getFilePath()
    {
        return $this->getData(self::FILE_PATH);
    }

    /**
     * @inheritDoc
     */
    public function setFilePath($filePath)
    {
        return $this->setData(self::FILE_PATH, $filePath);
    }

    /**
     * @inheritDoc
     */
    public function getUploadedAt()
    {
        return $this->getData(self::UPLOADED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUploadedAt($uploadedAt)
    {
        return $this->setData(self::UPLOADED_AT, $uploadedAt);
    }

    /**
     * @inheritDoc
     */
    public function getSellerId()
    {
        return $this->getData(self::SELLER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSellerId($sellerId)
    {
        return $this->setData(self::SELLER_ID, $sellerId);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getComment()
    {
        return $this->getData(self::COMMENT);
    }

    /**
     * @inheritDoc
     */
    public function setComment($comment)
    {
        return $this->setData(self::COMMENT, $comment);
    }
}

