<?php

declare(strict_types=1);

namespace Branch8\MarketplaceStaging\Api\Data;

interface ProductVersionDataInterface
{
    /**#@+
     * Constants for keys of data array.
     */
    public const ID = 'id';
    public const PARENT_ID = 'parent_id';
    public const INFORMATION = 'information';
    /**#@-*/

    /**
     * Returns ID.
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Returns the parent ID.
     *
     * @return int|null
     */
    public function getParentId(): ?int;

    /**
     * Sets the parent ID.
     *
     * @param int $parentId
     *
     * @return $this
     */
    public function setParentId(int $parentId): self;

    /**
     * Returns information.
     *
     * @return string
     */
    public function getInformation(): string;

    /**
     * Sets the information.
     *
     * @param string $information
     *
     * @return $this
     */
    public function setInformation(string $information): self;
}

