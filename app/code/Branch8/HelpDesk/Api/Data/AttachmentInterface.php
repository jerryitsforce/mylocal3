<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for Attachment
 */
interface AttachmentInterface extends ExtensibleDataInterface
{
    /**
     *
     */
    const NAME = 'name';
    /**
     *
     */
    const PATH = 'path';

    const TYPE = 'type';

    const FULL_PATH='full_path';

    /**
     * @param string $value
     * @return AttachmentInterface
     */
    public function setName(string $value);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $value
     * @return AttachmentInterface
     */
    public function setPath(string $value);

    /**
     * @return string
     */
    public function getPath();

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $value
     * @return AttachmentInterface
     */
    public function setType(string $value);

    /**
     * @return string
     */
    public function getFullPath();

    /**
     * @param string $value
     * @return AttachmentInterface
     */
    public function setFullPath(string $value);

}
