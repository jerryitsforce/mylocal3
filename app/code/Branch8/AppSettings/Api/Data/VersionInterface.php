<?php
namespace Branch8\AppSettings\Api\Data;


interface VersionInterface
{
    /**
     * Get Major version
     *
     * @return int
     */
    public function getMajor(): int;

    /**
     * Set Major version
     *
     * @param int $major
     * @return self
     */
    public function setMajor(int $major): self;

    /**
     * Get Minor version
     *
     * @return int
     */
    public function getMinor(): int;

    /**
     * Set Minor version
     *
     * @param int $minor
     * @return self
     */
    public function setMinor(int $minor): self;

    /**
     * Get Patch version
     *
     * @return int|null
     */
    public function getPatch(): ?int;

    /**
     * Set Patch version
     *
     * @param int|null $patch
     * @return self
     */
    public function setPatch(?int $patch): self;

    /**
     * Get Build number
     *
     * @return int|null
     */
    public function getBuild(): ?int;

    /**
     * Set Build number
     *
     * @param int|null $build
     * @return self
     */
    public function setBuild(?int $build): self;
}
