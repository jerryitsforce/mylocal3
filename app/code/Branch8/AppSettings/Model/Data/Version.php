<?php

namespace Branch8\AppSettings\Model\Data;

use Branch8\AppSettings\Api\Data\VersionInterface;

class Version implements VersionInterface
{
    /**
     * @var int
     */
    protected $major;

    /**
     * @var int
     */
    protected $minor;

    /**
     * @var int|null
     */
    protected $patch;

    /**
     * @var int|null
     */
    protected $build;

    public function getMajor(): int
    {
        return $this->major;
    }

    public function setMajor(int $major): self
    {
        $this->major = $major;
        return $this;
    }

    public function getMinor(): int
    {
        return $this->minor;
    }

    public function setMinor(int $minor): self
    {
        $this->minor = $minor;
        return $this;
    }

    public function getPatch(): ?int
    {
        return $this->patch;
    }

    public function setPatch(?int $patch): self
    {
        $this->patch = $patch;
        return $this;
    }

    public function getBuild(): ?int
    {
        return $this->build;
    }

    public function setBuild(?int $build): self
    {
        $this->build = $build;
        return $this;
    }
}
