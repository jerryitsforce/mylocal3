<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SellerDocument\Api\Data;

interface FileInterface
{

    const FILE_NAME = 'file_name';
    const SELLER_ID = 'seller_id';
    const FILE_ID = 'file_id';
    const UPLOADED_AT = 'uploaded_at';
    const COMMENT = 'comment';
    const FILE_PATH = 'file_path';
    const STATUS = 'status';

    /**
     * Get file_id
     * @return string|null
     */
    public function getFileId();

    /**
     * Set file_id
     * @param string $fileId
     * @return \Branch8\SellerDocument\File\Api\Data\FileInterface
     */
    public function setFileId($fileId);

    /**
     * Get file_name
     * @return string|null
     */
    public function getFileName();

    /**
     * Set file_name
     * @param string $fileName
     * @return \Branch8\SellerDocument\File\Api\Data\FileInterface
     */
    public function setFileName($fileName);

    /**
     * Get file_path
     * @return string|null
     */
    public function getFilePath();

    /**
     * Set file_path
     * @param string $filePath
     * @return \Branch8\SellerDocument\File\Api\Data\FileInterface
     */
    public function setFilePath($filePath);

    /**
     * Get uploaded_at
     * @return string|null
     */
    public function getUploadedAt();

    /**
     * Set uploaded_at
     * @param string $uploadedAt
     * @return \Branch8\SellerDocument\File\Api\Data\FileInterface
     */
    public function setUploadedAt($uploadedAt);

    /**
     * Get seller_id
     * @return string|null
     */
    public function getSellerId();

    /**
     * Set seller_id
     * @param string $sellerId
     * @return \Branch8\SellerDocument\File\Api\Data\FileInterface
     */
    public function setSellerId($sellerId);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Branch8\SellerDocument\File\Api\Data\FileInterface
     */
    public function setStatus($status);

    /**
     * Get comment
     * @return string|null
     */
    public function getComment();

    /**
     * Set comment
     * @param string $comment
     * @return \Branch8\SellerDocument\File\Api\Data\FileInterface
     */
    public function setComment($comment);
}

